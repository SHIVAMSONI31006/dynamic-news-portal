<?php
$pageTitle = 'Category Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';

// ONLY Admin can access categories. Editors are blocked.
if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

try {
    $pdo = connectDB();
    
    // Fetch categories with article count
    $stmt = $pdo->query("
        SELECT c.*, COUNT(n.id) as article_count 
        FROM categories c
        LEFT JOIN news n ON c.id = n.category_id
        GROUP BY c.id
        ORDER BY c.name ASC
    ");
    $categories = $stmt->fetchAll();
    
} catch (PDOException $e) {
    setFlashMessage("Failed to retrieve categories.", "error");
    $categories = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
    <div>
        <h2 class="h4 mb-1 text-dark fw-bold">Content Taxonomies</h2>
        <p class="text-muted small mb-0">Manage news categories, URL slugs, and coverage structure.</p>
    </div>
    <a href="create.php" class="btn btn-primary btn-sm px-3 shadow-sm">
        <i class="fas fa-plus me-1"></i> Add Category
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-admin align-middle mb-0">
            <thead>
                <tr>
                    <th class="px-3" style="min-width: 180px;">Category Name</th>
                    <th>URL Slug</th>
                    <th>Status</th>
                    <th>Published Articles</th>
                    <th>Created</th>
                    <th class="text-end px-3">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5">
                        <div class="admin-empty-state">
                            <div class="admin-empty-icon"><i class="fas fa-tags"></i></div>
                            <div class="admin-empty-title">No categories created</div>
                            <div class="admin-empty-desc">Create your first news topic to categorize incoming stories.</div>
                            <a href="create.php" class="btn btn-primary btn-sm mt-2">Add Category</a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($categories as $cat): ?>
                    <tr>
                        <td class="px-3 fw-semibold text-dark">
                            <i class="fas fa-folder me-2 text-primary opacity-75"></i><?= e($cat['name']) ?>
                        </td>
                        <td>
                            <code class="text-secondary small bg-light px-2 py-1 rounded border">/category/<?= e($cat['slug']) ?></code>
                        </td>
                        <td>
                            <?php if ($cat['status'] === 'active'): ?>
                                <span class="badge-status-published"><i class="fas fa-circle" style="font-size: 6px;"></i> Active</span>
                            <?php else: ?>
                                <span class="badge-status-draft"><i class="fas fa-circle" style="font-size: 6px;"></i> Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border px-2 py-1 fw-bold"><?= (int)$cat['article_count'] ?> stories</span>
                        </td>
                        <td class="small text-muted"><?= date('M j, Y', strtotime($cat['created_at'])) ?></td>
                        <td class="text-end px-3">
                            <div class="d-inline-flex gap-1 align-items-center">
                                <a href="edit.php?id=<?= $cat['id'] ?>" class="btn-action btn-action-edit" title="Edit Category">
                                    <i class="fas fa-pen-to-square"></i>
                                </a>
                                <form method="POST" action="delete.php" class="d-inline m-0" onsubmit="return confirm('Permanently delete this category? Make sure no articles rely on it first.');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
                                    <button type="submit" class="btn-action btn-action-delete" title="Delete Category">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
