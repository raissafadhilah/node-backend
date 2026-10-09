<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
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

$currentPassword = $input['current_password'] ?? '';
$newPassword = $input['new_password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

if (
    $currentPassword === '' ||
    $newPassword === '' ||
    $confirmPassword === ''
) {
    jsonResponse(
        false,
        'Semua field password wajib diisi.',
        null,
        422
    );
}

if ($newPassword !== $confirmPassword) {
    jsonResponse(
        false,
        'Password baru dan confirm password tidak sama.',
        null,
        422
    );
}

if (strlen($newPassword) < 8) {
    jsonResponse(
        false,
        'Password baru minimal 8 karakter.',
        null,
        422
    );
}

if ($currentPassword === $newPassword) {
    jsonResponse(
        false,
        'Password baru harus berbeda dari password saat ini.',
        null,
        422
    );
}

$userId = (int) $_SESSION['user_id'];



$stmt = $pdo->prepare(
    "SELECT
        id,
        password
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



if (
    empty($user['password']) ||
    !password_verify(
        $currentPassword,
        $user['password']
    )
) {
    jsonResponse(
        false,
        'Password saat ini salah.',
        null,
        422
    );
}


$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);


$stmt = $pdo->prepare(
    "UPDATE users
     SET password = :password
     WHERE id = :user_id"
);

$stmt->execute([
    ':password' => $hashedPassword,
    ':user_id' => $userId
]);

jsonResponse(
    true,
    'Password berhasil diperbarui.'
);