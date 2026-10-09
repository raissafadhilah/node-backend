<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];

$postId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$postId) {
    die('Post ID tidak valid.');
}

$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status,
        bookmarks.id AS bookmark_id
     FROM posts
     INNER JOIN users
        ON posts.user_id = users.id
     LEFT JOIN bookmarks
        ON bookmarks.post_id = posts.id
        AND bookmarks.user_id = :user_id
     WHERE posts.id = :post_id
     AND posts.status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId,
    ':post_id' => $postId
]);

$post = $stmt->fetch();

if (!$post) {
    die('Postingan tidak ditemukan atau sudah tidak tersedia.');
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

    <title>Post - Node</title>

</head>

<body>

<h1>Node</h1>

<a href="../public/index.php">
    ← Kembali ke Home
</a>

<hr>

<article>

    <!-- IDENTITAS USER -->

    <h3>
        <?= htmlspecialchars($post['full_name']) ?>
    </h3>

    <small>
        <?= htmlspecialchars(ucfirst($post['status'])) ?>
    </small>

    <br><br>

    <!-- ISI POST -->

    <p>
        <?= nl2br(htmlspecialchars($post['content'])) ?>
    </p>


    <!-- MEDIA -->

    <?php

    $stmtMedia = $pdo->prepare(
        "SELECT
            id,
            file_path,
            file_type
         FROM post_media
         WHERE post_id = :post_id
         ORDER BY id ASC"
    );

    $stmtMedia->execute([
        ':post_id' => $post['id']
    ]);

    $mediaFiles = $stmtMedia->fetchAll();

    ?>

    <?php if (!empty($mediaFiles)): ?>

        <?php foreach ($mediaFiles as $media): ?>

            <?php if (strpos($media['file_type'], 'image/') === 0): ?>

                <div>

                    <img
                        src="../<?= htmlspecialchars($media['file_path']) ?>"
                        alt="Media postingan"
                        style="max-width: 500px; height: auto;"
                    >

                </div>

            <?php elseif (strpos($media['file_type'], 'video/') === 0): ?>

                <div>

                    <video
                        controls
                        preload="metadata"
                        style="max-width: 500px; height: auto;"
                    >

                        <source
                            src="../<?= htmlspecialchars($media['file_path']) ?>"
                            type="<?= htmlspecialchars($media['file_type']) ?>"
                        >

                        Browser kamu tidak mendukung pemutaran video.

                    </video>

                </div>

            <?php endif; ?>

            <br>

        <?php endforeach; ?>

    <?php endif; ?>


    <small>
        <?= htmlspecialchars($post['created_at']) ?>
    </small>

    <br><br>


    <!-- REACTION -->

    <?php

    $stmtReaction = $pdo->prepare(
        "SELECT COUNT(*) AS total
         FROM reactions
         WHERE post_id = :post_id"
    );

    $stmtReaction->execute([
        ':post_id' => $post['id']
    ]);

    $reactionCount = $stmtReaction->fetch();


    $stmtUserReaction = $pdo->prepare(
        "SELECT id
         FROM reactions
         WHERE post_id = :post_id
         AND user_id = :user_id
         LIMIT 1"
    );

    $stmtUserReaction->execute([
        ':post_id' => $post['id'],
        ':user_id' => $userId
    ]);

    $userReaction = $stmtUserReaction->fetch();

    ?>

    <form
        action="../reactions/toggle.php"
        method="POST"
    >

        <input
            type="hidden"
            name="post_id"
            value="<?= htmlspecialchars($post['id']) ?>"
        >

        <button type="submit">

            <?php if ($userReaction): ?>

                ❤️

            <?php else: ?>

                🤍

            <?php endif; ?>

            <?= htmlspecialchars($reactionCount['total']) ?>

        </button>

    </form>


    <br>


    <!-- SAVE -->

    <form
        action="save_process.php"
        method="POST"
    >

        <input
            type="hidden"
            name="post_id"
            value="<?= htmlspecialchars($post['id']) ?>"
        >

        <button type="submit">

            <?php if ($post['bookmark_id']): ?>

                🔖 Unsave

            <?php else: ?>

                🔖 Save

            <?php endif; ?>

        </button>

    </form>


    <br>


    <!-- EDIT / DELETE POST -->

    <?php if ($post['user_id'] == $userId): ?>

        <a
            href="edit.php?id=<?= htmlspecialchars($post['id']) ?>"
        >
            Edit Postingan
        </a>

        <br><br>

        <form
            action="delete.php"
            method="POST"
        >

            <input
                type="hidden"
                name="post_id"
                value="<?= htmlspecialchars($post['id']) ?>"
            >

            <button type="submit">
                Hapus Postingan
            </button>

        </form>

        <br>

    <?php endif; ?>


    <!-- REPORT -->

    <a
        href="../reports/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
    >
        Report Postingan
    </a>


    <br><br>


    <!-- KOMENTAR -->

    <h4>
        Komentar
    </h4>


    <?php

    $stmtComment = $pdo->prepare(
        "SELECT
            comments.id,
            comments.user_id,
            comments.content,
            comments.created_at,
            users.full_name,
            users.status
         FROM comments
         INNER JOIN users
            ON comments.user_id = users.id
         WHERE comments.post_id = :post_id
         AND comments.status = 'active'
         ORDER BY comments.created_at ASC"
    );

    $stmtComment->execute([
        ':post_id' => $post['id']
    ]);

    $comments = $stmtComment->fetchAll();

    ?>


    <?php if (empty($comments)): ?>

        <p>
            Belum ada komentar.
        </p>

    <?php else: ?>

        <?php foreach ($comments as $comment): ?>

            <div>

                <strong>
                    <?= htmlspecialchars($comment['full_name']) ?>
                </strong>

                <small>
                    (<?= htmlspecialchars(ucfirst($comment['status'])) ?>)
                </small>

                <p>
                    <?= nl2br(htmlspecialchars($comment['content'])) ?>
                </p>

                <small>
                    <?= htmlspecialchars($comment['created_at']) ?>
                </small>


                <?php if ($comment['user_id'] == $userId): ?>

                    <br><br>

                    <a
                        href="../comments/edit.php?id=<?= htmlspecialchars($comment['id']) ?>"
                    >
                        Edit Komentar
                    </a>

                    <br><br>

                    <form
                        action="../comments/delete.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="comment_id"
                            value="<?= htmlspecialchars($comment['id']) ?>"
                        >

                        <button type="submit">
                            Hapus Komentar
                        </button>

                    </form>


                <?php elseif ($post['user_id'] == $userId): ?>

                    <br><br>

                    <form
                        action="../comments/delete.php"
                        method="POST"
                    >

                        <input
                            type="hidden"
                            name="comment_id"
                            value="<?= htmlspecialchars($comment['id']) ?>"
                        >

                        <button type="submit">
                            Hapus Komentar
                        </button>

                    </form>

                <?php endif; ?>

            </div>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>


    <br>

    <a
        href="../comments/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
    >
        + Tambah Komentar
    </a>


</article>

<hr>

<a href="../public/index.php">
    Kembali ke Home
</a>

</body>

</html>