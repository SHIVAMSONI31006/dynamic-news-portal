<?php
/**
 * ============================================================
 * SEARCH PAGE — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$queryParam = $_GET['q'] ?? '';
$searchQuery = trim($queryParam);
$sort = $_GET['sort'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$newsList = [];
$totalItems = 0;
$pagination = null;

if ($searchQuery !== '' || in_array($sort, ['latest', 'trending'])) {
    try {
        $pdo = connectDB();
        
        $searchTerm = '%' . $searchQuery . '%';
        
        $whereSql = "WHERE n.status = 'published'";
        $paramsCount = [];
        
        if ($searchQuery !== '') {
            $whereSql .= " AND (n.title LIKE :q1 OR n.short_description LIKE :q2 OR n.content LIKE :q3)";
            $paramsCount['q1'] = $searchTerm;
            $paramsCount['q2'] = $searchTerm;
            $paramsCount['q3'] = $searchTerm;
        }
        
        $orderSql = "ORDER BY n.published_at DESC";
        if ($sort === 'trending') {
            $orderSql = "ORDER BY n.views DESC, n.published_at DESC";
        }
        
        // Count total results
        $countSql = "SELECT COUNT(*) FROM news n $whereSql";
        $countStmt = $pdo->prepare($countSql);
        $countStmt->execute($paramsCount);
        $totalItems = $countStmt->fetchColumn();

        $itemsPerPage = 6;
        $pagination = paginate($totalItems, $itemsPerPage, $page);

        // Fetch paginated results
        if ($totalItems > 0) {
            $newsSql = "SELECT n.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
                        FROM news n 
                        JOIN categories c ON n.category_id = c.id 
                        JOIN users u ON n.author_id = u.id 
                        $whereSql 
                        $orderSql 
                        LIMIT :limit OFFSET :offset";
            $newsStmt = $pdo->prepare($newsSql);
            if ($searchQuery !== '') {
                $newsStmt->bindValue(':q1', $searchTerm, PDO::PARAM_STR);
                $newsStmt->bindValue(':q2', $searchTerm, PDO::PARAM_STR);
                $newsStmt->bindValue(':q3', $searchTerm, PDO::PARAM_STR);
            }
            $newsStmt->bindValue(':limit', $itemsPerPage, PDO::PARAM_INT);
            $newsStmt->bindValue(':offset', $pagination['offset'], PDO::PARAM_INT);
            $newsStmt->execute();
            $newsList = $newsStmt->fetchAll();
        }

    } catch (Exception $e) {
        setFlashMessage("An error occurred while fetching news.", "error");
        redirect('index.php');
        exit;
    }
}

$pageTitle = 'Search News | News Portal';
$headingTitle = 'Search';
if ($searchQuery !== '') {
    $pageTitle = 'Search Results for "' . e($searchQuery) . '" | News Portal';
    $headingTitle = 'Search Results';
} elseif ($sort === 'latest') {
    $pageTitle = 'Latest News | News Portal';
    $headingTitle = 'Latest News';
} elseif ($sort === 'trending') {
    $pageTitle = 'Trending News | News Portal';
    $headingTitle = 'Trending News';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="search-page py-5">
    <div class="container">
        
        <!-- Search Header -->
        <div class="text-center mb-5 pb-5" style="border-bottom: 2px solid var(--ink);">
            <h1 class="section-title mb-4" style="font-size: 3.5rem; text-transform: uppercase;">
                <?= e($headingTitle) ?>
            </h1>
            
            <form action="<?= buildUrl('search.php') ?>" method="GET" style="max-width: 600px; margin: 0 auto;">
                <div class="d-flex" style="border-bottom: 2px solid var(--ink); padding-bottom: 5px;">
                    <input class="form-control" type="search" name="q" placeholder="Enter keywords..." value="<?= e($searchQuery) ?>" required style="height: 52px; font-size: 1.2rem; font-family: var(--font-body); border: none; background: transparent; padding-left: 0; box-shadow: none;">
                    <button class="btn" type="submit" style="background: transparent; color: var(--ink); font-size: 1.2rem; border: none;">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <?php if ($searchQuery !== ''): ?>
                <p style="color: var(--muted); margin-top: 24px; margin-bottom: 0; font-size: 1.05rem; font-family: var(--font-body);">
                    Found <strong style="color: var(--accent);"><?= number_format($totalItems) ?></strong> article(s) matching "<strong style="color: var(--accent);"><?= e($searchQuery) ?></strong>"
                </p>
            <?php endif; ?>
        </div>

        <?php if ($searchQuery === '' && !in_array($sort, ['latest', 'trending'])): ?>
            <!-- Empty state when no search term provided -->
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="fas fa-search mb-4" style="font-size: 4rem; color: var(--line);"></i>
                    <h3 style="color: var(--muted); font-family: var(--font-display); font-size: 1.8rem;">Looking for something?</h3>
                    <p style="color: var(--muted); font-size: 1.1rem;">Enter a keyword above to search the publication.</p>
                </div>
            </div>
        <?php elseif (empty($newsList)): ?>
            <!-- No results found -->
            <div class="text-center py-5">
                <div class="empty-state">
                    <i class="far fa-folder-open mb-4" style="font-size: 4rem; color: var(--line);"></i>
                    <h3 style="color: var(--muted); font-family: var(--font-display); font-size: 1.8rem;">No articles found</h3>
                    <p style="color: var(--muted); font-size: 1.1rem;">We couldn't find any articles matching your search criteria.</p>
                </div>
            </div>
        <?php else: ?>
            <!-- Search Results — Horizontal editorial cards -->
            <div class="mb-5 mx-auto" style="max-width: 900px;">
                <?php foreach ($newsList as $news): ?>
                <div class="d-flex flex-column flex-md-row gap-4 pb-4 mb-4" style="border-bottom: 1px solid var(--line);">
                    <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>" style="flex-shrink: 0; display: block; width: 100%; max-width: 320px;">
                        <img src="<?= e(newsImageUrl($news['featured_image'])) ?>" alt="<?= e($news['title']) ?>" loading="lazy" style="width: 100%; aspect-ratio: 4/3; object-fit: cover; border-radius: 0;">
                    </a>
                    <div class="d-flex flex-column justify-content-center">
                        <a href="<?= buildUrl('category.php', ['slug' => $news['category_slug']]) ?>" class="story-cat mb-2" style="align-self: flex-start;">
                            <?= e($news['category_name']) ?>
                        </a>
                        <h3 class="story-title" style="font-size: 1.5rem; margin-bottom: 12px; line-height: 1.25;">
                            <a href="<?= buildUrl('news.php', ['slug' => $news['slug']]) ?>" style="color: var(--ink);">
                                <?= e($news['title']) ?>
                            </a>
                        </h3>
                        <p class="story-excerpt mb-3" style="font-size: 1rem; color: var(--muted); line-height: 1.6;">
                            <?= e(excerpt($news['short_description'] ?: $news['content'], 180)) ?>
                        </p>
                        <div class="story-meta mt-auto text-uppercase" style="font-size: 0.75rem; letter-spacing: 0.5px;">
                            <span class="me-3"><?= e($news['author_name']) ?></span>
                            <span class="me-3">&bull; <?= formatDate($news['published_at'], 'M j, Y') ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination Controls -->
            <?php if ($pagination && $pagination['total_pages'] > 1): ?>
                <nav aria-label="Search results navigation">
                    <ul class="pagination justify-content-center mt-5">
                        <?php if ($pagination['has_prev']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildUrl('search.php', ['q' => $searchQuery, 'sort' => $sort, 'page' => $pagination['current_page'] - 1]) ?>">Previous</a>
                            </li>
                        <?php else: ?>
                            <li class="page-item disabled"><span class="page-link">Previous</span></li>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                                <a class="page-link" href="<?= buildUrl('search.php', ['q' => $searchQuery, 'sort' => $sort, 'page' => $i]) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= buildUrl('search.php', ['q' => $searchQuery, 'sort' => $sort, 'page' => $pagination['current_page'] + 1]) ?>">Next</a>
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
