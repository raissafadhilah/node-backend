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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
$content = trim($input['content'] ?? '');

if (!filter_var($commentId, FILTER_VALIDATE_INT)) {
    jsonResponse(
        false,
        'ID komentar tidak valid.',
        null,
        400
    );
}

if ($content === '') {
    jsonResponse(
        false,
        'Komentar wajib diisi.',
        null,
        400
    );
}
$stmt = $pdo->prepare(
    "SELECT
        id,
        post_id,
        user_id,
        content
     FROM comments
     WHERE id = :comment_id
     AND user_id = :user_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':comment_id' => $commentId,
    ':user_id' => $userId
]);

$comment = $stmt->fetch();

if (!$comment) {
    jsonResponse(
        false,
        'Komentar tidak ditemukan atau bukan milik kamu.',
        null,
        403
    );
}
$stmt = $pdo->prepare(
    "UPDATE comments
     SET content = :content
     WHERE id = :comment_id
     AND user_id = :user_id
     AND status = 'active'"
);

$stmt->execute([
    ':content' => $content,
    ':comment_id' => $commentId,
    ':user_id' => $userId
]);
jsonResponse(
    true,
    'Komentar berhasil diperbarui.',
    [
        'id' => (int) $comment['id'],
        'post_id' => (int) $comment['post_id'],
        'user_id' => (int) $comment['user_id'],
        'content' => $content,
        'status' => 'active'
    ]
);