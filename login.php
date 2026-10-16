<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

// Redirect if already logged in
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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, status FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                if ($user['status'] !== 'active') {
                    $error = "Your account is currently inactive. Please contact support.";
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
    <!-- Left Editorial Panel (Desktop / Tablet) -->
    <aside class="auth-panel">
        <div class="auth-panel-content">
            <div>
                <a href="<?= url('index.php') ?>" class="brand-link" title="Return to Homepage">
                    <div class="brand">THE NEWS<span>ROOM</span></div>
                    <div class="brand-sub">Global &bull; Perspective &bull; Insight</div>
                </a>
                
                <h2 class="tagline">
                    Trusted reporting, deep analysis, and stories that shape our global discourse.
                </h2>

                <ul class="panel-features">
                    <li>
                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                        <span>Verified independent journalism &amp; editorial standards</span>
                    </li>
                    <li>
                        <i class="fas fa-bookmark" aria-hidden="true"></i>
                        <span>Save articles and participate in verified reader discussions</span>
                    </li>
                    <li>
                        <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        <span>End-to-end encrypted session and credential security</span>
                    </li>
                </ul>
            </div>

            <div class="panel-footer">
                <a href="<?= url('index.php') ?>" class="back-home-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Return to Newsroom Homepage</span>
                </a>
                <p style="font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-top: 14px; margin-bottom: 0;">
                    &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Editorial &amp; Reader Gateway.
                </p>
            </div>
        </div>
    </aside>

    <!-- Right Form Area -->
    <main class="auth-form-area">
        <!-- Mobile Brand Header (Visible on mobile/tablet) -->
        <div class="auth-mobile-header">
            <a href="<?= url('index.php') ?>" class="brand">
                THE NEWS<span>ROOM</span>
            </a>
            <div class="brand-sub">Global &bull; Perspective &bull; Insight</div>
        </div>

        <div class="auth-card">
            <header>
                <h1>Welcome Back</h1>
                <p class="auth-subtext">Sign in to your account to continue reading and commenting.</p>
            </header>
                
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>
            
            <?= renderFlashMessage() ?>
            
            <form method="POST" action="<?= url('login.php') ?>" novalidate>
                <?= csrfField() ?>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <span>Email Address</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                        </span>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?= e($email) ?>" placeholder="name@example.com" 
                               autocomplete="username email" required autofocus>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="password" class="form-label">
                        <span>Password</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group has-toggle">
                        <span class="auth-input-icon">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                        </span>
                        <input type="password" class="form-control" id="password" name="password" 
                               placeholder="Enter your password" autocomplete="current-password" required>
                        <button type="button" class="auth-password-toggle" data-target="password" 
                                aria-label="Toggle password visibility" aria-pressed="false" tabindex="0">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-accent" data-loading-text="Signing In...">
                    Sign In
                </button>
                
                <div class="auth-switch-text">
                    <span>Don't have an account?</span> 
                    <a href="<?= url('register.php') ?>">Create an account</a>
                </div>

                <div class="auth-bottom-nav">
                    <a href="<?= url('index.php') ?>">
                        <i class="fas fa-arrow-left" aria-hidden="true"></i> Back to Homepage
                    </a>
                </div>
            </form>
        </div>
    </main>
</div>

<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
<script src="<?= url('assets/js/auth.js') ?>?v=<?= time() ?>"></script>
</body>
</html>
