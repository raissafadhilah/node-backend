<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

// Pastikan request menggunakan metode POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];
$postId = $_POST['post_id'] ?? '';

// Validasi postId harus integer
if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

// Pastikan postingan ada dan masih aktif
$stmt = $pdo->prepare(
    "SELECT
        id
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

// Cek apakah user sudah pernah memberi reaction
$stmt = $pdo->prepare(
    "SELECT
        id
     FROM reactions
     WHERE user_id = :user_id
     AND post_id = :post_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId,
    ':post_id' => $postId
]);

$reaction = $stmt->fetch();

if ($reaction) {

    $stmt = $pdo->prepare(
        "DELETE FROM reactions
         WHERE id = :reaction_id"
    );

    $stmt->execute([
        ':reaction_id' => $reaction['id']
    ]);

    echo "Reaction dihapus!";
    exit;
}

// Jika belum ada reaction, tambahkan reaction baru
$stmt = $pdo->prepare(
    "INSERT INTO reactions (
        post_id,
        user_id
    ) VALUES (
        :post_id,
        :user_id
    )"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId
]);

echo "Reaction berhasil ditambahkan!";