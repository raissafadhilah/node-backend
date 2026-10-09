<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];

// Ambil postingan yang disimpan oleh user
$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status
     FROM bookmarks
     INNER JOIN posts
        ON bookmarks.post_id = posts.id
     INNER JOIN users
        ON posts.user_id = users.id
     WHERE bookmarks.user_id = :user_id
     AND posts.status = 'active'
     ORDER BY bookmarks.created_at DESC"
);

$stmt->execute([
    ':user_id' => $userId
]);

$savedPosts = $stmt->fetchAll();

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
        Saved Posts - Node
    </title>

</head>

<!-- SHARE MODAL -->

<div
    id="shareModal"
    style="
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        align-items: center;
        justify-content: center;
        z-index: 9999;
    "
>

    <div
        style="
            background: white;
            padding: 24px;
            border-radius: 12px;
            width: 90%;
            max-width: 500px;
        "
    >

        <h3>
            🔗 Share Post
        </h3>

        <p>
            Bagikan link postingan ini:
        </p>

        <input
            type="text"
            id="shareLink"
            readonly
            style="
                width: 100%;
                box-sizing: border-box;
                padding: 10px;
            "
        >

        <br><br>

        <button
            type="button"
            onclick="copyShareLink()"
        >
            📋 Copy Link
        </button>

        <button
            type="button"
            onclick="closeShareModal()"
        >
            Tutup
        </button>

        <p
            id="copyMessage"
            style="display: none;"
        >
            ✅ Link berhasil disalin!
        </p>

    </div>

</div>

<script>

function openShareModal(postId) {

    const shareModal =
        document.getElementById('shareModal');

    const shareLink =
        document.getElementById('shareLink');

    const copyMessage =
        document.getElementById('copyMessage');

    const url =
        window.location.origin +
        '/node/posts/view.php?id=' +
        postId;

    shareLink.value = url;

    copyMessage.style.display = 'none';

    shareModal.style.display = 'flex';
}

function closeShareModal() {

    const shareModal =
        document.getElementById('shareModal');

    shareModal.style.display = 'none';
}

function copyShareLink() {

    const shareLink =
        document.getElementById('shareLink');

    const copyMessage =
        document.getElementById('copyMessage');

    navigator.clipboard.writeText(shareLink.value)
        .then(function () {

            copyMessage.style.display = 'block';

        })
        .catch(function () {

            shareLink.select();

            document.execCommand('copy');

            copyMessage.style.display = 'block';

        });
}

</script>

<body>

    <h1>Saved Posts</h1>

    <hr>

    <?php if (empty($savedPosts)): ?>

        <p>
            Belum ada postingan yang disimpan.
        </p>

    <?php else: ?>

        <?php foreach ($savedPosts as $post): ?>

            <article>

                <h3>
                    <?= htmlspecialchars($post['full_name']) ?>
                </h3>

                <small>
                    <?= htmlspecialchars(ucfirst($post['status'])) ?>
                </small>

                <p>
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>

                <?php

                // Ambil media postingan
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

                    $userReaction = false;

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

                    <form action="../reactions/toggle.php" method="POST">

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

                
                <br>

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

                <br><br>

                <br>

                <br>

                <!-- SHARE POSTINGAN -->

                <button
                    type="button"
                    onclick="openShareModal(<?= htmlspecialchars($post['id']) ?>)"
                >
                    🔗 Share
                </button>



                <br>
                <br>
                <!-- Unsave -->

                <form
                    action="../posts/save_process.php"
                    method="POST"
                >

                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= htmlspecialchars($post['id']) ?>"
                    >

                    <button type="submit">
                        🔖 Unsave
                    </button>

                </form>

            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>

    <a href="index.php">
        Kembali ke Profile
    </a>

    <br>
    <br>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>

</html>