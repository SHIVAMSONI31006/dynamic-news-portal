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
    
    if ($id <= 0 || !in_array($new_status, ['draft', 'published', 'archived'])) {
        setFlashMessage("Invalid parameters.", "error");
        redirect('admin/news/index.php');
    }
    
    try {
        $pdo = connectDB();
        
        $stmt = $pdo->prepare("SELECT status, published_at FROM news WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch();
        
        if ($article) {
            $published_at = $article['published_at'];
            
            // If changing to published and it doesn't have a published_at date yet
            if ($new_status === 'published' && !$published_at) {
                $published_at = date('Y-m-d H:i:s');
            }
            
            $stmt = $pdo->prepare("UPDATE news SET status = :status, published_at = :published_at WHERE id = :id");
            $stmt->execute([
                ':status' => $new_status,
                ':published_at' => $published_at,
                ':id' => $id
            ]);
            
            setFlashMessage("Article status updated to {$new_status}.", "success");
        } else {
            setFlashMessage("Article not found.", "error");
        }
        
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not update status.", "error");
    }
}

redirect('admin/news/index.php');
