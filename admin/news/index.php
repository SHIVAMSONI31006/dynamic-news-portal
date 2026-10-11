<?php
$pageTitle = 'News Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

// Editor and Admin have access

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
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <h1 class="h3 mb-0 text-gray-800">News Management</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Create News</a>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="index.php" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Search Title</label>
                <input type="text" name="q" class="form-control" value="<?= e($q) ?>" placeholder="Keywords...">
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Status</label>
                <select name="status" class="form-select">
                    <option value="">All Statuses</option>
                    <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Published</option>
                    <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Draft</option>
                    <option value="archived" <?= $status === 'archived' ? 'selected' : '' ?>>Archived</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Category</label>
                <select name="category" class="form-select">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-secondary w-100"><i class="fas fa-filter me-2"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- News Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3" style="width: 60px;">Image</th>
                        <th>Title / Details</th>
                        <th>Status / Flags</th>
                        <th>Views</th>
                        <th>Date</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($newsList)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fas fa-newspaper fa-3x mb-3 text-light"></i>
                            <p>No news articles found matching your criteria.</p>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($newsList as $news): ?>
                        <tr>
                            <td class="px-3">
                                <?php if ($news['featured_image']): ?>
                                    <img src="<?= buildUrl($news['featured_image']) ?>" alt="Thumbnail" class="img-thumbnail" style="width: 50px; height: 50px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light text-center text-muted border rounded d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                        <i class="fas fa-image"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="fw-bold mb-1" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= e($news['title']) ?>">
                                    <?= e($news['title']) ?>
                                </div>
                                <div class="small">
                                    <span class="badge bg-secondary me-1"><?= e($news['category_name']) ?></span>
                                    <span class="text-muted"><i class="fas fa-user ms-2 me-1"></i><?= e($news['author_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($news['status'] === 'published'): ?>
                                    <span class="badge bg-success mb-1">Published</span>
                                <?php elseif ($news['status'] === 'archived'): ?>
                                    <span class="badge bg-secondary mb-1">Archived</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark mb-1">Draft</span>
                                <?php endif; ?>
                                
                                <br>
                                <?php if ($news['is_featured']): ?>
                                    <span class="badge bg-primary" title="Featured"><i class="fas fa-star"></i></span>
                                <?php endif; ?>
                                <?php if ($news['is_breaking']): ?>
                                    <span class="badge bg-danger" title="Breaking"><i class="fas fa-bolt"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($news['views']) ?></td>
                            <td class="small text-muted">
                                <div><i class="fas fa-calendar-alt me-1"></i> <?= date('M j, Y', strtotime($news['created_at'])) ?></div>
                                <?php if ($news['published_at']): ?>
                                    <div class="text-success"><i class="fas fa-paper-plane me-1"></i> <?= date('M j, Y', strtotime($news['published_at'])) ?></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-3">
                                <!-- Status Toggle Form -->
                                <form method="POST" action="toggle-status.php" class="d-inline">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $news['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $news['status'] === 'published' ? 'draft' : 'published' ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-<?= $news['status'] === 'published' ? 'warning' : 'success' ?> me-1" title="<?= $news['status'] === 'published' ? 'Unpublish' : 'Publish' ?>">
                                        <i class="fas fa-<?= $news['status'] === 'published' ? 'eye-slash' : 'eye' ?>"></i>
                                    </button>
                                </form>

                                <a href="edit.php?id=<?= $news['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Delete this article? This will permanently remove the article and its associated comments and likes.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $news['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
    <!-- Reusing existing pagination rendering logic concept -->
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
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
