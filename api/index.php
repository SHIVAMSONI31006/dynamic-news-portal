<?php
/**
 * ============================================================
 * VERCEL SERVERLESS FUNCTION DISPATCHER / ROUTER
 * ============================================================
 * Dynamically routes incoming HTTP requests on Vercel Serverless
 * to corresponding PHP scripts located across the project root.
 * ============================================================
 */

// Prevent deprecation notices from interfering with HTTP headers in serverless environment
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', '0');

// 1. Ensure current working directory is project root
$projectRoot = dirname(__DIR__);
chdir($projectRoot);

// 2. Parse request URI and normalize path
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';
$urlPath = parse_url($requestUri, PHP_URL_PATH);
$path = trim($urlPath, '/');

// 3. Root homepage handler
if ($path === '' || $path === 'index.php') {
    $_SERVER['SCRIPT_NAME'] = '/index.php';
    $_SERVER['PHP_SELF'] = '/index.php';
    $_SERVER['SCRIPT_FILENAME'] = $projectRoot . '/index.php';
    require $projectRoot . '/index.php';
    exit;
}

// 4. Resolve physical file path securely (prevents directory traversal)
$target = null;
$candidatePath = $projectRoot . '/' . $path;
$realCandidate = realpath($candidatePath);
$realRoot = realpath($projectRoot);

if ($realCandidate && $realRoot && str_starts_with($realCandidate, $realRoot)) {
    if (is_file($realCandidate) && str_ends_with($realCandidate, '.php')) {
        $target = $realCandidate;
    } elseif (is_dir($realCandidate)) {
        $dirIndex = realpath($realCandidate . '/index.php');
        if ($dirIndex && is_file($dirIndex)) {
            $target = $dirIndex;
        }
    }
}

// 5. Clean URL support: If path without extension matches an existing .php file
if (!$target && $realRoot) {
    $phpCandidate = realpath($projectRoot . '/' . $path . '.php');
    if ($phpCandidate && str_starts_with($phpCandidate, $realRoot) && is_file($phpCandidate)) {
        $target = $phpCandidate;
    }
}

// 6. Execute target PHP script
if ($target && $realRoot) {
    $relativeTarget = substr($target, strlen($realRoot));
    $relativeTarget = str_replace('\\', '/', $relativeTarget);
    $_SERVER['SCRIPT_NAME'] = $relativeTarget;
    $_SERVER['PHP_SELF'] = $relativeTarget;
    $_SERVER['SCRIPT_FILENAME'] = $target;
    require $target;
    exit;
}

// 7. Not found — Render 404 template
http_response_code(404);
$custom404 = $projectRoot . '/errors/404.php';
if (file_exists($custom404)) {
    require $custom404;
} else {
    echo "<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 - Page Not Found</h1></body></html>";
}
exit;
