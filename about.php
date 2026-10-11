<?php
/**
 * ============================================================
 * ABOUT PAGE — News Portal
 * ============================================================
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

$pageTitle = 'About | News Portal';
$pageMetaDesc = "Learn about the News Portal, a dynamic news publishing platform built as a college project.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<main class="about-page">
    <!-- Hero Section -->
    <div class="py-5 mb-5" style="background-color: var(--dark); color: white;">
        <div class="container py-4 text-center">
            <h1 style="font-family: var(--font-display); font-size: 2.8rem; font-weight: 800; color: var(--white); margin-bottom: 16px;">About the Project</h1>
            <p style="max-width: 650px; margin: 0 auto; color: rgba(255,255,255,0.65); font-size: 1.05rem; line-height: 1.7;">
                A dynamic news publishing platform designed to organize and present current news stories across multiple categories.
            </p>
        </div>
    </div>

    <div class="container mb-5 pb-4">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <section class="mb-5">
                    <h2 class="section-title mb-4" style="font-size: 1.8rem;">The Idea</h2>
                    <p style="font-size: 1.05rem; color: var(--muted); line-height: 1.8;">
                        The News Portal is a dynamic, database-driven news publishing system. It allows administrators and editors to create, manage, and publish news articles dynamically using PHP and MySQL. Visitors can seamlessly browse, search, and read published news content organized into clear, accessible categories.
                    </p>
                </section>

                <section class="mb-5">
                    <h2 class="section-title mb-4" style="font-size: 1.8rem;">How It Works</h2>
                    <p style="color: var(--muted); line-height: 1.8; font-size: 1.05rem;">
                        The platform operates through a custom-built Content Management System (CMS). Editors log into a secure dashboard to draft articles, assign categories, upload featured images, and set publication statuses. Only articles explicitly marked as 'Published' are visible to the public, ensuring a controlled editorial workflow.
                    </p>
                </section>

                <section class="mb-5">
                    <h2 class="section-title mb-4" style="font-size: 1.8rem;">Technology</h2>
                    <div class="row g-4 mt-2">
                        <div class="col-md-6">
                            <div style="padding: 28px; background: var(--paper); border-radius: var(--radius-md); border: 1px solid var(--line); height: 100%;">
                                <h4 style="font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 16px;">
                                    <i class="fas fa-server me-2" style="color: var(--accent);"></i> Backend Engine
                                </h4>
                                <ul style="list-style: none; padding: 0; margin: 0; color: var(--muted); font-size: 0.95rem;">
                                    <li style="margin-bottom: 10px;"><i class="fas fa-check me-2" style="color: #2e7d32;"></i> Native PHP 8+</li>
                                    <li style="margin-bottom: 10px;"><i class="fas fa-check me-2" style="color: #2e7d32;"></i> MySQL Database</li>
                                    <li><i class="fas fa-check me-2" style="color: #2e7d32;"></i> PDO (Prepared Statements)</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div style="padding: 28px; background: var(--paper); border-radius: var(--radius-md); border: 1px solid var(--line); height: 100%;">
                                <h4 style="font-family: var(--font-display); font-size: 1.1rem; margin-bottom: 16px;">
                                    <i class="fas fa-laptop-code me-2" style="color: var(--accent);"></i> Frontend Interface
                                </h4>
                                <ul style="list-style: none; padding: 0; margin: 0; color: var(--muted); font-size: 0.95rem;">
                                    <li style="margin-bottom: 10px;"><i class="fas fa-check me-2" style="color: #2e7d32;"></i> HTML5 & CSS3</li>
                                    <li style="margin-bottom: 10px;"><i class="fas fa-check me-2" style="color: #2e7d32;"></i> Bootstrap 5 Framework</li>
                                    <li><i class="fas fa-check me-2" style="color: #2e7d32;"></i> Vanilla JavaScript</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="mb-5">
                    <h2 class="section-title mb-4" style="font-size: 1.8rem;">Core Features</h2>
                    <div style="border-top: 1px solid var(--line);">
                        <div style="padding: 16px 0; border-bottom: 1px solid var(--soft); display: flex; align-items: center; gap: 16px;">
                            <i class="fas fa-newspaper" style="color: var(--accent); width: 20px; text-align: center;"></i>
                            <span style="color: var(--muted);"><strong style="color: var(--ink);">Dynamic News Delivery:</strong> Real-time publishing and updates.</span>
                        </div>
                        <div style="padding: 16px 0; border-bottom: 1px solid var(--soft); display: flex; align-items: center; gap: 16px;">
                            <i class="fas fa-folder-open" style="color: var(--accent); width: 20px; text-align: center;"></i>
                            <span style="color: var(--muted);"><strong style="color: var(--ink);">Categorization:</strong> Structured organization of topics like Technology, Business, etc.</span>
                        </div>
                        <div style="padding: 16px 0; border-bottom: 1px solid var(--soft); display: flex; align-items: center; gap: 16px;">
                            <i class="fas fa-search" style="color: var(--accent); width: 20px; text-align: center;"></i>
                            <span style="color: var(--muted);"><strong style="color: var(--ink);">Robust Search:</strong> Full-text keyword searching across all published articles.</span>
                        </div>
                        <div style="padding: 16px 0; display: flex; align-items: center; gap: 16px;">
                            <i class="fas fa-shield-alt" style="color: var(--accent); width: 20px; text-align: center;"></i>
                            <span style="color: var(--muted);"><strong style="color: var(--ink);">Security:</strong> Protected against XSS, SQL Injection, and CSRF attacks.</span>
                        </div>
                    </div>
                </section>

                <section style="background: var(--paper); border: 1px solid var(--line); border-radius: var(--radius-md); padding: 36px;">
                    <h3 style="font-family: var(--font-display); font-size: 1.3rem; margin-bottom: 12px;">Project Context</h3>
                    <p style="color: var(--muted); margin-bottom: 0; line-height: 1.7;">
                        This News Portal is developed as an educational project for the <strong style="color: var(--ink);">TYBScIT (AI/ML)</strong> program at <strong style="color: var(--ink);">GLS University</strong>. It demonstrates practical implementation of full-stack web development principles, database architecture, and application security.
                    </p>
                </section>

            </div>
        </div>
    </div>
</main>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
