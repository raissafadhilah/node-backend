<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    die('Email dan password wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Format email tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT id, full_name, status, email, password, is_verified
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch();

if (!$user) {
    die('Email atau password salah.');
}

if (!password_verify($password, $user['password'])) {
    die('Email atau password salah.');
}

if (!$user['is_verified']) {
    die('Akun belum terverifikasi.');
}

session_regenerate_id(true);

$_SESSION['user_id'] = $user['id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['status'] = $user['status'];
$_SESSION['email'] = $user['email'];

echo "Login berhasil!";