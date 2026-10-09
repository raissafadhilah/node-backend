<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';

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
$otp = trim($input['otp'] ?? '');

if ($email === '' || $otp === '') {
    jsonResponse(
        false,
        'Email dan OTP wajib diisi.',
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

if (!preg_match('/^[0-9]{6}$/', $otp)) {
    jsonResponse(
        false,
        'OTP harus terdiri dari 6 digit.',
        null,
        422
    );
}

$stmt = $pdo->prepare(
    "SELECT
        ev.id AS verification_id,
        ev.user_id,
        ev.otp,
        ev.expires_at,
        ev.is_used
     FROM email_verifications ev
     INNER JOIN users u
        ON u.id = ev.user_id
     WHERE u.email = :email
     ORDER BY ev.id DESC
     LIMIT 1"
);

$stmt->execute([
    ':email' => $email
]);

$verification = $stmt->fetch();

if (!$verification) {
    jsonResponse(
        false,
        'Data verifikasi tidak ditemukan.',
        null,
        404
    );
}

if ((int) $verification['is_used'] === 1) {
    jsonResponse(
        false,
        'OTP sudah digunakan.',
        null,
        409
    );
}

if (!hash_equals(
    (string) $verification['otp'],
    $otp
)) {
    jsonResponse(
        false,
        'OTP salah.',
        null,
        422
    );
}

if (
    strtotime($verification['expires_at']) < time()
) {
    jsonResponse(
        false,
        'OTP sudah expired. Silakan minta OTP baru.',
        null,
        422
    );
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE users
         SET is_verified = 1
         WHERE id = :user_id"
    );

    $stmt->execute([
        ':user_id' => $verification['user_id']
    ]);

    $stmt = $pdo->prepare(
        "UPDATE email_verifications
         SET is_used = 1
         WHERE id = :verification_id"
    );

    $stmt->execute([
        ':verification_id' => $verification['verification_id']
    ]);

    $pdo->commit();

    jsonResponse(
        true,
        'Email berhasil diverifikasi! Silakan login.',
        [
            'user_id' => (int) $verification['user_id'],
            'email' => $email
        ]
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal melakukan verifikasi email.',
        null,
        500
    );
}