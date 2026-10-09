<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
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

$otp = trim($input['otp'] ?? '');

if ($otp === '') {
    jsonResponse(
        false,
        'OTP wajib diisi.',
        null,
        422
    );
}

if (!preg_match('/^\d{6}$/', $otp)) {
    jsonResponse(
        false,
        'OTP harus terdiri dari 6 digit.',
        null,
        422
    );
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT
        id,
        user_id,
        new_email,
        otp,
        expires_at,
        is_used
     FROM email_change_verifications
     WHERE user_id = :user_id
       AND otp = :otp
       AND is_used = 0
     ORDER BY id DESC
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId,
    ':otp' => $otp
]);

$verification = $stmt->fetch();

if (!$verification) {
    jsonResponse(
        false,
        'OTP tidak valid atau sudah digunakan.',
        null,
        422
    );
}



if (strtotime($verification['expires_at']) < time()) {
    jsonResponse(
        false,
        'OTP sudah kedaluwarsa.',
        null,
        422
    );
}

$newEmail = $verification['new_email'];


$stmt = $pdo->prepare(
    "SELECT id
     FROM users
     WHERE email = :email
       AND id != :user_id
     LIMIT 1"
);

$stmt->execute([
    ':email' => $newEmail,
    ':user_id' => $userId
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

try {

    $pdo->beginTransaction();


    $stmt = $pdo->prepare(
        "UPDATE users
         SET email = :email
         WHERE id = :user_id"
    );

    $stmt->execute([
        ':email' => $newEmail,
        ':user_id' => $userId
    ]);


    $stmt = $pdo->prepare(
        "UPDATE email_change_verifications
         SET is_used = 1
         WHERE id = :verification_id
           AND user_id = :user_id
           AND is_used = 0"
    );

    $stmt->execute([
        ':verification_id' => $verification['id'],
        ':user_id' => $userId
    ]);



    if ($stmt->rowCount() !== 1) {
        throw new RuntimeException(
            'OTP gagal ditandai sebagai sudah digunakan.'
        );
    }



    $_SESSION['email'] = $newEmail;

    $pdo->commit();

    jsonResponse(
        true,
        'Email berhasil diperbarui.',
        [
            'email' => $newEmail
        ]
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal memperbarui email.',
        null,
        500
    );
}