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

if (!isset($_FILES['profile_photo'])) {
    jsonResponse(
        false,
        'Foto profile wajib diupload.',
        null,
        400
    );
}

$file = $_FILES['profile_photo'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(
        false,
        'Upload foto gagal.',
        null,
        400
    );
}

/*
|--------------------------------------------------------------------------
| Validasi ukuran
|--------------------------------------------------------------------------
*/

$maxFileSize = 2 * 1024 * 1024; // 2 MB

if ($file['size'] > $maxFileSize) {
    jsonResponse(
        false,
        'Ukuran foto maksimal 2 MB.',
        null,
        400
    );
}

/*
|--------------------------------------------------------------------------
| Validasi MIME type
|--------------------------------------------------------------------------
*/

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);

$allowedMimeTypes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];

if (!isset($allowedMimeTypes[$mimeType])) {
    jsonResponse(
        false,
        'Format foto harus JPG, PNG, atau WEBP.',
        null,
        400
    );
}

$extension = $allowedMimeTypes[$mimeType];

/*
|--------------------------------------------------------------------------
| Folder upload
|--------------------------------------------------------------------------
*/

$uploadDirectory = __DIR__ . '/../../uploads/profile/';

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true)) {
        jsonResponse(
            false,
            'Folder upload tidak dapat dibuat.',
            null,
            500
        );
    }
}

/*
|--------------------------------------------------------------------------
| Ambil foto lama
|--------------------------------------------------------------------------
*/

$userId = (int) $_SESSION['user_id'];

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

$oldProfilePhoto = $user['profile_photo'];

/*
|--------------------------------------------------------------------------
| Generate nama file baru
|--------------------------------------------------------------------------
*/

$fileName = bin2hex(random_bytes(16)) . '.' . $extension;

$destination = $uploadDirectory . $fileName;

$databasePath = 'uploads/profile/' . $fileName;

/*
|--------------------------------------------------------------------------
| Pindahkan file
|--------------------------------------------------------------------------
*/

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    jsonResponse(
        false,
        'Foto gagal disimpan.',
        null,
        500
    );
}

/*
|--------------------------------------------------------------------------
| Update database
|--------------------------------------------------------------------------
*/

try {
    $stmtUpdate = $pdo->prepare(
        "UPDATE users
         SET profile_photo = :profile_photo
         WHERE id = :user_id"
    );

    $stmtUpdate->execute([
        ':profile_photo' => $databasePath,
        ':user_id' => $userId
    ]);

} catch (PDOException $e) {

    if (file_exists($destination)) {
        unlink($destination);
    }

    jsonResponse(
        false,
        'Foto gagal disimpan ke database.',
        null,
        500
    );
}

/*
|--------------------------------------------------------------------------
| Hapus foto lama
|--------------------------------------------------------------------------
*/

if (!empty($oldProfilePhoto)) {

    $oldFilePath = __DIR__ . '/../../' . $oldProfilePhoto;

    if (
        file_exists($oldFilePath) &&
        is_file($oldFilePath)
    ) {
        unlink($oldFilePath);
    }
}

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    'Foto profile berhasil diperbarui.',
    [
        'profile_photo' => $databasePath
    ]
);