<?php
/**
 * ============================================================
 * ADMIN SIDEBAR — News Portal CMS
 * ============================================================
 */
$currentScript = basename($_SERVER['SCRIPT_NAME'] ?? '');
$currentPath = $_SERVER['SCRIPT_NAME'] ?? '';

// Real database badge counts
$unreadMessagesCount = 0;
$pendingCommentsCount = 0;
try {
    require_once __DIR__ . '/../../config/database.php';
    $db = connectDB();
    $unreadMessagesCount = (int)$db->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'")->fetchColumn();
    $pendingCommentsCount = (int)$db->query("SELECT COUNT(*) FROM comments WHERE status = 'pending'")->fetchColumn();
} catch (Throwable $e) {
    // Graceful fallback
}
?>
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-header">
        <a href="<?= buildUrl('admin/index.php') ?>" class="sidebar-brand">
            <div class="brand-logo-badge">
                <i class="fas fa-newspaper"></i>
            </div>
            <div class="brand-text">
                NEWS<span>ROOM</span>
                <div class="brand-badge">CMS CONTROL</div>
            </div>
        </a>
        <button type="button" class="sidebar-close-btn d-lg-none" id="mobileSidebarClose" aria-label="Close navigation">
            <i class="fas fa-xmark"></i>
        </button>
    </div>
    
    <div class="sidebar-scroll">
        <div class="sidebar-section-title">Core</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= buildUrl('admin/index.php') ?>" class="<?= $currentScript === 'index.php' && strpos($currentPath, '/admin/index.php') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
        </ul>

        <div class="sidebar-section-title">Editorial & Content</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= buildUrl('admin/news/index.php') ?>" class="<?= strpos($currentPath, '/news/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-file-lines"></i>
                    <span>Articles / News</span>
                </a>
            </li>
            <?php if (isAdminOrEditor()): ?>
            <li>
                <a href="<?= buildUrl('admin/categories/index.php') ?>" class="<?= strpos($currentPath, '/categories/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-tags"></i>
                    <span>Categories</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <div class="sidebar-section-title">Audience & Feedback</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= buildUrl('admin/comments/index.php') ?>" class="<?= strpos($currentPath, '/comments/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-comments"></i>
                    <span>Comments</span>
                    <?php if ($pendingCommentsCount > 0): ?>
                        <span class="nav-badge bg-warning text-dark"><?= $pendingCommentsCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li>
                <a href="<?= buildUrl('admin/messages/index.php') ?>" class="<?= strpos($currentPath, '/messages/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-envelope"></i>
                    <span>Messages</span>
                    <?php if ($unreadMessagesCount > 0): ?>
                        <span class="nav-badge bg-danger text-white"><?= $unreadMessagesCount ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <?php if (isAdmin()): ?>
            <li>
                <a href="<?= buildUrl('admin/newsletter/index.php') ?>" class="<?= strpos($currentPath, '/newsletter/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-paper-plane"></i>
                    <span>Newsletter</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>

        <?php if (isAdmin()): ?>
        <div class="sidebar-section-title">Administration</div>
        <ul class="sidebar-nav">
            <li>
                <a href="<?= buildUrl('admin/users/index.php') ?>" class="<?= strpos($currentPath, '/users/') !== false ? 'active' : '' ?>">
                    <i class="fas fa-users-gear"></i>
                    <span>User Management</span>
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>
    
    <div class="sidebar-footer">
        <a href="<?= buildUrl('index.php') ?>" class="btn btn-outline-light w-100 btn-sm d-flex align-items-center justify-content-center gap-2 py-2" target="_blank" rel="noopener">
            <i class="fas fa-arrow-up-right-from-square"></i>
            <span class="view-site-text">View Public Site</span>
        </a>
    </div>
</aside>
