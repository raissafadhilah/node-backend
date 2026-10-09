<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../public/index.php');
    exit;
}

$userId = $_SESSION['user_id'];

$postId = filter_input(
    INPUT_POST,
    'post_id',
    FILTER_VALIDATE_INT
);

if (!$postId) {
    die('Post ID tidak valid.');
}

// Cek apakah post benar-benar ada dan masih active
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
    die('Postingan tidak ditemukan atau sudah tidak tersedia.');
}

// Cek apakah post sudah disimpan user
$stmt = $pdo->prepare(
    "SELECT id
     FROM bookmarks
     WHERE user_id = :user_id
     AND post_id = :post_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId,
    ':post_id' => $postId
]);

$bookmark = $stmt->fetch();

if ($bookmark) {

    // Kalau sudah disimpan = hapus bookmark
    $stmt = $pdo->prepare(
        "DELETE FROM bookmarks
         WHERE user_id = :user_id
         AND post_id = :post_id"
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':post_id' => $postId
    ]);

    echo "Postingan dihapus dari Saved.";

} else {

    // Kalau belum disimpan = tambahkan bookmark
    $stmt = $pdo->prepare(
        "INSERT INTO bookmarks (
            user_id,
            post_id
        ) VALUES (
            :user_id,
            :post_id
        )"
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':post_id' => $postId
    ]);

    echo "Postingan berhasil disimpan.";

}