<?php
/**
 * ============================================================
 * ADMIN DASHBOARD — Newsroom CMS Operations Hub
 * ============================================================
 */
$pageTitle = 'Dashboard Overview';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$stats = [
    'total_news'         => 0,
    'published_news'     => 0,
    'draft_news'         => 0,
    'total_views'        => 0,
    'categories'         => 0,
    'users'              => 0,
    'comments_total'     => 0,
    'comments_pending'   => 0,
    'unread_messages'    => 0,
    'newsletter_subs'    => 0,
];

$recentNews = [];
$recentMessages = [];
$categoryDistribution = [];

try {
    $pdo = connectDB();
    
    // --------------------------------------------------------
    // 1. Fetch Comprehensive Statistics
    // --------------------------------------------------------
    $stats['total_news'] = (int)$pdo->query("SELECT COUNT(*) FROM news")->fetchColumn();
    $stats['published_news'] = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'")->fetchColumn();
    $stats['draft_news'] = (int)$pdo->query("SELECT COUNT(*) FROM news WHERE status = 'draft'")->fetchColumn();
    $stats['total_views'] = (int)$pdo->query("SELECT COALESCE(SUM(views), 0) FROM news")->fetchColumn();
    
    $stats['categories'] = (int)$pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'")->fetchColumn();
    $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'")->fetchColumn();
    
    $stats['comments_total'] = (int)$pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
    $stats['comments_pending'] = (int)$pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
    $stats['unread_messages'] = (int)$pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
    
    // Newsletter subscribers
    $stats['newsletter_subs'] = (int)$pdo->query("SELECT COUNT(*) FROM newsletter_subscribers WHERE status = 'active'")->fetchColumn();
    
    // --------------------------------------------------------
    // 2. Fetch Category Distribution (Top 5)
    // --------------------------------------------------------
    $stmtCat = $pdo->query("
        SELECT c.name, COUNT(n.id) as news_count
        FROM categories c
        LEFT JOIN news n ON n.category_id = c.id
        WHERE c.status = 'active'
        GROUP BY c.id, c.name
        ORDER BY news_count DESC
        LIMIT 5
    ");
    $categoryDistribution = $stmtCat->fetchAll();

    // --------------------------------------------------------
    // 3. Fetch Recent News
    // --------------------------------------------------------
    $stmt = $pdo->query("
        SELECT n.id, n.title, n.slug, n.status, n.views, n.created_at, n.featured_image,
               c.name as category_name, u.name as author_name
        FROM news n
        JOIN categories c ON n.category_id = c.id
        JOIN users u ON n.author_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 6
    ");
    $recentNews = $stmt->fetchAll();
    
    // --------------------------------------------------------
    // 4. Fetch Recent Messages
    // --------------------------------------------------------
    $stmt = $pdo->query("
        SELECT id, name, email, subject, status, created_at 
        FROM contact_messages 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentMessages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Database connection interrupted: " . $e->getMessage(), "error");
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Welcome Banner -->
<div class="welcome-banner mb-4">
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <span class="welcome-banner-badge">Control Center</span>
                <span class="welcome-banner-status">
                    <span class="status-dot"></span> PHP <?= PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ?> · MySQL Active
                </span>
            </div>
            <h2 class="welcome-banner-title">Welcome back, <?= e(currentUserName()) ?>!</h2>
            <p class="welcome-banner-desc">
                Newsroom editorial operations hub. You have <strong><?= number_format($stats['draft_news']) ?></strong> drafts pending publication and <strong><?= number_format($stats['unread_messages']) ?></strong> unread inquiries.
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2 flex-shrink-0">
            <a href="<?= buildUrl('admin/news/create.php') ?>" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fas fa-feather-pointed me-2"></i> Write Article
            </a>
            <a href="<?= buildUrl('index.php') ?>" target="_blank" rel="noopener" class="btn btn-outline-light btn-sm px-3">
                <i class="fas fa-arrow-up-right-from-square me-2"></i> Live Website
            </a>
        </div>
    </div>
</div>

<!-- Primary Stats Grid -->
<div class="row g-3 mb-4">
    <!-- Total Articles -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-pro">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Editorial Articles</div>
                    <div class="stat-card-value text-dark"><?= number_format($stats['total_news']) ?></div>
                </div>
                <div class="stat-card-icon icon-primary">
                    <i class="fas fa-newspaper"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="stat-trend trend-up">
                    <i class="fas fa-check-circle"></i> <?= number_format($stats['published_news']) ?> Published
                </span>
                <span class="text-muted ms-auto"><?= number_format($stats['draft_news']) ?> Drafts</span>
            </div>
        </div>
    </div>
    
    <!-- Total Views -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-pro">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Cumulative Views</div>
                    <div class="stat-card-value text-success"><?= number_format($stats['total_views']) ?></div>
                </div>
                <div class="stat-card-icon icon-success">
                    <i class="fas fa-chart-line"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="stat-trend trend-up">
                    <i class="fas fa-eye"></i> Across all stories
                </span>
                <span class="text-muted ms-auto">High Reach</span>
            </div>
        </div>
    </div>
    
    <!-- Active Categories -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-pro">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Active Categories</div>
                    <div class="stat-card-value text-info"><?= number_format($stats['categories']) ?></div>
                </div>
                <div class="stat-card-icon icon-info">
                    <i class="fas fa-tags"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="text-muted">
                    <i class="fas fa-folder-tree me-1"></i> Content taxonomies
                </span>
                <a href="<?= buildUrl('admin/categories/index.php') ?>" class="text-primary text-decoration-none fw-semibold ms-auto" style="font-size: 0.75rem;">Manage</a>
            </div>
        </div>
    </div>
    
    <!-- Active Community & Subscribers -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card-pro">
            <div class="stat-card-header">
                <div>
                    <div class="stat-card-title">Audience & Readers</div>
                    <div class="stat-card-value text-purple"><?= number_format($stats['users'] + $stats['newsletter_subs']) ?></div>
                </div>
                <div class="stat-card-icon icon-purple">
                    <i class="fas fa-users"></i>
                </div>
            </div>
            <div class="stat-card-footer">
                <span class="text-muted">
                    <?= number_format($stats['users']) ?> Users · <?= number_format($stats['newsletter_subs']) ?> Subs
                </span>
                <?php if (isAdmin()): ?>
                <a href="<?= buildUrl('admin/users/index.php') ?>" class="text-primary text-decoration-none fw-semibold ms-auto" style="font-size: 0.75rem;">Staff</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Monitoring Strip -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center justify-content-between h-100 mb-0">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-warning" style="width: 42px; height: 42px; font-size: 1.1rem;">
                    <i class="fas fa-comments"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6 text-dark"><?= number_format($stats['comments_total']) ?> Comments</div>
                    <div class="small text-muted">
                        <?php if ($stats['comments_pending'] > 0): ?>
                            <span class="text-warning fw-bold"><i class="fas fa-hourglass-half me-1"></i><?= $stats['comments_pending'] ?> pending moderation</span>
                        <?php else: ?>
                            <span class="text-success"><i class="fas fa-check me-1"></i>All comments reviewed</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <a href="<?= buildUrl('admin/comments/index.php') ?>" class="btn btn-outline-secondary btn-sm px-3">Review</a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center justify-content-between h-100 mb-0">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-danger" style="width: 42px; height: 42px; font-size: 1.1rem;">
                    <i class="fas fa-envelope"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6 text-dark"><?= number_format($stats['unread_messages']) ?> Unread Inquiries</div>
                    <div class="small text-muted">From public contact forms</div>
                </div>
            </div>
            <a href="<?= buildUrl('admin/messages/index.php') ?>" class="btn btn-outline-secondary btn-sm px-3">Inbox</a>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center justify-content-between h-100 mb-0">
            <div class="d-flex align-items-center gap-3">
                <div class="stat-card-icon icon-primary" style="width: 42px; height: 42px; font-size: 1.1rem;">
                    <i class="fas fa-paper-plane"></i>
                </div>
                <div>
                    <div class="fw-bold fs-6 text-dark"><?= number_format($stats['newsletter_subs']) ?> Newsletter Readers</div>
                    <div class="small text-muted">Active mail subscribers</div>
                </div>
            </div>
            <?php if (isAdmin()): ?>
            <a href="<?= buildUrl('admin/newsletter/index.php') ?>" class="btn btn-outline-secondary btn-sm px-3">Manage</a>
            <?php else: ?>
            <span class="badge bg-light text-muted">Subscribers</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Main Two-Column Workflow Section (Desktop Balanced Grid) -->
<div class="dashboard-workflow-grid">
    <!-- Left Column: Recent News & Category Distribution -->
    <div class="dashboard-main-col">
        
        <!-- Recent Articles Table -->
        <div class="admin-card mb-4">
            <div class="admin-card-header d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-file-lines text-primary fs-5"></i>
                    <h5 class="admin-card-title mb-0">Recent Editorial Articles</h5>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= buildUrl('admin/news/create.php') ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus me-1"></i> New
                    </a>
                    <a href="<?= buildUrl('admin/news/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                        View All
                    </a>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-admin align-middle mb-0">
                    <thead>
                        <tr>
                            <th scope="col" style="min-width: 140px;">Story Title</th>
                            <th scope="col" style="white-space: nowrap;">Category</th>
                            <th scope="col" style="white-space: nowrap;">Status</th>
                            <th scope="col" class="text-center" style="white-space: nowrap;">Views</th>
                            <th scope="col" style="white-space: nowrap;">Published</th>
                            <th scope="col" class="text-end pe-3" style="width: 76px; white-space: nowrap;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentNews)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="admin-empty-state">
                                    <div class="admin-empty-icon mb-2"><i class="fas fa-newspaper fa-2x text-muted opacity-50"></i></div>
                                    <h6 class="fw-bold mb-1">No news articles published yet</h6>
                                    <p class="small text-muted mb-3">Create your first breaking news story to populate the news portal.</p>
                                    <a href="<?= buildUrl('admin/news/create.php') ?>" class="btn btn-primary btn-sm">Create News Article</a>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($recentNews as $news): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <?php if (!empty($news['featured_image'])): ?>
                                            <img src="<?= buildUrl($news['featured_image']) ?>" alt="" class="rounded border flex-shrink-0" style="width: 38px; height: 28px; object-fit: cover;" onerror="this.style.display='none'">
                                        <?php endif; ?>
                                        <div class="text-truncate" style="max-width: 180px;">
                                            <a href="<?= buildUrl('admin/news/edit.php?id=' . $news['id']) ?>" class="fw-semibold text-dark text-decoration-none">
                                                <?= e($news['title']) ?>
                                            </a>
                                            <div class="small text-muted">by <?= e($news['author_name']) ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="white-space: nowrap;">
                                    <span class="badge bg-light text-dark border"><?= e($news['category_name']) ?></span>
                                </td>
                                <td style="white-space: nowrap;">
                                    <?php if ($news['status'] === 'published'): ?>
                                        <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Published</span>
                                    <?php else: ?>
                                        <span class="badge-status-draft"><i class="fas fa-circle" style="font-size: 6px;"></i> Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="fw-semibold text-muted text-center" style="white-space: nowrap;">
                                    <i class="fas fa-eye me-1 small"></i><?= number_format($news['views']) ?>
                                </td>
                                <td class="small text-muted" style="white-space: nowrap;">
                                    <?= date('M j, Y', strtotime($news['created_at'])) ?>
                                </td>
                                <td class="text-end pe-3" style="white-space: nowrap;">
                                    <div class="d-inline-flex gap-1 align-items-center">
                                        <a href="<?= buildUrl('admin/news/edit.php?id=' . $news['id']) ?>" class="btn-action edit" title="Edit Article" aria-label="Edit Article <?= e($news['title']) ?>">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="<?= buildUrl('news.php?id=' . $news['id']) ?>" target="_blank" rel="noopener" class="btn-action view" title="View Published Story" aria-label="View Published Story <?= e($news['title']) ?>">
                                            <i class="fas fa-arrow-up-right-from-square"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Category Coverage Progress Bars -->
        <div class="admin-card">
            <div class="admin-card-header d-flex align-items-center justify-content-between py-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-chart-pie text-primary fs-5"></i>
                    <h5 class="admin-card-title mb-0">Category Editorial Coverage</h5>
                </div>
                <a href="<?= buildUrl('admin/categories/index.php') ?>" class="btn btn-outline-secondary btn-sm">
                    All Categories
                </a>
            </div>
            <div class="admin-card-body p-4">
                <?php if (empty($categoryDistribution)): ?>
                    <p class="text-muted small mb-0">No active categories found.</p>
                <?php else: ?>
                    <div class="row g-3">
                        <?php 
                        $maxCount = max(array_column($categoryDistribution, 'news_count') ?: [1]);
                        if ($maxCount <= 0) $maxCount = 1;
                        foreach ($categoryDistribution as $cat): 
                            $pct = round(($cat['news_count'] / $maxCount) * 100);
                        ?>
                        <div class="col-md-6 mb-2">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="fw-semibold small text-dark"><?= e($cat['name']) ?></span>
                                <span class="small text-muted fw-bold"><?= $cat['news_count'] ?> stories</span>
                            </div>
                            <div class="progress" style="height: 8px; border-radius: 4px; background: #EEF2F6;">
                                <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $pct ?>%;" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
    
    <!-- Right Column: Quick Actions & Inquiries -->
    <div class="dashboard-side-col">
        
        <!-- Live Quick Operations Card (100% Functional, 0% Dummy) -->
        <div class="admin-card mb-4">
            <div class="admin-card-header py-3">
                <h5 class="admin-card-title mb-0">
                    <i class="fas fa-bolt me-2 text-warning"></i> Operational Quick Actions
                </h5>
            </div>
            <div class="admin-card-body p-3">
                <div class="quick-actions-stack">
                    <a href="<?= buildUrl('admin/news/create.php') ?>" class="quick-action-row primary">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-feather-pointed"></i>
                            <span>Compose Article</span>
                        </span>
                        <i class="fas fa-chevron-right small opacity-75"></i>
                    </a>
                    
                    <?php if (isAdminOrEditor()): ?>
                    <a href="<?= buildUrl('admin/categories/index.php') ?>" class="quick-action-row">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-tags text-info"></i>
                            <span>Manage Categories</span>
                        </span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>
                    <?php endif; ?>

                    <a href="<?= buildUrl('admin/comments/index.php?status=pending') ?>" class="quick-action-row">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-comments text-warning"></i>
                            <span>Moderate Comments</span>
                        </span>
                        <?php if ($stats['comments_pending'] > 0): ?>
                            <span class="badge bg-warning text-dark font-monospace"><?= $stats['comments_pending'] ?></span>
                        <?php else: ?>
                            <i class="fas fa-chevron-right small text-muted"></i>
                        <?php endif; ?>
                    </a>

                    <?php if (isAdmin()): ?>
                    <a href="<?= buildUrl('admin/users/create.php') ?>" class="quick-action-row">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-user-plus text-success"></i>
                            <span>Add Staff / User</span>
                        </span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>

                    <a href="<?= buildUrl('admin/newsletter/index.php') ?>" class="quick-action-row">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-paper-plane text-primary"></i>
                            <span>Newsletter Campaigns</span>
                        </span>
                        <i class="fas fa-chevron-right small text-muted"></i>
                    </a>
                    <?php endif; ?>

                    <a href="<?= buildUrl('index.php') ?>" target="_blank" rel="noopener" class="quick-action-row border-dashed">
                        <span class="d-flex align-items-center gap-2">
                            <i class="fas fa-globe text-danger"></i>
                            <span>Open Live Website</span>
                        </span>
                        <i class="fas fa-arrow-up-right-from-square small text-muted"></i>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Contact Inquiries -->
        <div class="admin-card">
            <div class="admin-card-header py-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-inbox text-danger"></i>
                    <h5 class="admin-card-title mb-0">Public Inquiries</h5>
                </div>
                <a href="<?= buildUrl('admin/messages/index.php') ?>" class="btn btn-outline-secondary btn-sm" style="font-size: 0.75rem;">
                    All Messages
                </a>
            </div>
            <div class="admin-card-body p-0">
                <div class="list-group list-group-flush">
                    <?php if (empty($recentMessages)): ?>
                        <div class="p-4 text-center text-muted small">
                            <i class="fas fa-envelope-open fs-4 mb-2 d-block text-secondary opacity-50"></i>
                            No contact inquiries recorded.
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $msg): ?>
                            <a href="<?= buildUrl('admin/messages/index.php') ?>" class="list-group-item list-group-item-action p-3 text-decoration-none">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <div class="fw-bold text-dark text-truncate" style="max-width: 170px;">
                                        <?= e($msg['name']) ?>
                                    </div>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?= date('M j', strtotime($msg['created_at'])) ?></small>
                                </div>
                                <div class="text-truncate small text-secondary mb-1" style="max-width: 240px;">
                                    <?= e($msg['subject']) ?>
                                </div>
                                <div>
                                    <?php if ($msg['status'] === 'unread'): ?>
                                        <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">Unread</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">Read</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
