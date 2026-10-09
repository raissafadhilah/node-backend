<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
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
        'Data tidak valid.',
        null,
        400
    );
}

$fullName = trim($input['full_name'] ?? '');
$status = trim($input['status'] ?? '');

if ($fullName === '') {
    jsonResponse(
        false,
        'Nama lengkap wajib diisi.',
        null,
        400
    );
}

if (mb_strlen($fullName) > 100) {
    jsonResponse(
        false,
        'Nama lengkap maksimal 100 karakter.',
        null,
        400
    );
}

$allowedStatuses = [
    'mahasiswa',
    'alumni'
];

if (!in_array($status, $allowedStatuses, true)) {
    jsonResponse(
        false,
        'Status tidak valid.',
        null,
        400
    );
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "UPDATE users
     SET
        full_name = :full_name,
        status = :status
     WHERE id = :user_id"
);

$stmt->execute([
    ':full_name' => $fullName,
    ':status' => $status,
    ':user_id' => $userId
]);

$_SESSION['full_name'] = $fullName;
$_SESSION['status'] = $status;

jsonResponse(
    true,
    'Profile berhasil diperbarui.',
    [
        'full_name' => $fullName,
        'status' => $status
    ]
);