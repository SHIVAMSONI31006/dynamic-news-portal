<?php
$pageTitle = 'Category Management';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Category Management</h1>
    <a href="create.php" class="btn btn-primary"><i class="fas fa-plus me-2"></i>Add Category</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="px-3">Name</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Articles</th>
                        <th>Created</th>
                        <th class="text-end px-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No categories found.</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($categories as $cat): ?>
                        <tr>
                            <td class="px-3 fw-bold"><?= e($cat['name']) ?></td>
                            <td class="text-muted"><?= e($cat['slug']) ?></td>
                            <td>
                                <?php if ($cat['status'] === 'active'): ?>
                                    <span class="badge bg-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-info rounded-pill"><?= (int)$cat['article_count'] ?></span>
                            </td>
                            <td class="small text-muted"><?= date('M j, Y', strtotime($cat['created_at'])) ?></td>
                            <td class="text-end px-3">
                                <a href="edit.php?id=<?= $cat['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <form method="POST" action="delete.php" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <?= csrfField() ?>
                                    <input type="hidden" name="id" value="<?= $cat['id'] ?>">
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
