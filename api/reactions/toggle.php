
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

$postId = $input['post_id'] ?? '';

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
    "SELECT id
     FROM reactions
     WHERE user_id = :user_id
       AND post_id = :post_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId,
    ':post_id' => $postId
]);

$reaction = $stmt->fetch(PDO::FETCH_ASSOC);

if ($reaction) {
    $stmt = $pdo->prepare(
        "DELETE FROM reactions
         WHERE id = :reaction_id
           AND user_id = :user_id"
    );

    $stmt->execute([
        ':reaction_id' => (int) $reaction['id'],
        ':user_id' => $userId
    ]);

    $isReacted = false;
    $message = 'Reaction dihapus.';
} else {
    $stmt = $pdo->prepare(
        "INSERT INTO reactions (
            post_id,
            user_id
        ) VALUES (
            :post_id,
            :user_id
        )"
    );

    $stmt->execute([
        ':post_id' => $postId,
        ':user_id' => $userId
    ]);

    $isReacted = true;
    $message = 'Reaction berhasil ditambahkan.';

    if ($postOwnerId !== $userId) {
        $stmtNotification = $pdo->prepare(
            "INSERT INTO notifications (
                recipient_id,
                actor_id,
                post_id,
                type
            ) VALUES (
                :recipient_id,
                :actor_id,
                :post_id,
                'reaction'
            )"
        );

        $stmtNotification->execute([
            ':recipient_id' => $postOwnerId,
            ':actor_id' => $userId,
            ':post_id' => $postId
        ]);
    }
}

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM reactions
     WHERE post_id = :post_id"
);

$stmt->execute([
    ':post_id' => $postId
]);

$reactionCount = $stmt->fetch(PDO::FETCH_ASSOC);

jsonResponse(
    true,
    $message,
    [
        'post_id' => $postId,
        'is_reacted' => $isReacted,
        'reaction_count' => (int) $reactionCount['total']
    ]
);
