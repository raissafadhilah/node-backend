<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

$userId = $_SESSION['user_id'];

$caption = trim($_POST['caption'] ?? '');
$content = trim($_POST['content'] ?? '');
$hashtagsInput = trim($_POST['hashtags'] ?? '');

if ($caption === '') {
    jsonResponse(
        false,
        'Caption wajib diisi.',
        null,
        400
    );
}

if ($content === '') {
    jsonResponse(
        false,
        'Kalimat tambahan wajib diisi.',
        null,
        400
    );
}

$uploadedFilePath = null;

try {
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

    $postId = (int) $pdo->lastInsertId();

    $savedHashtags = [];

    if ($hashtagsInput !== '') {
        $hashtags = preg_split(
            '/\s+/',
            $hashtagsInput
        );

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
                $hashtagId = (int) $existingHashtag['id'];
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

                $hashtagId = (int) $pdo->lastInsertId();
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

            $savedHashtags[] = '#' . $hashtagName;
        }
    }

    /*
     * MEDIA
     */
    $mediaData = null;

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
            throw new Exception(
                'Ukuran media maksimal 5 MB.'
            );
        }

        $allowedTypes = [
            'image/jpeg',
            'image/png',
            'image/webp',
            'video/mp4',
            'video/webm',
            'video/ogg'
        ];

        $fileType = mime_content_type(
            $file['tmp_name']
        );

        if (!in_array(
            $fileType,
            $allowedTypes,
            true
        )) {
            throw new Exception(
                'Format media tidak didukung. ' .
                'Gunakan JPG, PNG, WEBP, MP4, WEBM, atau OGG.'
            );
        }

        $uploadDir = __DIR__ . '/../../uploads/posts/';

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0777, true)) {
                throw new Exception(
                    'Folder upload gagal dibuat.'
                );
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
            throw new Exception(
                'Media gagal disimpan.'
            );
        }

        $uploadedFilePath = $filePath;

        $databaseFilePath =
            'uploads/posts/' . $fileName;

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
            ':file_path' => $databaseFilePath,
            ':file_type' => $fileType
        ]);

        $mediaData = [
            'file_name' => $fileName,
            'file_path' => $databaseFilePath,
            'file_type' => $fileType
        ];
    }

    $pdo->commit();

    jsonResponse(
        true,
        'Postingan berhasil dibuat.',
        [
            'post' => [
                'id' => $postId,
                'user_id' => (int) $userId,
                'caption' => $caption,
                'content' => $content,
                'status' => 'active',
                'hashtags' => $savedHashtags,
                'media' => $mediaData
            ]
        ],
        201
    );

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

    jsonResponse(
        false,
        'Postingan gagal dibuat.',
        [
            'error' => $e->getMessage()
        ],
        500
    );
}