
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

$userId = (int) $_SESSION['user_id'];

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

if (!is_array($input)) {
    jsonResponse(
        false,
        'Data request tidak valid.',
        null,
        400
    );
}

$postId = $input['post_id'] ?? '';
$content = trim($input['content'] ?? '');

if (
    !is_scalar($postId) ||
    !filter_var($postId, FILTER_VALIDATE_INT) ||
    (int) $postId < 1
) {
    jsonResponse(
        false,
        'ID postingan tidak valid.',
        null,
        400
    );
}

$postId = (int) $postId;

if (!is_string($content) || $content === '') {
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
        user_id
     FROM posts
     WHERE id = :post_id
       AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId
]);

$post = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    jsonResponse(
        false,
        'Postingan tidak ditemukan atau sudah dihapus.',
        null,
        404
    );
}

$postOwnerId = (int) $post['user_id'];
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
        'active'
    )"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId,
    ':content' => $content
]);

$commentId = (int) $pdo->lastInsertId();
if ($postOwnerId !== $userId) {
    $stmtNotification = $pdo->prepare(
        "INSERT INTO notifications (
            recipient_id,
            actor_id,
            post_id,
            comment_id,
            type
        ) VALUES (
            :recipient_id,
            :actor_id,
            :post_id,
            :comment_id,
            'comment'
        )"
    );

    $stmtNotification->execute([
        ':recipient_id' => $postOwnerId,
        ':actor_id' => $userId,
        ':post_id' => $postId,
        ':comment_id' => $commentId
    ]);
}

jsonResponse(
    true,
    'Komentar berhasil dibuat.',
    [
        'id' => $commentId,
        'post_id' => $postId,
        'user_id' => $userId,
        'content' => $content,
        'status' => 'active'
    ],
    201
);
