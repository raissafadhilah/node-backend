<?php

require_once '../includes/session.php';

if (
    !isset($_SESSION['password_reset_user_id'])
    || !isset($_SESSION['password_reset_verified'])
    || $_SESSION['password_reset_verified'] !== true
) {
    header('Location: forgot_password.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Reset Password - Node</title>
</head>

<body>

    <h1>Reset Password</h1>

    <p>
        Masukkan password baru untuk akun kamu.
    </p>

    <form action="reset_password_process.php" method="POST">

        <div>
            <label for="password">
                Password Baru
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <div>
            <label for="password_confirmation">
                Konfirmasi Password Baru
            </label>

            <input
                type="password"
                id="password_confirmation"
                name="password_confirmation"
                required
            >
        </div>

        <br>

        <button type="submit">
            Ubah Password
        </button>

    </form>

</body>

</html>