<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

$postId = $_GET['post_id'] ?? '';

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
        id,
        user_id
     FROM posts
     WHERE id = :post_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId
]);

$post = $stmt->fetch();

if (!$post) {
    jsonResponse(
        false,
        'Postingan tidak ditemukan atau sudah dihapus.',
        null,
        404
    );
}

$stmt = $pdo->prepare(
    "SELECT
        comments.id,
        comments.post_id,
        comments.user_id,
        comments.content,
        comments.created_at,
        users.full_name,
        users.status
     FROM comments
     INNER JOIN users
        ON comments.user_id = users.id
     WHERE comments.post_id = :post_id
     AND comments.status = 'active'
     ORDER BY comments.created_at ASC"
);

$stmt->execute([
    ':post_id' => $postId
]);

$comments = $stmt->fetchAll();

foreach ($comments as &$comment) {

    $comment['id'] = (int) $comment['id'];
    $comment['post_id'] = (int) $comment['post_id'];
    $comment['user_id'] = (int) $comment['user_id'];

    $comment['can_edit'] = false;
    $comment['can_delete'] = false;

    if ($isLoggedIn) {

        if ((int) $comment['user_id'] === (int) $userId) {
            $comment['can_edit'] = true;
            $comment['can_delete'] = true;
        }

        if (
            (int) $post['user_id'] === (int) $userId
            &&
            (int) $comment['user_id'] !== (int) $userId
        ) {
            $comment['can_delete'] = true;
        }
    }
}

unset($comment);

jsonResponse(
    true,
    'Komentar berhasil diambil.',
    [
        'post_id' => (int) $postId,
        'comments' => $comments
    ]
);