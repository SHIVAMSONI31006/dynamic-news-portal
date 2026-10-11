<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $id = (int)post('id', 0);
    $new_status = post('new_status');
    
    if ($id <= 0 || !in_array($new_status, ['active', 'inactive'])) {
        setFlashMessage("Invalid parameters.", "error");
        redirect('admin/users/index.php');
    }
    
    if ($id === currentUserId()) {
        setFlashMessage("You cannot change your own status from here.", "error");
        redirect('admin/users/index.php');
    }
    
    try {
        $pdo = connectDB();
        $stmt = $pdo->prepare("UPDATE users SET status = :status WHERE id = :id");
        $stmt->execute([
            ':status' => $new_status,
            ':id' => $id
        ]);
        
        setFlashMessage("User status updated successfully.", "success");
    } catch (PDOException $e) {
        setFlashMessage("Database error: Could not update status.", "error");
    }
}

redirect('admin/users/index.php');
