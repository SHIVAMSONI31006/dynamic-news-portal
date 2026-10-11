<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    
    if ($id <= 0) {
        setFlashMessage("Invalid subscriber ID.", "error");
        redirect('admin/newsletter/index.php');
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = :id");
        $stmt->execute([':id' => $id]);
        
        setFlashMessage("Subscriber deleted successfully.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not delete subscriber.", "error");
    }
}

redirect('admin/newsletter/index.php');
