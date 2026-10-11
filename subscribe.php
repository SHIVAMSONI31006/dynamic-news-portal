<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(post('email', ''));
    
    // Auth check - Not required for subscription
    
    // CSRF check
    if (!isValidCsrfToken(post('csrf_token'))) {
        setFlashMessage("Invalid request token. Please try again.", "error");
        redirect('index.php#newsletter');
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlashMessage("Please enter a valid email address.", "error");
        redirect('index.php#newsletter');
    }
    
    try {
        $pdo = connectDB();
        
        // Check if already exists
        $stmt = $pdo->prepare("SELECT id, status FROM newsletter_subscribers WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            if ($existing['status'] === 'active') {
                setFlashMessage("This email is already subscribed.", "info");
            } else {
                // Reactivate
                $stmt = $pdo->prepare("UPDATE newsletter_subscribers SET status = 'active' WHERE id = :id");
                $stmt->execute([':id' => $existing['id']]);
                setFlashMessage("You're subscribed successfully.", "success");
            }
        } else {
            // New subscription
            $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (:email)");
            $stmt->execute([':email' => $email]);
            setFlashMessage("You're subscribed successfully.", "success");
        }
        
    } catch (PDOException $e) {
        setFlashMessage("An error occurred while subscribing. Please try again.", "error");
    }
    
    redirect('index.php#newsletter');
}

// Block GET
require_once __DIR__ . '/errors/404.php';
