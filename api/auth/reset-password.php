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

$password = $input['password'] ?? '';
$passwordConfirmation = $input['password_confirmation'] ?? '';

if (
    $password === '' ||
    $passwordConfirmation === ''
) {
    jsonResponse(
        false,
        'Password wajib diisi.',
        null,
        422
    );
}

if ($password !== $passwordConfirmation) {
    jsonResponse(
        false,
        'Konfirmasi password tidak cocok.',
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

if (
    !isset($_SESSION['password_reset_user_id']) ||
    !isset($_SESSION['password_reset_verified']) ||
    $_SESSION['password_reset_verified'] !== true
) {
    jsonResponse(
        false,
        'Kamu belum menyelesaikan verifikasi OTP.',
        null,
        403
    );
}

$userId = (int) $_SESSION['password_reset_user_id'];

$passwordHash = password_hash(
    $password,
    PASSWORD_DEFAULT
);

try {

    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "UPDATE users
         SET password = :password
         WHERE id = :user_id"
    );

    $stmt->execute([
        ':password' => $passwordHash,
        ':user_id' => $userId
    ]);

    if ($stmt->rowCount() === 0) {
        throw new RuntimeException(
            'User tidak ditemukan.'
        );
    }

    $pdo->commit();

    unset(
        $_SESSION['password_reset_user_id'],
        $_SESSION['password_reset_email'],
        $_SESSION['password_reset_verified']
    );

    jsonResponse(
        true,
        'Password berhasil diubah! Silakan login kembali.',
        null
    );

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(
        false,
        'Gagal mengubah password.',
        null,
        500
    );
}