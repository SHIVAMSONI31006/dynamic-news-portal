<?php
/**
 * ============================================================
 * CSRF PROTECTION — News Portal
 * ============================================================
 * Token-based Cross-Site Request Forgery protection.
 *
 * Usage in forms:
 *   <form method="POST">
 *       <?= csrfField() ?>
 *       ...
 *   </form>
 *
 * Usage in handlers:
 *   if (isPostRequest()) {
 *       verifyCsrfToken(post('csrf_token'));
 *       // ... process form
 *   }
 * ============================================================
 */

/**
 * Generate a CSRF token and store it in the session.
 * Reuses the existing token if one is already set.
 *
 * @return string  The CSRF token
 */
function generateCsrfToken(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Get the current CSRF token (generates one if needed).
 *
 * @return string  The CSRF token
 */
function csrfToken(): string
{
    return generateCsrfToken();
}

/**
 * Generate a hidden HTML input field containing the CSRF token.
 * Drop this into any <form> that uses POST.
 *
 * @return string  HTML hidden input element
 */
function csrfField(): string
{
    $token = csrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verify a submitted CSRF token against the session token.
 * Uses hash_equals() for timing-safe comparison.
 *
 * @param  string $token  Token submitted via form
 * @return bool           True if valid
 */
function isValidCsrfToken(string $token): bool
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Verify CSRF token — dies with error if invalid.
 * Call this at the top of any POST request handler.
 *
 * @param  string|null $token  Token from form submission
 * @return void
 */
function verifyCsrfToken(?string $token): void
{
    if ($token === null || !isValidCsrfToken($token)) {
        http_response_code(403);

        if (function_exists('setFlashMessage')) {
            setFlashMessage('Security validation failed. Please try again.', 'error');
        }

        // Redirect back if possible, otherwise show error
        $referer = $_SERVER['HTTP_REFERER'] ?? null;
        if ($referer && !headers_sent()) {
            header('Location: ' . $referer);
            exit;
        }

        die('<h1>403 Forbidden</h1><p>Security token validation failed. Please go back and try again.</p>');
    }
}

/**
 * Regenerate the CSRF token.
 * Call after processing a form to prevent token reuse.
 */
function regenerateCsrfToken(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
