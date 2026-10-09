<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Admin Login - Node</title>

</head>

<body>

    <h1>Node Admin</h1>

    <h2>Login Admin</h2>

    <form action="login_process.php" method="POST">

        <label for="username">
            Username
        </label>

        <br>

        <input
            type="text"
            id="username"
            name="username"
            required
        >

        <br><br>

        <label for="password">
            Password
        </label>

        <br>

        <input
            type="password"
            id="password"
            name="password"
            required
        >

        <br><br>

        <button type="submit">
            Login
        </button>

    </form>

</body>

</html>