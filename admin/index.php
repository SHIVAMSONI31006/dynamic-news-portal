<?php
/**
 * ============================================================
 * ADMIN DASHBOARD — News Portal
 * ============================================================
 */
$pageTitle = 'Dashboard';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
// Header will automatically enforce auth and admin/editor roles
require_once __DIR__ . '/includes/header.php';

try {
    $pdo = connectDB();
    
    // --------------------------------------------------------
    // 1. Fetch Statistics
    // --------------------------------------------------------
    
    // Total News
    $stmt = $pdo->query("SELECT COUNT(*) FROM news");
    $stats['total_news'] = $stmt->fetchColumn();
    
    // Published News
    $stmt = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'published'");
    $stats['published_news'] = $stmt->fetchColumn();
    
    // Draft News
    $stmt = $pdo->query("SELECT COUNT(*) FROM news WHERE status = 'draft'");
    $stats['draft_news'] = $stmt->fetchColumn();
    
    // Total Categories
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories WHERE status = 'active'");
    $stats['categories'] = $stmt->fetchColumn();
    
    // Total Users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'active'");
    $stats['users'] = $stmt->fetchColumn();
    
    // Total Comments
    $stmt = $pdo->query("SELECT COUNT(*) FROM comments");
    $stats['comments'] = $stmt->fetchColumn();
    
    // Unread Contact Messages
    $stmt = $pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
    $stats['unread_messages'] = $stmt->fetchColumn();
    
    // --------------------------------------------------------
    // 2. Fetch Recent News
    // --------------------------------------------------------
    $stmt = $pdo->query("
        SELECT n.title, n.status, n.views, n.created_at, c.name as category_name, u.name as author_name
        FROM news n
        JOIN categories c ON n.category_id = c.id
        JOIN users u ON n.author_id = u.id
        ORDER BY n.created_at DESC
        LIMIT 5
    ");
    $recentNews = $stmt->fetchAll();
    
    // --------------------------------------------------------
    // 3. Fetch Recent Messages
    // --------------------------------------------------------
    $stmt = $pdo->query("
        SELECT name, email, subject, status, created_at 
        FROM contact_messages 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recentMessages = $stmt->fetchAll();
    
} catch (PDOException $e) {
    // Graceful degradation
    $stats = array_fill_keys(['total_news', 'published_news', 'draft_news', 'categories', 'users', 'comments', 'unread_messages'], 0);
    $recentNews = [];
    $recentMessages = [];
    setFlashMessage("Database connection failed. Unable to load statistics.", "error");
}
?>

<div class="row g-4 mb-5">
    <!-- Statistics Cards -->
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100">
            <div class="stat-card-info">
                <h3><?= number_format($stats['total_news']) ?></h3>
                <p>Total News</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-newspaper text-primary"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100" style="border-left-color: var(--bs-success);">
            <div class="stat-card-info">
                <h3 class="text-success"><?= number_format($stats['published_news']) ?></h3>
                <p>Published</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-check-circle text-success"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100" style="border-left-color: var(--bs-warning);">
            <div class="stat-card-info">
                <h3 class="text-warning"><?= number_format($stats['draft_news']) ?></h3>
                <p>Drafts</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-file-alt text-warning"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card h-100" style="border-left-color: var(--bs-info);">
            <div class="stat-card-info">
                <h3 class="text-info"><?= number_format($stats['categories']) ?></h3>
                <p>Categories</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-tags text-info"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card h-100" style="border-left-color: var(--bs-purple);">
            <div class="stat-card-info">
                <h3 style="color: #6f42c1;"><?= number_format($stats['users']) ?></h3>
                <p>Active Users</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-users" style="color: #6f42c1;"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card h-100" style="border-left-color: var(--bs-secondary);">
            <div class="stat-card-info">
                <h3 class="text-secondary"><?= number_format($stats['comments']) ?></h3>
                <p>Comments</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-comments text-secondary"></i>
            </div>
        </div>
    </div>
    
    <div class="col-sm-6 col-xl-4">
        <div class="stat-card h-100" style="border-left-color: var(--bs-danger);">
            <div class="stat-card-info">
                <h3 class="text-danger"><?= number_format($stats['unread_messages']) ?></h3>
                <p>Unread Messages</p>
            </div>
            <div class="stat-card-icon">
                <i class="fas fa-envelope text-danger"></i>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent News Table -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold"><i class="fas fa-clock me-2 text-primary"></i>Recent News</h5>
                <a href="#" class="btn btn-sm btn-outline-primary" title="Coming Soon">View All</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-3">Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($recentNews)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No news articles found.</td>
                            </tr>
                            <?php else: ?>
                                <?php foreach ($recentNews as $news): ?>
                                <tr>
                                    <td class="px-3">
                                        <div class="fw-bold text-truncate" style="max-width: 250px;">
                                            <?= e($news['title']) ?>
                                        </div>
                                        <div class="small text-muted">by <?= e($news['author_name']) ?></div>
                                    </td>
                                    <td><span class="badge bg-secondary"><?= e($news['category_name']) ?></span></td>
                                    <td>
                                        <?php if ($news['status'] === 'published'): ?>
                                            <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>Published</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark"><i class="fas fa-file-alt me-1"></i>Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= number_format($news['views']) ?></td>
                                    <td class="small text-muted"><?= date('M j, Y', strtotime($news['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions & Recent Messages -->
    <div class="col-lg-4">
        
        <!-- Quick Actions -->
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-bolt me-2 text-warning"></i>Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="#" class="btn btn-primary text-start"><i class="fas fa-plus-circle me-2"></i> Create News <span class="badge bg-light text-dark ms-2 float-end">SOON</span></a>
                    <?php if (isAdmin()): ?>
                    <a href="#" class="btn btn-outline-secondary text-start"><i class="fas fa-tag me-2"></i> Add Category <span class="badge bg-light text-dark ms-2 float-end">SOON</span></a>
                    <?php endif; ?>
                    <a href="<?= buildUrl('index.php') ?>" target="_blank" class="btn btn-outline-info text-start"><i class="fas fa-globe me-2"></i> View Live Website</a>
                </div>
            </div>
        </div>
        
        <!-- Recent Messages -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                <h5 class="mb-0 fw-bold"><i class="fas fa-envelope-open-text me-2 text-danger"></i>Messages</h5>
                <a href="#" class="btn btn-sm btn-outline-secondary" title="Coming Soon">All</a>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($recentMessages)): ?>
                        <li class="list-group-item text-center py-4 text-muted">No recent messages.</li>
                    <?php else: ?>
                        <?php foreach ($recentMessages as $msg): ?>
                            <li class="list-group-item p-3">
                                <div class="d-flex w-100 justify-content-between align-items-center mb-1">
                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 180px;"><?= e($msg['name']) ?></h6>
                                    <small class="text-muted" style="font-size: 0.7rem;"><?= date('M j', strtotime($msg['created_at'])) ?></small>
                                </div>
                                <div class="mb-1 text-truncate" style="font-size: 0.85rem; max-width: 250px;">
                                    <strong><?= e($msg['subject']) ?></strong>
                                </div>
                                <?php if ($msg['status'] === 'unread'): ?>
                                    <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">New</span>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
