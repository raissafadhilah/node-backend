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

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

if ($content === '') {
    die('Komentar wajib diisi.');
}

$stmt = $pdo->prepare(
    "SELECT id
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

$stmt = $pdo->prepare(
    "INSERT INTO comments (
        post_id,
        user_id,
        content,
        status
    ) VALUES (
        :post_id,
        :user_id,
        :content,
        :status
    )"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId,
    ':content' => $content,
    ':status' => 'active'
]);
echo "Komentar berhasil dibuat!";