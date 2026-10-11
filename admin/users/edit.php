<?php
$pageTitle = 'Edit User';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../includes/header.php';

if (!isAdmin()) {
    setFlashMessage("Access Denied.", "error");
    redirect('admin/index.php');
}

$id = (int)get('id', 0);
if ($id <= 0) {
    setFlashMessage("Invalid user ID.", "error");
    redirect('admin/users/index.php');
}

$pdo = connectDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $name = trim(post('name'));
    $role = post('role');
    $status = post('status');
    
    $errors = [];
    
    if (empty($name)) $errors[] = "Name is required.";
    if (!in_array($role, ['admin', 'editor', 'user'])) $errors[] = "Invalid role.";
    if (!in_array($status, ['active', 'inactive'])) $errors[] = "Invalid status.";
    
    // Self-protection
    if ($id === currentUserId()) {
        if ($role !== 'admin') $errors[] = "You cannot remove your own admin privileges.";
        if ($status !== 'active') $errors[] = "You cannot deactivate your own account.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET name = :name, role = :role, status = :status WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':role' => $role,
                ':status' => $status,
                ':id' => $id
            ]);
            
            setFlashMessage("User updated successfully.", "success");
            redirect('admin/users/index.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not update user.";
        }
    }
    
    if (!empty($errors)) {
        foreach ($errors as $err) {
            echo "<div class='alert alert-danger mx-3 mt-3'>" . e($err) . "</div>";
        }
    }
}

try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        setFlashMessage("User not found.", "error");
        redirect('admin/users/index.php');
    }
    
    $name = $_POST['name'] ?? $user['name'];
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];
    
} catch (PDOException $e) {
    setFlashMessage("Database error.", "error");
    redirect('admin/users/index.php');
}
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">Edit User</h1>
    <a href="index.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Back to List</a>
</div>

<div class="card shadow-sm border-0 mb-5">
    <div class="card-body">
        <?php if ($id === currentUserId()): ?>
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i> You are editing your own account. Some actions (like deactivating or changing role) are disabled for your safety.
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <?= csrfField() ?>
            
            <div class="mb-3">
                <label class="form-label text-muted">Email (Read Only)</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" readonly disabled>
            </div>
            
            <div class="mb-3">
                <label for="name" class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
            </div>
            
            <div class="row">
                <div class="col-md-6 mb-4">
                    <label for="role" class="form-label fw-bold">Role</label>
                    <select class="form-select" id="role" name="role" <?= ($id === currentUserId()) ? 'readonly style="pointer-events:none;"' : '' ?>>
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor</option>
                        <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User</option>
                    </select>
                </div>
                
                <div class="col-md-6 mb-4">
                    <label for="status" class="form-label fw-bold">Status</label>
                    <select class="form-select" id="status" name="status" <?= ($id === currentUserId()) ? 'readonly style="pointer-events:none;"' : '' ?>>
                        <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary"><i class="fas fa-save me-2"></i>Update User</button>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
