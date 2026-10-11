<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $news_id = (int)post('news_id', 0);
    $slug = post('slug', '');
    $comment = trim(post('comment', ''));
    
    // Redirect target
    $redirectUrl = 'index.php';
    if ($slug) {
        $redirectUrl = 'news.php?slug=' . urlencode($slug) . '#comments';
    }
    
    // Auth check
    if (!isLoggedIn()) {
        setFlashMessage("Please log in to submit a comment.", "error");
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
    
    if (empty($comment)) {
        setFlashMessage("Comment cannot be empty.", "error");
        redirect($redirectUrl);
    }
    
    if (strlen($comment) > 1000) {
        setFlashMessage("Comment is too long (maximum 1000 characters).", "error");
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
        
        // Insert comment
        $stmt = $pdo->prepare("INSERT INTO comments (news_id, user_id, comment, status) VALUES (:news_id, :user_id, :comment, 'pending')");
        $stmt->execute([
            ':news_id' => $news_id,
            ':user_id' => $user_id,
            ':comment' => strip_tags($comment) // Extra safety
        ]);
        
        setFlashMessage("Your comment has been submitted and is pending moderation.", "success");
        
    } catch (PDOException $e) {
        setFlashMessage("An error occurred while submitting your comment.", "error");
    }
    
    redirect($redirectUrl);
}

// Block GET
require_once __DIR__ . '/errors/404.php';
