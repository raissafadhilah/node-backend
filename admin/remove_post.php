<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reports.php');
    exit;
}

$reportId = $_POST['report_id'] ?? '';

if (!filter_var($reportId, FILTER_VALIDATE_INT)) {
    die('ID report tidak valid.');
}


// nyandak report nu valid jeung aya post_id ti database

$stmt = $pdo->prepare(
    "SELECT
        id,
        post_id,
        status
     FROM reports
     WHERE id = :report_id
     AND status = 'valid'
     AND post_id IS NOT NULL
     LIMIT 1"
);

$stmt->execute([
    ':report_id' => $reportId
]);

$report = $stmt->fetch();

if (!$report) {
    die('Report tidak ditemukan, belum valid, atau bukan report postingan.');
}


// pastikeun postingan aya jeung statusna masih aktif

$stmt = $pdo->prepare(
    "SELECT
        id,
        user_id
     FROM posts
     WHERE id = :post_id
     AND status = 'active'
     LIMIT 1"
);

$stmt->execute([
    ':post_id' => $report['post_id']
]);

$post = $stmt->fetch();

if (!$post) {
    die('Postingan tidak ditemukan atau sudah dihapus.');
}


// nga mimitian transaction

$pdo->beginTransaction();

try {

   // nga update status postingan jadi dihapus

    $stmt = $pdo->prepare(
        "UPDATE posts
         SET status = 'removed'
         WHERE id = :post_id
         AND status = 'active'"
    );

    $stmt->execute([
        ':post_id' => $post['id']
    ]);

    if ($stmt->rowCount() === 0) {
        throw new Exception('Postingan gagal dihapus.');
    }


    // nyimpen moderation action ka tabel moderation_actions

    $stmt = $pdo->prepare(
        "INSERT INTO moderation_actions (
            admin_id,
            report_id,
            user_id,
            post_id,
            comment_id,
            action_type,
            notes
        ) VALUES (
            :admin_id,
            :report_id,
            :user_id,
            :post_id,
            NULL,
            'remove_post',
            :notes
        )"
    );

    $stmt->execute([
        ':admin_id' => $_SESSION['admin_id'],
        ':report_id' => $report['id'],
        ':user_id' => $post['user_id'],
        ':post_id' => $post['id'],
        ':notes' => 'Postingan dihapus berdasarkan report yang dinyatakan valid.'
    ]);


    // commit transaction lamun sadayana sukses

    $pdo->commit();

    echo "Postingan berhasil dihapus dan moderation action berhasil dicatat.";

} catch (Exception $e) {

    // nga batalkeun transaction lamun aya error

    $pdo->rollBack();

    die('Moderation action gagal: ' . $e->getMessage());
}