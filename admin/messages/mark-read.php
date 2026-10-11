<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isAdminOrEditor()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    $new_status = post('new_status');
    
    if ($id <= 0 || !in_array($new_status, ['read', 'unread'])) {
        setFlashMessage("Invalid parameters.", "error");
        redirect('admin/messages/index.php');
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $id
        ]);
        
        setFlashMessage("Message marked as {$new_status}.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not update status.", "error");
    }
}

redirect('admin/messages/index.php');
