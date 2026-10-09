<?php

require_once '../config/database.php';
require_once '../includes/session.php';



    if (!isset($_SESSION['user_id'])) {

        header('Location: ../auth/login.php');
        exit;

    }


    $userId = $_SESSION['user_id'];

    $keyword = trim($_GET['q'] ?? '');

    $sort = $_GET['sort'] ?? 'newest';

    $posts = [];


    if (!in_array($sort, ['newest', 'oldest'], true)) {

        $sort = 'newest';

    }


    $orderBy = $sort === 'oldest'
        ? 'ASC'
        : 'DESC';

    if ($keyword !== '') {

        $search = '%' . $keyword . '%';

        $stmt = $pdo->prepare(
            "SELECT
                posts.id,
                posts.user_id,
                posts.content,
                posts.created_at,
                users.full_name,
                users.status
            FROM posts
            INNER JOIN users
                ON posts.user_id = users.id
            WHERE posts.status = 'active'
            AND posts.content LIKE :keyword
            ORDER BY posts.created_at $orderBy"
        );

        $stmt->execute([
            ':keyword' => $search
        ]);

        $posts = $stmt->fetchAll();

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

    <title>Node - Explore</title>

</head>

<body>

    <h1>Node</h1>

    <h2>Explore</h2>

    <p>
        Cari postingan berdasarkan isi postingan.
    </p>




   <form action="explore.php" method="GET">

    <input
        type="text"
        name="q"
        value="<?= htmlspecialchars($keyword) ?>"
        placeholder="Cari postingan..."
    >

    <select name="sort">

        <option
            value="newest"
            <?= $sort === 'newest' ? 'selected' : '' ?>
        >
            Terbaru
        </option>

        <option
            value="oldest"
            <?= $sort === 'oldest' ? 'selected' : '' ?>
        >
            Terlama
        </option>

    </select>

    <button type="submit">
        Cari
    </button>

</form>

    <hr>


    <?php if ($keyword === ''): ?>

        <p>
            Masukkan keyword untuk mencari postingan.
        </p>


    <?php elseif (empty($posts)): ?>

        <p>
            Tidak ada postingan yang ditemukan untuk:
            <strong><?= htmlspecialchars($keyword) ?></strong>
        </p>


    <?php else: ?>

        <p>
            Hasil pencarian untuk:
            <strong><?= htmlspecialchars($keyword) ?></strong>
        </p>


        <?php foreach ($posts as $post): ?>

            <article>

                <h3>
                    Anonymous
                </h3>



                <p>
                    <?= nl2br(htmlspecialchars($post['content'])) ?>
                </p>


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

                            <br>

                        <?php endif; ?>

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
                                Anonymous
                            </strong>

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


                <!--
                    Tambah komentar
                -->

                <br>

                <a
                    href="../comments/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
                >
                    + Tambah Komentar
                </a>
                
                <br><br>

                <a
                    href="../reports/create.php?post_id=<?= htmlspecialchars($post['id']) ?>"
                >
                    Report Postingan
                </a>

            </article>

            <hr>

        <?php endforeach; ?>

    <?php endif; ?>


    <br>


    <a href="index.php">
        Kembali ke Home
    </a>

</body>

</html>