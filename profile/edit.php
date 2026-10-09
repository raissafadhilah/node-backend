<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

$userId = $_SESSION['user_id'];

// Ambil data user yang sedang login
$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        email
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $userId
]);

$user = $stmt->fetch();

if (!$user) {
    die('Data user tidak ditemukan.');
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

    <title>Edit Profile - Node</title>

</head>

<body>

    <h1>Edit Profile</h1>

    <hr>

    <form action="edit_process.php" method="POST">

        <div>

            <label for="full_name">
                Full Name
            </label>

            <br>

            <input
                type="text"
                id="full_name"
                name="full_name"
                value="<?= htmlspecialchars($user['full_name']) ?>"
                maxlength="100"
                required
            >

        </div>

        <br>

        <div>

            <label for="status">
                Status
            </label>

            <br>

            <select
                id="status"
                name="status"
                required
            >

                <option value="">
                    -- Pilih Status --
                </option>

                <option
                    value="mahasiswa"
                    <?= $user['status'] === 'mahasiswa' ? 'selected' : '' ?>
                >
                    Mahasiswa
                </option>

                <option
                    value="alumni"
                    <?= $user['status'] === 'alumni' ? 'selected' : '' ?>
                >
                    Alumni
                </option>

            </select>

        </div>

        <br>

        <div>

            <label for="email">
                Email
            </label>

            <br>

            <input
                type="email"
                id="email"
                name="email"
                value="<?= htmlspecialchars($user['email']) ?>"
                maxlength="255"
                required
            >

        </div>

        <br>

        <button type="submit">
            Simpan Perubahan
        </button>

    </form>

    <br>

    <a href="index.php">
        Kembali ke Profile
    </a>

</body>

</html>