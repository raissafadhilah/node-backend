<?php

require_once '../config/database.php';
require_once '../includes/session.php';

$isLoggedIn = isset($_SESSION['user_id']);

$fullName = $_SESSION['full_name'] ?? 'Guest';
$status = $_SESSION['status'] ?? null;
$userId = $_SESSION['user_id'] ?? null;

$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status,
        bookmarks.id AS bookmark_id,

        (
            SELECT GROUP_CONCAT(
                CONCAT('#', hashtags.name)
                ORDER BY hashtags.name
                SEPARATOR ' '
            )
            FROM post_hashtags
            INNER JOIN hashtags
                ON post_hashtags.hashtag_id = hashtags.id
            WHERE post_hashtags.post_id = posts.id
        ) AS hashtags

    FROM posts

    INNER JOIN users
        ON posts.user_id = users.id

    LEFT JOIN bookmarks
        ON bookmarks.post_id = posts.id
        AND bookmarks.user_id = :user_id

    WHERE posts.status = 'active'

    ORDER BY posts.created_at DESC"
);

$stmt->execute([
    ':user_id' => $userId
]);

$posts = $stmt->fetchAll();

$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,

        users.full_name,
        users.status,

        (
            SELECT COUNT(*)
            FROM reactions
            WHERE reactions.post_id = posts.id
        ) AS reaction_count,

        (
            SELECT COUNT(*)
            FROM comments
            WHERE comments.post_id = posts.id
            AND comments.status = 'active'
        ) AS comment_count,

        (
            (
                SELECT COUNT(*)
                FROM reactions
                WHERE reactions.post_id = posts.id
            )
            +
            (
                SELECT COUNT(*)
                FROM comments
                WHERE comments.post_id = posts.id
                AND comments.status = 'active'
            )
        ) AS trending_score

    FROM posts

    INNER JOIN users
        ON posts.user_id = users.id

    WHERE posts.status = 'active'

    AND posts.created_at >= NOW() - INTERVAL 7 DAY

    ORDER BY
        trending_score DESC,
        posts.created_at DESC

    LIMIT 3"
);

$stmt->execute();

$trendingPosts = $stmt->fetchAll();


$stmt = $pdo->prepare(
    "SELECT
        hashtags.name,
        COUNT(post_hashtags.post_id) AS post_count

    FROM hashtags

    INNER JOIN post_hashtags
        ON hashtags.id = post_hashtags.hashtag_id

    INNER JOIN posts
        ON post_hashtags.post_id = posts.id

    WHERE posts.status = 'active'

    AND hashtags.name NOT IN ('fict', 'fmb', 'fhs')

    GROUP BY
        hashtags.id,
        hashtags.name

    ORDER BY
        post_count DESC,
        hashtags.name ASC

    LIMIT 5"
);

$stmt->execute();

$trendingTopics = $stmt->fetchAll();

?>

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Node - Home</title>

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

    const shareModal = document.getElementById('shareModal');
    const shareLink = document.getElementById('shareLink');
    const copyMessage = document.getElementById('copyMessage');

    const url =
        window.location.origin +
        '/node/posts/view.php?id=' +
        postId;

    shareLink.value = url;

    copyMessage.style.display = 'none';

    shareModal.style.display = 'flex';
}

function closeShareModal() {

    const shareModal = document.getElementById('shareModal');

    shareModal.style.display = 'none';
}

function copyShareLink() {

    const shareLink = document.getElementById('shareLink');
    const copyMessage = document.getElementById('copyMessage');

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

    <h1>Node</h1>

    <p>
    Selamat datang,
    <strong><?= htmlspecialchars($fullName) ?></strong> 👋
    </p>

    <?php if ($isLoggedIn && $status !== null): ?>

    <p>
        Status:
        <strong><?= htmlspecialchars(ucfirst($status)) ?></strong>
    </p>

    <?php endif; ?>

    <hr>

        <!-- TRENDING DISCUSSIONS -->

        <h2>
            🔥 Trending Discussions
        </h2>

        <?php if (!empty($trendingPosts)): ?>

            <?php foreach ($trendingPosts as $trending): ?>

                <article>

                    <h3>
                        <?= htmlspecialchars($trending['full_name']) ?>
                    </h3>

                    <small>
                        <?= htmlspecialchars(ucfirst($trending['status'])) ?>
                    </small>

                    <h3>
                        <?= htmlspecialchars($trending['caption']) ?>
                    </h3>

                    <p>
                        <?= nl2br(
                            htmlspecialchars($trending['content'])
                        ) ?>
                    </p>

                    <small>
                        ❤️ <?= htmlspecialchars($trending['reaction_count']) ?>

                        &nbsp;&nbsp;

                        💬 <?= htmlspecialchars($trending['comment_count']) ?>
                    </small>

                </article>

                <hr>

            <?php endforeach; ?>

        <?php else: ?>

            <p>
                Belum ada trending discussions.
            </p>

        <?php endif; ?>


        <!-- TRENDING TOPICS -->
        <h2>🔥 Trending Topics</h2>

        <!-- FIXED FACULTY TOPICS -->
        <p>
            <a href="../hashtag/view.php?tag=fict">#FICT</a>
        </p>

        <p>
            <a href="../hashtag/view.php?tag=fmb">#FMB</a>
        </p>

        <p>
            <a href="../hashtag/view.php?tag=fhs">#FHS</a>
        </p>

        <!-- DYNAMIC TOPICS -->
        <?php if (!empty($trendingTopics)): ?>

            <?php foreach ($trendingTopics as $topic): ?>

                <p>
                    <a href="../hashtag/view.php?tag=<?= urlencode($topic['name']) ?>">
                        #<?= htmlspecialchars($topic['name']) ?>
                    </a>
                </p>
            <?php endforeach; ?>

        <?php else: ?>

            <p>Belum ada topic lainnya.</p>

        <?php endif; ?>


        <!-- FEED UTAMA -->

        <h2>
            Feed
        </h2>
    <?php if ($isLoggedIn): ?>

        <a href="../profile/index.php">
            Profile
        </a>

    <?php endif; ?>

    <br><br>

    <a href="explore.php">
        Explore
    </a>

    <br><br>
    
    <a href="../posts/create.php">
        + Buat Postingan
    </a>

    <br><br>


    <?php if (empty($posts)): ?>

        <p>
            Belum ada postingan.
        </p>

    <?php else: ?>

        <?php foreach ($posts as $post): ?>

            <article>

                <h3>
                    <?= htmlspecialchars($post['full_name']) ?>
                </h3>

                <small>
                    <?= htmlspecialchars(ucfirst($post['status'])) ?>
                </small>

                <h3>
                    <?= htmlspecialchars($post['caption']) ?>
                </h3>

                <p>
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>

                <?php if (!empty($post['hashtags'])): ?>

                    <p>
                        <?php
                        $hashtags = preg_split('/\s+/', trim($post['hashtags']));

                        foreach ($hashtags as $hashtag):
                            $tagName = ltrim($hashtag, '#');
                        ?>

                            <a href="../hashtag/view.php?tag=<?= urlencode($tagName) ?>">
                                <?= htmlspecialchars($hashtag) ?>
                            </a>

                        <?php endforeach; ?>
                    </p>

                <?php endif; ?>

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

                    if ($isLoggedIn) {

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
                    }

                ?>

                <?php if ($isLoggedIn): ?>

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

                    <?php else: ?>

                        <p>
                            ❤️ <?= htmlspecialchars($reactionCount['total']) ?>
                            <a href="../auth/login.php">Login untuk memberi reaction</a>
                        </p>

                    <?php endif; ?>
                <br>

                    <!-- SHARE POSTINGAN -->

                    <button
                        type="button"
                        onclick="openShareModal(<?= htmlspecialchars($post['id']) ?>)"
                    >
                        🔗 Share
                    </button>


                <?php if ($isLoggedIn): ?>

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

                            <?php if ($post['bookmark_id']): ?>

                                🔖 Unsave

                            <?php else: ?>

                                🔖 Save

                            <?php endif; ?>

                        </button>

                    </form>

                

                <?php else: ?>

                    <p>
                        🔖
                        <a href="../auth/login.php">
                            Login untuk menyimpan postingan
                        </a>
                    </p>

                <?php endif; ?>


                <?php if ($isLoggedIn && $post['user_id'] == $userId): ?>

                    <!-- Edit Postingan -->

                    <a
                        href="../posts/edit.php?id=<?= htmlspecialchars($post['id']) ?>"
                    >
                        Edit Postingan
                    </a>

                    <br><br>


                    <!-- Delete Postingan -->

                    <form
                        action="../posts/delete.php"
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

                <?php endif; ?>


                <br>

               <?php if ($isLoggedIn && $post['user_id'] != $userId): ?>

                    <a
                        href="../reports/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
                    >
                        Report Postingan
                    </a>

                <?php elseif (!$isLoggedIn): ?>

                    <a
                        href="../auth/login.php"
                    >
                        Report Postingan
                    </a>

                <?php endif; ?>
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
                                    <?= htmlspecialchars(ucfirst($comment['status'])) ?>
                                </small>

                                    <p>
                                        <?= nl2br(htmlspecialchars($comment['content'])) ?>
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

                <?php endif; ?>


                <br>


                    <!-- Tambah Komentar -->

                    <?php if ($isLoggedIn): ?>

                        <!-- Tambah Komentar -->

                        <a
                            href="../comments/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
                        >
                            + Tambah Komentar
                        </a>

                    <?php else: ?>

                        <a href="../auth/login.php">
                            Login untuk menambahkan komentar
                        </a>

                    <?php endif; ?>


            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>


   <?php if ($isLoggedIn): ?>

        <!-- Logout -->

        <form
            action="../auth/logout.php"
            method="POST"
        >

            <button type="submit">
                Logout
            </button>

        </form>

    <?php endif; ?>

</body>

</html>