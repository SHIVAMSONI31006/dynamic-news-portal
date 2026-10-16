<?php
$pageTitle = 'Edit Category';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

requireAdmin();
if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

$id = (int)get('id', 0);
if ($id <= 0) {
    setFlashMessage("Invalid category ID.", "error");
    redirect('admin/categories/index.php');
}

$pdo = connectDB();

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
            // Check slug uniqueness excluding current category
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = :slug AND id != :id");
            $stmt->execute([':slug' => $slug, ':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                $slug .= '-' . time();
            }
            
            $stmt = $pdo->prepare("UPDATE categories SET name = :name, slug = :slug, description = :description, status = :status WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':description' => $description,
                ':status' => $status,
                ':id' => $id
            ]);
            
            setFlashMessage("Category updated successfully.", "success");
            redirect('admin/categories/index.php');
        } catch (PDOException $e) {
            setFlashMessage("Database error: Could not update category.", "error");
        }
    }
}

// Fetch current category
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $category = $stmt->fetch();
    
    if (!$category) {
        setFlashMessage("Category not found.", "error");
        redirect('admin/categories/index.php');
    }
    
    $name = $_POST['name'] ?? $category['name'];
    $description = $_POST['description'] ?? $category['description'];
    $status = $_POST['status'] ?? $category['status'];
    
} catch (PDOException $e) {
    setFlashMessage("Database error.", "error");
    redirect('admin/categories/index.php');
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">Edit Category</h2>
                <p class="text-muted small mb-0">Modify category name, description, and public portal visibility.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Categories
            </a>
        </div>

        <div class="admin-card">
            <div class="admin-card-header py-3">
                <h5 class="admin-card-title mb-0">
                    <i class="fas fa-pen-to-square me-2 text-primary"></i> Edit Taxonomy Details
                </h5>
            </div>
            <div class="admin-card-body p-4">
                <form method="POST" action="" novalidate>
                    <?= csrfField() ?>
                    
                    <div class="mb-3">
                        <label for="name" class="form-label small fw-semibold text-dark mb-1">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted mb-1">Current URL Slug</label>
                        <input type="text" class="form-control bg-light text-muted" value="/category/<?= e($category['slug']) ?>" readonly disabled>
                        <div class="form-text small">Slug will be automatically regenerated and updated from the new name.</div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label small fw-semibold text-dark mb-1">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"><?= e($description) ?></textarea>
                    </div>
                    
                    <div class="mb-4">
                        <label for="status" class="form-label small fw-semibold text-dark mb-1">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Visible in public navigation)</option>
                            <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Hidden)</option>
                        </select>
                    </div>
                    
                    <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-check me-2"></i> Update Category
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
