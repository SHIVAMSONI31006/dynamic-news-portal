<?php
/**
 * ============================================================
 * ADMIN HEADER — News Portal
 * ============================================================
 */
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Enforce admin/editor role for the entire admin area
requireAdmin();

$pageTitle = $pageTitle ?? 'Admin Dashboard';

// Security Headers (Admin)
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Merriweather:wght@400;700;900&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    
    <!-- Bootstrap 5 CSS -->
    <link href="<?= buildUrl('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= buildUrl('assets/css/style.css') ?>">
    
    <style>
        :root {
            --admin-sidebar-width: 260px;
            --admin-header-height: 64px;
            --color-accent: #C62828;
            --color-dark: #151515;
            --color-border: #D9D6CF;
            --color-bg: #F5F3EE;
            --color-surface: #FFFFFF;
            --color-text: #111111;
            --color-text-light: #6B6B6B;
            --font-heading: 'Playfair Display', Georgia, serif;
            --font-body: 'Inter', -apple-system, sans-serif;
            --transition-base: all 250ms cubic-bezier(0.4, 0, 0.2, 1);
        }
        body {
            font-family: var(--font-body);
            background-color: var(--color-bg);
        }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        .admin-sidebar {
            width: var(--admin-sidebar-width);
            background-color: var(--color-dark);
            color: #fff;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: var(--transition-base);
        }
        .admin-main {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }
        .admin-topbar {
            height: var(--admin-header-height);
            background-color: var(--color-surface);
            border-bottom: 1px solid var(--color-border);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 2rem;
        }
        .sidebar-brand {
            height: var(--admin-header-height);
            display: flex;
            align-items: center;
            padding: 0 1.5rem;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 1.2rem;
            color: #fff;
            text-decoration: none;
            background-color: #0a0a0a;
        }
        .sidebar-brand .brand-accent {
            color: var(--color-accent);
        }
        .sidebar-nav {
            padding: 1.5rem 0;
            list-style: none;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-nav li a {
            display: flex;
            align-items: center;
            padding: 0.8rem 1.5rem;
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            transition: var(--transition-base);
            font-weight: 500;
            font-size: 0.9rem;
            border-left: 3px solid transparent;
        }
        .sidebar-nav li a:hover, .sidebar-nav li a.active {
            color: #fff;
            background-color: rgba(255,255,255,0.06);
            border-left-color: var(--color-accent);
        }
        .sidebar-nav li a i {
            width: 20px;
            margin-right: 12px;
            text-align: center;
            font-size: 0.85rem;
        }
        
        .admin-content {
            padding: 2rem;
            flex-grow: 1;
            overflow-y: auto;
            background-color: var(--color-bg);
        }

        .stat-card {
            background: var(--color-surface);
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid var(--color-border);
            border-left: 4px solid var(--color-accent);
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: var(--transition-base);
        }
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.06);
        }
        .stat-card-info h3 {
            font-size: 2rem;
            font-weight: 700;
            margin: 0;
            color: var(--color-text);
            font-family: var(--font-heading);
        }
        .stat-card-info p {
            margin: 0;
            color: var(--color-text-light);
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 600;
            margin-top: 0.5rem;
        }
        .stat-card-icon {
            font-size: 2.2rem;
            color: rgba(0,0,0,0.04);
        }

        /* Admin Cards */
        .card {
            border: 1px solid var(--color-border);
            border-radius: 8px;
        }
        .card-header {
            border-bottom-color: var(--color-border);
        }

        /* Admin Tables */
        .table {
            font-size: 0.9rem;
        }
        .table th {
            font-weight: 600;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--color-text-light);
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                position: fixed;
                left: calc(var(--admin-sidebar-width) * -1);
                top: 0;
                bottom: 0;
                z-index: 1000;
            }
            .admin-sidebar.show {
                left: 0;
            }
        }
    </style>
</head>
<body>
    
<div class="admin-layout">
    
    <!-- Sidebar -->
    <?php require_once __DIR__ . '/sidebar.php'; ?>

    <div class="admin-main">
        <!-- Topbar -->
        <header class="admin-topbar">
            <div class="d-flex align-items-center">
                <button class="btn btn-sm btn-outline-secondary d-md-none me-3" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h4 class="mb-0 fs-5 d-none d-sm-block text-dark fw-bold" style="font-family: var(--font-heading);">
                    Dashboard
                </h4>
            </div>
            
            <div class="d-flex align-items-center">
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-dark text-decoration-none dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="me-2 text-end d-none d-md-block">
                            <div class="fw-bold" style="font-size: 0.9rem; line-height: 1.2;"><?= e(currentUserName()) ?></div>
                            <div class="text-muted text-uppercase" style="font-size: 0.7rem; font-weight: 700;"><?= e(currentUserRole()) ?></div>
                        </div>
                        <i class="fas fa-user-circle fs-3 text-secondary"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                        <li><a class="dropdown-item" href="<?= buildUrl('index.php') ?>" target="_blank"><i class="fas fa-external-link-alt me-2 text-muted"></i> View Website</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= buildUrl('admin/logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="admin-content">
            <div class="container-fluid p-0">
                <?= renderFlashMessage() ?>
