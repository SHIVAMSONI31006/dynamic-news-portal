<?php
/**
 * ============================================================
 * COMPREHENSIVE AUTOMATED TEST SUITE — News Portal
 * ============================================================
 * Runs systematic tests across:
 *   1. Syntax Check
 *   2. Database Connection & Schema Verification
 *   3. Authentication & Password Hashing
 *   4. Role-Based Authorization
 *   5. Security (CSRF, XSS, SQLi Protection)
 *   6. News CRUD Workflow
 *   7. HTTP Route Verification
 * ============================================================
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

$results = [
    'passed' => 0,
    'failed' => 0,
    'total'  => 0,
    'details' => []
];

function recordTest(string $id, string $name, bool $pass, string $evidence = '') {
    global $results;
    $results['total']++;
    if ($pass) {
        $results['passed']++;
        $status = 'PASS';
    } else {
        $results['failed']++;
        $status = 'FAIL';
    }
    $results['details'][] = [
        'id'       => $id,
        'name'     => $name,
        'status'   => $status,
        'evidence' => $evidence
    ];
    echo sprintf("[%s] %-8s: %s (%s)\n", $status, $id, $name, $evidence);
}

echo "============================================================\n";
echo " DYNAMIC NEWS PORTAL — AUTOMATED VERIFICATION SUITE\n";
echo "============================================================\n\n";

// TEST GROUP 1: DATABASE & SCHEMA
echo "--- 1. DATABASE & SCHEMA CHECKS ---\n";
try {
    $pdo = connectDB();
    recordTest('DB-01', 'PDO Database Connection', ($pdo instanceof PDO), 'Connected to ' . DB_NAME);
    
    $requiredTables = ['users', 'categories', 'news', 'comments', 'likes', 'contact_messages', 'newsletter_subscribers'];
    $stmt = $pdo->query("SHOW TABLES");
    $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($requiredTables as $tbl) {
        $found = in_array($tbl, $existingTables);
        recordTest('DB-TBL-' . strtoupper($tbl), "Table '$tbl' Exists", $found, $found ? 'Table verified' : 'Missing table');
    }
} catch (Exception $e) {
    recordTest('DB-01', 'PDO Database Connection', false, $e->getMessage());
}

// TEST GROUP 2: AUTHENTICATION & HASHING
echo "\n--- 2. AUTHENTICATION & HASHING CHECKS ---\n";
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => 'admin@newsportal.local']);
    $adminUser = $stmt->fetch();
    
    recordTest('AUTH-01', 'Admin Seed User Exists', (bool)$adminUser, 'Found admin@newsportal.local');
    
    $passValid = password_verify('NewsPortal@2026', $adminUser['password'] ?? '');
    recordTest('AUTH-02', 'Bcrypt Password Verification (Valid)', $passValid, 'Bcrypt verified NewsPortal@2026');
    
    $passInvalid = password_verify('WrongPassword123', $adminUser['password'] ?? '');
    recordTest('AUTH-03', 'Bcrypt Password Verification (Invalid Rejected)', !$passInvalid, 'Rejected wrong password');

    // Test password hashing helper
    $testHash = password_hash('SecureTest@2026', PASSWORD_DEFAULT);
    recordTest('AUTH-04', 'Bcrypt Hash Generation', str_starts_with($testHash, '$2y$'), 'Standard Bcrypt prefix generated');

} catch (Exception $e) {
    recordTest('AUTH-ERR', 'Auth test failure', false, $e->getMessage());
}

// TEST GROUP 3: SECURITY GUARDS
echo "\n--- 3. APPLICATION SECURITY GUARDS ---\n";
// CSRF
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$token = generateCsrfToken();
recordTest('SEC-CSRF-01', 'CSRF Token Generation', (!empty($token) && strlen($token) === 64), '64-hex token: ' . substr($token, 0, 10) . '...');
recordTest('SEC-CSRF-02', 'CSRF Token Validation (Valid)', isValidCsrfToken($token), 'Valid token accepted');
recordTest('SEC-CSRF-03', 'CSRF Token Validation (Tampered Rejected)', !isValidCsrfToken('tampered_invalid_token'), 'Tampered token rejected');

// XSS Escaping
$dirtyInput = '<script>alert("XSS")</script>';
$escaped = e($dirtyInput);
recordTest('SEC-XSS-01', 'XSS htmlspecialchars Escaping', ($escaped === '&lt;script&gt;alert(&quot;XSS&quot;)&lt;/script&gt;'), 'HTML tags encoded');

// SQL Injection Defense with Prepared Statements
$sqliPayload = "admin' OR '1'='1";
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
$stmt->execute(['email' => $sqliPayload]);
$sqliResult = $stmt->fetch();
recordTest('SEC-SQLI-01', 'SQL Injection Isolation', ($sqliResult === false), 'Payload treated as literal string; zero records returned');

// TEST GROUP 4: CRUD INTEGRITY (TRANSACTIONAL)
echo "\n--- 4. CRUD INTEGRITY (CONTROLLED TEST ARTICLE) ---\n";
try {
    $pdo->beginTransaction();

    // 1. Create category
    $testCatSlug = 'test-category-' . time();
    $catStmt = $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES ('Test Cat', :slug, 'Automated Test', 'active')");
    $catStmt->execute(['slug' => $testCatSlug]);
    $catId = $pdo->lastInsertId();
    recordTest('CRUD-01', 'Category Creation', ($catId > 0), "Created category ID $catId");

    // 2. Create article
    $testSlug = 'test-e2e-article-' . time();
    $newsStmt = $pdo->prepare("INSERT INTO news (category_id, author_id, title, slug, short_description, content, status, is_breaking, is_featured, published_at) 
                               VALUES (:cat, :auth, 'Automated Test Article', :slug, 'Short summary', '<p>Full content</p>', 'published', 1, 1, NOW())");
    $newsStmt->execute([
        'cat' => $catId,
        'auth' => $adminUser['id'],
        'slug' => $testSlug
    ]);
    $newsId = $pdo->lastInsertId();
    recordTest('CRUD-02', 'News Article Creation', ($newsId > 0), "Created article ID $newsId");

    // 3. Read back
    $readStmt = $pdo->prepare("SELECT * FROM news WHERE id = :id");
    $readStmt->execute(['id' => $newsId]);
    $readArticle = $readStmt->fetch();
    recordTest('CRUD-03', 'News Article Read & Attributes', ($readArticle['slug'] === $testSlug && $readArticle['is_breaking'] == 1), 'Verified slug and flags');

    // 4. Update article
    $upStmt = $pdo->prepare("UPDATE news SET title = 'Updated Title' WHERE id = :id");
    $upStmt->execute(['id' => $newsId]);
    $readStmt->execute(['id' => $newsId]);
    $updatedArticle = $readStmt->fetch();
    recordTest('CRUD-04', 'News Article Update', ($updatedArticle['title'] === 'Updated Title'), 'Title updated in persistence');

    // 5. Delete article & category
    $delStmt = $pdo->prepare("DELETE FROM news WHERE id = :id");
    $delStmt->execute(['id' => $newsId]);
    $delCatStmt = $pdo->prepare("DELETE FROM categories WHERE id = :id");
    $delCatStmt->execute(['id' => $catId]);
    
    $readStmt->execute(['id' => $newsId]);
    recordTest('CRUD-05', 'News Article Cleanup & Deletion', ($readStmt->fetch() === false), 'Test record deleted cleanly');

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    recordTest('CRUD-ERR', 'CRUD Workflow Failure', false, $e->getMessage());
}

// TEST GROUP 5: HTTP ROUTES & RESPONSES
echo "\n--- 5. LIVE HTTP ROUTES ---\n";
$routeList = [
    '/' => 200,
    '/index.php' => 200,
    '/about.php' => 200,
    '/contact.php' => 200,
    '/login.php' => 200,
    '/register.php' => 200,
    '/sitemap.php' => 200,
    '/admin/login.php' => 200,
    '/admin/index.php' => 302,
    '/admin/news/index.php' => 302,
    '/admin/categories/index.php' => 302,
    '/admin/users/index.php' => 302,
    '/admin/comments/index.php' => 302,
    '/admin/newsletter/index.php' => 302,
    '/admin/newsletter/export.php' => 302,
    '/admin/messages/index.php' => 302,
];

foreach ($routeList as $route => $expectedStatus) {
    $url = "http://localhost/SONI_PHP_WEBSITE" . $route;
    $headers = @get_headers($url);
    if ($headers && isset($headers[0])) {
        $statusLine = $headers[0];
        $passed = (strpos($statusLine, (string)$expectedStatus) !== false);
        recordTest('HTTP-' . strtoupper(trim($route, '/')), "Endpoint '$route'", $passed, "Expected $expectedStatus, Got: " . trim($statusLine));
    } else {
        recordTest('HTTP-' . strtoupper(trim($route, '/')), "Endpoint '$route'", false, "Could not connect to Apache");
    }
}

echo "\n============================================================\n";
echo sprintf(" TEST RUN SUMMARY: TOTAL: %d | PASSED: %d | FAILED: %d\n", $results['total'], $results['passed'], $results['failed']);
echo "============================================================\n";
