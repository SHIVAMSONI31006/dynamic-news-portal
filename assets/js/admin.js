/**
 * ============================================================
 * ADMIN CMS JAVASCRIPT CONTROLLER — Newsroom
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sidebar Management (Desktop Collapse & Mobile Offcanvas)
    const sidebarToggleMobile = document.getElementById('mobileSidebarToggle') || document.getElementById('sidebarToggleMobile');
    const desktopSidebarToggle = document.getElementById('desktopSidebarToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    const adminBackdrop = document.getElementById('adminBackdrop');

    // Restore desktop collapsed state from localStorage
    if (window.innerWidth >= 992) {
        if (localStorage.getItem('adminSidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
    }

    if (desktopSidebarToggle) {
        desktopSidebarToggle.addEventListener('click', (e) => {
            e.preventDefault();
            document.body.classList.toggle('sidebar-collapsed');
            const isCollapsed = document.body.classList.contains('sidebar-collapsed');
            localStorage.setItem('adminSidebarCollapsed', isCollapsed ? 'true' : 'false');
        });
    }

    const mobileSidebarClose = document.getElementById('mobileSidebarClose');

    if (sidebarToggleMobile && adminSidebar) {
        sidebarToggleMobile.addEventListener('click', (e) => {
            e.preventDefault();
            adminSidebar.classList.toggle('show');
            if (adminBackdrop) {
                adminBackdrop.classList.toggle('show');
            }
        });

        if (mobileSidebarClose) {
            mobileSidebarClose.addEventListener('click', (e) => {
                e.preventDefault();
                adminSidebar.classList.remove('show');
                if (adminBackdrop) {
                    adminBackdrop.classList.remove('show');
                }
            });
        }

        if (adminBackdrop) {
            adminBackdrop.addEventListener('click', () => {
                adminSidebar.classList.remove('show');
                adminBackdrop.classList.remove('show');
            });
        }

        // Escape key to dismiss drawer
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                if (adminSidebar.classList.contains('show')) {
                    adminSidebar.classList.remove('show');
                    if (adminBackdrop) adminBackdrop.classList.remove('show');
                }
            }
        });
    }

    // 2. Initialize Bootstrap Tooltips
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0 && typeof bootstrap !== 'undefined') {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // 3. Auto-Dismiss Flash Messages
    const flashAlerts = document.querySelectorAll('.alert-dismissible');
    flashAlerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.remove();
            }
        }, 5000);
    });

    // 4. Live Slug Generation Helper
    const titleInput = document.getElementById('title') || document.getElementById('category_name');
    const slugInput = document.getElementById('slug');
    const slugLockBtn = document.getElementById('btnAutoSlug');

    function generateSlug(text) {
        return text
            .toString()
            .toLowerCase()
            .trim()
            .replace(/[\s\W-]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    if (slugLockBtn && titleInput && slugInput) {
        slugLockBtn.addEventListener('click', (e) => {
            e.preventDefault();
            slugInput.value = generateSlug(titleInput.value);
            slugInput.focus();
        });
    }

    if (titleInput && slugInput && slugInput.value === '') {
        titleInput.addEventListener('input', () => {
            if (!slugInput.dataset.manualEdit) {
                slugInput.value = generateSlug(titleInput.value);
            }
        });

        slugInput.addEventListener('input', () => {
            if (slugInput.value.trim() !== '') {
                slugInput.dataset.manualEdit = 'true';
            }
        });
    }

    // 5. Image File Upload Preview
    const fileInput = document.getElementById('image_upload') || document.querySelector('input[type="file"][name="image"]');
    const previewImg = document.getElementById('imagePreview');
    const previewContainer = document.getElementById('imagePreviewContainer');

    if (fileInput && previewImg) {
        fileInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    previewImg.src = e.target.result;
                    if (previewContainer) {
                        previewContainer.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    // 6. Generic Action Confirmation
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', (e) => {
            const msg = el.getAttribute('data-confirm') || 'Are you sure you want to proceed?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // 7. Explicit Dropdown Management & Keyboard Accessibility
    const userDropdownEl = document.getElementById('adminUserDropdown');
    if (userDropdownEl && typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
        new bootstrap.Dropdown(userDropdownEl);
    }
});
