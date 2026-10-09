<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];
$postId = $_GET['post_id'] ?? '';

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

// Pastikan postingan ada, aktif, dan ambil pemiliknya
$stmt = $pdo->prepare(
    "SELECT
        id,
        user_id,
        content
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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Report Postingan - Node</title>
</head>

<body>

    <h1>Report Postingan</h1>

    <h3>Postingan:</h3>

    <p>
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </p>

    <hr>

    <form action="create_process.php" method="POST">

        <input
            type="hidden"
            name="post_id"
            value="<?= htmlspecialchars($post['id']) ?>"
        >

        <label for="reason">
            Alasan Report
        </label>

        <br>

        <textarea
            id="reason"
            name="reason"
            rows="6"
            cols="50"
            placeholder="Jelaskan alasan kamu melaporkan postingan ini..."
            required
        ></textarea>

        <br>
        <br>

        <button type="submit">
            Kirim Report
        </button>

    </form>

    <br>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>
</html>