<?php
/**
 * ============================================================
 * ADMIN LOGOUT — News Portal
 * ============================================================
 */
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

// If user is logged in, log them out
if (isLoggedIn()) {
    logoutUser();
    setFlashMessage("You have been successfully logged out.", "success");
}

// Redirect to login page
redirect('admin/login.php');
