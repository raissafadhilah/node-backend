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

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        email,
        profile_photo
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    jsonResponse(
        false,
        'User tidak ditemukan.',
        null,
        404
    );
}

jsonResponse(
    true,
    'User sedang login.',
    [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'status' => $user['status'],
        'email' => $user['email'],
        'profile_photo' => $user['profile_photo']
    ]
);