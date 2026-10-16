<?php
/**
 * ============================================================
 * ADMIN LOGIN — Newsroom CMS Control Center
 * ============================================================
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// If already logged in as admin/editor, redirect to dashboard
if (isLoggedIn()) {
    if (isAdminOrEditor()) {
        redirect('admin/index.php');
    } else {
        redirect('index.php');
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(post('email', ''));
    $password = post('password', '');
    
    // 1. Verify CSRF
    if (!isValidCsrfToken(post('csrf_token', ''))) {
        $error = "Security validation failed. Please refresh and try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both your email address and password.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            // 2. Verify Credentials
            if ($user && password_verify($password, $user['password'])) {
                // 3. Check Account Status
                if ($user['status'] !== 'active') {
                    $error = "Your administrative account is currently suspended or inactive.";
                } elseif (!in_array($user['role'], ['admin', 'editor'], true)) {
                    $error = "Access Denied: Administrative privileges required.";
                } else {
                    // 4. Successful Admin Login
                    loginUser($user);
                    setFlashMessage("Welcome back to Newsroom CMS, {$user['name']}!", 'success');
                    redirect('admin/index.php');
                }
            } else {
                $error = "Invalid email address or password.";
            }
        } catch (PDOException $e) {
            $error = "A database connectivity error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Admin Login | News Portal CMS</title>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    
    <!-- Local Bootstrap 5 CSS -->
    <link href="<?= buildUrl('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Dedicated Admin Stylesheet -->
    <link rel="stylesheet" href="<?= buildUrl('assets/css/admin.css') ?>">
    
    <style>
        body.admin-login-body {
            background: radial-gradient(circle at top right, #1C2541 0%, #0B132B 60%, #060B18 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem;
            font-family: 'Inter', -apple-system, sans-serif;
            color: #E2E8F0;
        }
        .admin-login-wrapper {
            width: 100%;
            max-width: 440px;
        }
        .admin-login-card {
            background: #FFFFFF;
            color: #0F172A;
            border-radius: 14px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.4), 0 8px 10px -6px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        .login-card-header {
            background: #0B132B;
            border-bottom: 3px solid #991B1B;
            padding: 2rem 2rem 1.75rem;
            text-align: center;
        }
        .login-card-body {
            padding: 2.25rem 2rem 2rem;
        }
        .form-floating > .form-control:focus ~ label,
        .form-floating > .form-control:not(:placeholder-shown) ~ label {
            color: #991B1B;
        }
        .btn-admin-submit {
            background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%);
            border: none;
            color: #FFFFFF;
            font-weight: 600;
            padding: 0.75rem;
            border-radius: 8px;
            transition: all 180ms ease;
        }
        .btn-admin-submit:hover {
            background: linear-gradient(135deg, #EF4444 0%, #B91C1C 100%);
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(153, 27, 27, 0.4);
            transform: translateY(-1px);
        }
        .demo-pill {
            background: #F1F5F9;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 0.35rem 0.65rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 150ms ease;
        }
        .demo-pill:hover {
            background: #E2E8F0;
            border-color: #CBD5E1;
        }
    </style>
</head>
<body class="admin-login-body">

<div class="admin-login-wrapper">
    <div class="admin-login-card">
        
        <!-- Header with Brand Logo -->
        <div class="login-card-header">
            <div class="d-flex align-items-center justify-content-center gap-2 mb-2">
                <div style="width: 40px; height: 40px; background: #DC2626; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 1.25rem;">
                    <i class="fas fa-newspaper"></i>
                </div>
                <div class="text-start">
                    <span style="font-family: 'Playfair Display', serif; font-size: 1.35rem; font-weight: 800; color: #fff; line-height: 1;">
                        NEWS<span style="color: #F87171;">ROOM</span>
                    </span>
                    <div style="font-size: 0.65rem; color: #94A3B8; text-transform: uppercase; letter-spacing: 1.5px; font-weight: 700;">
                        CMS Control Center
                    </div>
                </div>
            </div>
            <p class="text-light text-opacity-75 small mb-0">Authorized Editorial Staff Authentication</p>
        </div>

        <!-- Form Body -->
        <div class="login-card-body">
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger py-2 px-3 small d-flex align-items-center mb-3" role="alert">
                    <i class="fas fa-triangle-exclamation me-2 fs-6"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <?= renderFlashMessage() ?>

            <form method="POST" action="<?= buildUrl('admin/login.php') ?>" novalidate>
                <?= csrfField() ?>

                <div class="mb-3">
                    <label for="email" class="form-label small fw-bold text-dark mb-1">Staff Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-envelope"></i>
                        </span>
                        <input type="email" class="form-control border-start-0" id="email" name="email" value="<?= e($email) ?>" required autofocus placeholder="name@newsportal.local">
                    </div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label for="password" class="form-label small fw-bold text-dark mb-0">Password</label>
                    </div>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted">
                            <i class="fas fa-lock"></i>
                        </span>
                        <input type="password" class="form-control border-start-0 border-end-0" id="password" name="password" required placeholder="••••••••">
                        <button class="btn btn-outline-secondary border-start-0 text-muted" type="button" id="togglePassword" title="Show/Hide Password" aria-label="Toggle password visibility">
                            <i class="fas fa-eye" id="togglePasswordIcon"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-admin-submit w-100 mb-3">
                    <i class="fas fa-arrow-right-to-bracket me-2"></i> Access Newsroom CMS
                </button>

                <!-- Quick Fill Helper for Examination / Testing -->
                <div class="p-2 bg-light rounded border mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-1">
                        <span class="text-muted fw-bold" style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.5px;">Staff Demo Credentials</span>
                        <i class="fas fa-key text-muted" style="font-size: 0.75rem;"></i>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="demo-pill text-dark text-truncate w-50" onclick="fillCredentials('admin@newsportal.local', 'NewsPortal@2026')">
                            <strong>Admin:</strong> admin@...
                        </button>
                        <button type="button" class="demo-pill text-dark text-truncate w-50" onclick="fillCredentials('editor@newsportal.local', 'Editor@2026')">
                            <strong>Editor:</strong> editor@...
                        </button>
                    </div>
                </div>

                <div class="text-center">
                    <a href="<?= buildUrl('index.php') ?>" class="text-muted text-decoration-none small d-inline-flex align-items-center gap-1">
                        <i class="fas fa-arrow-left"></i> Return to Live News Website
                    </a>
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Local Bootstrap JS -->
<script src="<?= buildUrl('assets/js/bootstrap.bundle.min.js') ?>"></script>

<script>
    // Password Reveal Toggle
    const toggleBtn = document.getElementById('togglePassword');
    const pwdInput = document.getElementById('password');
    const toggleIcon = document.getElementById('togglePasswordIcon');
    if (toggleBtn && pwdInput) {
        toggleBtn.addEventListener('click', function() {
            if (pwdInput.type === 'password') {
                pwdInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                pwdInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        });
    }

    // Demo Fill Helper
    function fillCredentials(email, pwd) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = pwd;
    }
</script>

</body>
</html>
