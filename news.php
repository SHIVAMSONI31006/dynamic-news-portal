<?php
/**
 * ============================================================
 * ARTICLE DETAILS — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    require_once __DIR__ . '/errors/404.php';
    exit;
}

try {
    $pdo = connectDB();
    
    // 1. Retrieve article
    $sql = "SELECT n.*, c.name as category_name, c.slug as category_slug, u.name as author_name 
            FROM news n 
            JOIN categories c ON n.category_id = c.id 
            JOIN users u ON n.author_id = u.id 
            WHERE n.slug = :slug AND n.status = 'published'";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['slug' => $slug]);
    $article = $stmt->fetch();

    if (!$article) {
        require_once __DIR__ . '/errors/404.php';
        exit;
    }

    // 2. Increment view count (single controlled increment per load)
    $updateSql = "UPDATE news SET views = views + 1 WHERE id = :id AND status = 'published'";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute(['id' => $article['id']]);
    
    // Increment local view count for display so it shows the new value immediately
    $article['views']++;

    // 3. Related News (Same category, published, exclude current, order latest)
    $relatedSql = "SELECT n.*, c.name as category_name, c.slug as category_slug
                   FROM news n 
                   JOIN categories c ON n.category_id = c.id
                   WHERE n.category_id = :cat_id AND n.id != :id AND n.status = 'published' 
                   ORDER BY n.published_at DESC LIMIT 4";
    $relatedStmt = $pdo->prepare($relatedSql);
    $relatedStmt->execute([
        'cat_id' => $article['category_id'],
        'id' => $article['id']
    ]);
    $relatedNews = $relatedStmt->fetchAll();

    // 4. Fetch Comments (Only approved)
    $commentsSql = "SELECT c.*, u.name as user_name 
                    FROM comments c 
                    JOIN users u ON c.user_id = u.id 
                    WHERE c.news_id = :id AND c.status = 'approved' 
                    ORDER BY c.created_at DESC";
    $commentsStmt = $pdo->prepare($commentsSql);
    $commentsStmt->execute(['id' => $article['id']]);
    $comments = $commentsStmt->fetchAll();
    
    // 5. Fetch Likes
    $likesSql = "SELECT COUNT(*) FROM likes WHERE news_id = :id";
    $likesStmt = $pdo->prepare($likesSql);
    $likesStmt->execute(['id' => $article['id']]);
    $likeCount = $likesStmt->fetchColumn();
    
    $userLiked = false;
    if (isLoggedIn()) {
        $checkLike = $pdo->prepare("SELECT id FROM likes WHERE news_id = :id AND user_id = :user_id");
        $checkLike->execute(['id' => $article['id'], 'user_id' => currentUserId()]);
        if ($checkLike->fetch()) {
            $userLiked = true;
        }
    }

} catch (Exception $e) {
    setFlashMessage("An error occurred while loading the article.", "error");
    redirect('index.php');
    exit;
}

$pageTitle = $article['title'] . ' | News Portal';
$pageMetaDesc = excerpt($article['short_description'] ?: $article['content'], 160);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Safe rendering subset for HTML content
$allowedTags = '<p><br><strong><em><b><i><h2><h3><h4><ul><ol><li><blockquote><a>';
$safeContent = strip_tags($article['content'], $allowedTags);

$rawUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
$currentUrl = $rawUrl; // Clean URL for canonical and copy
$shareUrl = urlencode($rawUrl);
$encodedTitle = urlencode($article['title']);
?>

<main class="py-5" style="background-color: var(--white);">
    <!-- Article Header Area (Centered, wide) -->
    <div class="container">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-3">
            <ol class="breadcrumb" style="font-size: 0.85rem;">
                <li class="breadcrumb-item"><a href="<?= url('index.php') ?>" class="text-muted">Home</a></li>
                <li class="breadcrumb-item"><a href="<?= buildUrl('category.php', ['slug' => $article['category_slug']]) ?>" class="text-muted"><?= e($article['category_name']) ?></a></li>
                <li class="breadcrumb-item active text-truncate" style="max-width: 300px;" aria-current="page"><?= e($article['title']) ?></li>
            </ol>
        </nav>

        <div class="article-header">
            <a href="<?= buildUrl('category.php', ['slug' => $article['category_slug']]) ?>" class="category-badge mb-4">
                <?= e($article['category_name']) ?>
            </a>
            
            <h1 class="article-title">
                <?= e($article['title']) ?>
            </h1>
            
            <?php if (!empty($article['short_description'])): ?>
                <p class="article-lead">
                    <?= e($article['short_description']) ?>
                </p>
            <?php endif; ?>
            
            <div class="article-meta justify-content-center">
                <span><strong>By <?= e($article['author_name']) ?></strong></span>
                <span><?= formatDate($article['published_at'], 'F j, Y') ?></span>
                <span><?= calculateReadingTime($article['content']) ?></span>
                <span><?= number_format($article['views']) ?> views</span>
            </div>
        </div>
    </div>

    <!-- Featured Image -->
    <div class="container-fluid px-0 px-md-4">
        <img src="<?= e(newsImageUrl($article['featured_image'])) ?>" alt="<?= e($article['title']) ?>" class="article-featured-image shadow-sm" loading="lazy">
    </div>

    <div class="container">
        <div class="row">
            <!-- Main Content Area -->
            <div class="col-lg-8 mx-auto" style="max-width: 820px;">
                <div class="article-body">
                    <?= $safeContent ?>
                    
                    <!-- Like + Share -->
                    <div class="mt-5 pt-4 d-flex align-items-center justify-content-between flex-wrap" style="border-top: 1px solid var(--line);">
                        
                        <!-- Like Button -->
                        <div>
                            <?php if (isLoggedIn()): ?>
                                <form method="POST" action="like.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                    <input type="hidden" name="slug" value="<?= e($article['slug']) ?>">
                                    <button type="submit" class="btn-action <?= $userLiked ? 'active' : '' ?>">
                                        <i class="<?= $userLiked ? 'fas' : 'far' ?> fa-heart me-2" aria-hidden="true"></i> 
                                        <?= $userLiked ? 'Liked' : 'Like' ?> 
                                        <span class="ms-2" style="font-weight: 800; border-left: 1px solid currentColor; padding-left: 10px;"><?= number_format($likeCount) ?></span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn-action text-muted" title="Log in to like" disabled style="opacity: 0.6;">
                                    <i class="far fa-heart me-2" aria-hidden="true"></i> Like 
                                    <span class="ms-2" style="font-weight: 800; border-left: 1px solid currentColor; padding-left: 10px;"><?= number_format($likeCount) ?></span>
                                </button>
                            <?php endif; ?>
                        </div>

                        <!-- Share Buttons -->
                        <div class="d-flex gap-2">
                            <a href="https://api.whatsapp.com/send?text=<?= $encodedTitle ?>%20<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer" class="btn-action" title="Share on WhatsApp">
                                <i class="fab fa-whatsapp" aria-hidden="true"></i>
                            </a>
                            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $shareUrl ?>" target="_blank" rel="noopener noreferrer" class="btn-action" title="Share on Facebook">
                                <i class="fab fa-facebook-f" aria-hidden="true"></i>
                            </a>
                            <a href="https://twitter.com/intent/tweet?url=<?= $shareUrl ?>&text=<?= $encodedTitle ?>" target="_blank" rel="noopener noreferrer" class="btn-action" title="Share on X">
                                <i class="fab fa-x-twitter" aria-hidden="true"></i>
                            </a>
                            <button type="button" class="btn-action" onclick="navigator.clipboard.writeText('<?= addslashes($rawUrl) ?>').then(()=>alert('Article link copied to clipboard!'))" title="Copy Link">
                                <i class="far fa-copy" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Comments Section -->
                    <div id="comments" class="comments-section">
                        <h4 class="comments-title">Comments (<?= count($comments) ?>)</h4>
                        
                        <!-- Comment Form -->
                        <div class="comment-card shadow-sm" style="background: var(--paper);">
                            <?php if (isLoggedIn()): ?>
                                <h5 class="mb-3" style="font-family: var(--font-display); font-size: 1.3rem;">Leave a Comment</h5>
                                <form method="POST" action="submit-comment.php">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="news_id" value="<?= $article['id'] ?>">
                                    <input type="hidden" name="slug" value="<?= e($article['slug']) ?>">
                                    
                                    <div class="mb-3">
                                        <textarea class="form-control" name="comment" rows="4" placeholder="Share your perspective..." required maxlength="1000" style="background: var(--white); border-color: var(--line);"></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-primary px-4 py-2">
                                        Submit Comment
                                    </button>
                                </form>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <h5 style="font-family: var(--font-display); font-size: 1.4rem;">Join the Discussion</h5>
                                    <p class="mb-0 text-muted"><a href="<?= url('login.php') ?>" class="text-accent text-decoration-underline" style="font-weight: 600;">Log in</a> or <a href="<?= url('register.php') ?>" class="text-accent text-decoration-underline" style="font-weight: 600;">Register</a> to comment.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comment List -->
                        <div class="mt-5">
                            <?php if (empty($comments)): ?>
                                <p class="text-center text-muted fst-italic">No comments yet.</p>
                            <?php else: ?>
                                <?php foreach ($comments as $comment): ?>
                                    <div class="comment-card" style="border-radius: 0; border: none; border-bottom: 1px solid var(--line); background: transparent; padding: 24px 0;">
                                        <div class="d-flex">
                                            <div class="flex-shrink-0">
                                                <div style="width: 48px; height: 48px; border-radius: 50%; background: var(--dark); color: var(--white); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-size: 1.4rem;">
                                                    <?= strtoupper(substr(e($comment['user_name']), 0, 1)) ?>
                                                </div>
                                            </div>
                                            <div class="flex-grow-1 ms-3">
                                                <div class="d-flex align-items-baseline gap-2 mb-1">
                                                    <span class="comment-author"><?= e($comment['user_name']) ?></span>
                                                    <span class="comment-date"><?= date('F j, Y', strtotime($comment['created_at'])) ?></span>
                                                </div>
                                                <p class="comment-text mb-0"><?= e($comment['comment']) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Related Stories Full Width Section -->
    <?php if (!empty($relatedNews)): ?>
    <div style="background-color: var(--paper); padding: 80px 0; margin-top: 60px;">
        <div class="container">
            <div class="section-header border-0 mb-4 text-center justify-content-center">
                <h3 class="section-title" style="font-size: 2rem;">More from <?= e($article['category_name']) ?></h3>
            </div>
            
            <div class="row g-4 justify-content-center">
                <?php foreach ($relatedNews as $related): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card story-card text-center">
                        <a href="<?= buildUrl('news.php', ['slug' => $related['slug']]) ?>" class="story-img-wrap" style="aspect-ratio: 4/3; border-radius: 0;">
                            <img src="<?= e(newsImageUrl($related['featured_image'])) ?>" alt="<?= e($related['title']) ?>">
                        </a>
                        <div class="card-body px-0 pt-3">
                            <span class="story-cat mx-auto" style="margin-bottom: 12px;"><?= e($related['category_name']) ?></span>
                            <h4 class="story-title" style="font-size: 1.1rem; line-height: 1.4;">
                                <a href="<?= buildUrl('news.php', ['slug' => $related['slug']]) ?>">
                                    <?= e($related['title']) ?>
                                </a>
                            </h4>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
