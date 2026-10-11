<?php
/**
 * ============================================================
 * 403 FORBIDDEN — News Portal
 * ============================================================
 */
http_response_code(403);

if (!isset($pageTitle)) {
    $pageTitle = 'Access Denied';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
}

?>

<main class="container section text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="display-1 fw-bold text-muted mb-3">403</h1>
            <h2 class="h3 mb-4 text-dark">Access Denied</h2>
            <p class="text-muted mb-5">
                You do not have permission to access this page. 
                This area is restricted to authorized users only.
            </p>
            
            <div class="d-flex justify-content-center gap-3">
                <?php if (isLoggedIn() && isAdminOrEditor()): ?>
                <a href="<?= buildUrl('admin/index.php') ?>" class="btn btn-accent px-4 py-2">
                    <i class="fas fa-tachometer-alt me-2"></i> Back to Dashboard
                </a>
                <?php endif; ?>
                <a href="<?= buildUrl('index.php') ?>" class="btn btn-outline-secondary px-4 py-2">
                    <i class="fas fa-home me-2"></i> Back to Website
                </a>
            </div>
            
        </div>
    </div>
</main>

<?php
if (!isset($footerIncluded)) {
    require_once __DIR__ . '/../includes/footer.php';
    $footerIncluded = true;
}
?>
