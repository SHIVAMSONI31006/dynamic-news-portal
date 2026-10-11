<?php
/**
 * ============================================================
 * DATABASE CONNECTION TEST — Development Only
 * ============================================================
 * Verifies the complete database setup:
 *   1. PHP is working
 *   2. PDO extension is available
 *   3. MySQL connection succeeds
 *   4. news_portal database is accessible
 *   5. All 7 tables exist
 *   6. Foreign keys are configured
 *   7. Seed data is present
 *
 * ⚠️  DELETE THIS FILE before deployment or demonstration.
 *     It exposes diagnostic information.
 *
 * URL: http://localhost/news-portal/database_test.php
 * ============================================================
 */

// --- Start output ---
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Test — News Portal</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: "Segoe UI", system-ui, -apple-system, sans-serif; background: #f0f2f5; padding: 24px; color: #212529; }
        .container { max-width: 720px; margin: 0 auto; }
        h1 { color: #1a1a2e; margin-bottom: 8px; font-size: 24px; }
        .subtitle { color: #6c757d; margin-bottom: 24px; font-size: 14px; }
        .card { background: #fff; border-radius: 10px; padding: 24px; margin-bottom: 16px; box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
        .card h2 { font-size: 16px; color: #1a1a2e; margin-bottom: 12px; border-bottom: 2px solid #f0f2f5; padding-bottom: 8px; }
        .check { display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f8f9fa; }
        .check:last-child { border-bottom: none; }
        .check-label { flex: 1; font-size: 14px; }
        .pass { color: #198754; font-weight: 600; }
        .fail { color: #dc3545; font-weight: 600; }
        .warn { color: #f5a623; font-weight: 600; }
        .icon { margin-right: 8px; font-size: 16px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 14px; }
        th { background: #1a1a2e; color: #fff; padding: 8px 12px; text-align: left; }
        td { padding: 8px 12px; border-bottom: 1px solid #e9ecef; }
        .warning-box { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 14px; margin-top: 20px; font-size: 13px; }
        .summary { background: #d4edda; border: 1px solid #198754; border-radius: 8px; padding: 14px; margin-top: 20px; font-size: 14px; }
        .summary.error { background: #f8d7da; border-color: #dc3545; }
    </style>
</head>
<body>
<div class="container">
<h1>🔍 Database Connection Test</h1>
<p class="subtitle">News Portal — Development Diagnostic Tool</p>';

$totalChecks = 0;
$passedChecks = 0;

// ---------------------------------------------------------
// CHECK 1: PHP Version
// ---------------------------------------------------------
echo '<div class="card"><h2>1. PHP Environment</h2>';
$phpVersion = phpversion();
$phpOk = version_compare($phpVersion, '8.0.0', '>=');
$totalChecks++;
if ($phpOk) $passedChecks++;
echo '<div class="check">
    <span class="icon">' . ($phpOk ? '✅' : '❌') . '</span>
    <span class="check-label">PHP Version</span>
    <span class="' . ($phpOk ? 'pass' : 'fail') . '">' . $phpVersion . ($phpOk ? '' : ' (requires 8.0+)') . '</span>
</div>';

// CHECK 2: PDO Extension
$pdoLoaded = extension_loaded('pdo_mysql');
$totalChecks++;
if ($pdoLoaded) $passedChecks++;
echo '<div class="check">
    <span class="icon">' . ($pdoLoaded ? '✅' : '❌') . '</span>
    <span class="check-label">PDO MySQL Extension</span>
    <span class="' . ($pdoLoaded ? 'pass' : 'fail') . '">' . ($pdoLoaded ? 'Loaded' : 'NOT LOADED') . '</span>
</div>';
echo '</div>';

// ---------------------------------------------------------
// CHECK 3: Database Connection
// ---------------------------------------------------------
echo '<div class="card"><h2>2. Database Connection</h2>';
$dbConnected = false;
$pdo = null;

try {
    require_once __DIR__ . '/config/database.php';
    $pdo = connectDB();
    $dbConnected = true;
    $totalChecks++;
    $passedChecks++;
    echo '<div class="check">
        <span class="icon">✅</span>
        <span class="check-label">PDO Connection</span>
        <span class="pass">Connected</span>
    </div>';

    // Database name check
    $dbName = $pdo->query("SELECT DATABASE()")->fetchColumn();
    $dbNameOk = ($dbName === 'news_portal');
    $totalChecks++;
    if ($dbNameOk) $passedChecks++;
    echo '<div class="check">
        <span class="icon">' . ($dbNameOk ? '✅' : '❌') . '</span>
        <span class="check-label">Database Name</span>
        <span class="' . ($dbNameOk ? 'pass' : 'fail') . '">' . htmlspecialchars($dbName) . '</span>
    </div>';

} catch (Exception $e) {
    $totalChecks++;
    echo '<div class="check">
        <span class="icon">❌</span>
        <span class="check-label">PDO Connection</span>
        <span class="fail">FAILED — ' . htmlspecialchars($e->getMessage()) . '</span>
    </div>';
}
echo '</div>';

// ---------------------------------------------------------
// CHECK 4: Tables
// ---------------------------------------------------------
if ($dbConnected && $pdo) {
    echo '<div class="card"><h2>3. Tables</h2>';

    $expectedTables = ['users', 'categories', 'news', 'comments', 'likes', 'contact_messages', 'newsletter_subscribers'];
    $existingTables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

    echo '<table><tr><th>Table</th><th>Status</th><th>Rows</th></tr>';

    foreach ($expectedTables as $table) {
        $exists = in_array($table, $existingTables);
        $totalChecks++;
        if ($exists) $passedChecks++;

        $rowCount = '-';
        if ($exists) {
            $rowCount = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        }

        echo '<tr>
            <td><code>' . $table . '</code></td>
            <td class="' . ($exists ? 'pass' : 'fail') . '">' . ($exists ? '✅ Exists' : '❌ Missing') . '</td>
            <td>' . $rowCount . '</td>
        </tr>';
    }

    echo '</table>';

    // Extra tables check
    $extraTables = array_diff($existingTables, $expectedTables);
    if (!empty($extraTables)) {
        echo '<div class="check" style="margin-top: 8px;">
            <span class="icon">⚠️</span>
            <span class="check-label">Unexpected tables found</span>
            <span class="warn">' . implode(', ', $extraTables) . '</span>
        </div>';
    }

    $tableCount = count(array_intersect($expectedTables, $existingTables));
    echo '<div class="check" style="margin-top: 8px;">
        <span class="icon">' . ($tableCount === 7 ? '✅' : '❌') . '</span>
        <span class="check-label">Total Expected Tables</span>
        <span class="' . ($tableCount === 7 ? 'pass' : 'fail') . '">' . $tableCount . ' / 7</span>
    </div>';

    echo '</div>';

    // ---------------------------------------------------------
    // CHECK 5: Foreign Keys
    // ---------------------------------------------------------
    echo '<div class="card"><h2>4. Foreign Keys</h2>';

    $fkQuery = "SELECT
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = 'news_portal'
      AND REFERENCED_TABLE_NAME IS NOT NULL
    ORDER BY TABLE_NAME, CONSTRAINT_NAME";

    $fks = $pdo->query($fkQuery)->fetchAll();

    $expectedFKs = [
        'fk_news_category'  => ['news', 'category_id', 'categories', 'id'],
        'fk_news_author'    => ['news', 'author_id', 'users', 'id'],
        'fk_comments_news'  => ['comments', 'news_id', 'news', 'id'],
        'fk_comments_user'  => ['comments', 'user_id', 'users', 'id'],
        'fk_likes_news'     => ['likes', 'news_id', 'news', 'id'],
        'fk_likes_user'     => ['likes', 'user_id', 'users', 'id'],
    ];

    echo '<table><tr><th>Constraint</th><th>Table.Column</th><th>References</th><th>Status</th></tr>';

    $foundFKs = [];
    foreach ($fks as $fk) {
        $foundFKs[$fk['CONSTRAINT_NAME']] = $fk;
    }

    foreach ($expectedFKs as $name => $spec) {
        $found = isset($foundFKs[$name]);
        $totalChecks++;
        if ($found) $passedChecks++;

        echo '<tr>
            <td><code>' . $name . '</code></td>
            <td>' . $spec[0] . '.' . $spec[1] . '</td>
            <td>' . $spec[2] . '.' . $spec[3] . '</td>
            <td class="' . ($found ? 'pass' : 'fail') . '">' . ($found ? '✅' : '❌ Missing') . '</td>
        </tr>';
    }
    echo '</table></div>';

    // ---------------------------------------------------------
    // CHECK 6: Seed Data
    // ---------------------------------------------------------
    echo '<div class="card"><h2>5. Seed Data</h2>';

    // Admin user
    $admin = $pdo->query("SELECT id, name, email, role, status, LENGTH(password) as pw_len FROM users WHERE role = 'admin' LIMIT 1")->fetch();
    $adminOk = $admin && $admin['pw_len'] >= 60; // bcrypt hashes are 60 chars
    $totalChecks++;
    if ($adminOk) $passedChecks++;
    echo '<div class="check">
        <span class="icon">' . ($adminOk ? '✅' : '❌') . '</span>
        <span class="check-label">Admin user exists with hashed password</span>
        <span class="' . ($adminOk ? 'pass' : 'fail') . '">' .
        ($admin ? htmlspecialchars($admin['email']) . ' (hash length: ' . $admin['pw_len'] . ')' : 'NOT FOUND') .
        '</span>
    </div>';

    // Password hash verification
    if ($admin) {
        $adminFull = $pdo->query("SELECT password FROM users WHERE role = 'admin' LIMIT 1")->fetch();
        $hashValid = password_verify('NewsPortal@2026', $adminFull['password']);
        $totalChecks++;
        if ($hashValid) $passedChecks++;
        echo '<div class="check">
            <span class="icon">' . ($hashValid ? '✅' : '❌') . '</span>
            <span class="check-label">Admin password verification (NewsPortal@2026)</span>
            <span class="' . ($hashValid ? 'pass' : 'fail') . '">' . ($hashValid ? 'VERIFIED' : 'FAILED') . '</span>
        </div>';
    }

    // Categories count
    $catCount = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();
    $catOk = ($catCount >= 9);
    $totalChecks++;
    if ($catOk) $passedChecks++;
    echo '<div class="check">
        <span class="icon">' . ($catOk ? '✅' : '⚠️') . '</span>
        <span class="check-label">Demo categories</span>
        <span class="' . ($catOk ? 'pass' : 'warn') . '">' . $catCount . ' categories</span>
    </div>';

    // News count
    $newsCount = $pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
    $newsOk = ($newsCount >= 1);
    $totalChecks++;
    if ($newsOk) $passedChecks++;
    echo '<div class="check">
        <span class="icon">' . ($newsOk ? '✅' : '⚠️') . '</span>
        <span class="check-label">Demo news articles</span>
        <span class="' . ($newsOk ? 'pass' : 'warn') . '">' . $newsCount . ' articles</span>
    </div>';

    echo '</div>';

    // ---------------------------------------------------------
    // CHECK 7: Indexes
    // ---------------------------------------------------------
    echo '<div class="card"><h2>6. Key Indexes</h2>';

    $indexChecks = [
        ['users',    'uk_users_email',            'email UNIQUE'],
        ['categories', 'uk_categories_slug',      'slug UNIQUE'],
        ['categories', 'uk_categories_name',      'name UNIQUE'],
        ['news',     'uk_news_slug',              'slug UNIQUE'],
        ['news',     'idx_news_status_published', 'status + published_at'],
        ['news',     'idx_news_category',         'category_id'],
        ['news',     'idx_news_featured',         'is_featured'],
        ['news',     'idx_news_breaking',         'is_breaking'],
        ['comments', 'idx_comments_news_status',  'news_id + status'],
        ['likes',    'uk_likes_user_news',        'news_id + user_id UNIQUE'],
        ['newsletter_subscribers', 'uk_newsletter_email', 'email UNIQUE'],
    ];

    echo '<table><tr><th>Table</th><th>Index Name</th><th>Columns</th><th>Status</th></tr>';
    foreach ($indexChecks as $ic) {
        $idxResult = $pdo->query("SHOW INDEX FROM `{$ic[0]}` WHERE Key_name = '{$ic[1]}'")->fetchAll();
        $exists = count($idxResult) > 0;
        $totalChecks++;
        if ($exists) $passedChecks++;
        echo '<tr>
            <td>' . $ic[0] . '</td>
            <td><code>' . $ic[1] . '</code></td>
            <td>' . $ic[2] . '</td>
            <td class="' . ($exists ? 'pass' : 'fail') . '">' . ($exists ? '✅' : '❌') . '</td>
        </tr>';
    }
    echo '</table></div>';
}

// ---------------------------------------------------------
// SUMMARY
// ---------------------------------------------------------
$allPassed = ($passedChecks === $totalChecks);
echo '<div class="summary' . ($allPassed ? '' : ' error') . '">';
echo '<strong>' . ($allPassed ? '✅ ALL CHECKS PASSED' : '⚠️ SOME CHECKS FAILED') . '</strong><br>';
echo 'Result: <strong>' . $passedChecks . ' / ' . $totalChecks . ' checks passed</strong>';
echo '</div>';

echo '<div class="warning-box">
    <strong>⚠️ Development Only:</strong> This diagnostic file must be deleted before 
    project submission or demonstration. It exposes database structure information.
</div>';

echo '</div></body></html>';
