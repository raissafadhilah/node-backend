
<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method tidak diperbolehkan.', null, 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'User belum login.', null, 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$notificationId = $input['notification_id'] ?? '';

if (!is_scalar($notificationId) ||
    !filter_var($notificationId, FILTER_VALIDATE_INT) ||
    (int) $notificationId < 1) {
    jsonResponse(false, 'ID notifikasi tidak valid.', null, 400);
}

$stmt = $pdo->prepare(
    "UPDATE notifications
     SET is_read = 1
     WHERE id = :id
       AND recipient_id = :recipient_id"
);

$stmt->execute([
    ':id' => (int) $notificationId,
    ':recipient_id' => (int) $_SESSION['user_id']
]);

if ($stmt->rowCount() === 0) {
    $stmt = $pdo->prepare(
        "SELECT id
         FROM notifications
         WHERE id = :id
           AND recipient_id = :recipient_id
         LIMIT 1"
    );

    $stmt->execute([
        ':id' => (int) $notificationId,
        ':recipient_id' => (int) $_SESSION['user_id']
    ]);

    if (!$stmt->fetch()) {
        jsonResponse(
            false,
            'Notifikasi tidak ditemukan.',
            null,
            404
        );
    }
}

jsonResponse(true, 'Notifikasi ditandai sudah dibaca.', [
    'notification_id' => (int) $notificationId,
    'is_read' => true
]);
