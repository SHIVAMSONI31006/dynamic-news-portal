<?php
/**
 * ============================================================
 * EXPORT NEWSLETTER SUBSCRIBERS TO CSV — News Portal Admin
 * ============================================================
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

requireAdmin();

try {
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT id, email, status, created_at FROM newsletter_subscribers ORDER BY created_at DESC");
    $subscribers = $stmt->fetchAll();

    $filename = "newsletter_subscribers_" . date('Y-m-d_His') . ".csv";

    // Set download headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'w');

    // Add UTF-8 BOM for Excel compatibility
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

    // CSV Header Row
    fputcsv($output, ['ID', 'Email Address', 'Status', 'Subscribed At']);

    // CSV Data Rows
    foreach ($subscribers as $row) {
        fputcsv($output, [
            $row['id'],
            $row['email'],
            ucfirst($row['status']),
            $row['created_at']
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    error_log("CSV Export Error: " . $e->getMessage());
    setFlashMessage("Failed to export newsletter subscribers.", "error");
    redirect('admin/newsletter/index.php');
    exit;
}
