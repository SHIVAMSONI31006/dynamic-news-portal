<?php
/**
 * ============================================================
 * CATEGORY PAGE — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$slug = $_GET['slug'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

if (empty($slug)) {
    require_once __DIR__ . '/errors/404.php';
    exit;
}

try {
    $pdo = connectDB();
    
    // 1. Fetch category
    $catSql = "SELECT * FROM categories WHERE slug = :slug AND status = 'active'";
    $catStmt = $pdo->prepare($catSql);
    $catStmt->execute(['slug' => $slug]);
    $category = $catStmt->fetch();

    if (!$category) {
        require_once __DIR__ . '/errors/404.php';
        exit;
    }

    $categoryId = $category['id'];

    // 2. Count total published articles for pagination
    $countSql = "SELECT COUNT(*) FROM news WHERE category_id = :cat_id AND status = 'published'";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute(['cat_id' => $categoryId]);
    $totalItems = $countStmt->fetchColumn();

    $itemsPerPage = 6;
    $pagination = paginate($totalItems, $itemsPerPage, $page);

    // 3. Fetch paginated news
    $newsList = [];
    if ($totalItems > 0) {
        $newsSql = "SELECT n.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
                    FROM news n 
                    JOIN categories c ON n.category_id = c.id 
                    JOIN users u ON n.author_id = u.id 
                    WHERE n.category_id = :cat_id AND n.status = 'published'
                    ORDER BY n.published_at DESC 
                    LIMIT :limit OFFSET :offset";
        $newsStmt = $pdo->prepare($newsSql);
        $newsStmt->bindValue(':cat_id', $categoryId, PDO::PARAM_INT);
        $newsStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
        $newsStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
        $newsStmt->execute();
        $newsList = $newsStmt->fetchAll();
    }

} catch (Exception $e) {
    setFlashMessage("An error occurred while loading the category.", "error");
    redirect('index.php');
    exit;
}

$pageTitle = $category['name'] . ' News | News Portal';
$pageMetaDesc = excerpt($category['description'], 160) ?: "Latest " . $category['name'] . " news and updates.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// First article for featured display
$featuredArticle = $newsList[0] ?? null;
$remainingNews = array_slice($newsList, 1);
?>

<main class="category-page py-5">
    <div class="container">
        
        <!-- Category Header -->
        <div class="text-center mb-5" style="border-bottom: 2px solid var(--ink); padding-bottom: 40px;">
            <span class="category-badge mb-3" style="font-size: 0.85rem; padding: 6px 18px; border-radius: 0; letter-spacing: 1px;">THE SECTION</span>
            <h1 class="section-title mt-2 mb-3" style="font-size: 3.5rem; text-transform: uppercase;">
                <?= e($category['name']) ?>
            </h1>
            <?php if (!empty($category['description'])): ?>
                <p style="color: var(--muted); font-size: 1.15rem; max-width: 700px; margin: 0 auto; line-height: 1.6; font-family: var(--font-body);">
                    <?= e($category['description']) ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- News Grid -->
        <?php if (empty($newsList)): ?>
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="far fa-folder-open mb-3" style="font-size: 3.5rem; color: var(--line);"></i>
                    <h3 style="color: var(--muted); font-family: var(--font-display);">No news available</h3>
                    <p style="color: var(--muted);">There are currently no published articles in this category.</p>
                </div>
            </div>
        <?php else: ?>

            <!-- Featured Category Story -->
            <?php if ($featuredArticle): ?>
            <div class="mb-5 border-bottom pb-5" style="border-color: var(--line) !important;">
                <div class="row g-5 align-items-center">
                    <div class="col-md-7">
                        <a href="<?= buildUrl('news.php', ['slug' => $featuredArticle['slug']]) ?>" class="d-block">
                            <img src="<?= e(newsImageUrl($featuredArticle['featured_image'], $category['slug'])) ?>" alt="<?= e($featuredArticle['title']) ?>" style="width: 100%; aspect-ratio: 16/9; object-fit: cover; border-radius: 0;">
                        </a>
                    </div>
                    <div class="col-md-5">
                        <h2 style="font-family: var(--font-display); font-size: 2.2rem; font-weight: 800; line-height: 1.15; margin-bottom: 16px;">
                            <a href="<?= buildUrl('news.php', ['slug' => $featuredArticle['slug']]) ?>" style="color: var(--ink);">
                                <?= e($featuredArticle['title']) ?>
                            </a>
                        </h2>
                        <p style="color: var(--ink); font-size: 1.05rem; line-height: 1.6; margin-bottom: 24px; opacity: 0.8;">
                            <?= e(excerpt($featuredArticle['short_description'] ?: $featuredArticle['content'], 180)) ?>
                        </p>
                        <div style="font-size: 0.85rem; color: var(--muted); display: flex; gap: 16px; font-family: var(--font-body); font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                            <span><?= formatDate($featuredArticle['published_at'], 'M j, Y') ?></span>
                            <span><?= calculateReadingTime($featuredArticle['content']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Remaining Articles Grid -->
            <?php if (!empty($remainingNews)): ?>
            <div class="row g-5 mb-5">
                <?php foreach ($remainingNews as $news): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card story-card h-100 border-0 bg-transparent">
                        <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>" class="story-img-wrap" style="aspect-ratio: 4/3; border-radius: 0;">
                            <img src="<?= e(newsImageUrl($news['featured_image'], $category['slug'])) ?>" alt="<?= e($news['title']) ?>" loading="lazy">
                        </a>
                        <div class="card-body px-0 pt-4 flex-column">
                            <h4 class="story-title" style="font-size: 1.4rem;">
                                <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>">
                                    <?= e($news['title']) ?>
                                </a>
                            </h4>
                            <p class="story-excerpt flex-grow-1" style="font-size: 0.95rem;">
                                <?= e(excerpt($news['short_description'] ?: $news['content'], 110)) ?>
                            </p>
                            <div class="story-meta mt-3 text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                                <span><?= formatDate($news['published_at'], 'M j, Y') ?></span>
                                <span>&bull; <?= calculateReadingTime($news['content']) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Pagination Controls -->
            <?php if ($pagination['total_pages'] > 1): ?>
                <nav aria-label="Category page navigation">
                    <ul class="pagination justify-content-center">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildUrl('category.php', ['slug' => $slug, 'page' => $pagination['current_page'] - 1]) ?>">Previous</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Previous</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildUrl('category.php', ['slug' => $slug, 'page' => $i]) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildUrl('category.php', ['slug' => $slug, 'page' => $pagination['current_page'] + 1]) ?>">Next</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Next</span></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
