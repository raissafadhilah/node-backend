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

$userId = $_SESSION['user_id'] ?? null;

$postId = $_GET['id'] ?? '';

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    jsonResponse(
        false,
        'ID postingan tidak valid.',
        null,
        400
    );
}

$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,
        posts.updated_at,
        users.full_name,
        users.status
     FROM posts
     INNER JOIN users
        ON posts.user_id = users.id
     WHERE posts.id = :post_id
     AND posts.status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId
]);

$post = $stmt->fetch();

if (!$post) {
    jsonResponse(
        false,
        'Postingan tidak ditemukan atau sudah tidak tersedia.',
        null,
        404
    );
}

$post['id'] = (int) $post['id'];
$post['user_id'] = (int) $post['user_id'];

$stmtHashtag = $pdo->prepare(
    "SELECT
        hashtags.name
     FROM post_hashtags
     INNER JOIN hashtags
        ON post_hashtags.hashtag_id = hashtags.id
     WHERE post_hashtags.post_id = :post_id
     ORDER BY hashtags.name ASC"
);

$stmtHashtag->execute([
    ':post_id' => $post['id']
]);

$hashtags = $stmtHashtag->fetchAll(
    PDO::FETCH_COLUMN
);

$post['hashtags'] = array_map(
    function ($hashtag) {
        return '#' . $hashtag;
    },
    $hashtags
);

$stmtMedia = $pdo->prepare(
    "SELECT
        id,
        file_name,
        file_path,
        file_type
     FROM post_media
     WHERE post_id = :post_id
     ORDER BY id ASC"
);

$stmtMedia->execute([
    ':post_id' => $post['id']
]);

$media = $stmtMedia->fetchAll();

foreach ($media as &$item) {
    $item['id'] = (int) $item['id'];
}

unset($item);

$post['media'] = $media;

$stmtReaction = $pdo->prepare(
    "SELECT COUNT(*)
     FROM reactions
     WHERE post_id = :post_id"
);

$stmtReaction->execute([
    ':post_id' => $post['id']
]);

$post['reaction_count'] =
    (int) $stmtReaction->fetchColumn();

$post['is_reacted'] = false;

if ($userId !== null) {
    $stmtUserReaction = $pdo->prepare(
        "SELECT id
         FROM reactions
         WHERE post_id = :post_id
         AND user_id = :user_id
         LIMIT 1"
    );

    $stmtUserReaction->execute([
        ':post_id' => $post['id'],
        ':user_id' => $userId
    ]);

    $post['is_reacted'] =
        (bool) $stmtUserReaction->fetch();
}

$post['is_bookmarked'] = false;

if ($userId !== null) {
    $stmtBookmark = $pdo->prepare(
        "SELECT id
         FROM bookmarks
         WHERE post_id = :post_id
         AND user_id = :user_id
         LIMIT 1"
    );

    $stmtBookmark->execute([
        ':post_id' => $post['id'],
        ':user_id' => $userId
    ]);

    $post['is_bookmarked'] =
        (bool) $stmtBookmark->fetch();
}

$stmtComment = $pdo->prepare(
    "SELECT COUNT(*)
     FROM comments
     WHERE post_id = :post_id
     AND status = 'active'"
);

$stmtComment->execute([
    ':post_id' => $post['id']
]);

$post['comment_count'] =
    (int) $stmtComment->fetchColumn();

$post['can_edit'] = false;
$post['can_delete'] = false;

if ($userId !== null) {
    $post['can_edit'] =
        ((int) $post['user_id'] === (int) $userId);

    $post['can_delete'] =
        ((int) $post['user_id'] === (int) $userId);
}

jsonResponse(
    true,
    'Detail postingan berhasil diambil.',
    [
        'post' => $post
    ]
);