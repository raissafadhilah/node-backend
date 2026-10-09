<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

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

    <title>Node Admin Dashboard</title>

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

                <a
                    href="dashboard.php"
                    class="active"
                >
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
         MAIN
         ========================= -->

    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <div class="admin-heading">

                <h2>Dashboard</h2>

                <p>
                    Kelola dan pantau aktivitas Node.
                </p>

            </div>


            <div class="admin-user">

                <div class="admin-avatar">
                    <?= strtoupper(substr($adminUsername, 0, 1)) ?>
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



        <!-- WELCOME -->

        <section class="admin-welcome">

            <h3>
                Selamat datang, <?= htmlspecialchars($adminUsername) ?>.
            </h3>

            <p>
                Kelola user, laporan, riwayat moderasi,
                dan rekap data Node dari satu tempat.
            </p>

        </section>



        <!-- MENU -->

        <section class="admin-section">

            <div class="admin-section-title">

                <h3>
                    Admin Management
                </h3>

            </div>


            <div class="admin-menu-grid">


                <!-- USER -->

                <a
                    href="users.php"
                    class="admin-menu-card"
                >

                    <div class="admin-card-icon">
                        U
                    </div>

                    <h4>
                        User Management
                    </h4>

                    <p>
                        Lihat dan pantau data user
                        yang terdaftar di Node.
                    </p>

                </a>



                <!-- REPORT -->

                <a
                    href="reports.php"
                    class="admin-menu-card"
                >

                    <div class="admin-card-icon">
                        R
                    </div>

                    <h4>
                        Reports
                    </h4>

                    <p>
                        Tinjau laporan dan lakukan
                        proses review terhadap konten.
                    </p>

                </a>



                <!-- HISTORY -->

                <a
                    href="moderation_history.php"
                    class="admin-menu-card"
                >

                    <div class="admin-card-icon">
                        H
                    </div>

                    <h4>
                        History
                    </h4>

                    <p>
                        Lihat riwayat aktivitas
                        dan tindakan moderasi Admin.
                    </p>

                </a>



                <!-- RECAP -->

                <a
                    href="recap.php"
                    class="admin-menu-card"
                >

                    <div class="admin-card-icon">
                        R
                    </div>

                    <h4>
                        Rekap Data
                    </h4>

                    <p>
                        Lihat ringkasan data Node
                        yang tersimpan di database.
                    </p>

                </a>


            </div>

        </section>


    </main>

</div>

</body>

</html>