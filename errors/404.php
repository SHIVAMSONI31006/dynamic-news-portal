<?php
/**
 * ============================================================
 * 404 NOT FOUND — News Portal
 * ============================================================
 */
http_response_code(404);

// This might be included from another file (like news.php), so we check if header is already included
if (!isset($pageTitle)) {
    $pageTitle = 'Page Not Found';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/header.php';
    require_once __DIR__ . '/../includes/navbar.php';
}

?>

<main class="container section text-center py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <h1 class="display-1 fw-bold text-muted mb-3">404</h1>
            <h2 class="h3 mb-4 text-dark">Page Not Found</h2>
            <p class="text-muted mb-5">
                The article or page you're looking for could not be found. 
                It might have been removed, renamed, or did not exist in the first place.
            </p>
            <a href="<?= buildUrl('index.php') ?>" class="btn btn-accent px-4 py-2">
                <i class="fas fa-home me-2"></i> Back to Homepage
            </a>
            
            <div class="mt-5 pt-4 border-top">
                <p class="text-muted small mb-3">Or try searching for news:</p>
                <form action="<?= buildUrl('search.php') ?>" method="GET" class="d-flex max-w-md mx-auto" style="max-width: 400px;">
                    <input class="form-control me-2" type="search" name="q" placeholder="Search news..." required>
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
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
