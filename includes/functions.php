<?php
/**
 * ============================================================
 * GENERAL UTILITY FUNCTIONS — News Portal
 * ============================================================
 * Reusable helpers used throughout the application.
 * No authentication, no database connection, no page-specific logic.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/functions.php';
 * ============================================================
 */

// --- Application Constants ---
define('APP_NAME', 'News Portal');
define('APP_TIMEZONE', 'Asia/Kolkata');
define('ITEMS_PER_PAGE', 9);

// Set timezone globally once
date_default_timezone_set(APP_TIMEZONE);


// ============================================================
// OUTPUT ESCAPING / XSS PROTECTION
// ============================================================

/**
 * Escape a value for safe HTML output (XSS prevention).
 *
 * @param  mixed  $value  The value to escape
 * @return string         Escaped string safe for HTML output
 */
function e($value): string
{
    if ($value === null) {
        return '';
    }
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}


// ============================================================
// URL / PATH HELPERS
// ============================================================

/**
 * Generate an application URL from a relative path.
 * Works regardless of where the project is installed in htdocs.
 *
 * @param  string $path  Relative path (e.g. 'news.php?slug=abc')
 * @return string        Full URL from document root
 */
function url(string $path = ''): string
{
    $path = ltrim($path, '/');
    $isAdminPath = str_starts_with($path, 'admin/') || $path === 'admin';
    if ($isAdminPath) {
        $path = preg_replace('#^admin/?#', '', $path);
    }
    
    // Check if APP_URL is defined from environment configuration
    if (defined('APP_URL') && APP_URL !== '') {
        return rtrim(APP_URL, '/') . '/' . ($isAdminPath ? 'admin/' . $path : $path);
    }
    
    $clientUrl = 'http://localhost:8000';
    $backendUrl = 'http://localhost:9000';
    
    $port = $_SERVER['SERVER_PORT'] ?? '';
    if ($port == '8000' || $port == '9000') {
        if ($isAdminPath) {
            return $backendUrl . '/' . $path;
        }
        return $clientUrl . '/' . $path;
    }
    
    // Fallback for generic XAMPP setups
    static $basePath = null;
    if ($basePath === null) {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        $projectRoot = dirname(__DIR__);
        $docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
        if ($docRoot && $projectRoot && str_starts_with($projectRoot, $docRoot)) {
            $basePath = str_replace('\\', '/', substr($projectRoot, strlen($docRoot)));
        } else {
            $basePath = $scriptDir;
            if (preg_match('#(/[^/]+)(/admin.*)$#', $basePath, $m)) {
                $basePath = $m[1];
            }
        }
        $basePath = rtrim($basePath, '/');
    }

    return $basePath . '/' . ($isAdminPath ? 'admin/' . $path : $path);
}

/**
 * Generate the filesystem path to the project root.
 *
 * @return string  Absolute path to project root directory
 */
function projectRoot(): string
{
    return dirname(__DIR__);
}

/**
 * Build a URL with query string parameters.
 * Handles proper URL encoding automatically.
 *
 * @param  string $path   Base path (e.g. 'search.php')
 * @param  array  $params Associative array of query parameters
 * @return string         URL with encoded query string
 */
function buildUrl(string $path, array $params = []): string
{
    $fullPath = url($path);
    if (!empty($params)) {
        $fullPath .= '?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
    }
    return $fullPath;
}


// ============================================================
// REDIRECT HELPER
// ============================================================

/**
 * Redirect to a URL and stop script execution.
 *
 * @param  string $location  URL or relative path to redirect to
 * @return never
 */
function redirect(string $location): never
{
    // If it's a relative path (not starting with http), prepend base URL
    if (!preg_match('#^https?://#', $location)) {
        $location = url($location);
    }

    if (!headers_sent()) {
        header('Location: ' . $location);
    } else {
        // Fallback if headers already sent
        echo '<script>window.location.href="' . e($location) . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . e($location) . '"></noscript>';
    }
    exit;
}


// ============================================================
// FLASH MESSAGES (Session-based, one-time display)
// ============================================================

/**
 * Store a flash message in the session.
 *
 * @param string $message  Message text
 * @param string $type     Type: 'success', 'error', 'warning', 'info'
 */
function setFlashMessage(string $message, string $type = 'info'): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type'    => $type,
    ];
}

/**
 * Retrieve and clear the flash message from the session.
 *
 * @return array|null  ['message' => '...', 'type' => '...'] or null
 */
function getFlashMessage(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Render flash message HTML if one exists.
 * Uses Bootstrap alert classes.
 *
 * @return string  HTML string (empty if no flash message)
 */
function renderFlashMessage(): string
{
    $flash = getFlashMessage();
    if (!$flash) {
        return '';
    }

    // Map our types to Bootstrap alert classes
    $typeMap = [
        'success' => 'alert-success',
        'error'   => 'alert-danger',
        'warning' => 'alert-warning',
        'info'    => 'alert-info',
    ];

    $alertClass = $typeMap[$flash['type']] ?? 'alert-info';

    $iconMap = [
        'success' => 'fas fa-check-circle',
        'error'   => 'fas fa-exclamation-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'info'    => 'fas fa-info-circle',
    ];

    $icon = $iconMap[$flash['type']] ?? 'fas fa-info-circle';

    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">'
         . '<i class="' . $icon . ' me-2"></i>'
         . e($flash['message'])
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
         . '</div>';
}


// ============================================================
// DATE / TIME FORMATTING
// ============================================================

/**
 * Format a date string for display on the news portal.
 *
 * @param  string|null $date  Date string (from MySQL)
 * @param  string      $format  PHP date format
 * @return string              Formatted date or 'N/A'
 */
function formatDate(?string $date, string $format = 'd M Y'): string
{
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $dt = new DateTime($date, new DateTimeZone(APP_TIMEZONE));
        return $dt->format($format);
    } catch (Exception) {
        return 'N/A';
    }
}

/**
 * Format a date with time for display.
 *
 * @param  string|null $date  Date string
 * @return string             e.g. "25 Sep 2026, 09:30 AM"
 */
function formatDateTime(?string $date): string
{
    return formatDate($date, 'd M Y, h:i A');
}

/**
 * Get a relative time string (e.g., "2 hours ago").
 *
 * @param  string|null $date  Date string
 * @return string             Relative time or formatted date
 */
function timeAgo(?string $date): string
{
    if (empty($date)) {
        return 'N/A';
    }
    try {
        $now  = new DateTime('now', new DateTimeZone(APP_TIMEZONE));
        $then = new DateTime($date, new DateTimeZone(APP_TIMEZONE));
        $diff = $now->diff($then);

        if ($diff->y > 0) return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        if ($diff->m > 0) return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        if ($diff->d > 6) return floor($diff->d / 7) . ' week' . (floor($diff->d / 7) > 1 ? 's' : '') . ' ago';
        if ($diff->d > 0) return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        if ($diff->h > 0) return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        if ($diff->i > 0) return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        return 'Just now';
    } catch (Exception) {
        return formatDate($date);
    }
}


// ============================================================
// SLUG GENERATOR
// ============================================================

/**
 * Generate a URL-friendly slug from text.
 * NOTE: Does NOT guarantee database uniqueness — that must
 * be checked separately when saving records.
 *
 * @param  string $text  Raw text (e.g. article title)
 * @return string        URL-safe slug (e.g. "my-article-title")
 */
function generateSlug(string $text): string
{
    // Convert to lowercase
    $slug = mb_strtolower($text, 'UTF-8');
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    // Collapse multiple hyphens into one
    $slug = preg_replace('/-+/', '-', $slug);
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    return $slug;
}


// ============================================================
// READING TIME / WORD COUNT / EXCERPT
// ============================================================

/**
 * Calculate estimated reading time for an article.
 * Uses average reading speed of ~200 words per minute.
 *
 * @param  string $content  Article content (may contain HTML)
 * @return string           e.g. "3 min read"
 */
function calculateReadingTime(string $content): string
{
    $words = wordCount($content);
    $minutes = max(1, (int) ceil($words / 200));
    return $minutes . ' min read';
}

/**
 * Count words in content, stripping HTML tags.
 *
 * @param  string $content  Content (may contain HTML)
 * @return int              Word count
 */
function wordCount(string $content): int
{
    $text = strip_tags($content);
    $text = trim($text);
    if ($text === '') {
        return 0;
    }
    return str_word_count($text);
}

/**
 * Generate a plain-text excerpt from content.
 * Strips HTML, truncates at word boundary, adds ellipsis.
 *
 * @param  string $content  Content (may contain HTML)
 * @param  int    $length   Maximum character length
 * @return string           Plain-text excerpt
 */
function excerpt(string $content, int $length = 150): string
{
    // Strip HTML tags
    $text = strip_tags($content);
    // Normalize whitespace
    $text = preg_replace('/\s+/', ' ', trim($text));

    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }

    // Cut at the length limit
    $truncated = mb_substr($text, 0, $length, 'UTF-8');
    // Find the last space to avoid cutting a word
    $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');

    if ($lastSpace !== false && $lastSpace > $length * 0.5) {
        $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
    }

    return rtrim($truncated, '.,!? ') . '…';
}


// ============================================================
// PAGINATION HELPER
// ============================================================

/**
 * Calculate pagination parameters.
 *
 * @param  int    $totalRecords  Total number of records
 * @param  int    $perPage       Records per page
 * @param  int    $currentPage   Current page number (1-indexed)
 * @return array  [
 *                  'current_page'  => int,
 *                  'per_page'      => int,
 *                  'total_records' => int,
 *                  'total_pages'   => int,
 *                  'offset'        => int,
 *                  'has_prev'      => bool,
 *                  'has_next'      => bool,
 *                  'prev_page'     => int|null,
 *                  'next_page'     => int|null,
 *                ]
 */
function paginate(int $totalRecords, int $perPage = ITEMS_PER_PAGE, int $currentPage = 1): array
{
    $totalPages  = max(1, (int) ceil($totalRecords / $perPage));
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset      = ($currentPage - 1) * $perPage;

    return [
        'current_page'  => $currentPage,
        'per_page'      => $perPage,
        'total_records' => $totalRecords,
        'total_pages'   => $totalPages,
        'offset'        => $offset,
        'has_prev'      => $currentPage > 1,
        'has_next'      => $currentPage < $totalPages,
        'prev_page'     => $currentPage > 1 ? $currentPage - 1 : null,
        'next_page'     => $currentPage < $totalPages ? $currentPage + 1 : null,
    ];
}

/**
 * Render Bootstrap pagination navigation HTML.
 *
 * @param  array  $pagination  Output from paginate()
 * @param  string $baseUrl     Base URL for page links (e.g. 'search.php')
 * @param  array  $extraParams Extra query params to preserve
 * @return string              HTML string
 */
function renderPagination(array $pagination, string $baseUrl, array $extraParams = []): string
{
    if ($pagination['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';

    // Previous button
    if ($pagination['has_prev']) {
        $params = array_merge($extraParams, ['page' => $pagination['prev_page']]);
        $html .= '<li class="page-item"><a class="page-link" href="' . e(buildUrl($baseUrl, $params)) . '">&laquo; Previous</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">&laquo; Previous</span></li>';
    }

    // Page numbers (show max ~7 pages around current)
    $start = max(1, $pagination['current_page'] - 3);
    $end   = min($pagination['total_pages'], $pagination['current_page'] + 3);

    if ($start > 1) {
        $params = array_merge($extraParams, ['page' => 1]);
        $html .= '<li class="page-item"><a class="page-link" href="' . e(buildUrl($baseUrl, $params)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $params = array_merge($extraParams, ['page' => $i]);
        $active = $i === $pagination['current_page'] ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e(buildUrl($baseUrl, $params)) . '">' . $i . '</a></li>';
    }

    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        }
        $params = array_merge($extraParams, ['page' => $pagination['total_pages']]);
        $html .= '<li class="page-item"><a class="page-link" href="' . e(buildUrl($baseUrl, $params)) . '">' . $pagination['total_pages'] . '</a></li>';
    }

    // Next button
    if ($pagination['has_next']) {
        $params = array_merge($extraParams, ['page' => $pagination['next_page']]);
        $html .= '<li class="page-item"><a class="page-link" href="' . e(buildUrl($baseUrl, $params)) . '">Next &raquo;</a></li>';
    } else {
        $html .= '<li class="page-item disabled"><span class="page-link">Next &raquo;</span></li>';
    }

    $html .= '</ul></nav>';
    return $html;
}


// ============================================================
// REQUEST HELPERS
// ============================================================

/**
 * Check if the current request is a POST request.
 * @return bool
 */
function isPostRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

/**
 * Check if the current request is a GET request.
 * @return bool
 */
function isGetRequest(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'GET';
}

/**
 * Get the current request method.
 * @return string  e.g. 'GET', 'POST'
 */
function requestMethod(): string
{
    return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
}

/**
 * Safely retrieve a value from $_POST.
 *
 * @param  string      $key      Key name
 * @param  mixed       $default  Default value if key is missing
 * @return mixed                 Trimmed string value or default
 */
function post(string $key, mixed $default = null): mixed
{
    if (!isset($_POST[$key])) {
        return $default;
    }
    $value = $_POST[$key];
    return is_string($value) ? trim($value) : $value;
}

/**
 * Safely retrieve a value from $_GET.
 *
 * @param  string      $key      Key name
 * @param  mixed       $default  Default value if key is missing
 * @return mixed                 Trimmed string value or default
 */
function get(string $key, mixed $default = null): mixed
{
    if (!isset($_GET[$key])) {
        return $default;
    }
    $value = $_GET[$key];
    return is_string($value) ? trim($value) : $value;
}

/**
 * Get the current page number from the query string.
 *
 * @return int  Page number (minimum 1)
 */
function currentPage(): int
{
    return max(1, (int) get('page', 1));
}


// ============================================================
// VALIDATION HELPERS
// ============================================================

/**
 * Validate an email address.
 *
 * @param  string $email
 * @return bool
 */
function isValidEmail(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate that a value is not empty (after trimming).
 *
 * @param  mixed $value
 * @return bool
 */
function isValidRequired(mixed $value): bool
{
    if ($value === null) return false;
    if (is_string($value)) return trim($value) !== '';
    return true;
}

/**
 * Validate that a string is a valid URL slug.
 *
 * @param  string $slug
 * @return bool
 */
function isValidSlug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}


// ============================================================
// FILE / SECURITY HELPERS
// ============================================================

/**
 * Sanitize a filename for safe storage.
 * Removes path traversal, special characters, and adds uniqueness.
 *
 * @param  string $filename  Original filename
 * @return string            Sanitized filename
 */
function sanitizeFilename(string $filename): string
{
    // Get extension
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    // Generate a unique name
    $name = uniqid('img_', true);
    // Clean extension to only allow safe characters
    $ext = preg_replace('/[^a-z0-9]/', '', $ext);
    return $name . '.' . $ext;
}

/**
 * Get the URL for a news article's featured image.
 * Returns a default placeholder if no image is set.
 *
 * @param  string|null $imagePath  Stored image path from database
 * @return string                  URL to the image
 */
function newsImageUrl(?string $imagePath): string
{
    if (!empty($imagePath) && file_exists(projectRoot() . '/' . $imagePath)) {
        return url($imagePath);
    }
    return url('assets/images/default-news.jpg');
}
