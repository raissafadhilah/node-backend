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

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
$content = trim($input['content'] ?? '');

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    jsonResponse(
        false,
        'ID postingan tidak valid.',
        null,
        400
    );
}

if ($content === '') {
    jsonResponse(
        false,
        'Isi postingan wajib diisi.',
        null,
        400
    );
}

/*
 * Pastikan post milik user
 */
$stmt = $pdo->prepare(
    "SELECT
        id,
        caption,
        content,
        status
     FROM posts
     WHERE id = :post_id
     AND user_id = :user_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId
]);

$post = $stmt->fetch();

if (!$post) {
    jsonResponse(
        false,
        'Postingan tidak ditemukan atau bukan milik kamu.',
        null,
        404
    );
}

/*
 * Update content
 */
$stmt = $pdo->prepare(
    "UPDATE posts
     SET content = :content,
         updated_at = CURRENT_TIMESTAMP
     WHERE id = :post_id
     AND user_id = :user_id
     AND status = 'active'"
);

$stmt->execute([
    ':content' => $content,
    ':post_id' => $postId,
    ':user_id' => $userId
]);

jsonResponse(
    true,
    'Postingan berhasil diubah.',
    [
        'post' => [
            'id' => (int) $post['id'],
            'caption' => $post['caption'],
            'content' => $content,
            'status' => $post['status']
        ]
    ]
);