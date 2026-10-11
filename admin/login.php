<?php
/**
 * ============================================================
 * ADMIN LOGIN — News Portal
 * ============================================================
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';

// If already logged in, redirect away
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
        $error = "Security validation failed. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
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
                    $error = "Your account is currently inactive.";
                } else {
                    // 4. Successful Login
                    loginUser($user);
                    
                    if (isAdminOrEditor()) {
                        setFlashMessage("Welcome back, {$user['name']}!", 'success');
                        redirect('admin/index.php');
                    } else {
                        // Regular users shouldn't use admin login, but if they do, send them to homepage
                        setFlashMessage("Logged in successfully.", 'success');
                        redirect('index.php');
                    }
                }
            } else {
                // Generic error for invalid credentials
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "A system error occurred. Please try again later.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | News Portal</title>
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= buildUrl('assets/css/style.css') ?>">
    
    <style>
        body {
            background-color: var(--np-surface);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }
        .login-card {
            background: #fff;
            border-radius: var(--np-radius-lg);
            box-shadow: var(--np-shadow-md);
            padding: 3rem 2.5rem;
            width: 100%;
            max-width: 450px;
            border-top: 5px solid var(--np-accent);
        }
        .login-brand {
            font-family: var(--np-font-heading);
            font-size: 1.75rem;
            font-weight: 800;
            color: var(--np-primary);
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .login-brand .brand-accent {
            color: var(--np-accent);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="login-brand">
            <i class="fas fa-newspaper me-2"></i>NEWS<span class="brand-accent">PORTAL</span>
        </div>
        <h1 class="h5 text-muted fw-normal">Admin Login</h1>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle me-2"></i> <?= e($error) ?>
        </div>
    <?php endif; ?>
    
    <?= renderFlashMessage() ?>
    
    <form method="POST" action="<?= buildUrl('admin/login.php') ?>">
        <?= csrfField() ?>
        
        <div class="mb-3">
            <label for="email" class="form-label fw-bold">Email Address</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-envelope text-muted"></i></span>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
            </div>
        </div>
        
        <div class="mb-4">
            <label for="password" class="form-label fw-bold">Password</label>
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fas fa-lock text-muted"></i></span>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold mb-3">
            <i class="fas fa-sign-in-alt me-2"></i> Login
        </button>
        
        <div class="text-center">
            <a href="<?= buildUrl('index.php') ?>" class="text-muted text-decoration-none small">
                <i class="fas fa-arrow-left me-1"></i> Back to Website
            </a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
