
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

$stmt = $pdo->prepare(
    "UPDATE notifications
     SET is_read = 1
     WHERE recipient_id = :recipient_id
       AND is_read = 0"
);

$stmt->execute([
    ':recipient_id' => (int) $_SESSION['user_id']
]);

jsonResponse(true, 'Semua notifikasi ditandai sudah dibaca.', [
    'updated_count' => $stmt->rowCount()
]);
