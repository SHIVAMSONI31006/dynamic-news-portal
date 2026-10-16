<?php
/**
 * ============================================================
 * ADMIN HEADER — News Portal CMS
 * ============================================================
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Enforce admin/editor role for the entire admin area
requireAdmin();

$pageTitle = $pageTitle ?? 'Admin Dashboard';
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');

// Security Headers (Admin)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Compute user initials for avatar
$nameParts = explode(' ', trim(currentUserName()));
$initials = '';
if (!empty($nameParts[0])) {
    $initials .= strtoupper(substr($nameParts[0], 0, 1));
}
if (count($nameParts) > 1 && !empty($nameParts[count($nameParts) - 1])) {
    $initials .= strtoupper(substr($nameParts[count($nameParts) - 1], 0, 1));
}
if ($initials === '') {
    $initials = 'AD';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?= e($pageTitle) ?> | News Portal Admin</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= buildUrl('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dedicated Admin SaaS Stylesheet -->
    <link rel="stylesheet" href="<?= buildUrl('assets/css/admin.css') ?>">
</head>
<body class="admin-body">
    
<div class="admin-layout">
    
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>
    <div class="admin-backdrop" id="adminBackdrop"></div>

    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="topbar-left">
                <!-- Mobile Offcanvas Hamburger -->
                <button class="admin-hamburger d-lg-none" id="mobileSidebarToggle" type="button" aria-label="Open Navigation">
                    <i class="fas fa-bars"></i>
                </button>
                <!-- Desktop Width Toggle -->
                <button class="admin-collapse-toggle d-none d-lg-inline-flex" id="desktopSidebarToggle" type="button" title="Collapse / Expand Sidebar">
                    <i class="fas fa-bars-staggered"></i>
                </button>
                
                <div class="topbar-breadcrumbs">
                    <h1 class="h4 mb-0 fw-bold"><?= e($pageTitle) ?></h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb admin-breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="<?= buildUrl('admin/index.php') ?>">Admin</a></li>
                            <?php if ($currentScript !== 'index.php'): ?>
                                <li class="breadcrumb-item active" aria-current="page"><?= e($pageTitle) ?></li>
                            <?php else: ?>
                                <li class="breadcrumb-item active" aria-current="page">Overview</li>
                            <?php endif; ?>
                        </ol>
                    </nav>
                </div>
            </div>
            
            <div class="topbar-right">
                <!-- Quick link: Public Site -->
                <a href="<?= buildUrl('index.php') ?>" class="btn-topbar-action d-none d-sm-inline-flex" target="_blank" rel="noopener" title="Open Public Website">
                    <i class="fas fa-globe text-primary"></i>
                    <span>Live Portal</span>
                    <i class="fas fa-arrow-up-right-from-square" style="font-size: 0.75rem;"></i>
                </a>

                <!-- User Dropdown -->
                <div class="dropdown">
                    <button class="user-menu-btn" type="button" id="adminUserDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true" aria-label="Account Settings for <?= e(currentUserName()) ?>">
                        <div class="user-avatar-badge"><?= e($initials) ?></div>
                        <div class="user-meta d-none d-md-flex flex-column text-start">
                            <span class="user-name"><?= e(currentUserName()) ?></span>
                            <span class="user-role-pill"><?= e(strtoupper(currentUserRole())) ?></span>
                        </div>
                        <i class="fas fa-chevron-down user-chevron"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 py-2 mt-2" aria-labelledby="adminUserDropdown" style="min-width: 220px; border-radius: 10px;">
                        <li class="px-3 py-2 border-bottom mb-1">
                            <div class="fw-bold text-dark text-truncate"><?= e(currentUserName()) ?></div>
                            <small class="text-muted text-truncate d-block"><?= e($_SESSION['user_email'] ?? '') ?></small>
                        </li>
                        <li><a class="dropdown-item py-2" href="<?= buildUrl('index.php') ?>" target="_blank"><i class="fas fa-globe me-2 text-primary"></i> View Public Website</a></li>
                        <?php if (isAdmin()): ?>
                        <li><a class="dropdown-item py-2" href="<?= buildUrl('admin/users/index.php') ?>"><i class="fas fa-user-shield me-2 text-secondary"></i> Manage Admins</a></li>
                        <?php endif; ?>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item text-danger py-2" href="<?= buildUrl('admin/logout.php') ?>">
                                <i class="fas fa-arrow-right-from-bracket me-2"></i> Sign Out
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="admin-content" id="mainContent">
            <div class="container-fluid p-0">
                <?= renderFlashMessage() ?>
