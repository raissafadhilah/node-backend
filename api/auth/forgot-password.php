<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/session.php';

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

$email = trim($input['email'] ?? '');

if ($email === '') {
    jsonResponse(
        false,
        'Email wajib diisi.',
        null,
        422
    );
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(
        false,
        'Format email tidak valid.',
        null,
        422
    );
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email,
        is_verified
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$user = $stmt->fetch();

if (!$user) {
    jsonResponse(
        false,
        'Email tidak ditemukan.',
        null,
        404
    );
}

if (!(int) $user['is_verified']) {
    jsonResponse(
        false,
        'Akun belum terverifikasi.',
        null,
        403
    );
}

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

try {

    $pdo->beginTransaction();

    /*
     * OTP reset sebelumnya yang belum digunakan
     * tidak perlu dipakai lagi.
     */
    $stmt = $pdo->prepare(
        "UPDATE email_verifications
         SET is_used = 1
         WHERE user_id = :user_id
         AND is_used = 0"
    );

    $stmt->execute([
        ':user_id' => $user['id']
    ]);

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

    $mail->clearAddresses();

    $mail->addAddress(
        $user['email'],
        $user['full_name']
    );

    $mail->isHTML(true);

    $mail->Subject = 'OTP Forgot Password - Node';

    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>

            <h2>Forgot Password - Node</h2>

            <p>
                Halo <strong>{$user['full_name']}</strong>,
            </p>

            <p>
                Gunakan kode OTP berikut untuk
                mengatur ulang password akun Node kamu:
            </p>

            <h1 style='letter-spacing: 8px;'>
                {$otp}
            </h1>

            <p>
                Kode OTP ini berlaku selama
                <strong>5 menit</strong>.
            </p>

            <p>
                Jangan berikan kode ini kepada siapa pun.
            </p>

            <p>
                Terima kasih,<br>
                <strong>Node</strong>
            </p>

        </div>
    ";

    $mail->AltBody = "
        OTP Forgot Password Node: {$otp}

        Kode OTP ini berlaku selama 5 menit.

        Jangan berikan kode ini kepada siapa pun.
    ";

    $mail->send();

    $pdo->commit();

    $_SESSION['password_reset_user_id'] = (int) $user['id'];
    $_SESSION['password_reset_email'] = $user['email'];
    $_SESSION['password_reset_verified'] = false;

    jsonResponse(
        true,
        'OTP reset password berhasil dikirim ke email kamu.',
        [
            'email' => $user['email']
        ]
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'OTP gagal dikirim. Silakan coba lagi.',
        null,
        500
    );
}