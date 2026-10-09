<?php

require_once '../config/database.php';
require_once '../includes/admin_auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: reports.php');
    exit;
}

$reportId = $_POST['report_id'] ?? '';
$status = $_POST['status'] ?? '';

if (!filter_var($reportId, FILTER_VALIDATE_INT)) {
    die('ID report tidak valid.');
}

if (!in_array($status, ['valid', 'invalid'], true)) {
    die('Status review tidak valid.');
}


// pastikeun report aya jeung statusna masih pending

$stmt = $pdo->prepare(
    "SELECT id
     FROM reports
     WHERE id = :report_id
     AND status = 'pending'
     LIMIT 1"
);

$stmt->execute([
    ':report_id' => $reportId
]);

$report = $stmt->fetch();

if (!$report) {
    die('Report tidak ditemukan atau sudah direview.');
}


// nga update status report di database

$stmt = $pdo->prepare(
    "UPDATE reports
     SET
        status = :status,
        reviewed_by = :admin_id,
        reviewed_at = NOW()
     WHERE id = :report_id
     AND status = 'pending'"
);

$stmt->execute([
    ':status' => $status,
    ':admin_id' => $_SESSION['admin_id'],
    ':report_id' => $reportId
]);

if ($stmt->rowCount() === 0) {
    die('Report gagal direview.');
}

echo "Report berhasil ditandai sebagai " . htmlspecialchars($status) . ".";