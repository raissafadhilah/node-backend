<?php

require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: create.php');
    exit;
}

$userId = $_SESSION['user_id'];

$caption = trim($_POST['caption'] ?? '');
$content = trim($_POST['content'] ?? '');
$hashtagsInput = trim($_POST['hashtags'] ?? '');

if ($caption === '') {
    die('Caption wajib diisi.');
}

if ($content === '') {
    die('Kalimat tambahan wajib diisi.');
}

$uploadedFilePath = null;

try {

    // Mulai transaction
    $pdo->beginTransaction();

    $stmt = $pdo->prepare(
        "INSERT INTO posts (
            user_id,
            caption,
            content,
            status
        ) VALUES (
            :user_id,
            :caption,
            :content,
            :status
        )"
    );

    $stmt->execute([
        ':user_id' => $userId,
        ':caption' => $caption,
        ':content' => $content,
        ':status' => 'active'
    ]);

    $postId = $pdo->lastInsertId();

    if ($hashtagsInput !== '') {

        $hashtags = preg_split('/\s+/', $hashtagsInput);

        $processedHashtags = [];

        foreach ($hashtags as $hashtag) {

            $hashtag = trim($hashtag);

            if ($hashtag === '') {
                continue;
            }

            if ($hashtag[0] !== '#') {
                $hashtag = '#' . $hashtag;
            }
            $hashtagName = substr($hashtag, 1);

            if ($hashtagName === '') {
                continue;
            }
            $hashtagName = strtolower($hashtagName);
            if (isset($processedHashtags[$hashtagName])) {
                continue;
            }

            $processedHashtags[$hashtagName] = true;


            $stmt = $pdo->prepare(
                "SELECT id
                 FROM hashtags
                 WHERE name = :name
                 LIMIT 1"
            );

            $stmt->execute([
                ':name' => $hashtagName
            ]);

            $existingHashtag = $stmt->fetch();
            if ($existingHashtag) {

                $hashtagId = $existingHashtag['id'];

            } else {

                $stmt = $pdo->prepare(
                    "INSERT INTO hashtags (
                        name
                    ) VALUES (
                        :name
                    )"
                );

                $stmt->execute([
                    ':name' => $hashtagName
                ]);

                $hashtagId = $pdo->lastInsertId();
            }

            $stmt = $pdo->prepare(
                "INSERT INTO post_hashtags (
                    post_id,
                    hashtag_id
                ) VALUES (
                    :post_id,
                    :hashtag_id
                )"
            );

            $stmt->execute([
                ':post_id' => $postId,
                ':hashtag_id' => $hashtagId
            ]);
        }
    }

    if (
        isset($_FILES['media'])
        && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE
    ) {

        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload media gagal.');
        }

        $file = $_FILES['media'];
        $maxSize = 5 * 1024 * 1024;

        if ($file['size'] > $maxSize) {
            throw new Exception('Ukuran media maksimal 5 MB.');
        }
        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/webm',
            'video/ogg'
        ];

        $fileType = mime_content_type($file['tmp_name']);

        if (!in_array($fileType, $allowedTypes, true)) {
            throw new Exception(
                'Format media tidak didukung. ' .
                'Gunakan JPG, PNG, WEBP, MP4, WEBM, atau OGG.'
            );
        }
        $uploadDir = '../uploads/posts/';

        if (!is_dir($uploadDir)) {

            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception('Folder upload gagal dibuat.');
            }
        }
        $extension = pathinfo(
            $file['name'],
            PATHINFO_EXTENSION
        );

        $fileName =
            uniqid('post_', true)
            . '.'
            . strtolower($extension);

        $filePath = $uploadDir . $fileName;

        if (!move_uploaded_file(
            $file['tmp_name'],
            $filePath
        )) {
            throw new Exception('Media gagal disimpan.');
        }
        $uploadedFilePath = $filePath;

        $stmt = $pdo->prepare(
            "INSERT INTO post_media (
                post_id,
                file_name,
                file_path,
                file_type
            ) VALUES (
                :post_id,
                :file_name,
                :file_path,
                :file_type
            )"
        );

        $stmt->execute([
            ':post_id' => $postId,
            ':file_name' => $fileName,
            ':file_path' => 'uploads/posts/' . $fileName,
            ':file_type' => $fileType
        ]);
    }

    $pdo->commit();

    echo "Postingan berhasil dibuat!";


} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }


    if (
        $uploadedFilePath !== null
        && file_exists($uploadedFilePath)
    ) {
        unlink($uploadedFilePath);
    }


    die(
        'Postingan gagal dibuat: '
        . htmlspecialchars($e->getMessage())
    );
}