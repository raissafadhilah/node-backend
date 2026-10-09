<?php

require_once '../config/database.php';
require_once '../includes/session.php';
require_once '../config/mail.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: register.php');
    exit;
}

$fullName = trim($_POST['full_name'] ?? '');
$status = $_POST['status'] ?? '';
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (
    $fullName === '' ||
    $status === '' ||
    $email === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    die('Semua field wajib diisi.');
}

if (!in_array($status, ['mahasiswa', 'alumni'], true)) {
    die('Status tidak valid.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die('Format email tidak valid.');
}

if ($password !== $confirmPassword) {
    die('Password dan confirm password tidak sama.');
}

if (strlen($password) < 8) {
    die('Password minimal 8 karakter.');
}

$stmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$existingUser = $stmt->fetch();

if ($existingUser) {
    die('Email sudah digunakan.');
}

$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

$stmt = $pdo->prepare(
    "INSERT INTO users (
        full_name,
        status,
        email,
        password,
        is_verified
    ) VALUES (
        :full_name,
        :status,
        :email,
        :password,
        :is_verified
    )"
);

$stmt->execute([
    ':full_name' => $fullName,
    ':status' => $status,
    ':email' => $email,
    ':password' => $hashedPassword,
    ':is_verified' => false
]);

$userId = $pdo->lastInsertId();

$otp = str_pad(
    random_int(0, 999999),
    6,
    '0',
    STR_PAD_LEFT
);

$expiresAt = date(
    'Y-m-d H:i:s',
    time() + (5 * 60)
);

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
        :is_used
    )"
);

$stmt->execute([
    ':user_id' => $userId,
    ':otp' => $otp,
    ':expires_at' => $expiresAt,
    ':is_used' => false
]);

try {

    $mail->addAddress($email);
    $mail->isHTML(true);

    $mail->Subject = 'Kode Verifikasi Node';

    $mail->Body = "

        <div style='font-family: Arial, sans-serif;'>

            <h2>Verifikasi Email Node</h2>

            <p>
                Halo <strong>{$fullName}</strong>,
            </p>

            <p>
                Gunakan kode OTP berikut untuk memverifikasi akun Node kamu:
            </p>

            <h1 style='letter-spacing: 8px;'>
                {$otp}
            </h1>

            <p>
                Kode OTP ini berlaku selama
                <strong>5 menit</strong>.
            </p>

            <p>
                Jangan berikan kode ini kepada orang lain.
            </p>

            <p>
                Terima kasih,<br>
                <strong>Node</strong>
            </p>

        </div>

    ";

    $mail->AltBody = "
        Kode verifikasi Node kamu adalah: {$otp}

        Kode ini berlaku selama 5 menit.

        Jangan berikan kode ini kepada orang lain.
    ";

    $mail->send();

} catch (Throwable $e) {

    die(
        'Gagal mengirim email OTP.<br>' .
        'Error: ' . $mail->ErrorInfo
    );

}

echo "Register berhasil! OTP sudah dikirim ke email kamu.";