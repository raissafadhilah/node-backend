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




if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

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
        'Komentar wajib diisi.',
        null,
        400
    );
}
$stmt = $pdo->prepare(
    "SELECT
        id
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
    "INSERT INTO comments (
        post_id,
        user_id,
        content,
        status
    ) VALUES (
        :post_id,
        :user_id,
        :content,
        :status
    )"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId,
    ':content' => $content,
    ':status' => 'active'
]);

$commentId = $pdo->lastInsertId();


jsonResponse(
    true,
    'Komentar berhasil dibuat.',
    [
        'id' => (int) $commentId,
        'post_id' => (int) $postId,
        'user_id' => (int) $userId,
        'content' => $content,
        'status' => 'active'
    ],
    201
);