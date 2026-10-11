<?php
require_once __DIR__ . '/config/database.php';
$pdo = connectDB();

$stmt = $pdo->query("SELECT slug FROM news WHERE status='published' LIMIT 1");
$slug = $stmt->fetchColumn() ?: 'test-slug';

$newsUrl = "http://localhost/SONI_PHP_WEBSITE/news.php?slug=" . urlencode($slug);

$urls = [
    "http://localhost/SONI_PHP_WEBSITE/index.php",
    $newsUrl,
    "http://localhost/SONI_PHP_WEBSITE/category.php?slug=education",
    "http://localhost/SONI_PHP_WEBSITE/search.php?q=education",
    "http://localhost/SONI_PHP_WEBSITE/about.php",
    "http://localhost/SONI_PHP_WEBSITE/contact.php",
    "http://localhost/SONI_PHP_WEBSITE/login.php",
    "http://localhost/SONI_PHP_WEBSITE/register.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/login.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/news/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/categories/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/users/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/comments/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/newsletter/index.php",
    "http://localhost/SONI_PHP_WEBSITE/admin/messages/index.php"
];

foreach ($urls as $url) {
    $headers = @get_headers($url);
    if (!$headers) {
        echo "FAIL: $url (Could not connect)\n";
        continue;
    }
    $statusLine = $headers[0];
    if (strpos($statusLine, '200 OK') !== false) {
        echo "PASS: $url (Status: 200)\n";
    } elseif (strpos($statusLine, '302') !== false) {
        echo "PASS: $url (Redirect 302)\n";
    } else {
        echo "FAIL: $url ($statusLine)\n";
    }
}
?>
