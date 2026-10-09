<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
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

$userId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Ambil foto profile user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT profile_photo
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

$profilePhoto = $user['profile_photo'];

/*
|--------------------------------------------------------------------------
| Kalau user tidak punya foto
|--------------------------------------------------------------------------
*/

if (empty($profilePhoto)) {
    jsonResponse(
        false,
        'User belum memiliki foto profile.',
        null,
        400
    );
}

/*
|--------------------------------------------------------------------------
| Hapus file fisik
|--------------------------------------------------------------------------
*/

$filePath = __DIR__ . '/../../' . $profilePhoto;

if (
    file_exists($filePath) &&
    is_file($filePath)
) {
    if (!unlink($filePath)) {
        jsonResponse(
            false,
            'Foto profile gagal dihapus.',
            null,
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| Update database
|--------------------------------------------------------------------------
*/

$stmtUpdate = $pdo->prepare(
    "UPDATE users
     SET profile_photo = NULL
     WHERE id = :user_id"
);

$stmtUpdate->execute([
    ':user_id' => $userId
]);

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    'Foto profile berhasil dihapus.',
    [
        'profile_photo' => null
    ]
);