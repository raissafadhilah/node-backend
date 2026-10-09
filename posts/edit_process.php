<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$postId = $_POST['post_id'] ?? '';
$content = trim($_POST['content'] ?? '');

// Validasi

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

if ($content === '') {
    die('Isi postingan wajib diisi.');
}

// User ID sengaja dicek supaya user tidak bisa mengedit postingan orang lain

$stmt = $pdo->prepare(
    "UPDATE posts
     SET content = :content
     WHERE id = :post_id
     AND user_id = :user_id
     AND status = 'active'"
);

$stmt->execute([
    ':content' => $content,
    ':post_id' => $postId,
    ':user_id' => $userId
]);

if ($stmt->rowCount() === 0) {
    die('Postingan tidak ditemukan atau bukan milik kamu.');
}

echo "Postingan berhasil diperbarui!";