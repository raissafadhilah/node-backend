
<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
    );
}

$currentUserId = (int) $_SESSION['user_id'];

$profileUserId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$profileUserId || $profileUserId < 1) {
    $profileUserId = $currentUserId;
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        profile_photo
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $profileUserId
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    jsonResponse(
        false,
        'Profile tidak ditemukan.',
        null,
        404
    );
}

$stmtPosts = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status,

        (
            SELECT GROUP_CONCAT(
                CONCAT('#', hashtags.name)
                ORDER BY hashtags.name
                SEPARATOR ' '
            )
            FROM post_hashtags
            INNER JOIN hashtags
                ON post_hashtags.hashtag_id = hashtags.id
            WHERE post_hashtags.post_id = posts.id
        ) AS hashtags,

        (
            SELECT COUNT(*)
            FROM reactions
            WHERE reactions.post_id = posts.id
        ) AS reaction_count,

        (
            SELECT COUNT(*)
            FROM comments
            WHERE comments.post_id = posts.id
              AND comments.status = 'active'
        ) AS comment_count,

        EXISTS (
            SELECT 1
            FROM reactions
            WHERE reactions.post_id = posts.id
              AND reactions.user_id = :reaction_user_id
        ) AS is_reacted,

        EXISTS (
            SELECT 1
            FROM bookmarks
            WHERE bookmarks.post_id = posts.id
              AND bookmarks.user_id = :bookmark_user_id
        ) AS is_bookmarked

    FROM posts
    INNER JOIN users
        ON users.id = posts.user_id

    WHERE posts.user_id = :profile_user_id
      AND posts.status = 'active'

    ORDER BY posts.created_at DESC"
);

$stmtPosts->execute([
    ':reaction_user_id' => $currentUserId,
    ':bookmark_user_id' => $currentUserId,
    ':profile_user_id' => $profileUserId
]);

$posts = $stmtPosts->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as &$post) {
    $stmtMedia = $pdo->prepare(
        "SELECT
            id,
            file_path,
            file_type
         FROM post_media
         WHERE post_id = :post_id
         ORDER BY id ASC"
    );

    $stmtMedia->execute([
        ':post_id' => $post['id']
    ]);

    $post['media'] = $stmtMedia->fetchAll(PDO::FETCH_ASSOC);

    $post['hashtags'] = !empty($post['hashtags'])
        ? preg_split('/\s+/', trim($post['hashtags']))
        : [];

    $post['id'] = (int) $post['id'];
    $post['user_id'] = (int) $post['user_id'];
    $post['reaction_count'] = (int) $post['reaction_count'];
    $post['comment_count'] = (int) $post['comment_count'];
    $post['is_reacted'] = (bool) $post['is_reacted'];
    $post['is_bookmarked'] = (bool) $post['is_bookmarked'];

    $isPostOwner = ((int) $post['user_id'] === $currentUserId);

    $post['can_edit'] = $isPostOwner;
    $post['can_delete'] = $isPostOwner;
}

unset($post);

$stmtReplies = $pdo->prepare(
    "SELECT
        comments.id,
        comments.post_id,
        comments.content,
        comments.created_at
     FROM comments
     INNER JOIN posts
        ON posts.id = comments.post_id
     WHERE comments.user_id = :user_id
       AND comments.status = 'active'
       AND posts.status = 'active'
     ORDER BY comments.created_at DESC"
);

$stmtReplies->execute([
    ':user_id' => $profileUserId
]);

$replies = $stmtReplies->fetchAll(PDO::FETCH_ASSOC);

foreach ($replies as &$reply) {
    $reply['id'] = (int) $reply['id'];
    $reply['post_id'] = (int) $reply['post_id'];
}

unset($reply);

$isOwnProfile = ((int) $profileUserId === $currentUserId);

jsonResponse(
    true,
    'Profile berhasil diambil.',
    [
        'user' => [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'status' => $user['status'],
            'profile_photo' => $user['profile_photo']
        ],
        'is_own_profile' => $isOwnProfile,
        'posts' => $posts,
        'replies' => $replies
    ]
);
