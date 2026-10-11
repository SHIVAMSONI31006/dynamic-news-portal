<?php
/**
 * ============================================================
 * CONTACT PAGE — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$errors = [];
$formData = [
    'name' => '',
    'email' => '',
    'subject' => '',
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF Token
    if (!isValidCsrfToken($_POST['csrf_token'] ?? '')) {
        $errors[] = "Security token validation failed. Please try submitting the form again.";
    } else {
        // 2. Extract and trim input
        $formData['name'] = trim($_POST['name'] ?? '');
        $formData['email'] = trim($_POST['email'] ?? '');
        $formData['subject'] = trim($_POST['subject'] ?? '');
        $formData['message'] = trim($_POST['message'] ?? '');

        // 3. Validate
        if (empty($formData['name'])) {
            $errors['name'] = "Name is required.";
        } elseif (strlen($formData['name']) > 100) {
            $errors['name'] = "Name is too long (max 100 characters).";
        }

        if (empty($formData['email'])) {
            $errors['email'] = "Email is required.";
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = "Please enter a valid email address.";
        }

        if (empty($formData['subject'])) {
            $errors['subject'] = "Subject is required.";
        } elseif (strlen($formData['subject']) > 200) {
            $errors['subject'] = "Subject is too long (max 200 characters).";
        }

        if (empty($formData['message'])) {
            $errors['message'] = "Message is required.";
        } elseif (strlen($formData['message']) > 2000) {
            $errors['message'] = "Message is too long (max 2000 characters).";
        }

        // 4. Insert into database if valid
        if (empty($errors)) {
            try {
                $pdo = connectDB();
                $sql = "INSERT INTO contact_messages (name, email, subject, message, status, created_at) 
                        VALUES (:name, :email, :subject, :message, 'unread', NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    'name' => $formData['name'],
                    'email' => $formData['email'],
                    'subject' => $formData['subject'],
                    'message' => $formData['message']
                ]);

                // 5. Success - Set flash and PRG redirect
                setFlashMessage("Your message has been sent successfully. We will get back to you soon.", "success");
                redirect('contact.php');

            } catch (Exception $e) {
                $errors['database'] = "An error occurred while saving your message. Please try again later.";
            }
        }
    }
}

$pageTitle = 'Contact Us | News Portal';
$pageMetaDesc = "Get in touch with the News Portal team for questions, suggestions, or feedback.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="contact-page py-5">
    <div class="container mb-5">
        
        <!-- Header -->
        <div class="text-center mb-5 pb-3">
            <h1 class="section-title mb-3" style="font-size: 2.5rem;">Contact the Newsroom</h1>
            <p style="color: var(--muted); font-size: 1.05rem; max-width: 550px; margin: 0 auto;">
                Have a question, story idea, or message? Get in touch with us.
            </p>
        </div>

        <div class="row g-5 justify-content-center">
            
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div style="background: var(--white); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 36px;">
                    <h3 style="font-family: var(--font-display); margin-bottom: 24px; font-size: 1.5rem;">Send a Message</h3>
                    
                    <?php if (isset($errors[0])): ?>
                        <div class="alert alert-danger"><?= e($errors[0]) ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($errors['database'])): ?>
                        <div class="alert alert-danger"><?= e($errors['database']) ?></div>
                    <?php endif; ?>

                    <form action="<?= buildUrl('contact.php') ?>" method="POST" novalidate>
                        <?= csrfField() ?>
                        
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Name <span style="color: var(--accent);">*</span></label>
                                <input type="text" class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>" id="name" name="name" value="<?= e($formData['name']) ?>" placeholder="Your full name" required>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span style="color: var(--accent);">*</span></label>
                                <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($formData['email']) ?>" placeholder="you@example.com" required>
                                <?php if (isset($errors['email'])): ?>
                                    <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject <span style="color: var(--accent);">*</span></label>
                            <input type="text" class="form-control <?= isset($errors['subject']) ? 'is-invalid' : '' ?>" id="subject" name="subject" value="<?= e($formData['subject']) ?>" placeholder="What is this about?" required>
                            <?php if (isset($errors['subject'])): ?>
                                <div class="invalid-feedback"><?= e($errors['subject']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <label for="message" class="form-label">Message <span style="color: var(--accent);">*</span></label>
                            <textarea class="form-control <?= isset($errors['message']) ? 'is-invalid' : '' ?>" id="message" name="message" rows="6" placeholder="Tell us more..." required><?= e($formData['message']) ?></textarea>
                            <?php if (isset($errors['message'])): ?>
                                <div class="invalid-feedback"><?= e($errors['message']) ?></div>
                            <?php endif; ?>
                        </div>

                        <button type="submit" class="btn btn-accent" style="height: 50px; padding: 0 32px;">
                            Send Message <i class="fas fa-paper-plane ms-2"></i>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Contact Information Panel -->
            <div class="col-lg-4">
                <div style="background: var(--dark); color: var(--white); border-radius: var(--radius-md); padding: 36px; height: 100%; display: flex; flex-direction: column;">
                    <h3 style="font-family: var(--font-display); color: var(--white); margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid rgba(255,255,255,0.15); font-size: 1.5rem;">Newsroom</h3>
                    
                    <div class="mb-4">
                        <h5 style="color: var(--accent); font-family: var(--font-body); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                            <i class="fas fa-building me-2"></i>Editorial Bureau
                        </h5>
                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 0; font-size: 0.92rem;">The Newsroom Central Bureau</p>
                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 0; font-size: 0.92rem;">Press Club Enclave</p>
                    </div>
                    
                    <div class="mb-4">
                        <h5 style="color: var(--accent); font-family: var(--font-body); font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px;">
                            <i class="fas fa-map-marker-alt me-2"></i>Headquarters
                        </h5>
                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 0; font-size: 0.92rem;">Ashram Road, Navrangpura</p>
                        <p style="color: rgba(255,255,255,0.6); margin-bottom: 0; font-size: 0.92rem;">Ahmedabad, Gujarat 380009</p>
                    </div>
                    
                    <div style="margin-top: auto; padding-top: 24px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <p style="font-size: 0.82rem; color: rgba(255,255,255,0.35); margin-bottom: 0;">
                            Have a question, story idea, or message? Get in touch.
                        </p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
