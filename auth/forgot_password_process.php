<?php

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: forgot_password.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '') {
    die('Email wajib diisi.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Format email tidak valid.');
}

// cari akun berdasaekan email

$stmt = $pdo->prepare(
    "SELECT id, username, email, is_verified
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch();

// cek akun

if (!$user) {
    die('Email tidak ditemukan.');
}

if (!$user['is_verified']) {
    die('Akun belum terverifikasi.');
}

// generate otp 6 digit

$otp = str_pad(
    (string) random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);

$expiresAt = date(
    'Y-m-d H:i:s',
    time() + (5 * 60)
);

// simpan otp

$stmt = $pdo->prepare(
    "INSERT INTO email_verifications (
        user_id,
        otp,
        expires_at,
        is_used
    ) VALUES (
        :user_id,
        :otp,
        :expires_at,
        FALSE
    )"
);

$stmt->execute([
    ':user_id' => $user['id'],
    ':otp' => $otp,
    ':expires_at' => $expiresAt
]);


$_SESSION['password_reset_user_id'] = $user['id'];
$_SESSION['password_reset_email'] = $user['email'];

// kirim otp 

try {

    $mail->addAddress($user['email'], $user['username']);

    $mail->Subject = 'OTP Forgot Password - Node';

    $mail->Body = "
        <h2>Forgot Password - Node</h2>

        <p>Halo {$user['username']},</p>

        <p>Gunakan kode OTP berikut untuk mengatur ulang password akun kamu:</p>

        <h1>{$otp}</h1>

        <p>Kode OTP ini berlaku selama <strong>5 menit</strong>.</p>

        <p>Jangan berikan kode ini kepada siapa pun.</p>
    ";

    $mail->isHTML(true);

    $mail->send();

} catch (Exception $e) {
    die('OTP gagal dikirim. Silakan coba lagi.');
}



header('Location: verify_reset_otp.php');
exit;