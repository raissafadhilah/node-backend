
<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(false, 'Method tidak diperbolehkan.', null, 405);
}

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, 'User belum login.', null, 401);
}

$userId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT
        n.id,
        n.recipient_id,
        n.actor_id,
        n.post_id,
        n.comment_id,
        n.type,
        n.is_read,
        n.created_at,
        actor.full_name AS actor_name,
        actor.profile_photo AS actor_photo,
        posts.caption AS post_caption,
        comments.content AS comment_content
     FROM notifications n
     INNER JOIN users actor
        ON actor.id = n.actor_id
     INNER JOIN posts
        ON posts.id = n.post_id
     LEFT JOIN comments
        ON comments.id = n.comment_id
     WHERE n.recipient_id = :recipient_id
     ORDER BY n.created_at DESC, n.id DESC
     LIMIT 50"
);

$stmt->execute([
    ':recipient_id' => $userId
]);

$notifications = $stmt->fetchAll();

foreach ($notifications as &$notification) {
    $notification['id'] = (int) $notification['id'];
    $notification['recipient_id'] = (int) $notification['recipient_id'];
    $notification['actor_id'] = (int) $notification['actor_id'];
    $notification['post_id'] = (int) $notification['post_id'];
    $notification['comment_id'] = $notification['comment_id'] !== null
        ? (int) $notification['comment_id']
        : null;
    $notification['is_read'] = (bool) $notification['is_read'];
}

unset($notification);

$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS total
     FROM notifications
     WHERE recipient_id = :recipient_id
       AND is_read = 0"
);

$stmt->execute([
    ':recipient_id' => $userId
]);

$unreadCount = (int) $stmt->fetch()['total'];

jsonResponse(
    true,
    'Notifikasi berhasil diambil.',
    [
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]
);
