
<?php

require_once '../config/database.php';
require_once '../includes/session.php';

// Pastikan request berasal dari POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Ambil email dan password
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
echo "FILE LOGIN PROCESS BERHASIL DIJALANKAN";

if ($email === '' || $password === '') {
    die('Email dan password wajib diisi.');
}

// Cari admin berdasarkan email
$stmt = $pdo->prepare(
    "SELECT
        id,
        email,
        username,
        password
     FROM admins
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$admin = $stmt->fetch();

if (!$admin) {
    die('DEBUG: EMAIL ADMIN TIDAK DITEMUKAN.');
}

if (!password_verify($password, $admin['password'])) {
    die('DEBUG: EMAIL DITEMUKAN, TETAPI PASSWORD TIDAK COCOK.');
}


session_regenerate_id(true);
$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_email'] = $admin['email'];
$_SESSION['admin_username'] = $admin['username'];


header('Location: dashboard.php');
exit;
