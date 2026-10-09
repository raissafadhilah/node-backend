<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$commentId = $_POST['comment_id'] ?? '';
$content = trim($_POST['content'] ?? '');

if (!filter_var($commentId, FILTER_VALIDATE_INT)) {
    die('ID komentar tidak valid.');
}

if ($content === '') {
    die('Komentar wajib diisi.');
}

$stmt = $pdo->prepare(
    "UPDATE comments
     SET content = :content
     WHERE id = :comment_id
     AND user_id = :user_id
     AND status = 'active'"
);

$stmt->execute([
    ':content' => $content,
    ':comment_id' => $commentId,
    ':user_id' => $userId
]);

if ($stmt->rowCount() === 0) {
    die('Komentar tidak ditemukan atau bukan milik kamu.');
}

echo "Komentar berhasil diperbarui!";