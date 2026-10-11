<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news_id = (int)post('news_id', 0);
    $slug = post('slug', '');
    
    // Redirect target
    $redirectUrl = 'index.php';
    if ($slug) {
        $redirectUrl = 'news.php?slug=' . urlencode($slug);
    }
    
    // Auth check
    if (!isLoggedIn()) {
        setFlashMessage("Please log in to like articles.", "error");
        redirect($redirectUrl);
    }
    
    // CSRF check
    if (!isValidCsrfToken(post('csrf_token'))) {
        setFlashMessage("Invalid request token.", "error");
        redirect($redirectUrl);
    }
    
    if ($news_id <= 0) {
        setFlashMessage("Invalid article.", "error");
        redirect($redirectUrl);
    }
    
    $user_id = currentUserId();
    
    try {
        $pdo = connectDB();
        
        // Verify article exists and is published
        $stmt = $pdo->prepare("SELECT id FROM news WHERE id = :id AND status = 'published'");
        $stmt->execute([':id' => $news_id]);
        if (!$stmt->fetch()) {
            setFlashMessage("Article not found or not published.", "error");
            redirect($redirectUrl);
        }
        
        // Check if already liked
        $stmt = $pdo->prepare("SELECT id FROM likes WHERE news_id = :news_id AND user_id = :user_id");
        $stmt->execute([':news_id' => $news_id, ':user_id' => $user_id]);
        $existingLike = $stmt->fetch();
        
        if ($existingLike) {
            // Unlike
            $stmt = $pdo->prepare("DELETE FROM likes WHERE id = :id");
            $stmt->execute([':id' => $existingLike['id']]);
            setFlashMessage("You unliked this article.", "info");
        } else {
            // Like
            $stmt = $pdo->prepare("INSERT INTO likes (news_id, user_id) VALUES (:news_id, :user_id)");
            $stmt->execute([':news_id' => $news_id, ':user_id' => $user_id]);
            setFlashMessage("You liked this article.", "success");
        }
        
    } catch (PDOException $e) {
        setFlashMessage("An error occurred while processing your like.", "error");
    }
    
    redirect($redirectUrl);
}

// Block GET
require_once __DIR__ . '/errors/404.php';
