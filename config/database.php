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

// --- Database Credentials ---
define('DB_HOST', 'localhost');
define('DB_NAME', 'news_portal');
define('DB_USER', 'root');
define('DB_PASS', '');           // Default XAMPP has no password
define('DB_CHARSET', 'utf8mb4');

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
             . ';dbname=' . DB_NAME
             . ';charset=' . DB_CHARSET;

        $options = [
            // Throw exceptions on errors — easier debugging
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,

            // Return associative arrays by default
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,

            // Use real prepared statements (not emulated)
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Never expose raw error details to the user
            error_log('Database Connection Error: ' . $e->getMessage());
            die('<h1>Service Temporarily Unavailable</h1>'
              . '<p>We are experiencing a database issue. Please try again later.</p>');
        }
    }

    return $pdo;
}
