<?php

require_once '../includes/session.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Forgot Password - Node</title>
</head>

<body>

    <h1>Forgot Password</h1>

    <p>
        Masukkan email yang terdaftar di akun Node.
    </p>

    <form action="forgot_password_process.php" method="POST">

        <div>
            <label for="email">
                Email
            </label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >
        </div>

        <br>

        <button type="submit">
            Kirim OTP
        </button>

    </form>

    <br>

    <a href="login.php">
        Kembali ke Login
    </a>

</body>

</html>