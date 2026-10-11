<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    $new_status = post('new_status');
    
    if ($id <= 0 || !in_array($new_status, ['active', 'unsubscribed'])) {
        setFlashMessage("Invalid parameters.", "error");
        redirect('admin/newsletter/index.php');
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $id
        ]);
        
        setFlashMessage("Subscriber status updated successfully.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not update status.", "error");
    }
}

redirect('admin/newsletter/index.php');
