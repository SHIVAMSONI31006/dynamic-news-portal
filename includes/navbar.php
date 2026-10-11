<?php
/**
 * ============================================================
 * PUBLIC NAVBAR — News Portal
 * ============================================================
 */

$navCategories = [];
$breakingNews = [];
try {
    require_once __DIR__ . '/../config/database.php';
    $pdo = connectDB();
    $stmt = $pdo->query("SELECT name, slug FROM categories WHERE status = 'active' ORDER BY name ASC");
    $navCategories = $stmt->fetchAll();
    
    // Fetch breaking news (strictly published stories marked as breaking)
    $breakStmt = $pdo->query("SELECT title, slug FROM news WHERE status = 'published' AND is_breaking = 1 ORDER BY published_at DESC LIMIT 5");
    $breakingNews = $breakStmt->fetchAll();
} catch (Exception $e) {
    $navCategories = [];
    $breakingNews = [];
}

$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>

<!-- ============ TOP INFORMATION BAR ============ -->
<div class="top-bar">
    <div class="container d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-4">
            <span class="d-flex align-items-center gap-2">
                <i class="far fa-calendar-alt"></i> <?= date('l, d F Y') ?>
            </span>
            <span class="d-flex align-items-center gap-2 d-none d-md-flex">
                <i class="fas fa-globe"></i> National Edition &bull; Independent Journalism
            </span>
        </div>
        <div class="d-flex align-items-center gap-4">
            <a href="<?= url('about.php') ?>" class="d-none d-sm-inline">About</a>
            <a href="<?= url('contact.php') ?>" class="d-none d-sm-inline">Contact</a>
            <div class="top-bar-social d-none d-lg-flex">
                <a href="#" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="#" title="X / Twitter"><i class="fab fa-x-twitter"></i></a>
                <a href="#" title="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="#" title="YouTube"><i class="fab fa-youtube"></i></a>
            </div>
        </div>
    </div>
</div>

<!-- ============ MAIN NAVBAR ============ -->
<nav class="navbar navbar-expand-lg main-navbar">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand" href="<?= url('index.php') ?>" style="display: flex; flex-direction: column; align-items: center;">
            <span style="font-family: var(--font-display); font-size: 2.5rem; font-weight: 900; letter-spacing: -1px; line-height: 1; color: var(--ink);">THE NEWSROOM</span>
            <span class="brand-subtitle" style="font-family: var(--font-body); font-size: 0.65rem; letter-spacing: 4px; text-transform: uppercase; margin-top: 4px; font-weight: 600;">Global &bull; Perspective &bull; Insight</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links (Centered) -->
        <div class="collapse navbar-collapse justify-content-center" id="navbarMain">
            <ul class="navbar-nav">
                <li class="nav-item <?= $currentScript === 'index.php' ? 'active' : '' ?>">
                    <a class="nav-link" href="<?= url('index.php') ?>">Home</a>
                </li>
                <li class="nav-item <?= $currentScript === 'latest.php' ? 'active' : '' ?>">
                    <a class="nav-link" href="<?= url('search.php?sort=latest') ?>">Latest</a>
                </li>

                <li class="nav-item dropdown <?= $currentScript === 'category.php' ? 'active' : '' ?>">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Categories
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (!empty($navCategories)): ?>
                            <?php foreach ($navCategories as $cat): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= buildUrl('category.php', ['slug' => $cat['slug']]) ?>">
                                        <?= e($cat['name']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </li>
                
                <li class="nav-item <?= $currentScript === 'trending.php' ? 'active' : '' ?>">
                    <a class="nav-link" href="<?= url('search.php?sort=trending') ?>">Trending</a>
                </li>
                <li class="nav-item <?= $currentScript === 'about.php' ? 'active' : '' ?>">
                    <a class="nav-link" href="<?= url('about.php') ?>">About</a>
                </li>
                <li class="nav-item <?= $currentScript === 'contact.php' ? 'active' : '' ?>">
                    <a class="nav-link" href="<?= url('contact.php') ?>">Contact</a>
                </li>
            </ul>
        </div>

        <!-- Right Icons / Auth -->
        <div class="d-flex align-items-center">
            <a href="<?= url('search.php') ?>" class="nav-icon-link" title="Search">
                <i class="fas fa-search"></i>
            </a>
            
            <?php if (isLoggedIn()): ?>
                <div class="dropdown">
                    <a class="btn-nav-login dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" style="text-decoration:none;">
                        <i class="far fa-user"></i> <?= e(currentUserName()) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <?php if (isAdminOrEditor()): ?>
                            <li><a class="dropdown-item" href="<?= url('admin/index.php') ?>"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><hr class="dropdown-divider"></li>
                        <?php endif; ?>
                        <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="btn-nav-login" style="text-decoration:none;">
                    <i class="far fa-user"></i> Login
                </a>
                <a href="<?= url('register.php') ?>" class="btn btn-nav-register" style="text-decoration:none;">
                    Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ============ BREAKING NEWS BAR ============ -->
<?php if (!empty($breakingNews)): ?>
<div class="breaking-bar" role="region" aria-label="Breaking News">
    <div class="container">
        <span class="breaking-label">
            <i class="fas fa-bolt" aria-hidden="true"></i> BREAKING
        </span>
        <div class="breaking-text" id="breakingText" aria-live="polite">
            <a href="<?= buildUrl('news.php', ['slug' => $breakingNews[0]['slug']]) ?>">
                <?= e($breakingNews[0]['title']) ?>
            </a>
        </div>
        <?php if (count($breakingNews) > 1): ?>
        <div class="breaking-nav">
            <button type="button" id="breakingPrev" aria-label="Previous breaking story"><i class="fas fa-chevron-left" aria-hidden="true"></i></button>
            <button type="button" id="breakingNext" aria-label="Next breaking story"><i class="fas fa-chevron-right" aria-hidden="true"></i></button>
        </div>
        <?php endif; ?>
    </div>
</div>
<script>
(function() {
    var items = <?= json_encode(array_map(function($n) {
        return ['title' => $n['title'], 'slug' => $n['slug']];
    }, $breakingNews)) ?>;
    if (items.length <= 1) return;
    var idx = 0;
    var isPaused = false;
    var el = document.getElementById('breakingText');
    var base = '<?= url('news.php') ?>';
    function show(i) {
        idx = (i + items.length) % items.length;
        el.innerHTML = '<a href="' + base + '?slug=' + encodeURIComponent(items[idx].slug) + '">' + items[idx].title.replace(/</g,'&lt;') + '</a>';
    }
    var prev = document.getElementById('breakingPrev');
    var next = document.getElementById('breakingNext');
    if (prev) prev.addEventListener('click', function() { show(idx - 1); });
    if (next) next.addEventListener('click', function() { show(idx + 1); });
    
    // Pause cycling on hover or focus
    el.addEventListener('mouseenter', function() { isPaused = true; });
    el.addEventListener('mouseleave', function() { isPaused = false; });
    el.addEventListener('focusin', function() { isPaused = true; });
    el.addEventListener('focusout', function() { isPaused = false; });
    
    setInterval(function() {
        if (!isPaused) {
            show(idx + 1);
        }
    }, 5500);
})();
</script>
<?php endif; ?>

<div class="container mt-3">
    <?= renderFlashMessage() ?>
</div>
