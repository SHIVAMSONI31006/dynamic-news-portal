<?php
/**
 * ============================================================
 * PUBLIC HOMEPAGE — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

try {
    $pdo = connectDB();
} catch (Exception $e) {
    die("Database connection failed. Please try again later.");
}

function getNewsQuery(PDO $pdo, string $where, string $orderBy, int $limit = 5): array
{
    $sql = "SELECT n.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
            FROM news n 
            JOIN categories c ON n.category_id = c.id 
            JOIN users u ON n.author_id = u.id 
            WHERE n.status = 'published' AND $where 
            ORDER BY $orderBy 
            LIMIT $limit";
    return $pdo->query($sql)->fetchAll();
}

// 1. HERO / FEATURED NEWS (up to 3 for carousel)
$heroNewsList = getNewsQuery($pdo, "n.is_featured = 1", "n.published_at DESC", 3);
if (empty($heroNewsList)) {
    $heroNewsList = getNewsQuery($pdo, "1=1", "n.published_at DESC", 3);
}
$heroArticle = $heroNewsList[0] ?? null;

$heroIds = array_column($heroNewsList, 'id');
$heroId = $heroArticle ? $heroArticle['id'] : 0;

// 2. MOST READ (Trending)
$trendingNews = getNewsQuery($pdo, "1=1", "n.views DESC, n.published_at DESC", 5);

// 3. LATEST STORIES
$latestNews = getNewsQuery($pdo, "n.id != " . (int)$heroId, "n.published_at DESC", 4);

// Helper to fetch news by category slugs
function getCategoryNews(PDO $pdo, array $slugs, int $limit = 3): array
{
    if (empty($slugs)) return [];
    $placeholders = implode(',', array_fill(0, count($slugs), '?'));
    $sql = "SELECT n.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
            FROM news n 
            JOIN categories c ON n.category_id = c.id 
            JOIN users u ON n.author_id = u.id 
            WHERE n.status = 'published' AND c.slug IN ($placeholders)
            ORDER BY n.published_at DESC 
            LIMIT ?";
    $stmt = $pdo->prepare($sql);
    $idx = 1;
    foreach ($slugs as $slug) {
        $stmt->bindValue($idx++, $slug, PDO::PARAM_STR);
    }
    $stmt->bindValue($idx, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

// 4. Dedicated Editorial Category Sections
$indiaGujaratNews = getCategoryNews($pdo, ['india', 'gujarat'], 3);
$techAiNews       = getCategoryNews($pdo, ['technology', 'ai-ml'], 3);
$bizEduNews       = getCategoryNews($pdo, ['business', 'education'], 3);
$worldCultureNews = getCategoryNews($pdo, ['world', 'sports', 'entertainment'], 3);

// 5. CATEGORIES (For Icon Grid)
$categoriesWithNews = $pdo->query("SELECT name, slug FROM categories WHERE status = 'active' ORDER BY name ASC LIMIT 8")->fetchAll();

// Handle newsletter subscription
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'newsletter_subscribe') {
    require_once __DIR__ . '/includes/csrf.php';
    if (isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        $email = trim($_POST['newsletter_email'] ?? '');
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, status, created_at) VALUES (:email, 'active', NOW()) ON DUPLICATE KEY UPDATE status='active'");
                $stmt->execute(['email' => $email]);
                setFlashMessage('Successfully subscribed to the newsletter!', 'success');
            } catch (Exception $e) {
                setFlashMessage('Subscription failed. Please try again.', 'error');
            }
        } else {
            setFlashMessage('Please enter a valid email address.', 'error');
        }
    }
    redirect('index.php');
}

$pageTitle = 'Home';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="homepage">

    <!-- ============ HERO + EDITOR'S PICKS ============ -->
    <div class="container hero-section">
        <div class="hero-grid">
            
            <!-- Left: Hero Story (Lead) -->
            <div class="hero-lead">
                <?php if (!empty($heroNewsList)): ?>
                <?php $leadStory = $heroNewsList[0]; ?>
                <div class="hero-carousel shadow-sm">
                    <img src="<?= e(newsImageUrl($leadStory['featured_image'], $leadStory['category_slug'] ?? null)) ?>" alt="<?= e($leadStory['title']) ?>">
                    <div class="hero-overlay">
                        <a href="<?= buildUrl('category.php', ['slug' => $leadStory['category_slug']]) ?>" class="category-badge">
                            <?= e($leadStory['category_name']) ?>
                        </a>
                        <h1 class="hero-title">
                            <a href="<?= buildUrl('news.php', ['slug' => $leadStory['slug']]) ?>" class="text-white">
                                <?= e($leadStory['title']) ?>
                            </a>
                        </h1>
                        <p class="hero-excerpt">
                            <?= e(excerpt($leadStory['short_description'] ?: $leadStory['content'], 180)) ?>
                        </p>
                        <div class="hero-meta">
                            <span><i class="far fa-user"></i> <?= e($leadStory['author_name']) ?></span>
                            <span><i class="far fa-calendar-alt"></i> <?= formatDate($leadStory['published_at'], 'd M Y') ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Right: Secondary / Featured Stories -->
            <div class="hero-secondary">
                <div class="section-header">
                    <h3 class="section-title">Editor's Picks</h3>
                </div>
                <div class="d-flex flex-column gap-4">
                    <?php 
                    $secondaryFeatured = array_slice($heroNewsList, 1);
                    if (empty($secondaryFeatured)) {
                        $secondaryFeatured = array_slice($latestNews, 0, 2);
                    }
                    foreach ($secondaryFeatured as $sec): 
                    ?>
                    <div class="story-card d-flex gap-3 align-items-center">
                        <a href="<?= buildUrl('news.php', ['slug' => $sec['slug']]) ?>" style="width: 120px; height: 120px; flex-shrink: 0;">
                            <img src="<?= e(newsImageUrl($sec['featured_image'], $sec['category_slug'] ?? null)) ?>" alt="<?= e($sec['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                        </a>
                        <div>
                            <span class="story-cat"><?= e($sec['category_name']) ?></span>
                            <h4 class="story-title" style="font-size: 1.15rem; margin-bottom: 6px;">
                                <a href="<?= buildUrl('news.php', ['slug' => $sec['slug']]) ?>">
                                    <?= e($sec['title']) ?>
                                </a>
                            </h4>
                            <div class="story-meta">
                                <?= formatDate($sec['published_at'], 'd M Y') ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <!-- Include Trending Mini inside Editor's Picks column if space allows -->
                    <?php if (!empty($trendingNews)): $topTrend = $trendingNews[0]; ?>
                    <div class="mt-2 pt-4 border-top" style="border-color: var(--line) !important;">
                        <span class="story-cat text-muted mb-2"><i class="fas fa-chart-line me-1"></i> Top Trending</span>
                        <h4 class="story-title" style="font-size: 1.35rem;">
                            <a href="<?= buildUrl('news.php', ['slug' => $topTrend['slug']]) ?>">
                                <?= e($topTrend['title']) ?>
                            </a>
                        </h4>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
        </div>
    </div>

    <!-- ============ LATEST STORIES + NEWSLETTER ============ -->
    <div class="container" style="padding: 40px var(--container-px);">
        <div class="row g-5">
            
            <!-- Left: Latest Stories -->
            <div class="col-lg-8">
                <div class="section-header">
                    <h3 class="section-title">Latest Stories</h3>
                    <a href="<?= url('search.php') ?>" class="view-all">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                
                <div class="row g-4">
                    <?php foreach ($latestNews as $news): ?>
                    <div class="col-md-6">
                        <div class="card story-card">
                            <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>" class="story-img-wrap">
                                <img src="<?= e(newsImageUrl($news['featured_image'], $news['category_slug'] ?? null)) ?>" alt="<?= e($news['title']) ?>">
                            </a>
                            <div class="card-body p-0">
                                <span class="story-cat"><?= e($news['category_name']) ?></span>
                                <h4 class="story-title">
                                    <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>">
                                        <?= e($news['title']) ?>
                                    </a>
                                </h4>
                                <p class="story-excerpt">
                                    <?= e(excerpt($news['short_description'] ?: $news['content'], 110)) ?>
                                </p>
                                <div class="story-meta">
                                    <span><?= formatDate($news['published_at'], 'd M Y') ?></span>
                                    <span>&bull; <?= calculateReadingTime($news['content']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Right: Sidebar (Newsletter & Most Read) -->
            <div class="col-lg-4">
                
                <!-- Most Read -->
                <?php if (!empty($trendingNews)): ?>
                <div class="mb-5">
                    <div class="section-header">
                        <h3 class="section-title">Most Read</h3>
                    </div>
                    <ul class="most-read-list">
                        <?php foreach (array_slice($trendingNews, 0, 4) as $idx => $tr): ?>
                        <li class="most-read-item">
                            <div class="most-read-number"><?= sprintf('%02d', $idx + 1) ?></div>
                            <div class="most-read-content">
                                <h5>
                                    <a href="<?= buildUrl('news.php', ['slug' => $tr['slug']]) ?>">
                                        <?= e($tr['title']) ?>
                                    </a>
                                </h5>
                                <div class="most-read-meta">
                                    <?= formatDate($tr['published_at'], 'd M Y') ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- Newsletter -->
                <div class="newsletter-box mt-4">
                    <div class="news-icon-header">
                        <i class="fas fa-envelope-open-text"></i> STAY INFORMED
                    </div>
                    <h3>Get the latest news and updates.</h3>
                    <p>Stay up-to-date with the stories that matter most.</p>
                    
                    <form class="newsletter-form" action="<?= buildUrl('index.php') ?>" method="POST">
                        <?= csrfField() ?>
                        <input type="hidden" name="action" value="newsletter_subscribe">
                        <input type="email" name="newsletter_email" class="form-control" placeholder="Your email address" required>
                        <button type="submit" class="btn">Subscribe</button>
                    </form>
                    
                    <div class="newsletter-privacy">
                        <i class="fas fa-lock"></i> We respect your privacy.
                    </div>
                </div>

            </div>

        </div>
    </div>

    <!-- ============ SECTION: INDIA & GUJARAT NEWS ============ -->
    <?php if (!empty($indiaGujaratNews)): ?>
    <section class="container py-4 border-top" style="border-color: var(--line) !important;">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title m-0" style="font-family: var(--font-display); font-size: 1.6rem;">
                <span class="text-accent me-2">|</span>India &amp; Gujarat Focus
            </h3>
            <a href="<?= buildUrl('category.php', ['slug' => 'india']) ?>" class="view-all">More India News <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($indiaGujaratNews as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card story-card h-100 shadow-sm border-0" style="background: var(--paper);">
                    <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>" class="story-img-wrap" style="height: 200px; display: block; overflow: hidden;">
                        <img src="<?= e(newsImageUrl($item['featured_image'], $item['category_slug'] ?? null)) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    </a>
                    <div class="card-body p-3 d-flex flex-column">
                        <span class="story-cat mb-1"><?= e($item['category_name']) ?></span>
                        <h4 class="story-title" style="font-size: 1.15rem; line-height: 1.35; margin-bottom: 8px;">
                            <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>">
                                <?= e($item['title']) ?>
                            </a>
                        </h4>
                        <p class="story-excerpt flex-grow-1" style="font-size: 0.9rem; color: var(--muted); margin-bottom: 12px;">
                            <?= e(excerpt($item['short_description'] ?: $item['content'], 95)) ?>
                        </p>
                        <div class="story-meta mt-auto pt-2 border-top" style="border-color: var(--line) !important; font-size: 0.8rem; color: var(--muted);">
                            <span><?= formatDate($item['published_at'], 'd M Y') ?></span>
                            <span>&bull; <?= calculateReadingTime($item['content']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ SECTION: TECHNOLOGY & AI ============ -->
    <?php if (!empty($techAiNews)): ?>
    <section class="container py-4 border-top" style="border-color: var(--line) !important;">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title m-0" style="font-family: var(--font-display); font-size: 1.6rem;">
                <span class="text-accent me-2">|</span>Technology &amp; Artificial Intelligence
            </h3>
            <a href="<?= buildUrl('category.php', ['slug' => 'technology']) ?>" class="view-all">More Tech News <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($techAiNews as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card story-card h-100 shadow-sm border-0" style="background: var(--paper);">
                    <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>" class="story-img-wrap" style="height: 200px; display: block; overflow: hidden;">
                        <img src="<?= e(newsImageUrl($item['featured_image'], $item['category_slug'] ?? null)) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    </a>
                    <div class="card-body p-3 d-flex flex-column">
                        <span class="story-cat mb-1"><?= e($item['category_name']) ?></span>
                        <h4 class="story-title" style="font-size: 1.15rem; line-height: 1.35; margin-bottom: 8px;">
                            <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>">
                                <?= e($item['title']) ?>
                            </a>
                        </h4>
                        <p class="story-excerpt flex-grow-1" style="font-size: 0.9rem; color: var(--muted); margin-bottom: 12px;">
                            <?= e(excerpt($item['short_description'] ?: $item['content'], 95)) ?>
                        </p>
                        <div class="story-meta mt-auto pt-2 border-top" style="border-color: var(--line) !important; font-size: 0.8rem; color: var(--muted);">
                            <span><?= formatDate($item['published_at'], 'd M Y') ?></span>
                            <span>&bull; <?= calculateReadingTime($item['content']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ SECTION: BUSINESS & EDUCATION ============ -->
    <?php if (!empty($bizEduNews)): ?>
    <section class="container py-4 border-top" style="border-color: var(--line) !important;">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title m-0" style="font-family: var(--font-display); font-size: 1.6rem;">
                <span class="text-accent me-2">|</span>Business &amp; Education
            </h3>
            <a href="<?= buildUrl('category.php', ['slug' => 'business']) ?>" class="view-all">More Business News <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($bizEduNews as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card story-card h-100 shadow-sm border-0" style="background: var(--paper);">
                    <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>" class="story-img-wrap" style="height: 200px; display: block; overflow: hidden;">
                        <img src="<?= e(newsImageUrl($item['featured_image'], $item['category_slug'] ?? null)) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    </a>
                    <div class="card-body p-3 d-flex flex-column">
                        <span class="story-cat mb-1"><?= e($item['category_name']) ?></span>
                        <h4 class="story-title" style="font-size: 1.15rem; line-height: 1.35; margin-bottom: 8px;">
                            <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>">
                                <?= e($item['title']) ?>
                            </a>
                        </h4>
                        <p class="story-excerpt flex-grow-1" style="font-size: 0.9rem; color: var(--muted); margin-bottom: 12px;">
                            <?= e(excerpt($item['short_description'] ?: $item['content'], 95)) ?>
                        </p>
                        <div class="story-meta mt-auto pt-2 border-top" style="border-color: var(--line) !important; font-size: 0.8rem; color: var(--muted);">
                            <span><?= formatDate($item['published_at'], 'd M Y') ?></span>
                            <span>&bull; <?= calculateReadingTime($item['content']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ SECTION: WORLD, SPORTS & CULTURE ============ -->
    <?php if (!empty($worldCultureNews)): ?>
    <section class="container py-4 border-top" style="border-color: var(--line) !important;">
        <div class="section-header d-flex justify-content-between align-items-center mb-4">
            <h3 class="section-title m-0" style="font-family: var(--font-display); font-size: 1.6rem;">
                <span class="text-accent me-2">|</span>World &amp; Culture
            </h3>
            <a href="<?= buildUrl('category.php', ['slug' => 'world']) ?>" class="view-all">More World News <i class="fas fa-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($worldCultureNews as $item): ?>
            <div class="col-lg-4 col-md-6">
                <div class="card story-card h-100 shadow-sm border-0" style="background: var(--paper);">
                    <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>" class="story-img-wrap" style="height: 200px; display: block; overflow: hidden;">
                        <img src="<?= e(newsImageUrl($item['featured_image'], $item['category_slug'] ?? null)) ?>" alt="<?= e($item['title']) ?>" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy">
                    </a>
                    <div class="card-body p-3 d-flex flex-column">
                        <span class="story-cat mb-1"><?= e($item['category_name']) ?></span>
                        <h4 class="story-title" style="font-size: 1.15rem; line-height: 1.35; margin-bottom: 8px;">
                            <a href="<?= buildUrl('news.php', ['slug' => $item['slug']]) ?>">
                                <?= e($item['title']) ?>
                            </a>
                        </h4>
                        <p class="story-excerpt flex-grow-1" style="font-size: 0.9rem; color: var(--muted); margin-bottom: 12px;">
                            <?= e(excerpt($item['short_description'] ?: $item['content'], 95)) ?>
                        </p>
                        <div class="story-meta mt-auto pt-2 border-top" style="border-color: var(--line) !important; font-size: 0.8rem; color: var(--muted);">
                            <span><?= formatDate($item['published_at'], 'd M Y') ?></span>
                            <span>&bull; <?= calculateReadingTime($item['content']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- ============ CATEGORY ICONS ============ -->
    <?php if (!empty($categoriesWithNews)): ?>
    <div class="container">
        <div class="category-icons-grid">
            <?php 
            $icons = ['fa-landmark', 'fa-chart-line', 'fa-globe-americas', 'fa-film', 'fa-microchip', 'fa-futbol', 'fa-theater-masks', 'fa-laptop', 'fa-book'];
            foreach ($categoriesWithNews as $index => $cat): 
                $icon = $icons[$index % count($icons)];
            ?>
            <a href="<?= buildUrl('category.php', ['slug' => $cat['slug']]) ?>" class="text-decoration-none">
                <div class="cat-icon-box">
                    <i class="fas <?= $icon ?>"></i>
                    <span><?= e($cat['name']) ?></span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
