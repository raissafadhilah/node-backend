<?php

require_once '../config/database.php';
require_once '../includes/auth.php';


$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;


$tag = trim($_GET['tag'] ?? '');

if ($tag === '') {
    die('Hashtag tidak ditemukan.');
}

$tag = ltrim($tag, '#');

$stmt = $pdo->prepare(
    "SELECT
        id,
        name
    FROM hashtags
    WHERE name = :name
    LIMIT 1"
);

$stmt->execute([
    ':name' => strtolower($tag)
]);

$hashtag = $stmt->fetch();

if (!$hashtag) {
    die('Hashtag tidak ditemukan.');
}


$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status,

        post_media.file_name,
        post_media.file_path,
        post_media.file_type,
        (
            SELECT COUNT(*)
            FROM reactions
            WHERE reactions.post_id = posts.id
        ) AS reaction_count,

        (
            SELECT id
            FROM reactions
            WHERE reactions.post_id = posts.id
            AND reactions.user_id = :reaction_user_id
            LIMIT 1
        ) AS user_reaction_id

    FROM post_hashtags

    INNER JOIN posts
        ON post_hashtags.post_id = posts.id

    INNER JOIN users
        ON posts.user_id = users.id

    LEFT JOIN post_media
        ON post_media.post_id = posts.id

    WHERE post_hashtags.hashtag_id = :hashtag_id
    AND posts.status = 'active'

    ORDER BY posts.created_at DESC"
);

$stmt->execute([
    ':reaction_user_id' => $_SESSION['user_id'],
    ':hashtag_id' => $hashtag['id']
]);

$posts = $stmt->fetchAll();

$commentsByPost = [];

if (!empty($posts)) {

    $postIds = array_column($posts, 'id');

    $placeholders = implode(
        ',',
        array_fill(0, count($postIds), '?')
    );

    $stmt = $pdo->prepare(
        "SELECT
            comments.id,
            comments.post_id,
            comments.user_id,
            comments.content,
            comments.created_at,
            users.full_name,
            users.status
        FROM comments

        INNER JOIN users
            ON comments.user_id = users.id

        WHERE comments.post_id IN ($placeholders)
        AND comments.status = 'active'

        ORDER BY comments.created_at ASC"
    );

    $stmt->execute($postIds);

    $comments = $stmt->fetchAll();

    foreach ($comments as $comment) {

        $commentsByPost[$comment['post_id']][] = $comment;

    }
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

    <title>
        #<?= htmlspecialchars($hashtag['name']) ?> - Node
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

    <h1>
        #<?= htmlspecialchars($hashtag['name']) ?>
    </h1>

    <p>
        Semua postingan dengan hashtag ini
    </p>

    <hr>

    <?php if (empty($posts)): ?>

        <p>
            Belum ada postingan dengan hashtag ini.
        </p>

    <?php else: ?>

        <?php foreach ($posts as $post): ?>

            <article>

                <!-- USER -->
                <strong>
                    <?= htmlspecialchars($post['full_name']) ?>
                </strong>

                <small>
                    (<?= htmlspecialchars($post['status']) ?>)
                </small>

                <br>
                <br>


                <!-- CAPTION -->
                <h2>
                    <?= htmlspecialchars($post['caption']) ?>
                </h2>


                <!-- CONTENT -->
                <p>
                    <?= nl2br(
                        htmlspecialchars($post['content'])
                    ) ?>
                </p>

                <!-- MEDIA -->

                <?php if (!empty($post['file_path'])): ?>

                    <?php if (strpos($post['file_type'], 'image/') === 0): ?>

                        <div>
                            <img
                                src="../<?= htmlspecialchars($post['file_path']) ?>"
                                alt="Post media"
                                style="max-width: 100%;"
                            >
                        </div>

                    <?php elseif (strpos($post['file_type'], 'video/') === 0): ?>

                        <div>

                            <video
                                controls
                                style="max-width: 100%;"
                            >

                                <source
                                    src="../<?= htmlspecialchars($post['file_path']) ?>"
                                    type="<?= htmlspecialchars($post['file_type']) ?>"
                                >

                                Browser kamu tidak mendukung video.

                            </video>

                        </div>

                    <?php endif; ?>

                <?php endif; ?>


                <!-- REACTION -->

                <form action="../reactions/toggle.php" method="POST">

                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= htmlspecialchars($post['id']) ?>"
                    >

                    <button type="submit">

                        <?php if ($post['user_reaction_id']): ?>

                            ❤️ Unlike

                        <?php else: ?>

                            🤍 Like

                        <?php endif; ?>

                    </button>

                </form>

                <p>
                    ❤️ <?= htmlspecialchars($post['reaction_count']) ?>
                </p>
                


                <!-- SHARE POSTINGAN -->
                <button
                    type="button"
                    onclick="openShareModal(<?= htmlspecialchars($post['id']) ?>)"
                >
                    🔗 Share
                </button>

                <br><br>

                    <!-- COMMENTS -->
                <div>

                    <h4>
                        💬 Komentar
                    </h4>

                    <?php if (!empty($commentsByPost[$post['id']])): ?>

                        <?php foreach ($commentsByPost[$post['id']] as $comment): ?>

                            <div>

                                <strong>
                                    <?= htmlspecialchars($comment['full_name']) ?>
                                </strong>

                                <small>
                                    (<?= htmlspecialchars($comment['status']) ?>)
                                </small>

                                <p>
                                    <?= nl2br(
                                        htmlspecialchars($comment['content'])
                                    ) ?>
                                </p>

                                <small>
                                    <?= htmlspecialchars($comment['created_at']) ?>
                                </small>

                                <?php if ($isLoggedIn && $comment['user_id'] == $userId): ?>

                                    <br><br>

                                    <!-- Edit Komentar -->
                                    <a
                                        href="../comments/edit.php?id=<?= htmlspecialchars($comment['id']) ?>"
                                    >
                                        Edit Komentar
                                    </a>

                                    <br><br>

                                    <!-- Hapus Komentar -->
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

                                <?php elseif ($isLoggedIn && $post['user_id'] == $userId): ?>

                                    <br><br>

                                    <!-- Pemilik Postingan boleh menghapus komentar -->
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

                    <?php else: ?>

                        <p>
                            Belum ada komentar.
                        </p>

                    <?php endif; ?>

                </div>

                <form
                    action="../comments/create.php"
                    method="GET"
                >
                    <input
                        type="hidden"
                        name="post_id"
                        value="<?= htmlspecialchars($post['id']) ?>"
                    >

                    <button type="submit">
                        💬 Tambah Komentar
                    </button>
                </form>


                <!-- POST DATE -->
                <small>
                    <?= htmlspecialchars($post['created_at']) ?>
                </small>

            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>


    <br>

    <a href="../public/index.php">
        ← Kembali ke Home
    </a>

</body>

</html>