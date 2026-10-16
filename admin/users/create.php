<?php
/**
 * ============================================================
 * ADMIN — CREATE NEW USER
 * ============================================================
 */
$pageTitle = 'Add New User';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Enforce Admin Only
requireAdmin();
if (!isAdmin()) {
    setFlashMessage("Access Denied: Only administrators can create user accounts.", "error");
    redirect('admin/index.php');
}

$pdo = connectDB();
$errors = [];
$name = '';
$email = '';
$role = 'user';
$status = 'active';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken(post('csrf_token'));
    
    $name = trim(post('name'));
    $email = strtolower(trim(post('email')));
    $password = post('password');
    $confirmPassword = post('confirm_password');
    $role = post('role');
    $status = post('status');
    
    // Validation
    if (empty($name)) {
        $errors[] = "Full name is required.";
    } elseif (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors[] = "Name must be between 2 and 100 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email address is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please provide a valid email address.";
    } else {
        // Check duplicate email
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
        $checkStmt->execute([':email' => $email]);
        if ($checkStmt->fetch()) {
            $errors[] = "A user account with this email address already exists.";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if ($password !== $confirmPassword) {
        $errors[] = "Password confirmation does not match.";
    }
    
    if (!in_array($role, ['admin', 'editor', 'user'], true)) {
        $errors[] = "Invalid role selected.";
    }
    
    if (!in_array($status, ['active', 'inactive'], true)) {
        $errors[] = "Invalid status selected.";
    }
    
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, status, created_at)
                VALUES (:name, :email, :password, :role, :status, NOW())
            ");
            $stmt->execute([
                ':name'     => $name,
                ':email'    => $email,
                ':password' => $hashedPassword,
                ':role'     => $role,
                ':status'   => $status
            ]);
            
            setFlashMessage("User '{$name}' created successfully with role '{$role}'.", "success");
            redirect('admin/users/index.php');
        } catch (PDOException $e) {
            $errors[] = "Database error: Could not create user record.";
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="h4 mb-1 fw-bold text-dark">Create User Account</h2>
                <p class="text-muted small mb-0">Register a new administrator, editor, or subscriber directly into the system.</p>
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

        <div class="admin-card">
            <div class="admin-card-header">
                <h5 class="admin-card-title mb-0">
                    <i class="fas fa-user-plus me-2 text-primary"></i> Account Details
                </h5>
            </div>
            <div class="admin-card-body p-4">
                <form method="POST" action="create.php" novalidate>
                    <?= csrfField() ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" required placeholder="e.g. Jane Doe">
                        </div>

                        <div class="col-md-6">
                            <label for="email" class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required placeholder="jane.doe@example.com">
                        </div>

                        <div class="col-md-6">
                            <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="Minimum 8 characters">
                            <div class="form-text">Must be at least 8 characters.</div>
                        </div>

                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="Re-type password">
                        </div>

                        <div class="col-md-6">
                            <label for="role" class="form-label fw-semibold">System Role <span class="text-danger">*</span></label>
                            <select class="form-select" id="role" name="role">
                                <option value="user" <?= $role === 'user' ? 'selected' : '' ?>>User (Subscriber / Reader)</option>
                                <option value="editor" <?= $role === 'editor' ? 'selected' : '' ?>>Editor (Can create & manage articles)</option>
                                <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Administrator (Full system control)</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label for="status" class="form-label fw-semibold">Account Status <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?= $status === 'active' ? 'selected' : '' ?>>Active (Can log in)</option>
                                <option value="inactive" <?= $status === 'inactive' ? 'selected' : '' ?>>Inactive (Suspended)</option>
                            </select>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                        <a href="index.php" class="btn btn-light px-4">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="fas fa-check me-2"></i> Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
