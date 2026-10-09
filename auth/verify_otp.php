<?php

require_once '../config/database.php';
require_once '../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $otp = trim($_POST['otp'] ?? '');

    if ($email === '' || $otp === '') {
        die('Email dan OTP wajib diisi.');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die('Format email tidak valid.');
    }

    if (!preg_match('/^\d{6}$/', $otp)) {
        die('OTP harus terdiri dari 6 digit.');
    }


    $stmt = $pdo->prepare(
        "SELECT
            ev.id AS verification_id,
            ev.user_id,
            ev.otp,
            ev.expires_at,
            ev.is_used
         FROM email_verifications ev
         INNER JOIN users u ON u.id = ev.user_id
         WHERE u.email = :email
         ORDER BY ev.id DESC
         LIMIT 1"
    );

    $stmt->execute([
        ':email' => $email
    ]);

    $verification = $stmt->fetch();

    if (!$verification) {
        die('Data verifikasi tidak ditemukan.');
    }


    if ($verification['is_used']) {
        die('OTP sudah digunakan.');
    }



    if ($verification['otp'] !== $otp) {
        die('OTP salah.');
    }

 

    if (strtotime($verification['expires_at']) < time()) {
        die('OTP sudah expired. Silakan minta OTP baru.');
    }

    $stmt = $pdo->prepare(
        "UPDATE users
         SET is_verified = 1
         WHERE id = :user_id"
    );

    $stmt->execute([
        ':user_id' => $verification['user_id']
    ]);


    $stmt = $pdo->prepare(
        "UPDATE email_verifications
         SET is_used = 1
         WHERE id = :verification_id"
    );

    $stmt->execute([
        ':verification_id' => $verification['verification_id']
    ]);

    echo "Email berhasil diverifikasi! Silakan login.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Verify OTP - Node</title>
</head>

<body>

    <h1>Verifikasi Email Node</h1>

    <form action="verify_otp.php" method="POST">

        <div>
            <label for="email">Email</label>

            <input
                type="email"
                id="email"
                name="email"
                required
            >
        </div>

        <br>

        <div>
            <label for="otp">OTP</label>

            <input
                type="text"
                id="otp"
                name="otp"
                maxlength="6"
                pattern="[0-9]{6}"
                inputmode="numeric"
                required
            >
        </div>

        <br>

        <button type="submit">
            Verify OTP
        </button>

    </form>

</body>

</html>