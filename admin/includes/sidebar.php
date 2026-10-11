<?php
$currentScript = basename($_SERVER['SCRIPT_NAME']);
?>
<aside class="admin-sidebar" id="adminSidebar">
    <a href="<?= buildUrl('admin/index.php') ?>" class="sidebar-brand">
        <i class="fas fa-newspaper me-2"></i> NEWS<span class="brand-accent">PORTAL</span>
    </a>
    
    <ul class="sidebar-nav">
        <li>
            <a href="<?= buildUrl('admin/index.php') ?>" class="<?= $currentScript === 'index.php' ? 'active' : '' ?>">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>
        </li>
        
        <li class="px-3 mt-3 mb-1 text-uppercase text-muted" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">Content</li>
        
        <li>
            <a href="<?= buildUrl('admin/news/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/news/') !== false ? 'active' : '' ?>">
                <i class="fas fa-newspaper"></i> News
            </a>
        </li>
        
        <?php if (isAdminOrEditor()): ?>
        <li>
            <a href="<?= buildUrl('admin/categories/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/categories/') !== false ? 'active' : '' ?>">
                <i class="fas fa-tags"></i> Categories
            </a>
        </li>
        <?php endif; ?>
        
        <li class="px-3 mt-3 mb-1 text-uppercase text-muted" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">Engagement</li>
        
        <li>
            <a href="<?= buildUrl('admin/comments/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/comments/') !== false ? 'active' : '' ?>">
                <i class="fas fa-comments"></i> Comments
            </a>
        </li>
        <li>
            <a href="<?= buildUrl('admin/messages/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/messages/') !== false ? 'active' : '' ?>">
                <i class="fas fa-envelope"></i> Messages
            </a>
        </li>
        
        <?php if (isAdmin()): ?>
        <li>
            <a href="<?= buildUrl('admin/newsletter/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/newsletter/') !== false ? 'active' : '' ?>">
                <i class="fas fa-bullhorn"></i> Newsletter
            </a>
        </li>
        <?php endif; ?>

        <?php if (isAdmin()): ?>
        <li class="px-3 mt-3 mb-1 text-uppercase text-muted" style="font-size: 0.75rem; font-weight: 700; letter-spacing: 1px;">System</li>
        <li>
            <a href="<?= buildUrl('admin/users/index.php') ?>" class="<?= strpos($_SERVER['SCRIPT_NAME'], '/users/') !== false ? 'active' : '' ?>">
                <i class="fas fa-users"></i> Users
            </a>
        </li>
        <?php endif; ?>
    </ul>
    
    <div class="mt-auto p-3">
        <a href="<?= buildUrl('index.php') ?>" class="btn btn-outline-light w-100 btn-sm text-start" target="_blank">
            <i class="fas fa-globe me-2"></i> View Website
        </a>
    </div>
</aside>
