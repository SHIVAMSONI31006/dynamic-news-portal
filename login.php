<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// redirect if logged in
if (isLoggedIn()) {
    redirect('index.php');
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim(post('email', ''));
    $password = post('password', '');
    
    if (!isValidCsrfToken(post('csrf_token', ''))) {
        $error = "Security validation failed. Please try again.";
    } elseif (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is currently inactive.";
                } else {
                    loginUser($user);
                    setFlashMessage("Welcome back, {$user['name']}!", 'success');
                    redirect('index.php');
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "A system error occurred. Please try again later.";
        }
    }
}

// Security Headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>Login — <?= e(APP_NAME) ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Playfair+Display:wght@600;700;800;900&display=swap" rel="stylesheet">
    <link href="<?= url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet"
          integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous">
    <link href="<?= url('assets/css/style.css') ?>?v=<?= time() ?>" rel="stylesheet">
</head>
<body>
<div class="auth-page">
    <!-- Left Editorial Panel -->
    <div class="auth-panel">
        <div class="auth-panel-content">
            <a href="<?= url('index.php') ?>" style="text-decoration: none;">
                <div class="brand">NEWS<span>ROOM</span></div>
            </a>
            <p class="tagline">
                Your trusted source for the latest news, insights, and stories that shape our world.
            </p>
            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                <p style="font-size: 0.82rem; color: rgba(255,255,255,0.4);">
                    <i class="fas fa-shield-alt me-2"></i> Editorial &amp; Reader Secure Portal
                </p>
            </div>
        </div>
    </div>

    <!-- Right Form Area -->
    <div class="auth-form-area">
        <div class="auth-card" style="border: none; padding: 0; max-width: 400px;">
            <div class="mb-4">
                <h1 style="font-family: var(--font-display); font-size: 2rem; font-weight: 800; margin-bottom: 8px;">Welcome Back</h1>
                <p style="color: var(--muted); font-size: 0.95rem;">Sign in to your account to continue.</p>
            </div>
                
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            
            <?= renderFlashMessage() ?>
            
            <form method="POST" action="<?= url('login.php') ?>">
                <?= csrfField() ?>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required autofocus>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-accent w-100" style="height: 50px; font-size: 0.95rem;">
                    Sign In
                </button>
                
                <div class="text-center mt-4">
                    <span style="color: var(--muted); font-size: 0.9rem;">Don't have an account?</span> 
                    <a href="<?= url('register.php') ?>" style="color: var(--accent); font-weight: 600; font-size: 0.9rem;">Register here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
