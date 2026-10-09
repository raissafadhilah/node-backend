<?php

require_once '../includes/session.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Node</title>
</head>

<body>

    <h1>Login Node</h1>

    <form action="login_process.php" method="POST">

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

        <div>
            <label for="password">
                Password
            </label>

            <input
                type="password"
                id="password"
                name="password"
                required
            >
        </div>

        <br>

        <button type="submit">
            Login
        </button>

    </form>

    <br>

    <a href="forgot_password.php">
        Forgot Password?
    </a>

</body>

</html>