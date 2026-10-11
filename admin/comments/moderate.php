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
    $action = post('action');
    
    if ($id <= 0 || !in_array($action, ['approve', 'reject'])) {
        setFlashMessage("Invalid parameters.", "error");
        redirect('admin/comments/index.php');
    }
    
    $new_status = ($action === 'approve') ? 'approved' : 'rejected';
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE comments SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $id
        ]);
        
        setFlashMessage("Comment {$new_status} successfully.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not moderate comment.", "error");
    }
}

redirect('admin/comments/index.php');
