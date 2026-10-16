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
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim(post('name', ''));
    $email = trim(post('email', ''));
    $password = post('password', '');
    $password_confirm = post('password_confirm', '');
    
    if (!isValidCsrfToken(post('csrf_token', ''))) {
        $error = "Security validation failed. Please try again.";
    } elseif (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif (mb_strlen($name) < 2) {
        $error = "Please enter your full name (at least 2 characters).";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = "This email address is already registered. Please sign in or use another email.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, 'user', 'active')");
                $insert->execute([
                    ':name'     => $name,
                    ':email'    => $email,
                    ':password' => $hashed
                ]);
                
                setFlashMessage("Registration successful! You can now log in with your credentials.", 'success');
                redirect('login.php');
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
    <title>Create an Account — <?= e(APP_NAME) ?></title>

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
                    Join an informed community of readers, researchers, and global citizens.
                </h2>

                <ul class="panel-features">
                    <li>
                        <i class="fas fa-newspaper" aria-hidden="true"></i>
                        <span>Unlimited access to breaking news &amp; editorial investigations</span>
                    </li>
                    <li>
                        <i class="fas fa-comments" aria-hidden="true"></i>
                        <span>Contribute thoughtfully to moderated article comment discussions</span>
                    </li>
                    <li>
                        <i class="fas fa-bell" aria-hidden="true"></i>
                        <span>Personalized topic alerts and morning intelligence briefings</span>
                    </li>
                    <li>
                        <i class="fas fa-lock" aria-hidden="true"></i>
                        <span>Zero data reselling &bull; Industry standard bcrypt credential security</span>
                    </li>
                </ul>
            </div>

            <div class="panel-footer">
                <a href="<?= url('index.php') ?>" class="back-home-link">
                    <i class="fas fa-arrow-left" aria-hidden="true"></i>
                    <span>Return to Newsroom Homepage</span>
                </a>
                <p style="font-size: 0.78rem; color: rgba(255,255,255,0.45); margin-top: 14px; margin-bottom: 0;">
                    &copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. Independent Journalism Community.
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
                <h1>Create an Account</h1>
                <p class="auth-subtext">Join our reader community to engage with investigative journalism.</p>
            </header>
                
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-circle" aria-hidden="true"></i>
                    <div><?= e($error) ?></div>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="<?= url('register.php') ?>" novalidate>
                <?= csrfField() ?>
                
                <div class="mb-3">
                    <label for="reg_name" class="form-label">
                        <span>Full Name</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon">
                            <i class="fas fa-user" aria-hidden="true"></i>
                        </span>
                        <input type="text" class="form-control" id="reg_name" name="name" 
                               value="<?= e($name) ?>" placeholder="e.g. Eleanor Vance" 
                               autocomplete="name" required autofocus>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="reg_email" class="form-label">
                        <span>Email Address</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group">
                        <span class="auth-input-icon">
                            <i class="fas fa-envelope" aria-hidden="true"></i>
                        </span>
                        <input type="email" class="form-control" id="reg_email" name="email" 
                               value="<?= e($email) ?>" placeholder="name@example.com" 
                               autocomplete="email" required>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="reg_password" class="form-label">
                        <span>Password</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group has-toggle">
                        <span class="auth-input-icon">
                            <i class="fas fa-lock" aria-hidden="true"></i>
                        </span>
                        <input type="password" class="form-control" id="reg_password" name="password" 
                               placeholder="Minimum 6 characters" autocomplete="new-password" required>
                        <button type="button" class="auth-password-toggle" data-target="reg_password" 
                                aria-label="Toggle password visibility" aria-pressed="false" tabindex="0">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="password-meter-wrap">
                        <div class="password-meter" aria-hidden="true">
                            <div class="password-meter-bar" id="passwordMeterBar"></div>
                        </div>
                        <div class="password-meter-text" id="passwordMeterText"></div>
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="reg_password_confirm" class="form-label">
                        <span>Confirm Password</span>
                        <span class="req" title="Required">*</span>
                    </label>
                    <div class="auth-input-group has-toggle">
                        <span class="auth-input-icon">
                            <i class="fas fa-shield-alt" aria-hidden="true"></i>
                        </span>
                        <input type="password" class="form-control" id="reg_password_confirm" name="password_confirm" 
                               placeholder="Re-enter your password" autocomplete="new-password" required>
                        <button type="button" class="auth-password-toggle" data-target="reg_password_confirm" 
                                aria-label="Toggle confirm password visibility" aria-pressed="false" tabindex="0">
                            <i class="far fa-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="password-match-feedback" id="passwordMatchFeedback"></div>
                </div>
                
                <button type="submit" class="btn btn-accent" data-loading-text="Creating Account...">
                    Create Account
                </button>
                
                <div class="auth-switch-text">
                    <span>Already have an account?</span> 
                    <a href="<?= url('login.php') ?>">Sign in here</a>
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
