<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}



if (
    !isset($_SESSION['password_reset_user_id'])
    || !isset($_SESSION['password_reset_verified'])
    || $_SESSION['password_reset_verified'] !== true
) {
    header('Location: forgot_password.php');
    exit;
}

$userId = $_SESSION['password_reset_user_id'];

$password = $_POST['password'] ?? '';
$passwordConfirmation = $_POST['password_confirmation'] ?? '';



if ($password === '' || $passwordConfirmation === '') {
    die('Password wajib diisi.');
}

if ($password !== $passwordConfirmation) {
    die('Konfirmasi password tidak cocok.');
}

if (strlen($password) < 8) {
    die('Password minimal 8 karakter.');
}



$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);


$stmt = $pdo->prepare(
    "UPDATE users
     SET password = :password
     WHERE id = :user_id"
);

$stmt->execute([
    ':password' => $passwordHash,
    ':user_id' => $userId
]);



unset($_SESSION['password_reset_user_id']);
unset($_SESSION['password_reset_email']);
unset($_SESSION['password_reset_verified']);


echo "Password berhasil diubah! Silakan login kembali.";