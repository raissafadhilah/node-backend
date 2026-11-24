<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/response.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/session.php';

// Pastikan hanya request GET yang diterima.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(
        false,
        'Method tidak diperbolehkan.',
        null,
        405
    );
}

// User ID bersifat opsional.
// Guest tetap boleh membaca Explore.
$userId = isset($_SESSION['user_id'])
    ? (int) $_SESSION['user_id']
    : null;

$filter = trim($_GET['filter'] ?? 'all');

$allowedFilters = [
    'all',
    'hot',
    'FICT',
    'FMB',
    'FHS'
];

if (!in_array($filter, $allowedFilters, true)) {
    jsonResponse(
        false,
        'Filter Explore tidak valid.',
        null,
        400
    );
}

try {

    /*
    |--------------------------------------------------------------------------
    | 1. AMBIL POST AKTIF
    |--------------------------------------------------------------------------
    */

    $sql = "
        SELECT
            p.id,
            p.user_id,
            p.caption,
            p.content,
            p.category,
            p.status,
            p.created_at,

            u.full_name,
            u.profile_photo,

            (
                SELECT COUNT(*)
                FROM reactions r
                WHERE r.post_id = p.id
            ) AS reaction_count,

            (
                SELECT COUNT(*)
                FROM comments c
                WHERE c.post_id = p.id
                AND c.status = 'active'
            ) AS comment_count,

            (
                SELECT COUNT(*)
                FROM reactions r
                WHERE r.post_id = p.id
                AND r.created_at >= DATE_SUB(
                    NOW(),
                    INTERVAL 7 DAY
                )
            ) AS hot_reaction_count,

            (
                SELECT COUNT(*)
                FROM comments c
                WHERE c.post_id = p.id
                AND c.status = 'active'
                AND c.created_at >= DATE_SUB(
                    NOW(),
                    INTERVAL 7 DAY
                )
            ) AS hot_comment_count,

            (
                SELECT GROUP_CONCAT(
                    DISTINCT h.name
                    ORDER BY h.name ASC
                    SEPARATOR ','
                )
                FROM post_hashtags ph
                INNER JOIN hashtags h
                    ON h.id = ph.hashtag_id
                WHERE ph.post_id = p.id
            ) AS hashtags_list,

            CASE
                WHEN :viewer_reaction_check IS NULL THEN 0
                ELSE (
                    SELECT COUNT(*)
                    FROM reactions r
                    WHERE r.post_id = p.id
                    AND r.user_id = :viewer_reaction_user
                )
            END AS is_reacted_by_me,

            CASE
                WHEN :viewer_bookmark_check IS NULL THEN 0
                ELSE (
                    SELECT COUNT(*)
                    FROM bookmarks b
                    WHERE b.post_id = p.id
                    AND b.user_id = :viewer_bookmark_user
                )
            END AS is_bookmarked_by_me

        FROM posts p

        INNER JOIN users u
            ON u.id = p.user_id

        WHERE p.status = 'active'
";

    $params = [
        ':viewer_reaction_check' => $userId,
        ':viewer_reaction_user' => $userId,
        ':viewer_bookmark_check' => $userId,
        ':viewer_bookmark_user' => $userId
    ];
    if ($userId !== null) {
        $sql .= "
        AND p.user_id <> :exclude_user_id
    ";

        $params[':exclude_user_id'] = $userId;
    }

    /*
    |--------------------------------------------------------------------------
    | 2. FILTER KATEGORI
    |--------------------------------------------------------------------------
    */

    if (in_array($filter, ['FICT', 'FMB', 'FHS'], true)) {

        $sql .= "
            AND p.category = :category
        ";

        $params[':category'] = $filter;
    }

    /*
    |--------------------------------------------------------------------------
    | 3. URUTKAN POST
    |--------------------------------------------------------------------------
    */

    if ($filter === 'hot') {

        $sql .= "
            ORDER BY
                (
                    hot_reaction_count +
                    hot_comment_count
                ) DESC,
                p.created_at DESC
        ";

    } else {

        $sql .= "
            ORDER BY p.created_at DESC
        ";
    }

    $sql .= " LIMIT 50";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | 4. RAPKAN DATA POST
    |--------------------------------------------------------------------------
    */

    foreach ($posts as &$post) {

        $post['id'] = (int) $post['id'];
        $post['user_id'] = (int) $post['user_id'];

        $post['reaction_count'] =
            (int) $post['reaction_count'];

        $post['comment_count'] =
            (int) $post['comment_count'];

        $post['hot_reaction_count'] =
            (int) $post['hot_reaction_count'];

        $post['hot_comment_count'] =
            (int) $post['hot_comment_count'];

        $post['hot_score'] =
            $post['hot_reaction_count'] +
            $post['hot_comment_count'];

        $post['is_reacted_by_me'] =
            (bool) $post['is_reacted_by_me'];

        $post['is_bookmarked_by_me'] =
            (bool) $post['is_bookmarked_by_me'];

        // Alias agar kompatibel dengan komponen PostCard.
        $post['is_reacted'] =
            $post['is_reacted_by_me'];

        $post['is_bookmarked'] =
            $post['is_bookmarked_by_me'];

        // Ubah hashtag menjadi array.
        $post['hashtags'] = !empty($post['hashtags_list'])
            ? array_values(
                array_filter(
                    array_map(
                        'trim',
                        explode(',', $post['hashtags_list'])
                    )
                )
            )
            : [];

        unset($post['hashtags_list']);
    }

    unset($post);

    /*
    |--------------------------------------------------------------------------
    | 5. AMBIL TRENDING TOPICS
    |--------------------------------------------------------------------------
    */

    $stmtTopics = $pdo->prepare("
        SELECT
            h.id,
            h.name,
            COUNT(DISTINCT ph.post_id) AS post_count

        FROM hashtags h

        INNER JOIN post_hashtags ph
            ON h.id = ph.hashtag_id

        INNER JOIN posts p
            ON ph.post_id = p.id

        WHERE p.status = 'active'

        AND LOWER(h.name)
            NOT IN ('fict', 'fmb', 'fhs')

        GROUP BY
            h.id,
            h.name

        ORDER BY
            post_count DESC,
            h.name ASC

        LIMIT 5
    ");

    $stmtTopics->execute();

    $dynamicTopics = $stmtTopics->fetchAll(PDO::FETCH_ASSOC);

    /*
    |--------------------------------------------------------------------------
    | 6. GABUNGKAN TOPIK TETAP DAN DINAMIS
    |--------------------------------------------------------------------------
    */

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
            'type' => 'dynamic',
            'post_count' => (int) $topic['post_count']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | 7. KIRIM RESPONSE JSON
    |--------------------------------------------------------------------------
    */

    jsonResponse(
        true,
        'Data Explore berhasil diambil.',
        [
            'posts' => $posts,
            'filter' => $filter,
            'total' => count($posts),
            'trending_topics' => $trendingTopics
        ],
        200
    );

} catch (Throwable $e) {

    error_log(
        'Explore API Error: ' . $e->getMessage()
    );

    jsonResponse(
        false,
        'Gagal mengambil data Explore.',
        null,
        500
    );
}
