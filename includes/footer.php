<?php
/**
 * ============================================================
 * PUBLIC FOOTER — News Portal
 * ============================================================
 * Common footer for all public-facing pages.
 * Includes footer content, Bootstrap JS, and custom JS.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/footer.php';
 * ============================================================
 */

// Fetch categories for footer links (reuse from navbar if available)
if (!isset($navCategories)) {
    $navCategories = [];
    try {
        require_once __DIR__ . '/../config/database.php';
        $pdo = connectDB();
        $stmt = $pdo->query("SELECT name, slug FROM categories WHERE status = 'active' ORDER BY name ASC LIMIT 8");
        $navCategories = $stmt->fetchAll();
    } catch (Exception $e) {
        $navCategories = [];
    }
}
?>

<!-- ============ FOOTER ============ -->
<footer class="site-footer">
    <div class="container">
        <div class="row g-4">

            <!-- About Column -->
            <div class="col-lg-4 col-md-6">
                <div class="footer-brand">
                    NEWS<span class="brand-accent">PORTAL</span>
                </div>
                <p class="footer-about">
                    Dynamic web-based news publishing platform built with PHP and MySQL. Browse the latest news, trending articles, and stories across multiple categories.
                </p>
                <div class="footer-social">
                    <a href="#" class="social-icon" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="social-icon" title="X / Twitter"><i class="fab fa-x-twitter"></i></a>
                    <a href="#" class="social-icon" title="Instagram"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="social-icon" title="YouTube"><i class="fab fa-youtube"></i></a>
                </div>
            </div>

            <!-- Quick Links Column -->
            <div class="col-lg-2 col-md-6">
                <h5 class="footer-title">Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?= url('index.php') ?>"><i class="fas fa-chevron-right"></i> Home</a></li>
                    <li><a href="<?= url('search.php') ?>"><i class="fas fa-chevron-right"></i> Latest</a></li>
                    <li><a href="<?= url('about.php') ?>"><i class="fas fa-chevron-right"></i> About</a></li>
                    <li><a href="<?= url('contact.php') ?>"><i class="fas fa-chevron-right"></i> Contact</a></li>
                    <li><a href="<?= url('search.php') ?>"><i class="fas fa-chevron-right"></i> Search</a></li>
                </ul>
            </div>

            <!-- Categories Column -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Categories</h5>
                <ul class="footer-links">
                    <?php if (!empty($navCategories)): ?>
                        <?php foreach (array_slice($navCategories, 0, 8) as $cat): ?>
                            <li>
                                <a href="<?= buildUrl('category.php', ['slug' => $cat['slug']]) ?>">
                                    <i class="fas fa-chevron-right"></i> <?= e($cat['name']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>

            <!-- Newsletter Column -->
            <div class="col-lg-3 col-md-6">
                <h5 class="footer-title">Stay Updated</h5>
                <p class="footer-text">Subscribe to receive the latest news directly in your inbox.</p>
                <form class="footer-newsletter" id="footerNewsletter" action="<?= url('index.php') ?>" method="POST">
                    <?= csrfField() ?>
                    <input type="hidden" name="action" value="newsletter_subscribe">
                    <div class="input-group">
                        <input type="email" name="newsletter_email" class="form-control"
                               placeholder="Your email address" required>
                        <button class="btn btn-accent" type="submit" style="border-radius: 0 var(--radius-md) var(--radius-md) 0;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </form>

                <div class="footer-contact mt-3">
                    <p><i class="fas fa-map-marker-alt"></i> GLS University, Ahmedabad</p>
                    <p><i class="fas fa-envelope"></i> info@newsportal.local</p>
                </div>
            </div>

        </div>

        <!-- Bottom Bar -->
        <div class="footer-bottom">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">
                        &copy; <?= date('Y') ?> <strong><?= e(APP_NAME) ?></strong> · GLS University
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-0">
                        TYBScIT (AI/ML) · Built with PHP & MySQL
                    </p>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- ============ SCRIPTS ============ -->

<!-- Bootstrap 5 Bundle JS (includes Popper) -->
<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>

<!-- News Portal Custom JS -->
<script src="<?= url('assets/js/main.js') ?>"></script>

<?php if (isset($extraJs)): ?>
    <?php foreach ((array) $extraJs as $js): ?>
        <script src="<?= e($js) ?>"></script>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
