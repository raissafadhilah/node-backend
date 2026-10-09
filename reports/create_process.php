<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$postId = $_POST['post_id'] ?? '';
$reason = trim($_POST['reason'] ?? '');

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

if ($reason === '') {
    die('Alasan report wajib diisi.');
}

// Pastikan postingan ada, aktif, dan ambil pemiliknya
$stmt = $pdo->prepare(
    "SELECT
        id,
        user_id
     FROM posts
     WHERE id = :post_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId
]);

$post = $stmt->fetch();

if (!$post) {
    die('Postingan tidak ditemukan atau sudah dihapus.');
}

// User tidak boleh report postingannya sendiri
if ($post['user_id'] == $userId) {
    die('Kamu tidak dapat melaporkan postingan milik sendiri.');
}

// Buat report baru untuk postingan
$stmt = $pdo->prepare(
    "INSERT INTO reports (
        reporter_id,
        post_id,
        comment_id,
        reason,
        status
    ) VALUES (
        :reporter_id,
        :post_id,
        NULL,
        :reason,
        'pending'
    )"
);

$stmt->execute([
    ':reporter_id' => $userId,
    ':post_id' => $postId,
    ':reason' => $reason
]);

echo "Report berhasil dikirim!";