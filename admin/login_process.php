<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if ($username === '' || $password === '') {
    die('Username dan password wajib diisi.');
}


// neangan admin make username na

$stmt = $pdo->prepare(
    "SELECT
        id,
        username,
        password
     FROM admins
     WHERE username = :username
     LIMIT 1"
);

$stmt->execute([
    ':username' => $username
]);

$admin = $stmt->fetch();


// nga validasi password na make password_verify

if (!$admin) {
    die('Username atau password salah.');
}

if (!password_verify($password, $admin['password'])) {
    die('Username atau password salah.');
}


// login berhasil, simpen data admin kana session
session_regenerate_id(true);

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_username'] = $admin['username'];

echo "Login admin berhasil!";