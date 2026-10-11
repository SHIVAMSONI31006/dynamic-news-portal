/**
 * ============================================================
 * MAIN JAVASCRIPT — News Portal
 * ============================================================
 * General reusable JavaScript for the public site.
 * Admin-specific JS should go in a separate file later if needed.
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Bootstrap Tooltips (if any exist)
    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    if (tooltipTriggerList.length > 0 && typeof bootstrap !== 'undefined') {
        [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
    }

    // 2. Auto-dismiss Flash Messages after 5 seconds
    const flashMessages = document.querySelectorAll('.alert-dismissible');
    flashMessages.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.remove();
            }
        }, 5000);
    });

    // 3. Confirm Dangerous Actions
    // Usage: <a href="delete.php" class="confirm-action" data-confirm-msg="Are you sure?">Delete</a>
    const confirmActions = document.querySelectorAll('.confirm-action');
    confirmActions.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const msg = btn.getAttribute('data-confirm-msg') || 'Are you sure you want to perform this action?';
            if (!confirm(msg)) {
                e.preventDefault();
            }
        });
    });

    // 4. Newsletter Form basic validation (optional UX enhancement)
    const newsletterForm = document.getElementById('footerNewsletter');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            const btn = newsletterForm.querySelector('button[type="submit"]');
            if (btn) {
                // Prevent double submission
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            }
        });
    }
});
