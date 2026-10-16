<?php
/**
 * Router script for Server 1 — Public News Portal (http://localhost:8000)
 */
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$uri = parse_url($rawUri, PHP_URL_PATH);

// 1. If an admin path is accessed on public server, redirect to Admin server (port 9000)
if ($uri === '/admin' || str_starts_with($uri, '/admin/')) {
    header('Location: http://localhost:9000' . $rawUri);
    exit;
}

// 2. If it's a request for an existing physical file, let PHP built-in server handle it directly
$filePath = __DIR__ . $uri;
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    return false;
}

// 3. If directory contains index.php, let PHP handle it
if (is_dir($filePath) && file_exists(rtrim($filePath, '/') . '/index.php')) {
    return false;
}

// 4. Default entry point for root
require __DIR__ . '/index.php';
