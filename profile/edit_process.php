<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: edit.php');
    exit;
}

$userId = $_SESSION['user_id'];

$fullName = trim($_POST['full_name'] ?? '');
$status = $_POST['status'] ?? '';
$email = trim($_POST['email'] ?? '');

if ($fullName === '') {
    die('Full Name wajib diisi.');
}

if ($status === '') {
    die('Status wajib dipilih.');
}

if (!in_array($status, ['mahasiswa', 'alumni'], true)) {
    die('Status tidak valid.');
}

if ($email === '') {
    die('Email wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Format email tidak valid.');
}

if (strlen($fullName) > 100) {
    die('Full Name maksimal 100 karakter.');
}

// Cek apakah email sudah digunakan user lain
$stmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE email = :email
     AND id != :user_id
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email,
    ':user_id' => $userId
]);

$existingEmail = $stmt->fetch();

if ($existingEmail) {
    die('Email sudah digunakan.');
}

// Update data profile
$stmt = $pdo->prepare(
    "UPDATE users
     SET full_name = :full_name,
         status = :status,
         email = :email
     WHERE id = :user_id"
);

$stmt->execute([
    ':full_name' => $fullName,
    ':status' => $status,
    ':email' => $email,
    ':user_id' => $userId
]);

// Perbarui data session
$_SESSION['full_name'] = $fullName;
$_SESSION['status'] = $status;
$_SESSION['email'] = $email;

echo "Profile berhasil diperbarui!";