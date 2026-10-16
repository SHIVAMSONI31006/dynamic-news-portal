<?php
/**
 * Router script for Server 2 — Admin Panel CMS (http://localhost:9000)
 */
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($rawUri, PHP_URL_PATH);

// 1. When visiting root / or /index.php on port 9000, redirect to admin entry point
if ($uri === '/' || $uri === '' || $uri === '/index.php') {
    header('Location: /admin/index.php');
    exit;
}

// 2. When visiting /login.php on port 9000, redirect to admin login
if ($uri === '/login.php') {
    header('Location: /admin/login.php');
    exit;
}

// 3. When visiting /admin or /admin/
if ($uri === '/admin' || $uri === '/admin/') {
    header('Location: /admin/index.php');
    exit;
}

// 4. If it's a request for an existing physical file (assets, uploads, admin PHP scripts)
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 5. If directory with index.php exists
if (is_dir($filePath) && file_exists(rtrim($filePath, '/') . '/index.php')) {
    return false;
}

// 6. Default fallback to admin/index.php
require __DIR__ . '/admin/index.php';
