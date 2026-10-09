<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$commentId = $_POST['comment_id'] ?? '';

if (!filter_var($commentId, FILTER_VALIDATE_INT)) {
    die('ID komentar tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        post_id,
        user_id
     FROM comments
     WHERE id = :comment_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':comment_id' => $commentId
]);

$comment = $stmt->fetch();

if (!$comment) {
    die('Komentar tidak ditemukan atau sudah dihapus.');
}


// User bisa menghapus komentar sendiri.
// Pemilik postingan juga bisa menghapus komentar orang lain.
if ($comment['user_id'] != $userId) {

    $stmt = $pdo->prepare(
        "SELECT id
         FROM posts
         WHERE id = :post_id
         AND user_id = :user_id
         AND status = 'active'
         LIMIT 1"
    );

    $stmt->execute([
        ':post_id' => $comment['post_id'],
        ':user_id' => $userId
    ]);

    $post = $stmt->fetch();

    if (!$post) {
        die('Kamu tidak memiliki izin untuk menghapus komentar ini.');
    }
}


// Soft delete komentar
$stmt = $pdo->prepare(
    "UPDATE comments
     SET status = 'removed'
     WHERE id = :comment_id
     AND status = 'active'"
);

$stmt->execute([
    ':comment_id' => $commentId
]);

if ($stmt->rowCount() === 0) {
    die('Komentar gagal dihapus.');
}

echo "Komentar berhasil dihapus!";