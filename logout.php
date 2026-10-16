<?php
/**
 * User Logout — Public News Portal
 */
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    logoutUser();
    startSession();
    setFlashMessage("You have been successfully logged out.", "success");
}

redirect('login.php');
