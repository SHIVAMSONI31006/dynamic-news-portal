<?php
$pageTitle = 'News Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// Editor and Admin have access
requireAdmin();

$pdo = connectDB();

// Filtering
$q = trim(get('q', ''));
$status = get('status', '');
$category = get('category', '');

$whereClauses = [];
$params = [];

if ($q !== '') {
    $whereClauses[] = "(n.title LIKE :q OR n.short_description LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}
if ($status !== '' && in_array($status, ['draft', 'published', 'archived'])) {
    $whereClauses[] = "n.status = :status";
    $params[':status'] = $status;
}
if ($category !== '') {
    $whereClauses[] = "n.category_id = :category";
    $params[':category'] = (int)$category;
}

$whereSQL = '';
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

// Pagination
$page = (int)get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

try {
    // Total count for pagination
    $countSql = "SELECT COUNT(*) FROM news n $whereSQL";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalNews = $countStmt->fetchColumn();
    $totalPages = ceil($totalNews / $perPage);
    if ($page > $totalPages && $totalPages > 0) {
        $page = $totalPages;
        $offset = ($page - 1) * $perPage;
    }
    
    // Fetch records
    $sql = "
        SELECT n.*, c.name as category_name, u.name as author_name 
        FROM news n
        JOIN categories c ON n.category_id = c.id
        JOIN users u ON n.author_id = u.id
        $whereSQL
        ORDER BY n.created_at DESC
        LIMIT $perPage OFFSET $offset
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $newsList = $stmt->fetchAll();
    
    // Fetch categories for filter dropdown
    $catStmt = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC");
    $categories = $catStmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve news data.", "error");
    $newsList = [];
    $categories = [];
    $totalPages = 0;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Editorial Articles</h2>
        <p class="text-muted small mb-0">Publish, schedule, edit, and organize all news publications.</p>
    </div>
    <a href="create.php" class="btn btn-danger btn-sm px-3 shadow-sm">
        <i class="fas fa-feather-pointed me-2"></i> Compose Article
    </a>
</div>

<!-- Filters -->
<div class="admin-card mb-4">
    <div class="admin-card-body p-3">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-dark mb-1">Search Keywords</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light text-muted"><i class="fas fa-magnifying-glass"></i></span>
                    <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Title or summary...">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">Publication Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-dark mb-1">Category</label>
                <select name="category" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fas fa-filter me-1"></i> Filter</button>
                <?php if ($q !== '' || $status !== '' || $category !== ''): ?>
                    <a href="index.php" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fas fa-xmark"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<!-- News Table -->
<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3" style="width: 70px;">Media</th>
                    <th style="min-width: 250px;">Article Title & Author</th>
                    <th>Status & Flags</th>
                    <th>Readership</th>
                    <th>Timeline</th>
                    <th class="text-end px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($newsList)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="fas fa-newspaper"></i></div>
                            <div class="admin-empty-title">No articles found</div>
                            <div class="admin-empty-desc">No publications match your filter criteria or search keyword.</div>
                            <a href="create.php" class="btn btn-danger btn-sm mt-2">Create New Article</a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($newsList as $news): ?>
                    <tr>
                        <td class="px-3">
                            <?php if ($news['featured_image']): ?>
                                <img src="<?= buildUrl($news['featured_image']) ?>" alt="" class="rounded border" style="width: 48px; height: 36px; object-fit: cover;" onerror="this.style.display='none'">
                            <?php else: ?>
                                <div class="bg-light text-center text-muted border rounded d-flex align-items-center justify-content-center" style="width: 48px; height: 36px;">
                                    <i class="fas fa-image small"></i>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold mb-1 text-truncate" style="max-width: 280px;" title="<?= e($news['title']) ?>">
                                <a href="edit.php?id=<?= $news['id'] ?>" class="text-dark text-decoration-none">
                                    <?= e($news['title']) ?>
                                </a>
                            </div>
                            <div class="small d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border"><?= e($news['category_name']) ?></span>
                                <span class="text-muted"><i class="fas fa-user-pen me-1"></i><?= e($news['author_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="mb-1">
                                <?php if ($news['status'] === 'published'): ?>
                                    <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Published</span>
                                <?php elseif ($news['status'] === 'archived'): ?>
                                    <span class="badge-status-archived"><i class="fas fa-circle" style="font-size: 6px;"></i> Archived</span>
                                <?php else: ?>
                                    <span class="badge-status-draft"><i class="fas fa-circle" style="font-size: 6px;"></i> Draft</span>
                                <?php endif; ?>
                            </div>
                            <div>
                                <?php if ($news['is_featured']): ?>
                                    <span class="badge bg-primary text-white" style="font-size: 0.65rem;" title="Featured on Homepage"><i class="fas fa-star me-1"></i>Featured</span>
                                <?php endif; ?>
                                <?php if ($news['is_breaking']): ?>
                                    <span class="badge bg-danger text-white" style="font-size: 0.65rem;" title="Breaking News Alert"><i class="fas fa-bolt me-1"></i>Breaking</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <span class="fw-semibold text-dark"><i class="fas fa-eye text-muted me-1 small"></i><?= number_format($news['views']) ?></span>
                        </td>
                        <td class="small text-muted">
                            <div><i class="fas fa-calendar-plus me-1 text-muted"></i> <?= date('M j, Y', strtotime($news['created_at'])) ?></div>
                            <?php if ($news['published_at']): ?>
                                <div class="text-success small"><i class="fas fa-paper-plane me-1"></i> <?= date('M j, Y', strtotime($news['published_at'])) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end px-3">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <!-- Status Toggle Form -->
                                <form method="POST" action="toggle-status.php" class="d-inline m-0">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $news['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $news['status'] === 'published' ? 'draft' : 'published' ?>">
                                    <button type="submit" class="btn-action <?= $news['status'] === 'published' ? 'btn-action-view' : 'btn-action-edit' ?>" title="<?= $news['status'] === 'published' ? 'Unpublish to Draft' : 'Publish Live' ?>">
                                        <i class="fas fa-<?= $news['status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>

                                <a href="edit.php?id=<?= $news['id'] ?>" class="btn-action btn-action-edit" title="Edit Article">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                
                                <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently delete this article? All associated comments will also be removed.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $news['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Delete Article">
                                        <i class="fas fa-trash-can"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <nav class="mt-4" aria-label="Page navigation">
        <ul class="pagination pagination-sm justify-content-center">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&q=<?= urlencode($q) ?>&status=<?= urlencode($status) ?>&category=<?= urlencode($category) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
