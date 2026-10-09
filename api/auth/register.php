<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';

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

$fullName = trim($input['full_name'] ?? '');
$status = $input['status'] ?? '';
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirmPassword = $input['confirm_password'] ?? '';

/*
 * VALIDASI FIELD
 */
if (
    $fullName === '' ||
    $status === '' ||
    $email === '' ||
    $password === '' ||
    $confirmPassword === ''
) {
    jsonResponse(
        false,
        'Semua field wajib diisi.',
        null,
        422
    );
}

/*
 * VALIDASI STATUS
 */
if (!in_array($status, ['mahasiswa', 'alumni'], true)) {
    jsonResponse(
        false,
        'Status tidak valid.',
        null,
        422
    );
}

/*
 * VALIDASI EMAIL
 */
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(
        false,
        'Format email tidak valid.',
        null,
        422
    );
}

/*
 * VALIDASI PASSWORD
 */
if ($password !== $confirmPassword) {
    jsonResponse(
        false,
        'Password dan confirm password tidak sama.',
        null,
        422
    );
}

if (strlen($password) < 8) {
    jsonResponse(
        false,
        'Password minimal 8 karakter.',
        null,
        422
    );
}

/*
 * CEK EMAIL
 */
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
    jsonResponse(
        false,
        'Email sudah digunakan.',
        null,
        409
    );
}

/*
 * HASH PASSWORD
 */
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {
    $pdo->beginTransaction();

    /*
     * CREATE USER
     */
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

    /*
     * GENERATE OTP
     */
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

    /*
     * SAVE OTP
     */
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

    /*
     * SEND EMAIL OTP
     */
    $mail->clearAddresses();

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
                Gunakan kode OTP berikut untuk memverifikasi
                akun Node kamu:
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

    /*
     * SEMUA BERHASIL
     */
    $pdo->commit();

    jsonResponse(
        true,
        'Register berhasil. OTP sudah dikirim ke email kamu.',
        [
            'user_id' => (int) $userId,
            'email' => $email
        ],
        201
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal melakukan registrasi.',
        null,
        500
    );
}