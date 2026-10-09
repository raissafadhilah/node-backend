<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
    );
}

$userId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$commentId = $input['comment_id'] ?? '';

if (!filter_var($commentId, FILTER_VALIDATE_INT)) {
    jsonResponse(
        false,
        'ID komentar tidak valid.',
        null,
        400
    );
}


$stmt = $pdo->prepare(
    "SELECT
        id,
        post_id,
        user_id
     FROM comments
     WHERE id = :comment_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':comment_id' => $commentId
]);

$comment = $stmt->fetch();

if (!$comment) {
    jsonResponse(
        false,
        'Komentar tidak ditemukan atau sudah dihapus.',
        null,
        404
    );
}
$canDelete = false;

if ((int) $comment['user_id'] === (int) $userId) {
    $canDelete = true;
} else {

    $stmt = $pdo->prepare(
        "SELECT id
         FROM posts
         WHERE id = :post_id
         AND user_id = :user_id
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([
        ':post_id' => $comment['post_id'],
        ':user_id' => $userId
    ]);

    $post = $stmt->fetch();

    if ($post) {
        $canDelete = true;
    }
}

if (!$canDelete) {
    jsonResponse(
        false,
        'Kamu tidak memiliki izin untuk menghapus komentar ini.',
        null,
        403
    );
}

$stmt = $pdo->prepare(
    "UPDATE comments
     SET status = 'removed'
     WHERE id = :comment_id
     AND status = 'active'"
);

$stmt->execute([
    ':comment_id' => $commentId
]);

if ($stmt->rowCount() === 0) {
    jsonResponse(
        false,
        'Komentar gagal dihapus.',
        null,
        500
    );
}

jsonResponse(
    true,
    'Komentar berhasil dihapus.',
    [
        'id' => (int) $commentId,
        'status' => 'removed'
    ]
);