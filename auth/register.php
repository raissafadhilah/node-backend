<?php

require_once '../includes/session.php';

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Register</title>

</head>

<body>

    <h1>Register Node</h1>

    <form action="register_process.php" method="POST">

        <!-- Full Name -->
        <div>

            <label for="full_name">
                Full Name
            </label>

            <input
                type="text"
                id="full_name"
                name="full_name"
                required
            >

        </div>

        <br>

        <!-- Status -->
        <div>

            <label for="status">
                Status
            </label>

            <select
                id="status"
                name="status"
                required
            >

                <option value="">
                    -- Pilih Status --
                </option>

                <option value="mahasiswa">
                    Mahasiswa
                </option>

                <option value="alumni">
                    Alumni
                </option>

            </select>

        </div>

        <br>

        <!-- Email -->
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

        <!-- Password -->
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

        <!-- Confirm Password -->
        <div>

            <label for="confirm_password">
                Confirm Password
            </label>

            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
            >

        </div>

        <br>

        <button type="submit">
            Register
        </button>

    </form>

</body>

</html>