/**
 * ============================================================
 * AUTHENTICATION PAGES JAVASCRIPT — News Portal
 * ============================================================
 * Handles password visibility toggling, client-side validation,
 * password strength metering, and submit loading states.
 * ============================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Password Visibility Toggle
    const toggleButtons = document.querySelectorAll('.auth-password-toggle');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = btn.querySelector('i');
            
            if (!input) return;
            
            if (input.type === 'password') {
                input.type = 'text';
                if (icon) {
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                }
                btn.setAttribute('aria-label', 'Hide password');
                btn.setAttribute('aria-pressed', 'true');
            } else {
                input.type = 'password';
                if (icon) {
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
                btn.setAttribute('aria-label', 'Show password');
                btn.setAttribute('aria-pressed', 'false');
            }
            input.focus();
        });
    });

    // 2. Real-time Registration Password Strength and Match Meter
    const regPassword = document.getElementById('reg_password');
    const regConfirm = document.getElementById('reg_password_confirm');
    const meterBar = document.getElementById('passwordMeterBar');
    const meterText = document.getElementById('passwordMeterText');
    const matchFeedback = document.getElementById('passwordMatchFeedback');

    function checkPasswordStrength(pass) {
        if (!meterBar || !meterText) return;
        
        let score = 0;
        if (pass.length >= 6) score++;
        if (pass.length >= 10) score++;
        if (/[A-Z]/.test(pass)) score++;
        if (/[0-9]/.test(pass)) score++;
        if (/[^A-Za-z0-9]/.test(pass)) score++;

        if (pass.length === 0) {
            meterBar.style.width = '0%';
            meterBar.style.backgroundColor = 'transparent';
            meterText.textContent = '';
        } else if (pass.length < 6) {
            meterBar.style.width = '25%';
            meterBar.style.backgroundColor = '#ef4444'; // Red
            meterText.innerHTML = '<span style="color:#ef4444;">Too short (min. 6 characters)</span>';
        } else if (score <= 2) {
            meterBar.style.width = '50%';
            meterBar.style.backgroundColor = '#f59e0b'; // Amber
            meterText.innerHTML = '<span style="color:#f59e0b;">Moderate strength</span>';
        } else if (score <= 4) {
            meterBar.style.width = '75%';
            meterBar.style.backgroundColor = '#10b981'; // Green
            meterText.innerHTML = '<span style="color:#10b981;">Good password</span>';
        } else {
            meterBar.style.width = '100%';
            meterBar.style.backgroundColor = '#059669'; // Emerald
            meterText.innerHTML = '<span style="color:#059669;">Strong password</span>';
        }
    }

    function checkPasswordMatch() {
        if (!regPassword || !regConfirm || !matchFeedback) return;
        
        const pass = regPassword.value;
        const confirm = regConfirm.value;

        if (confirm.length === 0) {
            matchFeedback.style.display = 'none';
            matchFeedback.className = 'password-match-feedback';
            matchFeedback.textContent = '';
            return;
        }

        if (pass === confirm) {
            matchFeedback.style.display = 'block';
            matchFeedback.className = 'password-match-feedback valid';
            matchFeedback.innerHTML = '<i class="fas fa-check-circle me-1"></i> Passwords match';
        } else {
            matchFeedback.style.display = 'block';
            matchFeedback.className = 'password-match-feedback invalid';
            matchFeedback.innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match';
        }
    }

    if (regPassword) {
        regPassword.addEventListener('input', () => {
            checkPasswordStrength(regPassword.value);
            if (regConfirm && regConfirm.value.length > 0) {
                checkPasswordMatch();
            }
        });
    }

    if (regConfirm) {
        regConfirm.addEventListener('input', checkPasswordMatch);
    }

    // 3. Form Submit Loading State & Double-Click Prevention
    const authForms = document.querySelectorAll('.auth-card form');
    authForms.forEach(form => {
        form.addEventListener('submit', (e) => {
            // Trim whitespace on text and email inputs
            const textInputs = form.querySelectorAll('input[type="text"], input[type="email"]');
            textInputs.forEach(input => {
                input.value = input.value.trim();
            });

            // If registration, verify passwords match before sending
            if (regPassword && regConfirm) {
                if (regPassword.value !== regConfirm.value) {
                    e.preventDefault();
                    if (matchFeedback) {
                        matchFeedback.style.display = 'block';
                        matchFeedback.className = 'password-match-feedback invalid';
                        matchFeedback.innerHTML = '<i class="fas fa-times-circle me-1"></i> Passwords do not match.';
                    }
                    regConfirm.focus();
                    return;
                }
                if (regPassword.value.length < 6) {
                    e.preventDefault();
                    regPassword.focus();
                    return;
                }
            }

            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn) {
                const loadingText = submitBtn.getAttribute('data-loading-text') || 'Processing...';
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled');
                submitBtn.innerHTML = `<i class="fas fa-circle-notch fa-spin me-2"></i> ${loadingText}`;
            }
        });
    });
});
