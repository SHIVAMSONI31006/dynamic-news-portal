<?php
$pageTitle = 'Edit Category';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

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
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit Category</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body">
        <form method="POST" action="">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label fw-bold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3"><?= e($description) ?></textarea>
            </div>
            
            <div class="mb-4">
                <label for="status" class="form-label fw-bold">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update Category</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
