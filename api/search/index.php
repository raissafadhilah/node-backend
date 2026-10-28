<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;


$query = trim($_GET['q'] ?? '');

if ($query === '') {
    jsonResponse(
        true,
        'Masukkan kata kunci pencarian.',
        [
            'users' => [],
            'posts' => []
        ]
    );
}

if (mb_strlen($query) > 100) {
    jsonResponse(
        false,
        'Kata kunci maksimal 100 karakter.',
        null
    );
}

$keyword = '%' . $query . '%';



$stmtUsers = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        profile_photo
    FROM users
    WHERE full_name LIKE :keyword
    ORDER BY full_name ASC
    LIMIT 20"
);

$stmtUsers->execute([
    ':keyword' => $keyword
]);

$users = $stmtUsers->fetchAll();


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

        bookmarks.id AS bookmark_id

    FROM posts

    INNER JOIN users
        ON posts.user_id = users.id

    LEFT JOIN bookmarks
        ON bookmarks.post_id = posts.id
        AND bookmarks.user_id = :user_id

    WHERE posts.status = 'active'
    AND (
        posts.caption LIKE :caption_keyword
        OR posts.content LIKE :content_keyword
        OR EXISTS (
            SELECT 1
            FROM post_hashtags
            INNER JOIN hashtags
                ON post_hashtags.hashtag_id = hashtags.id
            WHERE post_hashtags.post_id = posts.id
            AND hashtags.name LIKE :hashtag_keyword
        )
    )

    ORDER BY posts.created_at DESC
    LIMIT 50"
);

$stmtPosts->execute([
    ':user_id' => $userId,
    ':caption_keyword' => $keyword,
    ':content_keyword' => $keyword,
    ':hashtag_keyword' => $keyword
]);

$posts = $stmtPosts->fetchAll();


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

    $post['media'] = $stmtMedia->fetchAll();

    $post['reaction_count'] = (int) $post['reaction_count'];
    $post['comment_count'] = (int) $post['comment_count'];

    $post['is_reacted'] = false;

    if ($isLoggedIn) {

        $stmtReaction = $pdo->prepare(
            "SELECT id
            FROM reactions
            WHERE post_id = :post_id
            AND user_id = :user_id
            LIMIT 1"
        );

        $stmtReaction->execute([
            ':post_id' => $post['id'],
            ':user_id' => $userId
        ]);

        $post['is_reacted'] =
            $stmtReaction->fetch() !== false;
    }

    $post['is_bookmarked'] =
        $post['bookmark_id'] !== null;

    unset($post['bookmark_id']);

    if (!empty($post['hashtags'])) {
        $post['hashtags'] = preg_split(
            '/\s+/',
            trim($post['hashtags'])
        );
    } else {
        $post['hashtags'] = [];
    }

    $post['can_edit'] = false;
    $post['can_delete'] = false;

    if ($userId !== null) {
        $post['can_edit'] =
            ((int) $post['user_id'] === (int) $userId);

        $post['can_delete'] =
            ((int) $post['user_id'] === (int) $userId);
    }
}

unset($post);

jsonResponse(
    true,
    'Hasil pencarian berhasil diambil.',
    [
        'users' => $users,
        'posts' => $posts
    ]
);