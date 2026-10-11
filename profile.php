<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

requireLogin();

$user_id = currentUserId();
$pdo = connectDB();

// Fetch current user data from DB
$stmt = $pdo->prepare("SELECT name, email, role, status, created_at, password FROM users WHERE id = :id");
$stmt->execute([':id' => $user_id]);
$userData = $stmt->fetch();

if (!$userData) {
    logoutUser();
    redirect('login.php');
}

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isValidCsrfToken(post('csrf_token', ''))) {
        $errors[] = "Security token validation failed. Please try again.";
    } else {
        $action = post('action', '');

        if ($action === 'update_profile') {
            $name = trim(post('name', ''));
            $email = trim(post('email', ''));

            if (empty($name) || empty($email)) {
                $errors[] = "Name and Email are required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Please enter a valid email address.";
            } else {
                // Check if email is already taken by another user
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :id");
                $checkStmt->execute([':email' => $email, ':id' => $user_id]);
                if ($checkStmt->fetch()) {
                    $errors[] = "This email address is already in use.";
                } else {
                    $updateStmt = $pdo->prepare("UPDATE users SET name = :name, email = :email WHERE id = :id");
                    $updateStmt->execute([
                        ':name' => $name,
                        ':email' => $email,
                        ':id' => $user_id
                    ]);
                    
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $userData['name'] = $name;
                    $userData['email'] = $email;
                    
                    setFlashMessage("Profile updated successfully.", "success");
                    redirect('profile.php');
                }
            }
        } elseif ($action === 'change_password') {
            $current_password = post('current_password', '');
            $new_password = post('new_password', '');
            $confirm_password = post('confirm_password', '');

            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                $errors[] = "All password fields are required.";
            } elseif ($new_password !== $confirm_password) {
                $errors[] = "New passwords do not match.";
            } elseif (strlen($new_password) < 6) {
                $errors[] = "New password must be at least 6 characters long.";
            } elseif (!password_verify($current_password, $userData['password'])) {
                $errors[] = "Current password is incorrect.";
            } else {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE users SET password = :password WHERE id = :id");
                $updateStmt->execute([
                    ':password' => $hashed,
                    ':id' => $user_id
                ]);
                
                // Regenerate session for security
                session_regenerate_id(true);
                
                setFlashMessage("Password changed successfully.", "success");
                redirect('profile.php');
            }
        }
    }
}

$pageTitle = 'My Profile | News Portal';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="profile-page py-5">
    <div class="container mb-5">
        
        <div class="text-center mb-5 pb-3">
            <h1 class="section-title mb-3" style="font-size: 2.5rem;">My Profile</h1>
            <p style="color: var(--muted); font-size: 1.05rem;">Manage your account details and password.</p>
        </div>

        <div class="row g-5 justify-content-center">
            
            <div class="col-lg-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4 text-center">
                        <div class="mb-3">
                            <div style="width: 80px; height: 80px; border-radius: 50%; background: var(--accent); color: var(--white); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 2rem; margin: 0 auto;">
                                <?= strtoupper(substr(e($userData['name']), 0, 1)) ?>
                            </div>
                        </div>
                        <h4 class="mb-1" style="font-family: var(--font-display);"><?= e($userData['name']) ?></h4>
                        <p class="text-muted mb-3"><?= e($userData['email']) ?></p>
                        
                        <div class="d-flex justify-content-between text-start mb-2 px-3">
                            <span class="text-muted">Role:</span>
                            <span class="fw-bold text-capitalize"><?= e($userData['role']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-start mb-2 px-3">
                            <span class="text-muted">Status:</span>
                            <span class="badge bg-<?= $userData['status'] === 'active' ? 'success' : 'warning text-dark' ?>"><?= ucfirst(e($userData['status'])) ?></span>
                        </div>
                        <div class="d-flex justify-content-between text-start mb-2 px-3">
                            <span class="text-muted">Joined:</span>
                            <span class="fw-bold"><?= date('M j, Y', strtotime($userData['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?= e($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h4 style="font-family: var(--font-display); margin-bottom: 24px;">Update Information</h4>
                        
                        <form action="<?= buildUrl('profile.php') ?>" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?= e($userData['name']) ?>" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?= e($userData['email']) ?>" required>
                            </div>

                            <button type="submit" class="btn btn-accent px-4 py-2">
                                Save Changes
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-body p-4">
                        <h4 style="font-family: var(--font-display); margin-bottom: 24px;">Change Password</h4>
                        
                        <form action="<?= buildUrl('profile.php') ?>" method="POST">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" placeholder="Min. 6 characters" required>
                            </div>
                            
                            <div class="mb-4">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>

                            <button type="submit" class="btn btn-dark px-4 py-2">
                                Update Password
                            </button>
                        </form>
                    </div>
                </div>

            </div>

        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
