<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../config/mail.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
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

$newEmail = trim($input['new_email'] ?? '');

if ($newEmail === '') {
    jsonResponse(
        false,
        'Email baru wajib diisi.',
        null,
        422
    );
}

if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(
        false,
        'Format email tidak valid.',
        null,
        422
    );
}

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Ambil data user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        email
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    jsonResponse(
        false,
        'User tidak ditemukan.',
        null,
        404
    );
}

/*
|--------------------------------------------------------------------------
| Email baru tidak boleh sama dengan email lama
|--------------------------------------------------------------------------
*/

if (strcasecmp($newEmail, $user['email']) === 0) {
    jsonResponse(
        false,
        'Email baru sama dengan email saat ini.',
        null,
        422
    );
}

/*
|--------------------------------------------------------------------------
| Cek email baru sudah digunakan atau belum
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE email = :email
     LIMIT 1"
);

$stmt->execute([
    ':email' => $newEmail
]);

$existingUser = $stmt->fetch();

if ($existingUser) {
    jsonResponse(
        false,
        'Email sudah digunakan oleh akun lain.',
        null,
        409
    );
}

/*
|--------------------------------------------------------------------------
| Generate OTP
|--------------------------------------------------------------------------
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
|--------------------------------------------------------------------------
| Transaction
|--------------------------------------------------------------------------
*/

try {

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Nonaktifkan OTP perubahan email sebelumnya
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "UPDATE email_change_verifications
         SET is_used = 1
         WHERE user_id = :user_id
           AND is_used = 0"
    );

    $stmt->execute([
        ':user_id' => $userId
    ]);

    /*
    |--------------------------------------------------------------------------
    | Simpan OTP baru
    |--------------------------------------------------------------------------
    */

    $stmt = $pdo->prepare(
        "INSERT INTO email_change_verifications (
            user_id,
            new_email,
            otp,
            expires_at,
            is_used
        ) VALUES (
            :user_id,
            :new_email,
            :otp,
            :expires_at,
            :is_used
        )"
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':new_email' => $newEmail,
        ':otp' => $otp,
        ':expires_at' => $expiresAt,
        ':is_used' => false
    ]);

    /*
    |--------------------------------------------------------------------------
    | Kirim OTP ke email baru
    |--------------------------------------------------------------------------
    */

    $mail->clearAddresses();
    $mail->addAddress($newEmail);
    $mail->isHTML(true);

    $mail->Subject = 'Kode Verifikasi Perubahan Email Node';

    $mail->Body = "
        <div style='font-family: Arial, sans-serif;'>
            <h2>Verifikasi Perubahan Email Node</h2>

            <p>
                Halo <strong>{$user['full_name']}</strong>,
            </p>

            <p>
                Gunakan kode OTP berikut untuk memverifikasi
                perubahan email akun Node kamu:
            </p>

            <h1 style='letter-spacing: 8px;'>{$otp}</h1>

            <p>
                Kode OTP ini berlaku selama
                <strong>5 menit</strong>.
            </p>

            <p>
                Jika kamu tidak meminta perubahan email,
                abaikan email ini.
            </p>

            <p>
                Jangan berikan kode OTP ini kepada orang lain.
            </p>

            <p>
                Terima kasih,<br>
                <strong>Node</strong>
            </p>
        </div>
    ";

    $mail->AltBody = "
        Kode verifikasi perubahan email Node kamu adalah: {$otp}

        Kode ini berlaku selama 5 menit.

        Jika kamu tidak meminta perubahan email,
        abaikan email ini.

        Jangan berikan kode OTP ini kepada orang lain.
    ";

    $mail->send();

    /*
    |--------------------------------------------------------------------------
    | Semua berhasil → commit
    |--------------------------------------------------------------------------
    */

    $pdo->commit();

    jsonResponse(
        true,
        'OTP berhasil dikirim ke email baru.',
        [
            'email' => $newEmail
        ]
    );

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Jika database/email gagal → rollback
    |--------------------------------------------------------------------------
    */

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal mengirim OTP perubahan email.',
        null,
        500
    );
}