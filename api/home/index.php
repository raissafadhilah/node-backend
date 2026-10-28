<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $_SESSION['user_id'] ?? null;



$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,
        users.full_name,
        users.status,
        users.profile_photo,

        (
            SELECT GROUP_CONCAT(
                CONCAT('#', hashtags.name)
                ORDER BY hashtags.name
                SEPARATOR ' '
            )
            FROM post_hashtags
            INNER JOIN hashtags
                ON post_hashtags.hashtag_id = hashtags.id
            WHERE post_hashtags.post_id = posts.id
        ) AS hashtags,

        (
            SELECT COUNT(*)
            FROM reactions
            WHERE reactions.post_id = posts.id
        ) AS reaction_count,

        (
            SELECT COUNT(*)
            FROM comments
            WHERE comments.post_id = posts.id
            AND comments.status = 'active'
        ) AS comment_count,

        (
            (
                SELECT COUNT(*)
                FROM reactions
                WHERE reactions.post_id = posts.id
            )
            +
            (
                SELECT COUNT(*)
                FROM comments
                WHERE comments.post_id = posts.id
                AND comments.status = 'active'
            )
        ) AS trending_score

    FROM posts

    INNER JOIN users
        ON posts.user_id = users.id

    WHERE posts.status = 'active'
    AND posts.created_at >= NOW() - INTERVAL 7 DAY

    ORDER BY
        trending_score DESC,
        posts.created_at DESC

    LIMIT 3"
);

$stmt->execute();

$trendingPosts = $stmt->fetchAll();

foreach ($trendingPosts as &$post) {

    if (!empty($post['hashtags'])) {
        $post['hashtags'] = preg_split(
            '/\s+/',
            trim($post['hashtags'])
        );
    } else {
        $post['hashtags'] = [];
    }
}

unset($post);


$stmt = $pdo->prepare(
    "SELECT
        hashtags.id,
        hashtags.name,
        COUNT(post_hashtags.post_id) AS post_count

    FROM hashtags

    INNER JOIN post_hashtags
        ON hashtags.id = post_hashtags.hashtag_id

    INNER JOIN posts
        ON post_hashtags.post_id = posts.id

    WHERE posts.status = 'active'

    AND LOWER(hashtags.name)
        NOT IN ('fict', 'fmb', 'fhs')

    GROUP BY
        hashtags.id,
        hashtags.name

    ORDER BY
        post_count DESC,
        hashtags.name ASC

    LIMIT 5"
);

$stmt->execute();

$dynamicTopics = $stmt->fetchAll();


$stmt = $pdo->prepare(
    "SELECT
        posts.id,
        posts.user_id,
        posts.caption,
        posts.content,
        posts.created_at,

        users.full_name,
        users.status,
        users.profile_photo,

        bookmarks.id AS bookmark_id,

        (
            SELECT GROUP_CONCAT(
                CONCAT('#', hashtags.name)
                ORDER BY hashtags.name
                SEPARATOR ' '
            )
            FROM post_hashtags

            INNER JOIN hashtags
                ON post_hashtags.hashtag_id = hashtags.id

            WHERE post_hashtags.post_id = posts.id
        ) AS hashtags

    FROM posts

    INNER JOIN users
        ON posts.user_id = users.id

    LEFT JOIN bookmarks
        ON bookmarks.post_id = posts.id
        AND bookmarks.user_id = :user_id

    WHERE posts.status = 'active'

    ORDER BY posts.created_at DESC"
);

$stmt->execute([
    ':user_id' => $userId
]);

$posts = $stmt->fetchAll();

foreach ($posts as &$post) {


    $stmtMedia = $pdo->prepare(
        "SELECT
            id,
            file_path,
            file_type
        FROM post_media
        WHERE post_id = :post_id
        ORDER BY id ASC"
    );

    $stmtMedia->execute([
        ':post_id' => $post['id']
    ]);

    $post['media'] = $stmtMedia->fetchAll();

    $stmtReaction = $pdo->prepare(
        "SELECT COUNT(*) AS total
        FROM reactions
        WHERE post_id = :post_id"
    );

    $stmtReaction->execute([
        ':post_id' => $post['id']
    ]);

    $reaction = $stmtReaction->fetch();

    $post['reaction_count'] = (int) $reaction['total'];

    $post['is_reacted'] = false;

    if ($isLoggedIn) {

        $stmtUserReaction = $pdo->prepare(
            "SELECT id
            FROM reactions
            WHERE post_id = :post_id
            AND user_id = :user_id
            LIMIT 1"
        );

        $stmtUserReaction->execute([
            ':post_id' => $post['id'],
            ':user_id' => $userId
        ]);

        $post['is_reacted'] =
            $stmtUserReaction->fetch() !== false;
    }

    $stmtComment = $pdo->prepare(
        "SELECT COUNT(*) AS total
        FROM comments
        WHERE post_id = :post_id
        AND status = 'active'"
    );

    $stmtComment->execute([
        ':post_id' => $post['id']
    ]);

    $comment = $stmtComment->fetch();

    $post['comment_count'] = (int) $comment['total'];


    $post['is_bookmarked'] =
        $post['bookmark_id'] !== null;

    unset($post['bookmark_id']);

        if (!empty($post['hashtags'])) {
        $post['hashtags'] = preg_split(
            '/\s+/',
            trim($post['hashtags'])
        );
    } else {
        $post['hashtags'] = [];
    }

    /*
     * POST PERMISSION
     */
    $post['can_edit'] = false;
    $post['can_delete'] = false;

    if ($userId !== null) {
        $post['can_edit'] =
            ((int) $post['user_id'] === (int) $userId);

        $post['can_delete'] =
            ((int) $post['user_id'] === (int) $userId);
    }
}

unset($post);

$trendingTopics = [
    [
        'name' => 'FICT',
        'type' => 'fixed'
    ],
    [
        'name' => 'FMB',
        'type' => 'fixed'
    ],
    [
        'name' => 'FHS',
        'type' => 'fixed'
    ]
];

foreach ($dynamicTopics as $topic) {

    $trendingTopics[] = [
        'name' => $topic['name'],
        'type' => 'dynamic'
    ];
}



jsonResponse(
    true,
    'Home berhasil diambil.',
    [
        'trending_discussions' => $trendingPosts,
        'trending_topics' => $trendingTopics,
        'posts' => $posts
    ]
);

