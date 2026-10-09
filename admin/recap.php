<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users"
);

$totalUsers = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE is_verified = 1"
);

$verifiedUsers = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM users
     WHERE is_verified = 0"
);

$unverifiedUsers = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM posts"
);

$totalPosts = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM posts
     WHERE status = 'active'"
);

$activePosts = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM posts
     WHERE status = 'removed'"
);

$removedPosts = $stmt->fetch()['total'];


// =========================
// REKAP COMMENT
// =========================

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM comments"
);

$totalComments = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM comments
     WHERE status = 'active'"
);

$activeComments = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM comments
     WHERE status = 'removed'"
);

$removedComments = $stmt->fetch()['total'];


// =========================
// REKAP REACTION
// =========================

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reactions"
);

$totalReactions = $stmt->fetch()['total'];


// =========================
// REKAP REPORT
// =========================

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reports"
);

$totalReports = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reports
     WHERE status = 'pending'"
);

$pendingReports = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reports
     WHERE status = 'valid'"
);

$validReports = $stmt->fetch()['total'];


$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM reports
     WHERE status = 'invalid'"
);

$invalidReports = $stmt->fetch()['total'];


// =========================
// REKAP MODERATION
// =========================

$stmt = $pdo->query(
    "SELECT COUNT(*) AS total
     FROM moderation_actions"
);

$totalModerationActions = $stmt->fetch()['total'];


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
        Rekap Data - Node Admin
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

                <a href="moderation_history.php">
                    History
                </a>

                <a
                    href="recap.php"
                    class="active"
                >
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
                    Rekap Data
                </h2>

                <p>
                    Ringkasan data dan aktivitas Node.
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
             OVERVIEW
             ========================= -->

        <section class="admin-section">


            <div class="recap-section-header">

                <h3>
                    Overview
                </h3>

                <p>
                    Ringkasan jumlah data utama yang tersimpan di Node.
                </p>

            </div>


            <div class="recap-grid">


                <!-- USERS -->

                <div class="recap-card primary">

                    <div class="recap-card-label">
                        Total User
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalUsers) ?>
                    </div>

                    <div class="recap-card-description">
                        Seluruh user yang terdaftar.
                    </div>

                </div>


                <!-- POSTS -->

                <div class="recap-card primary">

                    <div class="recap-card-label">
                        Total Postingan
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalPosts) ?>
                    </div>

                    <div class="recap-card-description">
                        Seluruh postingan yang tersimpan.
                    </div>

                </div>


                <!-- COMMENTS -->

                <div class="recap-card primary">

                    <div class="recap-card-label">
                        Total Komentar
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalComments) ?>
                    </div>

                    <div class="recap-card-description">
                        Seluruh komentar yang tersimpan.
                    </div>

                </div>


                <!-- REPORTS -->

                <div class="recap-card primary">

                    <div class="recap-card-label">
                        Total Report
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalReports) ?>
                    </div>

                    <div class="recap-card-description">
                        Seluruh laporan yang masuk.
                    </div>

                </div>


            </div>


        </section>



        <!-- =========================
             USER
             ========================= -->

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    User
                </h3>

                <p>
                    Status verifikasi akun pengguna.
                </p>

            </div>


            <div class="recap-sub-grid">


                <div class="recap-card">

                    <div class="recap-card-label">
                        Total User
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalUsers) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Terverifikasi
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($verifiedUsers) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Belum Terverifikasi
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($unverifiedUsers) ?>
                    </div>

                </div>


            </div>


        </section>



        <!-- =========================
             POST
             ========================= -->

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    Postingan
                </h3>

                <p>
                    Status seluruh postingan di Node.
                </p>

            </div>


            <div class="recap-sub-grid">


                <div class="recap-card">

                    <div class="recap-card-label">
                        Total
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalPosts) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Aktif
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($activePosts) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Dihapus
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($removedPosts) ?>
                    </div>

                </div>


            </div>


        </section>



        <!-- =========================
             COMMENT
             ========================= -->

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    Komentar
                </h3>

                <p>
                    Status komentar yang tersimpan di Node.
                </p>

            </div>


            <div class="recap-sub-grid">


                <div class="recap-card">

                    <div class="recap-card-label">
                        Total
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalComments) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Aktif
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($activeComments) ?>
                    </div>

                </div>


                <div class="recap-card">

                    <div class="recap-card-label">
                        Dihapus
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($removedComments) ?>
                    </div>

                </div>


            </div>


        </section>



        <!-- =========================
             REACTION
             ========================= -->

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    Reaction
                </h3>

                <p>
                    Jumlah reaction yang tersimpan.
                </p>

            </div>


            <div class="recap-sub-grid">


                <div class="recap-card">

                    <div class="recap-card-label">
                        Total Reaction
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($totalReactions) ?>
                    </div>

                </div>


            </div>


        </section>



        <!-- =========================
             REPORT
             ========================= -->

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    Reports
                </h3>

                <p>
                    Status laporan yang masuk ke sistem.
                </p>

            </div>


            <div class="recap-report-grid">


                <div class="recap-report-card pending">

                    <div class="recap-card-label">
                        Pending
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($pendingReports) ?>
                    </div>

                </div>


                <div class="recap-report-card valid">

                    <div class="recap-card-label">
                        Valid
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($validReports) ?>
                    </div>

                </div>


                <div class="recap-report-card invalid">

                    <div class="recap-card-label">
                        Invalid
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars($invalidReports) ?>
                    </div>

                </div>


            </div>


        </section>

        <section class="recap-section">


            <div class="recap-section-header">

                <h3>
                    Moderation
                </h3>

                <p>
                    Jumlah aktivitas moderasi yang dilakukan Admin.
                </p>

            </div>


            <div class="recap-sub-grid">


                <div class="recap-card">

                    <div class="recap-card-label">
                        Total Aktivitas Moderasi
                    </div>

                    <div class="recap-card-value">
                        <?= htmlspecialchars(
                            $totalModerationActions
                        ) ?>
                    </div>

                </div>


            </div>


        </section>

        <div class="recap-navigation">

            <a href="dashboard.php">
                ← Kembali ke Dashboard
            </a>

            <a href="users.php">
                User Management
            </a>

            <a href="reports.php">
                Reports
            </a>

            <a href="moderation_history.php">
                History
            </a>

        </div>


    </main>

</div>

</body>

</html>