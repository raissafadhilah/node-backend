<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];

$postId = $_GET['id'] ?? '';

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

// Mengambil postingan yang dimiliki user
$stmt = $pdo->prepare(
    "SELECT id, content
     FROM posts
     WHERE id = :post_id
     AND user_id = :user_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $postId,
    ':user_id' => $userId
]);

$post = $stmt->fetch();

if (!$post) {
    die('Postingan tidak ditemukan atau bukan milik kamu.');
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

    <title>Edit Post - Node</title>

</head>

<body>

    <h1>Edit Postingan</h1>

    <form action="edit_process.php" method="POST">

        <input
            type="hidden"
            name="post_id"
            value="<?= htmlspecialchars($post['id']) ?>"
        >

        <div>

            <label for="content">
                Isi Postingan
            </label>

            <br>

            <textarea
                id="content"
                name="content"
                rows="8"
                cols="50"
                required
            ><?= htmlspecialchars($post['content']) ?></textarea>

        </div>

        <br>

        <button type="submit">
            Simpan Perubahan
        </button>

    </form>

    <br>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>

</html>