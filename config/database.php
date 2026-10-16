<?php
/**
 * ============================================================
 * DATABASE CONFIGURATION — News Portal
 * ============================================================
 * Centralized PDO connection for the entire application.
 * All database credentials are defined here — nowhere else.
 * 
 * Usage:
 *   require_once __DIR__ . '/../config/database.php';
 *   $pdo = connectDB();
 * ============================================================
 */

// --- Load .env file if present in project root ---
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile) && is_readable($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (str_contains($line, '=')) {
            [$key, $val] = explode('=', $line, 2);
            $key = trim($key);
            $val = trim($val, " \t\n\r\0\x0B\"'");
            if (!isset($_SERVER[$key]) && !isset($_ENV[$key])) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Helper to read env variables with fallback
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? ($_SERVER[$key] ?? $default);
        }
        return $val;
    }
}

// --- Application Environment & Database Credentials ---
if (!defined('APP_ENV'))   define('APP_ENV', env('APP_ENV', 'development'));
if (!defined('APP_DEBUG')) define('APP_DEBUG', filter_var(env('APP_DEBUG', APP_ENV === 'development'), FILTER_VALIDATE_BOOLEAN));
if (!defined('APP_URL'))   define('APP_URL', rtrim(env('APP_URL', ''), '/'));
if (!defined('PUBLIC_URL'))define('PUBLIC_URL', rtrim(env('PUBLIC_URL', ''), '/'));

if (!defined('DB_HOST'))   define('DB_HOST', env('DB_HOST', 'localhost'));
if (!defined('DB_PORT'))   define('DB_PORT', env('DB_PORT', '3306'));
if (!defined('DB_NAME'))   define('DB_NAME', env('DB_NAME', 'news_portal'));
if (!defined('DB_USER'))   define('DB_USER', env('DB_USER', 'root'));
if (!defined('DB_PASS'))   define('DB_PASS', env('DB_PASS', '')); // Default XAMPP has no password
if (!defined('DB_CHARSET'))define('DB_CHARSET', env('DB_CHARSET', 'utf8mb4'));
if (!defined('DB_SSL'))    define('DB_SSL', filter_var(env('DB_SSL', false), FILTER_VALIDATE_BOOLEAN));

/**
 * Returns a PDO database connection instance.
 *
 * Uses a static variable to reuse the same connection within
 * a single request (lightweight singleton pattern).
 *
 * @return PDO  Active PDO connection
 */
function connectDB(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST
             . ';port=' . DB_PORT
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;

        $options = [
            // Throw exceptions on errors — easier debugging
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Return associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Use real prepared statements (not emulated)
            PDO::ATTR_EMULATE_PREPARES   => false,

            // Force connection character set to utf8mb4
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci",
        ];

        // Support SSL/TLS for cloud database connections (e.g., TiDB Serverless, Aiven, Azure MySQL)
        if (defined('DB_SSL') && DB_SSL === true) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw error details to public visitors in production
            error_log('Database Connection Error: ' . $e->getMessage());
            if (defined('APP_DEBUG') && APP_DEBUG === true) {
                die('<h1>Database Connection Error</h1><p>' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</p>');
            } else {
                http_response_code(503);
                die('<!DOCTYPE html><html><head><title>Service Unavailable</title><style>body{font-family:sans-serif;text-align:center;padding:50px;color:#333;}</style></head><body><h1>Service Temporarily Unavailable</h1><p>We are currently experiencing a database connectivity issue. Please try again in a few moments.</p></body></html>');
            }
        }
    }

    return $pdo;
}
