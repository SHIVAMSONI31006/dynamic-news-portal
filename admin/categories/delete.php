<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    if ($id <= 0) {
        setFlashMessage("Invalid category ID.", "error");
        redirect('admin/categories/index.php');
    }
    
    try {
        $pdo = connectDB();
        
        // Check if category has articles
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM news WHERE category_id = :id");
        $stmt->execute([':id' => $id]);
        $articleCount = $stmt->fetchColumn();
        
        if ($articleCount > 0) {
            setFlashMessage("This category cannot be deleted because {$articleCount} article(s) are assigned to it. Deactivate it instead.", "warning");
        } else {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
            $stmt->execute([':id' => $id]);
            setFlashMessage("Category deleted successfully.", "success");
        }
        
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not delete category.", "error");
    }
}

redirect('admin/categories/index.php');
