<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if (!isset($_SESSION['password_reset_user_id'])) {
    header('Location: forgot_password.php');
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verifikasi OTP - Node</title>
</head>

<body>

    <h1>Verifikasi OTP</h1>

    <p>
        Masukkan kode OTP yang telah dikirim ke email kamu.
    </p>

    <p>
        OTP berlaku selama 5 menit.
    </p>

    <form action="verify_reset_otp_process.php" method="POST">

        <div>
            <label for="otp">
                OTP
            </label>

            <input
                type="text"
                id="otp"
                name="otp"
                maxlength="6"
                minlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                required
            >
        </div>

        <br>

        <button type="submit">
            Verifikasi OTP
        </button>

    </form>

    <br>

    <a href="forgot_password.php">
        Kembali
    </a>

</body>

</html>