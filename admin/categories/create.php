<?php
$pageTitle = 'Add Category';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

$name = '';
$description = '';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $name = trim(post('name'));
    $description = trim(post('description'));
    $status = post('status');
    $slug = generateSlug($name);
    
    if (empty($name)) {
        setFlashMessage("Category name is required.", "error");
    } elseif (!in_array($status, ['active', 'inactive'])) {
        setFlashMessage("Invalid status selected.", "error");
    } else {
        try {
            $pdo = connectDB();
            
            // Check slug uniqueness
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = :slug");
            $stmt->execute([':slug' => $slug]);
            if ($stmt->fetchColumn() > 0) {
                $slug .= '-' . time(); // Make unique
            }
            
            $stmt = $pdo->prepare("INSERT INTO categories (name, slug, description, status) VALUES (:name, :slug, :description, :status)");
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':status' => $status
            ]);
            
            setFlashMessage("Category added successfully.", "success");
            redirect('admin/categories/index.php');
            
        } catch (PDOException $e) {
            setFlashMessage("Database error: Could not save category.", "error");
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">Add New Category</h2>
                <p class="text-muted small mb-0">Define a new editorial section with an automated URL slug.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Categories
            </a>
        </div>

        <div class="admin-card">
            <div class="admin-card-header py-3">
                <h5 class="admin-card-title mb-0">
                    <i class="fas fa-tag me-2 text-primary"></i> Taxonomy Configuration
                </h5>
            </div>
            <div class="admin-card-body p-4">
                <form method="POST" action="" novalidate>
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-dark mb-1">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100" placeholder="e.g. Artificial Intelligence">
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold text-dark mb-1">Editorial Scope / Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief outline of topics covered under this category..."><?= e($description) ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="form-label small fw-semibold text-dark mb-1">Visibility Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Visible in public navigation)</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-check me-2"></i> Save Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
