<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];

$commentId = $_GET['id'] ?? '';

if (!filter_var($commentId, FILTER_VALIDATE_INT)) {
    die('ID komentar tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT
        id,
        post_id,
        user_id,
        content
     FROM comments
     WHERE id = :comment_id
     AND user_id = :user_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':comment_id' => $commentId,
    ':user_id' => $userId
]);

$comment = $stmt->fetch();

if (!$comment) {
    die('Komentar tidak ditemukan atau bukan milik kamu.');
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

    <title>Edit Komentar - Node</title>

</head>

<body>

    <h1>Edit Komentar</h1>

    <form action="edit_process.php" method="POST">

        <input
            type="hidden"
            name="comment_id"
            value="<?= htmlspecialchars($comment['id']) ?>"
        >

        <textarea
            name="content"
            rows="6"
            cols="50"
            required
        ><?= htmlspecialchars($comment['content']) ?></textarea>

        <br>
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