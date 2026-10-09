<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

/*
|--------------------------------------------------------------------------
| Hanya GET
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

/*
|--------------------------------------------------------------------------
| User wajib login
|--------------------------------------------------------------------------
*/

if (!isset($_SESSION['user_id'])) {
    jsonResponse(
        false,
        'User belum login.',
        null,
        401
    );
}

$currentUserId = (int) $_SESSION['user_id'];

/*
|--------------------------------------------------------------------------
| Ambil ID profile dari URL
|--------------------------------------------------------------------------
|
| /api/profile/index.php?id=7
|
| Kalau tidak ada ID, tampilkan profile sendiri.
|
*/

$profileUserId = filter_input(
    INPUT_GET,
    'id',
    FILTER_VALIDATE_INT
);

if (!$profileUserId) {
    $profileUserId = $currentUserId;
}

/*
|--------------------------------------------------------------------------
| Ambil data user
|--------------------------------------------------------------------------
*/

$stmt = $pdo->prepare(
    "SELECT
        id,
        full_name,
        status,
        profile_photo
     FROM users
     WHERE id = :user_id
     LIMIT 1"
);

$stmt->execute([
    ':user_id' => $profileUserId
]);

$user = $stmt->fetch();

if (!$user) {
    jsonResponse(
        false,
        'Profile tidak ditemukan.',
        null,
        404
    );
}

/*
|--------------------------------------------------------------------------
| Ambil postingan user
|--------------------------------------------------------------------------
*/

$stmtPosts = $pdo->prepare(
    "SELECT
        id,
        content,
        created_at
     FROM posts
     WHERE user_id = :user_id
       AND status = 'active'
     ORDER BY created_at DESC"
);

$stmtPosts->execute([
    ':user_id' => $profileUserId
]);

$posts = $stmtPosts->fetchAll();

/*
|--------------------------------------------------------------------------
| Ambil replies/comments user
|--------------------------------------------------------------------------
*/

$stmtReplies = $pdo->prepare(
    "SELECT
        comments.id,
        comments.post_id,
        comments.content,
        comments.created_at
     FROM comments
     WHERE comments.user_id = :user_id
       AND comments.status = 'active'
     ORDER BY comments.created_at DESC"
);

$stmtReplies->execute([
    ':user_id' => $profileUserId
]);

$replies = $stmtReplies->fetchAll();

/*
|--------------------------------------------------------------------------
| Cek apakah profile milik user yang sedang login
|--------------------------------------------------------------------------
*/

$isOwnProfile = ($profileUserId === $currentUserId);

/*
|--------------------------------------------------------------------------
| Response
|--------------------------------------------------------------------------
*/

jsonResponse(
    true,
    'Profile berhasil diambil.',
    [
        'user' => [
            'id' => (int) $user['id'],
            'full_name' => $user['full_name'],
            'status' => $user['status'],
            'profile_photo' => $user['profile_photo']
        ],
        'is_own_profile' => $isOwnProfile,
        'posts' => $posts,
        'replies' => $replies
    ]
);