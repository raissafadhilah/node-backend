<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(
        false,
        'Method tidak diizinkan.',
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

$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if ($email === '' || $password === '') {
    jsonResponse(
        false,
        'Email dan password wajib diisi.',
        null,
        422
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(
        false,
        'Format email tidak valid.',
        null,
        422
    );
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        email,
        password,
        is_verified
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch();

if (!$user) {
    jsonResponse(
        false,
        'Email atau password salah.',
        null,
        401
    );
}

if (!password_verify($password, $user['password'])) {
    jsonResponse(
        false,
        'Email atau password salah.',
        null,
        401
    );
}

if (!(int) $user['is_verified']) {
    jsonResponse(
        false,
        'Akun belum terverifikasi.',
        null,
        403
    );
}

session_regenerate_id(true);

$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['status'] = $user['status'];
$_SESSION['email'] = $user['email'];

jsonResponse(
    true,
    'Login berhasil!',
    [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'status' => $user['status'],
        'email' => $user['email']
    ]
);