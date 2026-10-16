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

// Auto-detect cloud SSL requirement (e.g., TiDB Cloud Serverless or DB_PORT=4000)
$isCloudSsl = (str_contains((string)DB_HOST, 'tidbcloud.com') || (string)DB_PORT === '4000');
if (!defined('DB_SSL'))    define('DB_SSL', filter_var(env('DB_SSL', $isCloudSsl), FILTER_VALIDATE_BOOLEAN));

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
        ];

        // Safe INIT_COMMAND across PHP 8.0 through PHP 8.5+
        $initCmdAttr = defined('Pdo\Mysql::ATTR_INIT_COMMAND')
            ? \Pdo\Mysql::ATTR_INIT_COMMAND
            : (defined('PDO::MYSQL_ATTR_INIT_COMMAND') ? PDO::MYSQL_ATTR_INIT_COMMAND : 1002);
        $options[$initCmdAttr] = "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci";

        // Support SSL/TLS for cloud database connections (e.g., TiDB Serverless, Aiven, Azure MySQL)
        if (defined('DB_SSL') && DB_SSL === true) {
            $caBundles = [
                '/etc/ssl/certs/ca-certificates.crt', // Debian / Ubuntu / Docker
                '/etc/pki/tls/certs/ca-bundle.crt',   // RedHat / CentOS
                '/etc/ssl/cert.pem',                  // Alpine / macOS
            ];
            $caPath = null;
            foreach ($caBundles as $bundle) {
                if (file_exists($bundle)) {
                    $caPath = $bundle;
                    break;
                }
            }
            $sslCaAttr = defined('Pdo\Mysql::ATTR_SSL_CA')
                ? \Pdo\Mysql::ATTR_SSL_CA
                : (defined('PDO::MYSQL_ATTR_SSL_CA') ? PDO::MYSQL_ATTR_SSL_CA : 1007);

            $sslVerifyAttr = defined('Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT')
                ? \Pdo\Mysql::ATTR_SSL_VERIFY_SERVER_CERT
                : (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT') ? PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT : 1014);

            if ($caPath !== null) {
                $options[$sslCaAttr] = $caPath;
            } else {
                $options[$sslCaAttr] = true;
            }
            $options[$sslVerifyAttr] = false;
        }

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log('Database Connection Error: ' . $e->getMessage());

            // If running on Vercel and DB_HOST is localhost (cloud DB not yet linked)
            $isVercel = !empty(getenv('VERCEL')) || !empty($_ENV['VERCEL']) || !empty($_SERVER['VERCEL']);
            if ($isVercel && (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1')) {
                http_response_code(503);
                die('<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic News Portal — Cloud Database Setup Required</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: "Inter", sans-serif; background: #0f172a; color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; margin: 0; }
        .card { max-width: 680px; width: 100%; background: #1e293b; border: 1px solid #334155; border-radius: 16px; padding: 36px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5); }
        .badge { display: inline-block; background: #065f46; color: #34d399; font-size: 12px; font-weight: 700; padding: 4px 10px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 16px; }
        h1 { font-size: 26px; margin: 0 0 12px 0; color: #ffffff; }
        p { font-size: 15px; line-height: 1.6; color: #94a3b8; margin: 0 0 20px 0; }
        .code-box { background: #090d16; border: 1px solid #1e293b; border-radius: 8px; padding: 16px; font-family: monospace; font-size: 13px; color: #38bdf8; margin-bottom: 24px; overflow-x: auto; }
        ol { padding-left: 20px; color: #cbd5e1; font-size: 14px; line-height: 1.8; margin-bottom: 24px; }
        li strong { color: #f1f5f9; }
        .btn { display: inline-block; background: #2563eb; color: #ffffff; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 14px; transition: background 0.2s; }
        .btn:hover { background: #1d4ed8; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">▲ Vercel PHP Engine Active</span>
        <h1>Dynamic News Portal is Live on Vercel!</h1>
        <p>Your PHP application and static assets are successfully running in Vercel Serverless Functions. To display articles, categories, and enable user authentication, connect your free remote Cloud MySQL database.</p>
        
        <h3>Required Vercel Environment Variables:</h3>
        <div class="code-box">
DB_HOST = gateway01.ap-southeast-1.prod.aws.tidbcloud.com<br>
DB_PORT = 4000<br>
DB_NAME = news_portal<br>
DB_USER = your_database_username<br>
DB_PASS = your_database_password<br>
DB_SSL  = true
        </div>

        <ol>
            <li>Provision a free MySQL database on <strong><a href="https://tidbcloud.com" target="_blank" style="color: #60a5fa;">TiDB Cloud Serverless</a></strong> or <strong>Clever Cloud</strong> (0$ forever).</li>
            <li>Import your schema using <code>database/production_schema.sql</code>.</li>
            <li>In your <strong>Vercel Project Dashboard &rarr; Settings &rarr; Environment Variables</strong>, add the credentials above.</li>
            <li>Trigger a redeploy or run <code>vercel --prod</code>.</li>
        </ol>

        <a href="https://vercel.com/dashboard" target="_blank" class="btn">Open Vercel Project Dashboard &rarr;</a>
    </div>
</body>
</html>');
            }

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
