<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Change Password - Node</title>

</head>

<body>

    <h1>Change Password</h1>

    <hr>

    <form action="password_process.php" method="POST">

        <div>

            <label for="old_password">
                Password Lama
            </label>

            <br>

            <input
                type="password"
                id="old_password"
                name="old_password"
                required
            >

        </div>

        <br>

        <div>

            <label for="new_password">
                Password Baru
            </label>

            <br>

            <input
                type="password"
                id="new_password"
                name="new_password"
                required
            >

        </div>

        <br>

        <div>

            <label for="confirm_password">
                Konfirmasi Password Baru
            </label>

            <br>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >

        </div>

        <br>

        <button type="submit">
            Simpan Password
        </button>

    </form>

    <br>

    <a href="index.php">
        Kembali ke Profile
    </a>

</body>

</html>