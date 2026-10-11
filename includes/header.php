<?php
/**
 * ============================================================
 * PUBLIC HEADER — News Portal
 * ============================================================
 * Common HTML document head for all public-facing pages.
 *
 * Required variable before including:
 *   $pageTitle  — Page-specific title string
 *
 * Usage:
 *   $pageTitle = 'Latest News';
 *   require_once __DIR__ . '/../includes/header.php';
 * ============================================================
 */

// Load dependencies if not already loaded
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

// Default page title if not set
if (!isset($pageTitle)) {
    $pageTitle = APP_NAME;
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Build the full title
$fullTitle = $pageTitle === APP_NAME
    ? APP_NAME . ' — Publish News Dynamically'
    : $pageTitle . ' — ' . APP_NAME;
?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($pageMetaDesc) ? e($pageMetaDesc) : e($pageTitle) . ' — ' . e(APP_NAME) . '. Breaking news, investigative reporting, world affairs, technology, and in-depth analysis.' ?>">
    <meta name="author" content="The Newsroom Editorial Board">
    
    <?php if (isset($currentUrl)): ?>
    <link rel="canonical" href="<?= e($currentUrl) ?>">
    <?php endif; ?>

    <?php if (isset($article) && isset($currentUrl)): ?>
    <!-- Open Graph for Articles -->
    <meta property="og:title" content="<?= e($article['title']) ?>">
    <meta property="og:description" content="<?= isset($pageMetaDesc) ? e($pageMetaDesc) : '' ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?= e($currentUrl) ?>">
    <meta property="og:image" content="<?= e(newsImageUrl($article['featured_image'])) ?>">
    <?php endif; ?>

    <title><?= e($fullTitle) ?></title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= url('assets/images/favicon.ico') ?>">

    <!-- Google Fonts: Playfair Display, Merriweather, Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS -->
    <link href="<?= url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">

    <!-- Font Awesome 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">

    <!-- News Portal Custom CSS -->
    <link href="<?= url('assets/css/style.css') ?>?v=<?= time() ?>" rel="stylesheet">

    <?php if (isset($extraCss)): ?>
        <?php foreach ((array) $extraCss as $css): ?>
            <link href="<?= e($css) ?>" rel="stylesheet">
        <?php endforeach; ?>
    <?php endif; ?>
</head>
<body>
