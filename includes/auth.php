<?php
require_once __DIR__ . '/config.php'; // APP_BASE

// Start session immediately on include (CLAUDE.md: sessions start in auth.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Redirect to login if the current visitor is not an authenticated admin.
 * Called as the very first action in every admin page.
 */
function requireAdmin(): void
{
    if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'admin') {
        header('Location: ' . APP_BASE . '/admin/login.php');
        exit;
    }
}

/** Alias used in CLAUDE.md conventions. */
function auth_check(): void
{
    requireAdmin();
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['user_id']);
}

function getCurrentUser(): array
{
    return [
        'id'       => (int)($_SESSION['user_id']  ?? 0),
        'username' => (string)($_SESSION['username'] ?? ''),
        'role'     => (string)($_SESSION['role']     ?? ''),
    ];
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 86400,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}
