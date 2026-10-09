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

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

$userId = $_SESSION['user_id'];

$input = json_decode(
    file_get_contents('php://input'),
    true
);

$postId = $input['post_id'] ?? '';

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    jsonResponse(
        false,
        'ID postingan tidak valid.',
        null,
        400
    );
}

/*
 * Soft delete
 */
$stmt = $pdo->prepare(
    "UPDATE posts
     SET status = 'removed',
         updated_at = CURRENT_TIMESTAMP
     WHERE id = :post_id
     AND user_id = :user_id
     AND status = 'active'"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId
]);

if ($stmt->rowCount() === 0) {
    jsonResponse(
        false,
        'Postingan tidak ditemukan atau bukan milik kamu.',
        null,
        404
    );
}

jsonResponse(
    true,
    'Postingan berhasil dihapus.',
    [
        'post_id' => (int) $postId,
        'status' => 'removed'
    ]
);