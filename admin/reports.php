<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

// Ambil semua data report postingan atau komentar dari database

$stmt = $pdo->prepare(
    "SELECT
        reports.id,
        reports.reporter_id,
        reports.post_id,
        reports.comment_id,
        reports.reason,
        reports.status,
        reports.created_at,
        users.username AS reporter_username,
        posts.content AS post_content,
        comments.content AS comment_content
     FROM reports
     INNER JOIN users
        ON users.id = reports.reporter_id
     LEFT JOIN posts
        ON posts.id = reports.post_id
     LEFT JOIN comments
        ON comments.id = reports.comment_id
     ORDER BY reports.created_at DESC"
);

$stmt->execute();

$reports = $stmt->fetchAll();

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

    <title>Reports - Node Admin</title>

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


        <!-- Sidebar Bottom -->

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
         MAIN CONTENT
         ========================= -->

    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <div class="admin-heading">

                <h2>Reports</h2>

                <p>
                    Tinjau laporan yang masuk ke sistem Node.
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



        <!-- REPORT DATA -->

        <section class="admin-section">

            <div class="admin-section-title">

                <h3>
                    Daftar Report
                </h3>

            </div>


            <?php if (empty($reports)): ?>

                <div class="admin-empty">

                    <h3>
                        Belum ada report
                    </h3>

                    <p>
                        Belum ada laporan yang masuk
                        ke sistem Node.
                    </p>

                </div>

            <?php else: ?>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>
                                    Report ID
                                </th>

                                <th>
                                    Reporter
                                </th>

                                <th>
                                    Tipe
                                </th>

                                <th>
                                    Konten
                                </th>

                                <th>
                                    Alasan
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Dibuat
                                </th>

                                <th>
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($reports as $report): ?>

                                <tr>


                                    <!-- REPORT ID -->

                                    <td>

                                        <span class="user-id">

                                            #<?= htmlspecialchars(
                                                $report['id']
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- REPORTER -->

                                    <td>

                                        <span class="user-name">

                                            <?= htmlspecialchars(
                                                $report['reporter_username']
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- TYPE -->

                                    <td>

                                        <?php if ($report['post_id'] !== null): ?>

                                            <span class="report-type">
                                                Postingan
                                            </span>

                                        <?php elseif ($report['comment_id'] !== null): ?>

                                            <span class="report-type">
                                                Komentar
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- CONTENT -->

                                    <td>

                                        <?php if ($report['post_id'] !== null): ?>

                                            <div class="report-content">

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $report['post_content']
                                                    )
                                                ) ?>

                                            </div>

                                        <?php elseif ($report['comment_id'] !== null): ?>

                                            <div class="report-content">

                                                <?= nl2br(
                                                    htmlspecialchars(
                                                        $report['comment_content']
                                                    )
                                                ) ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>



                                    <!-- REASON -->

                                    <td>

                                        <div class="report-reason">

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $report['reason']
                                                )
                                            ) ?>

                                        </div>

                                    </td>



                                    <!-- STATUS -->

                                    <td>

                                        <?php if ($report['status'] === 'pending'): ?>

                                            <span class="report-status pending">
                                                Pending
                                            </span>

                                        <?php elseif ($report['status'] === 'valid'): ?>

                                            <span class="report-status valid">
                                                Valid
                                            </span>

                                        <?php elseif ($report['status'] === 'invalid'): ?>

                                            <span class="report-status invalid">
                                                Invalid
                                            </span>

                                        <?php endif; ?>

                                    </td>



                                    <!-- DATE -->

                                    <td>

                                        <span class="user-email">

                                            <?= htmlspecialchars(
                                                $report['created_at']
                                            ) ?>

                                        </span>

                                    </td>



                                    <!-- DETAIL -->

                                    <td>

                                        <a
                                            href="report_detail.php?id=<?= htmlspecialchars($report['id']) ?>"
                                            class="report-detail"
                                        >
                                            Detail
                                        </a>

                                    </td>


                                </tr>

                            <?php endforeach; ?>

                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


            <a
                href="dashboard.php"
                class="admin-back"
            >
                ← Kembali ke Dashboard
            </a>

        </section>


    </main>

</div>

</body>

</html>