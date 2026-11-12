<?php
/**
 * MobileShopFlat — Auth & Session Helpers
 * Secure session management, role checks, flash messages.
 */

// ─── Session Bootstrap ────────────────────────────────────────────────────────

function session_init(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                 || (defined('APP_ENV') && APP_ENV === 'production');

    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// ─── Auth State ──────────────────────────────────────────────────────────────

function auth_user(): ?array
{
    return $_SESSION['auth_user'] ?? null;
}

/** Alias for auth_user() — used in MVC views as auth() */
function auth(): ?array
{
    return auth_user();
}

function auth_id(): ?int
{
    $u = auth_user();
    return $u ? (int)$u['id'] : null;
}

function auth_role(): string
{
    return auth_user()['role_slug'] ?? 'guest';
}

function is_logged_in(): bool
{
    return auth_user() !== null;
}

function has_role(string ...$roles): bool
{
    $role = auth_role();
    return in_array($role, $roles, true);
}

// ─── Guards ──────────────────────────────────────────────────────────────────

function require_auth(): void
{
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_role(string ...$roles): void
{
    require_auth();
    if (!has_role(...$roles)) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body><h1>403 Forbidden</h1><p>You do not have permission to access this page.</p></body></html>';
        exit;
    }
}

function require_guest(): void
{
    if (is_logged_in()) {
        redirect('/index.php');
    }
}

// ─── Login / Logout ──────────────────────────────────────────────────────────

function login_user(array $user): void
{
    // Regenerate session ID to prevent session fixation
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_regenerate_id(true);
    }
    $_SESSION['auth_user'] = $user;
    $_SESSION['_login_time'] = time();
}

function logout_user(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']
        );
    }
    session_destroy();
}

// ─── Flash Messages ──────────────────────────────────────────────────────────

function set_flash(string $type, string $msg): void
{
    $_SESSION['_flash'][$type][] = $msg;
}

function get_flash(string $type): array
{
    $msgs = $_SESSION['_flash'][$type] ?? [];
    unset($_SESSION['_flash'][$type]);
    return $msgs;
}

function has_flash(string $type): bool
{
    return !empty($_SESSION['_flash'][$type]);
}

function flash_get(string $type): ?string
{
    $msgs = get_flash($type);
    return !empty($msgs) ? (string)$msgs[0] : null;
}

/**
 * Shorthand used by flash.php partial — flash('success'), flash('error'), etc.
 */
function flash(string $type): array
{
    return get_flash($type);
}
