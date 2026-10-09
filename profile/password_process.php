<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: password.php');
    exit;
}

$userId = $_SESSION['user_id'];

$oldPassword = $_POST['old_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if ($oldPassword === '') {
    die('Password lama wajib diisi.');
}

if ($newPassword === '') {
    die('Password baru wajib diisi.');
}

if ($confirmPassword === '') {
    die('Konfirmasi password wajib diisi.');
}

if ($newPassword !== $confirmPassword) {
    die('Konfirmasi password tidak cocok.');
}

// Ambil password lama dari database
$stmt = $pdo->prepare(
    "SELECT password
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    die('Data user tidak ditemukan.');
}

// Verifikasi password lama
if (!password_verify($oldPassword, $user['password'])) {
    die('Password lama salah.');
}

// Pastikan password baru berbeda dari password lama
if (password_verify($newPassword, $user['password'])) {
    die('Password baru harus berbeda dari password lama.');
}

// Hash password baru
$hashedPassword = password_hash(
    $newPassword,
    PASSWORD_DEFAULT
);

// Update password
$stmt = $pdo->prepare(
    "UPDATE users
     SET password = :password
     WHERE id = :user_id"
);

$stmt->execute([
    ':password' => $hashedPassword,
    ':user_id' => $userId
]);

echo "Password berhasil diperbarui!";