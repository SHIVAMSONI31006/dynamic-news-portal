<?php
/**
 * ============================================================
 * XML SITEMAP CONTROLLER — Dynamic News Portal
 * ============================================================
 * Generates an SEO-compliant XML sitemap for search engines.
 * Strictly includes only published articles and public pages.
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/xml; charset=utf-8');

try {
    $pdo = connectDB();

    // Fetch active categories
    $catStmt = $pdo->query("SELECT slug, updated_at FROM categories WHERE status = 'active' ORDER BY name ASC");
    $categories = $catStmt->fetchAll();

    // Fetch published news articles
    $newsStmt = $pdo->query("SELECT slug, published_at, updated_at FROM news WHERE status = 'published' ORDER BY published_at DESC");
    $articles = $newsStmt->fetchAll();

} catch (Exception $e) {
    $categories = [];
    $articles = [];
}

$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$baseUrl = defined('APP_URL') && APP_URL !== '' ? APP_URL : $protocol . '://' . $host . url();
$baseUrl = rtrim($baseUrl, '/');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <!-- Main Public Pages -->
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/index.php', ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>hourly</changefreq>
        <priority>1.0</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/about.php', ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/contact.php', ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d') ?></lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>

    <!-- Categories -->
    <?php foreach ($categories as $cat): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/category.php?slug=' . urlencode($cat['slug']), ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($cat['updated_at'] ?? 'now')) ?></lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
    </url>
    <?php endforeach; ?>

    <!-- Published News Articles -->
    <?php foreach ($articles as $art): ?>
    <url>
        <loc><?= htmlspecialchars($baseUrl . '/news.php?slug=' . urlencode($art['slug']), ENT_XML1, 'UTF-8') ?></loc>
        <lastmod><?= date('Y-m-d', strtotime($art['updated_at'] ?? $art['published_at'])) ?></lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.9</priority>
    </url>
    <?php endforeach; ?>
</urlset>
