<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit;
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

    <title>Create Post - Node</title>
</head>

<body>

    <h1>Buat Postingan</h1>

    <form
        action="create_process.php"
        method="POST"
        enctype="multipart/form-data"
    >

        <!-- CAPTION -->
        <div>

            <label for="caption">
                Caption
            </label>

            <br>

            <input
                type="text"
                id="caption"
                name="caption"
                maxlength="255"
                placeholder="Tulis caption..."
                required
            >

        </div>

        <br>

        <!-- CONTENT -->
        <div>

            <label for="content">
                Kalimat Tambahan
            </label>

            <br>

            <textarea
                id="content"
                name="content"
                rows="8"
                cols="50"
                placeholder="Tulis sesuatu..."
                required
            ></textarea>

        </div>

        <br>

        <!-- HASHTAG -->
        <div>

            <label for="hashtags">
                Hashtag
            </label>

            <br>

            <input
                type="text"
                id="hashtags"
                name="hashtags"
                maxlength="500"
                placeholder="#PHP #Programming #Kampus"
            >

            <br>

            <small>
                Pisahkan hashtag dengan spasi.
                Contoh: #PHP #Programming #Kampus
            </small>

        </div>

        <br>

        <!-- MEDIA -->
        <div>

            <input
                type="file"
                id="media"
                name="media"
                accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/ogg"
            >

            <br>
            <br>

            <small>
                Format yang didukung:
                JPG, PNG, WEBP, MP4, WEBM, OGG.
                Maksimal 5 MB.
            </small>

        </div>

        <br>

        <button type="submit">
            Posting
        </button>

    </form>

    <br>

    <a href="../public/index.php">
        Kembali ke Home
    </a>

</body>
</html>