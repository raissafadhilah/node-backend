<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

$stmt = $pdo->prepare(
    "SELECT
        moderation_actions.id,
        moderation_actions.admin_id,
        moderation_actions.report_id,
        moderation_actions.user_id,
        moderation_actions.post_id,
        moderation_actions.comment_id,
        moderation_actions.action_type,
        moderation_actions.notes,
        moderation_actions.created_at,
        admins.username AS admin_username,
        users.full_name AS affected_username
     FROM moderation_actions
     INNER JOIN admins
        ON admins.id = moderation_actions.admin_id
     LEFT JOIN users
        ON users.id = moderation_actions.user_id
     ORDER BY moderation_actions.created_at DESC"
);


$stmt->execute();

$actions = $stmt->fetchAll();

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
        Moderation History - Node Admin
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

                <a href="reports.php">
                    Reports
                </a>

                <a
                    href="moderation_history.php"
                    class="active"
                >
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
                    Moderation History
                </h2>

                <p>
                    Riwayat tindakan moderasi yang dilakukan Admin.
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
             HISTORY
             ========================= -->

        <section class="admin-section">


            <div class="admin-section-title">

                <h3>
                    Riwayat Aktivitas
                </h3>

            </div>


            <?php if (empty($actions)): ?>


                <div class="admin-empty">

                    <h3>
                        Belum ada riwayat moderasi
                    </h3>

                    <p>
                        Belum ada tindakan moderasi
                        yang tercatat di Node.
                    </p>

                </div>


            <?php else: ?>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>Action ID</th>

                                <th>Admin</th>

                                <th>Action</th>

                                <th>User</th>

                                <th>User ID</th>

                                <th>Post ID</th>

                                <th>Comment ID</th>

                                <th>Report ID</th>

                                <th>Notes</th>

                                <th>Waktu</th>

                            </tr>

                        </thead>


                        <tbody>


                            <?php foreach ($actions as $action): ?>


                                <tr>


                                    <!-- ACTION ID -->

                                    <td>

                                        <span class="history-id">

                                            #<?= htmlspecialchars(
                                                $action['id']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ADMIN -->

                                    <td>

                                        <span class="history-user">

                                            <?= htmlspecialchars(
                                                $action['admin_username']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- ACTION -->

                                    <td>

                                        <span class="history-action">

                                            <?= htmlspecialchars(
                                                $action['action_type']
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- AFFECTED USER -->

                                    <td>

                                        <?php if (
                                            $action['affected_username'] !== null
                                        ): ?>

                                            <span class="history-user">

                                                <?= htmlspecialchars(
                                                    $action['affected_username']
                                                ) ?>

                                            </span>

                                        <?php else: ?>

                                            <span class="history-muted">
                                                User tidak tersedia
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <!-- USER ID -->

                                    <td>

                                        <span class="history-meta">

                                            <?= htmlspecialchars(
                                                $action['user_id'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- POST ID -->

                                    <td>

                                        <span class="history-meta">

                                            <?= htmlspecialchars(
                                                $action['post_id'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- COMMENT ID -->

                                    <td>

                                        <span class="history-meta">

                                            <?= htmlspecialchars(
                                                $action['comment_id'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- REPORT ID -->

                                    <td>

                                        <span class="history-meta">

                                            <?= htmlspecialchars(
                                                $action['report_id'] ?? '-'
                                            ) ?>

                                        </span>

                                    </td>


                                    <!-- NOTES -->

                                    <td>

                                        <div class="history-notes">

                                            <?= nl2br(
                                                htmlspecialchars(
                                                    $action['notes'] ?? '-'
                                                )
                                            ) ?>

                                        </div>

                                    </td>


                                    <!-- CREATED AT -->

                                    <td>

                                        <span class="history-meta">

                                            <?= htmlspecialchars(
                                                $action['created_at']
                                            ) ?>

                                        </span>

                                    </td>


                                </tr>


                            <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php endif; ?>


            <!-- NAVIGATION -->

            <div class="report-navigation">

                <a href="dashboard.php">
                    ← Kembali ke Dashboard
                </a>

                <a href="reports.php">
                    Kembali ke Reports
                </a>

            </div>


        </section>


    </main>

</div>

</body>

</html>