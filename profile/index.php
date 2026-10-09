<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$currentUserId = $_SESSION['user_id'];

// Ambil user ID dari URL
$profileUserId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

// Kalau gada ID, tampilkan profile sendiri
if (!$profileUserId) {
    $profileUserId = $currentUserId;
}

// Ambil data user profile
$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $profileUserId
]);

$user = $stmt->fetch();

if (!$user) {
    die('Profile tidak ditemukan.');
}

// Ambil postingan milik user profile
$stmtPosts = $pdo->prepare(
    "SELECT
        id,
        content,
        created_at
     FROM posts
     WHERE user_id = :user_id
     AND status = 'active'
     ORDER BY created_at DESC"
);

$stmtPosts->execute([
    ':user_id' => $profileUserId
]);

$posts = $stmtPosts->fetchAll();

// Ambil balasan yang dibuat oleh user profile
$stmtReplies = $pdo->prepare(
    "SELECT
        comments.id,
        comments.post_id,
        comments.content,
        comments.created_at
     FROM comments
     WHERE comments.user_id = :user_id
     AND comments.status = 'active'
     ORDER BY comments.created_at DESC"
);

$stmtReplies->execute([
    ':user_id' => $profileUserId
]);

$replies = $stmtReplies->fetchAll();

$isOwnProfile = ($profileUserId === $currentUserId);

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>
        Profile - Node
    </title>

</head>

<body>

    <h1>Profile</h1>

    <hr>

    <h2>
        <?= htmlspecialchars($user['full_name']) ?>
    </h2>

    <p>
        <strong>Status:</strong>
        <?= htmlspecialchars(ucfirst($user['status'])) ?>
    </p>

    <hr>

    <h3>Postingan</h3>

    <?php if (empty($posts)): ?>

        <p>
            Belum ada postingan.
        </p>

    <?php else: ?>

        <?php foreach ($posts as $post): ?>

            <article>

                <p>
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>

                <small>
                    <?= htmlspecialchars($post['created_at']) ?>
                </small>

            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>

    <h3>Balasan</h3>

    <?php if (empty($replies)): ?>

        <p>
            Belum ada balasan.
        </p>

    <?php else: ?>

        <?php foreach ($replies as $reply): ?>

            <article>

                <p>
                    <?= nl2br(htmlspecialchars($reply['content'])) ?>
                </p>

                <small>
                    <?= htmlspecialchars($reply['created_at']) ?>
                </small>

            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>

    <?php if ($isOwnProfile): ?>

        <h3>Settings</h3>

        <p>
            <a href="edit.php">
                Edit Profile
            </a>
        </p>

        <p>
            <a href="password.php">
                Change Password
            </a>
        </p>

        <hr>

    <?php endif; ?>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>

</html>