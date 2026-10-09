<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
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

$otp = trim($input['otp'] ?? '');

if ($otp === '') {
    jsonResponse(
        false,
        'OTP wajib diisi.',
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

if (!isset($_SESSION['password_reset_user_id'])) {
    jsonResponse(
        false,
        'Sesi reset password tidak ditemukan. Silakan minta OTP kembali.',
        null,
        401
    );
}

$userId = (int) $_SESSION['password_reset_user_id'];

$stmt = $pdo->prepare(
    "SELECT
        id,
        otp,
        expires_at,
        is_used
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
    jsonResponse(
        false,
        'OTP tidak ditemukan.',
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

if (strtotime($verification['expires_at']) < time()) {
    jsonResponse(
        false,
        'OTP sudah expired. Silakan minta OTP baru.',
        null,
        422
    );
}

if (!hash_equals(
    (string) $verification['otp'],
    $otp
)) {
    jsonResponse(
        false,
        'OTP tidak valid.',
        null,
        422
    );
}

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE email_verifications
         SET is_used = 1
         WHERE id = :id"
    );

    $stmt->execute([
        ':id' => $verification['id']
    ]);

    $pdo->commit();

    $_SESSION['password_reset_verified'] = true;

    jsonResponse(
        true,
        'OTP berhasil diverifikasi.',
        [
            'verified' => true
        ]
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal melakukan verifikasi OTP.',
        null,
        500
    );
}