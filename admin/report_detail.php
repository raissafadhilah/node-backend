<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

$reportId = $_GET['id'] ?? '';

if (!filter_var($reportId, FILTER_VALIDATE_INT)) {
    die('ID report tidak valid.');
}

// Ambil report berdasarkan ID

$stmt = $pdo->prepare(
    "SELECT
        reports.id,
        reports.reporter_id,
        reports.post_id,
        reports.comment_id,
        reports.reason,
        reports.status,
        reports.reviewed_by,
        reports.reviewed_at,
        reports.created_at,

        reporter.username AS reporter_username,

        posts.content AS post_content,
        posts.user_id AS post_owner_id,
        post_owner.username AS post_owner_username,

        comments.content AS comment_content,
        comments.user_id AS comment_owner_id,
        comment_owner.username AS comment_owner_username

     FROM reports

     INNER JOIN users AS reporter
        ON reporter.id = reports.reporter_id

     LEFT JOIN posts
        ON posts.id = reports.post_id

     LEFT JOIN users AS post_owner
        ON post_owner.id = posts.user_id

     LEFT JOIN comments
        ON comments.id = reports.comment_id

     LEFT JOIN users AS comment_owner
        ON comment_owner.id = comments.user_id

     WHERE reports.id = :report_id

     LIMIT 1"
);

$stmt->execute([
    ':report_id' => $reportId
]);

$report = $stmt->fetch();

if (!$report) {
    die('Report tidak ditemukan.');
}

$adminUsername = $_SESSION['admin_username'] ?? 'Admin';

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
        Detail Report #<?= htmlspecialchars($report['id']) ?>
        - Node Admin
    </title>

    <link
        rel="stylesheet"
        href="../assets/css/admin.css"
    >

</head>

<body>

<div class="admin-layout">


    <!-- =========================
         SIDEBAR
         ========================= -->

    <aside class="admin-sidebar">

        <div>

            <div class="admin-brand">

                <h1>node</h1>

                <p>Admin Panel</p>

            </div>


            <nav class="admin-nav">

                <a href="dashboard.php">
                    Dashboard
                </a>

                <a href="users.php">
                    User Management
                </a>

                <a
                    href="reports.php"
                    class="active"
                >
                    Reports
                </a>

                <a href="moderation_history.php">
                    History
                </a>

                <a href="recap.php">
                    Rekap Data
                </a>

            </nav>

        </div>


        <div class="admin-sidebar-bottom">

            <form
                action="logout.php"
                method="POST"
            >

                <button
                    type="submit"
                    class="admin-logout"
                >
                    Logout Admin
                </button>

            </form>

        </div>

    </aside>



    <!-- =========================
         MAIN
         ========================= -->

    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <div class="admin-heading">

                <h2>
                    Detail Report #<?= htmlspecialchars($report['id']) ?>
                </h2>

                <p>
                    Tinjau detail laporan dan konten yang dilaporkan.
                </p>

            </div>


            <div class="admin-user">

                <div class="admin-avatar">

                    <?= strtoupper(
                        substr($adminUsername, 0, 1)
                    ) ?>

                </div>

                <div class="admin-user-info">

                    <strong>
                        <?= htmlspecialchars($adminUsername) ?>
                    </strong>

                    <span>
                        Administrator
                    </span>

                </div>

            </div>

        </header>



        <!-- =========================
             DETAIL LAYOUT
             ========================= -->

        <div class="report-detail-layout">


            <!-- =========================
                 LEFT CONTENT
                 ========================= -->

            <div>


                <!-- REPORT INFORMATION -->

                <section class="admin-panel">

                    <div class="admin-panel-title">

                        <h3>
                            Informasi Report
                        </h3>


                        <?php if ($report['status'] === 'pending'): ?>

                            <span class="report-detail-status pending">
                                Pending
                            </span>

                        <?php elseif ($report['status'] === 'valid'): ?>

                            <span class="report-detail-status valid">
                                Valid
                            </span>

                        <?php elseif ($report['status'] === 'invalid'): ?>

                            <span class="report-detail-status invalid">
                                Invalid
                            </span>

                        <?php endif; ?>

                    </div>


                    <div class="report-info-list">


                        <div class="report-info-item">

                            <span>
                                Report ID
                            </span>

                            <strong>
                                #<?= htmlspecialchars($report['id']) ?>
                            </strong>

                        </div>


                        <div class="report-info-item">

                            <span>
                                Reporter
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $report['reporter_username']
                                ) ?>
                            </strong>

                        </div>


                        <div class="report-info-item">

                            <span>
                                Tanggal Report
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $report['created_at']
                                ) ?>
                            </strong>

                        </div>


                        <div class="report-info-item">

                            <span>
                                Status
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    ucfirst($report['status'])
                                ) ?>
                            </strong>

                        </div>


                    </div>


                    <div style="margin-top: 18px;">

                        <div class="report-content-meta">

                            <div>

                                <span>
                                    Tipe Konten
                                </span>

                                <strong>

                                    <?php if ($report['post_id'] !== null): ?>

                                        Postingan

                                    <?php elseif ($report['comment_id'] !== null): ?>

                                        Komentar

                                    <?php endif; ?>

                                </strong>

                            </div>


                            <div>

                                <span>
                                    ID Konten
                                </span>

                                <strong>

                                    <?php if ($report['post_id'] !== null): ?>

                                        #<?= htmlspecialchars(
                                            $report['post_id']
                                        ) ?>

                                    <?php elseif ($report['comment_id'] !== null): ?>

                                        #<?= htmlspecialchars(
                                            $report['comment_id']
                                        ) ?>

                                    <?php endif; ?>

                                </strong>

                            </div>

                        </div>

                    </div>


                    <div>

                        <div
                            class="report-content-label"
                            style="margin-bottom: 10px;"
                        >
                            Alasan Report
                        </div>

                        <div class="report-reason-full">

                            <?= nl2br(
                                htmlspecialchars(
                                    $report['reason']
                                )
                            ) ?>

                        </div>

                    </div>

                </section>



                <!-- REPORTED CONTENT -->

                <section class="admin-panel">

                    <div class="admin-panel-title">

                        <h3>
                            Konten yang Dilaporkan
                        </h3>

                    </div>


                    <?php if ($report['post_id'] !== null): ?>


                        <div class="report-content-meta">

                            <div>

                                <span>
                                    Post ID
                                </span>

                                <strong>
                                    #<?= htmlspecialchars(
                                        $report['post_id']
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Pemilik Postingan
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $report['post_owner_username']
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    User ID Pemilik
                                </span>

                                <strong>
                                    #<?= htmlspecialchars(
                                        $report['post_owner_id']
                                    ) ?>
                                </strong>

                            </div>

                        </div>


                        <div class="reported-content">

                            <div class="reported-content-label">
                                Isi Postingan
                            </div>

                            <div class="reported-content-text">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $report['post_content']
                                    )
                                ) ?>

                            </div>

                        </div>


                    <?php elseif ($report['comment_id'] !== null): ?>


                        <div class="report-content-meta">

                            <div>

                                <span>
                                    Comment ID
                                </span>

                                <strong>
                                    #<?= htmlspecialchars(
                                        $report['comment_id']
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    Pemilik Komentar
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        $report['comment_owner_username']
                                    ) ?>
                                </strong>

                            </div>


                            <div>

                                <span>
                                    User ID Pemilik
                                </span>

                                <strong>
                                    #<?= htmlspecialchars(
                                        $report['comment_owner_id']
                                    ) ?>
                                </strong>

                            </div>

                        </div>


                        <div class="reported-content">

                            <div class="reported-content-label">
                                Isi Komentar
                            </div>

                            <div class="reported-content-text">

                                <?= nl2br(
                                    htmlspecialchars(
                                        $report['comment_content']
                                    )
                                ) ?>

                            </div>

                        </div>


                    <?php endif; ?>


                </section>


            </div>



            <!-- =========================
                 RIGHT ACTION PANEL
                 ========================= -->

            <aside>


                <?php if ($report['status'] === 'pending'): ?>

                    <section class="admin-panel report-action-panel">

                        <div class="admin-panel-title">

                            <h3>
                                Review Report
                            </h3>

                        </div>

                        <p class="report-action-description">
                            Tentukan apakah laporan ini valid
                            atau tidak valid.
                        </p>


                        <!-- VALID -->

                        <form
                            action="review_report.php"
                            method="POST"
                            class="report-action-form"
                        >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?= htmlspecialchars(
                                    $report['id']
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="status"
                                value="valid"
                            >

                            <button
                                type="submit"
                                class="report-action-button valid"
                            >
                                Tandai Valid
                            </button>

                        </form>


                        <!-- INVALID -->

                        <form
                            action="review_report.php"
                            method="POST"
                            class="report-action-form"
                        >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?= htmlspecialchars(
                                    $report['id']
                                ) ?>"
                            >

                            <input
                                type="hidden"
                                name="status"
                                value="invalid"
                            >

                            <button
                                type="submit"
                                class="report-action-button invalid"
                            >
                                Tandai Invalid
                            </button>

                        </form>

                    </section>


                <?php elseif (
                    $report['status'] === 'valid'
                    && $report['post_id'] !== null
                ): ?>


                    <section class="admin-panel report-action-panel">

                        <div class="admin-panel-title">

                            <h3>
                                Moderation Action
                            </h3>

                        </div>

                        <p class="report-action-description">
                            Report telah dinyatakan valid.
                            Admin dapat menghapus postingan
                            yang dilaporkan.
                        </p>


                        <form
                            action="remove_post.php"
                            method="POST"
                            class="report-action-form"
                        >

                            <input
                                type="hidden"
                                name="report_id"
                                value="<?= htmlspecialchars(
                                    $report['id']
                                ) ?>"
                            >

                            <button
                                type="submit"
                                class="report-action-button remove"
                            >
                                Hapus Postingan
                            </button>

                        </form>

                    </section>


                <?php elseif ($report['status'] === 'valid'): ?>


                    <section class="admin-panel report-action-panel">

                        <div class="admin-panel-title">

                            <h3>
                                Report Valid
                            </h3>

                        </div>

                        <p class="report-action-description">
                            Report telah dinyatakan valid.
                        </p>

                    </section>


                <?php endif; ?>


            </aside>


        </div>



        <!-- NAVIGATION -->

        <div class="report-navigation">

            <a href="reports.php">
                ← Kembali ke Reports
            </a>

            <a href="dashboard.php">
                Kembali ke Dashboard
            </a>

        </div>


    </main>

</div>

</body>

</html>