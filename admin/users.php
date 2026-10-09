<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

// Ambil data user dari database

$stmt = $pdo->prepare(
    "SELECT
        id,
        username,
        email,
        is_verified,
        created_at
     FROM users
     ORDER BY created_at DESC"
);

$stmt->execute();

$users = $stmt->fetchAll();

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

    <title>User Management - Node Admin</title>

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

                <a
                    href="users.php"
                    class="active"
                >
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
         MAIN CONTENT
         ========================= -->

    <main class="admin-main">


        <!-- TOPBAR -->

        <header class="admin-topbar">

            <div class="admin-heading">

                <h2>User Management</h2>

                <p>
                    Data pengguna yang terdaftar di Node.
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



        <!-- USER DATA -->

        <section class="admin-section">

            <div class="admin-section-title">

                <h3>
                    Daftar User
                </h3>

            </div>


            <?php if (empty($users)): ?>

                <div class="admin-empty">

                    <h3>
                        Belum ada user
                    </h3>

                    <p>
                        Belum ada pengguna yang terdaftar
                        di Node.
                    </p>

                </div>

            <?php else: ?>


                <div class="admin-table-wrapper">

                    <table class="admin-table">

                        <thead>

                            <tr>

                                <th>
                                    User ID
                                </th>

                                <th>
                                    Username
                                </th>

                                <th>
                                    Email
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Tanggal Daftar
                                </th>

                            </tr>

                        </thead>


                        <tbody>

                            <?php foreach ($users as $user): ?>

                                <tr>

                                    <td>

                                        <span class="user-id">

                                            #<?= htmlspecialchars(
                                                $user['id']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="user-name">

                                            <?= htmlspecialchars(
                                                $user['username']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <span class="user-email">

                                            <?= htmlspecialchars(
                                                $user['email']
                                            ) ?>

                                        </span>

                                    </td>


                                    <td>

                                        <?php if ($user['is_verified']): ?>

                                            <span class="status-badge verified">
                                                Terverifikasi
                                            </span>

                                        <?php else: ?>

                                            <span class="status-badge unverified">
                                                Belum Terverifikasi
                                            </span>

                                        <?php endif; ?>

                                    </td>


                                    <td>

                                        <?= htmlspecialchars(
                                            $user['created_at']
                                        ) ?>

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