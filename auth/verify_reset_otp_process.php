<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_reset_otp.php');
    exit;
}

if (!isset($_SESSION['password_reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

$userId = $_SESSION['password_reset_user_id'];
$otp = trim($_POST['otp'] ?? '');

if ($otp === '') {
    die('OTP wajib diisi.');
}

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    die('OTP harus terdiri dari 6 digit.');
}

$stmt = $pdo->prepare(
    "SELECT id, otp, expires_at, is_used
     FROM email_verifications
     WHERE user_id = :user_id
     ORDER BY id DESC
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId
]);

$verification = $stmt->fetch();

if (!$verification) {
    die('OTP tidak ditemukan.');
}

if ($verification['is_used']) {
    die('OTP sudah digunakan.');
}

if (strtotime($verification['expires_at']) < time()) {
    die('OTP sudah expired. Silakan minta OTP baru.');
}


if (!hash_equals($verification['otp'], $otp)) {
    die('OTP tidak valid.');
}

$stmt = $pdo->prepare(
    "UPDATE email_verifications
     SET is_used = TRUE
     WHERE id = :id"
);

$stmt->execute([
    ':id' => $verification['id']
]);

$_SESSION['password_reset_verified'] = true;

header('Location: reset_password.php');
exit;