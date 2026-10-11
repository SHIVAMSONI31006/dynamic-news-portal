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
    
    if ($id <= 0) {
        setFlashMessage("Invalid comment ID.", "error");
        redirect('admin/comments/index.php');
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("DELETE FROM comments WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        setFlashMessage("Comment deleted successfully.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not delete comment.", "error");
    }
}

redirect('admin/comments/index.php');
