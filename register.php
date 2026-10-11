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
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        try {
            $pdo = connectDB();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
            $stmt->execute([':email' => $email]);
            if ($stmt->fetch()) {
                $error = "This email address is already registered.";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $insert = $pdo->prepare("INSERT INTO users (name, email, password, role, status) VALUES (:name, :email, :password, 'user', 'active')");
                $insert->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':password' => $hashed
                ]);
                
                setFlashMessage("Registration successful! You can now log in.", 'success');
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
    <title>Register — <?= e(APP_NAME) ?></title>

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
                Join our community of informed readers. Get access to the latest news and exclusive insights.
            </p>
            <div style="margin-top: 40px; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                <p style="font-size: 0.82rem; color: rgba(255,255,255,0.4);">
                    <i class="fas fa-newspaper me-2"></i> Independent Digital Journalism Community
                </p>
            </div>
        </div>
    </div>

    <!-- Right Form Area -->
    <div class="auth-form-area">
        <div class="auth-card" style="border: none; padding: 0; max-width: 400px;">
            <div class="mb-4">
                <h1 style="font-family: var(--font-display); font-size: 2rem; font-weight: 800; margin-bottom: 8px;">Create an Account</h1>
                <p style="color: var(--muted); font-size: 0.95rem;">Join us to stay updated with the latest news.</p>
            </div>
                
            <?php if ($error): ?>
                <div class="alert alert-danger"><?= e($error) ?></div>
            <?php endif; ?>
            
            <form method="POST" action="<?= url('register.php') ?>">
                <?= csrfField() ?>
                
                <div class="mb-3">
                    <label for="name" class="form-label">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($name) ?>" placeholder="Enter your full name" required autofocus>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required>
                </div>
                
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Min. 6 characters" required>
                    <div class="form-text">Must be at least 6 characters.</div>
                </div>
                
                <div class="mb-4">
                    <label for="password_confirm" class="form-label">Confirm Password</label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" placeholder="Re-enter your password" required>
                </div>
                
                <button type="submit" class="btn btn-accent w-100" style="height: 50px; font-size: 0.95rem;">
                    Create Account
                </button>
                
                <div class="text-center mt-4">
                    <span style="color: var(--muted); font-size: 0.9rem;">Already have an account?</span> 
                    <a href="<?= url('login.php') ?>" style="color: var(--accent); font-weight: 600; font-size: 0.9rem;">Login here</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
