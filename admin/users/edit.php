<?php
$pageTitle = 'Edit User';
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
    setFlashMessage("Invalid user ID.", "error");
    redirect('admin/users/index.php');
}

$pdo = connectDB();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $name = trim(post('name'));
    $role = post('role');
    $status = post('status');
    
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

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="admin-page-title mb-1">Edit User Account</h1>
                <p class="admin-page-subtitle mb-0">Modify user profile, role permissions, and system access.</p>
            </div>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left me-1"></i> Back to Users
            </a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger shadow-sm border-0 mb-4" role="alert">
                <div class="d-flex align-items-center mb-2">
                    <i class="fas fa-triangle-exclamation me-2 fs-5"></i>
                    <strong>Please correct the following errors:</strong>
                </div>
                <ul class="mb-0 ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?= e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <?php if ($id === currentUserId()): ?>
            <div class="alert alert-info shadow-sm border-0 mb-4" role="alert">
                <i class="fas fa-circle-info me-2"></i>
                <strong>Notice:</strong> You are editing your currently active session account. For security, your administrator role and active status cannot be changed here.
            </div>
        <?php endif; ?>

        <div class="admin-card mb-5">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">
                    <i class="fas fa-user-pen me-2 text-primary"></i> Account Information
                </h5>
            </div>
            <div class="admin-card-body p-4">
                <form method="POST" action="edit.php?id=<?= $id ?>" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="userEmail" class="form-label fw-semibold text-muted">Email Address (Locked)</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted"><i class="fas fa-envelope"></i></span>
                                <input type="email" id="userEmail" class="form-control bg-light" value="<?= e($user['email']) ?>" readonly disabled>
                            </div>
                            <div class="form-text">Email addresses are primary login identifiers.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required maxlength="100">
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label fw-semibold">Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role" <?= ($id === currentUserId()) ? 'disabled' : '' ?>>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator (Full control)</option>
                                <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor (Manage news & articles)</option>
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User / Reader (Comment & profile)</option>
                            </select>
                            <?php if ($id === currentUserId()): ?>
                                <input type="hidden" name="role" value="admin">
                                <div class="form-text text-muted">Self-role demotion is restricted.</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" <?= ($id === currentUserId()) ? 'disabled' : '' ?>>
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Can login)</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
                            </select>
                            <?php if ($id === currentUserId()): ?>
                                <input type="hidden" name="status" value="active">
                                <div class="form-text text-muted">Self-deactivation is restricted.</div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-save me-2"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
