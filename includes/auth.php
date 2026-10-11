<?php
/**
 * ============================================================
 * AUTHENTICATION & AUTHORIZATION — News Portal
 * ============================================================
 * Session management, login state helpers, and role-based
 * authorization utilities.
 *
 * Usage:
 *   require_once __DIR__ . '/../includes/auth.php';
 *
 * This file automatically starts the session when included.
 * ============================================================
 */

/**
 * Start or resume a secure PHP session.
 * Safe to call multiple times — only starts if not already active.
 */
function startSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Session configuration — secure defaults
    // NOTE: 'cookie_secure' is false for local XAMPP (HTTP).
    // Set to true when deploying over HTTPS.
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

    session_set_cookie_params([
        'lifetime' => 0,              // Until browser closes
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,      // HTTPS only when available
        'httponly'  => true,           // Not accessible via JavaScript
        'samesite'  => 'Lax',         // CSRF protection via SameSite
    ]);

    session_name('NP_SESSION');
    session_start();

    // Session timeout: 30 minutes of inactivity
    $timeout = 30 * 60; // seconds
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout) {
        // Session expired — destroy and restart
        session_unset();
        session_destroy();
        session_start();
    }
    $_SESSION['last_activity'] = time();
}

// Auto-start session when this file is included
startSession();


// ============================================================
// LOGIN STATE HELPERS
// ============================================================

/**
 * Check if any user is currently logged in.
 *
 * @return bool
 */
function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']) && !empty($_SESSION['logged_in']);
}

/**
 * Get the current logged-in user's data from the session.
 *
 * @return array|null  ['id', 'name', 'email', 'role'] or null
 */
function currentUser(): ?array
{
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name'] ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role'] ?? 'user',
    ];
}

/**
 * Get the current logged-in user's ID.
 *
 * @return int|null  User ID or null if not logged in
 */
function currentUserId(): ?int
{
    return isLoggedIn() ? (int) $_SESSION['user_id'] : null;
}

/**
 * Get the current logged-in user's role.
 *
 * @return string|null  'admin', 'editor', 'user', or null
 */
function currentUserRole(): ?string
{
    return isLoggedIn() ? ($_SESSION['user_role'] ?? null) : null;
}

/**
 * Get the current logged-in user's display name.
 *
 * @return string  User name or empty string
 */
function currentUserName(): string
{
    return isLoggedIn() ? ($_SESSION['user_name'] ?? '') : '';
}


// ============================================================
// ROLE CHECKS
// ============================================================

/**
 * Check if the current user has a specific role.
 *
 * @param  string $role  Role to check ('admin', 'editor', 'user')
 * @return bool
 */
function hasRole(string $role): bool
{
    return isLoggedIn() && currentUserRole() === $role;
}

/**
 * Check if the current user is an admin.
 * @return bool
 */
function isAdmin(): bool
{
    return hasRole('admin');
}

/**
 * Check if the current user is an editor.
 * @return bool
 */
function isEditor(): bool
{
    return hasRole('editor');
}

/**
 * Check if the current user is a regular user.
 * @return bool
 */
function isUser(): bool
{
    return hasRole('user');
}

/**
 * Check if the current user is an admin or editor.
 * Useful for content management permissions.
 *
 * @return bool
 */
function isAdminOrEditor(): bool
{
    return isAdmin() || isEditor();
}


// ============================================================
// AUTHORIZATION GUARDS
// ============================================================

/**
 * Require the user to be logged in.
 * Redirects to login page if not authenticated.
 *
 * @param string $redirectTo  URL to redirect to after login denial
 */
function requireLogin(string $redirectTo = 'login.php'): void
{
    if (!isLoggedIn()) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('Please log in to continue.', 'warning');
        }
        if (function_exists('redirect')) {
            redirect($redirectTo);
        } else {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

/**
 * Require the user to have a specific role.
 * Redirects if the user doesn't have the required role.
 *
 * @param string|array $roles       Allowed role(s)
 * @param string       $redirectTo  Redirect URL on failure
 */
function requireRole(string|array $roles, string $redirectTo = 'index.php'): void
{
    requireLogin();

    $roles = (array) $roles;
    if (!in_array(currentUserRole(), $roles, true)) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('You do not have permission to access this page.', 'error');
        }
        if (function_exists('redirect')) {
            redirect($redirectTo);
        } else {
            header('Location: ' . $redirectTo);
            exit;
        }
    }
}

/**
 * Require admin role. Redirects to admin login if not authorized.
 */
function requireAdmin(): void
{
    if (!isLoggedIn()) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('Please log in as administrator.', 'warning');
        }
        if (function_exists('redirect')) {
            redirect('admin/login.php');
        } else {
            header('Location: admin/login.php');
            exit;
        }
    }

    if (!isAdminOrEditor()) {
        if (function_exists('setFlashMessage')) {
            setFlashMessage('Access denied. Administrator privileges required.', 'error');
        }
        if (function_exists('redirect')) {
            redirect('index.php');
        } else {
            header('Location: index.php');
            exit;
        }
    }
}


// ============================================================
// LOGIN / LOGOUT SESSION MANAGEMENT
// ============================================================

/**
 * Set session data after a successful login.
 * Regenerates session ID to prevent session fixation.
 *
 * @param array $user  User record from database
 */
function loginUser(array $user): void
{
    // Prevent session fixation
    session_regenerate_id(true);

    $_SESSION['user_id']    = (int) $user['id'];
    $_SESSION['user_name']  = $user['name'];
    $_SESSION['user_email'] = $user['email'];
    $_SESSION['user_role']  = $user['role'];
    $_SESSION['logged_in']  = true;
    $_SESSION['login_time'] = time();
    $_SESSION['last_activity'] = time();
}

/**
 * Destroy the current session and log the user out.
 */
function logoutUser(): void
{
    $_SESSION = [];

    // Delete the session cookie
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
