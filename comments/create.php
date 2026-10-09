<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$postId = $_GET['post_id'] ?? '';

if (!filter_var($postId, FILTER_VALIDATE_INT)) {
    die('ID postingan tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT id, content
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
    die('Postingan tidak ditemukan.');
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

    <title>Comment - Node</title>

</head>

<body>

    <h1>Tambah Komentar</h1>

    <h3>
        Postingan:
    </h3>

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

        <div>

            <label for="content">
                Komentar
            </label>

            <br>

            <textarea
                id="content"
                name="content"
                rows="6"
                cols="50"
                placeholder="Tulis komentar..."
                required
            ></textarea>

        </div>

        <br>

        <button type="submit">
            Kirim Komentar
        </button>

    </form>

    <br>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>

</html>