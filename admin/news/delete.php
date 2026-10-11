<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin(); // Reusing the top-level auth for admin area

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    if ($id <= 0) {
        setFlashMessage("Invalid news ID.", "error");
        redirect('admin/news/index.php');
    }
    
    try {
        $pdo = connectDB();
        
        // Fetch article to get image path before deletion
        $stmt = $pdo->prepare("SELECT featured_image FROM news WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $article = $stmt->fetch();
        
        if ($article) {
            // Delete the article
            $stmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            // Database likely uses ON DELETE CASCADE for comments and likes referencing this news.id.
            
            // Clean up the image if it's stored in our local uploads directory
            if ($article['featured_image']) {
                $imagePath = __DIR__ . '/../../' . $article['featured_image'];
                if (strpos($article['featured_image'], 'uploads/news/') === 0 && file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            setFlashMessage("Article deleted successfully.", "success");
        } else {
            setFlashMessage("Article not found.", "error");
        }
        
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not delete article.", "error");
    }
}

redirect('admin/news/index.php');
