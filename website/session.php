<?php
/**
 * Session + password helpers in one place.
 * Pepper: env APP_PASSWORD_PEPPER, or optional secrets.php, or auto-generated .app_pepper (persisted).
 *
 * Phase 2 (see README):
 * - APP_DEBUG: env APP_DEBUG=0 or false for production-style errors (default true for local XAMPP).
 * - SHOW_PHASE3_NAV_LINKS: when false, hides store/cart nav (see define below; default true after Phase 3A).
 * - Session idle 30m / max 24h when logged in.
 */

if (!defined('APP_DEBUG')) {
    $d = getenv('APP_DEBUG');
    define('APP_DEBUG', $d === false ? true : filter_var($d, FILTER_VALIDATE_BOOLEAN));
}
if (!defined('SHOW_PHASE3_NAV_LINKS')) {
    define('SHOW_PHASE3_NAV_LINKS', true);
}

if (!function_exists('app_password_hash')) {

    if (!defined('APP_PASSWORD_PEPPER')) {
        $pepper = getenv('APP_PASSWORD_PEPPER');
        if ($pepper !== false && $pepper !== '') {
            define('APP_PASSWORD_PEPPER', $pepper);
        } else {
            if (file_exists(__DIR__ . '/secrets.php')) {
                require_once __DIR__ . '/secrets.php';
            }
            if (!defined('APP_PASSWORD_PEPPER')) {
                $pepperFile = __DIR__ . '/.app_pepper';
                $generated = '';
                if (is_readable($pepperFile)) {
                    $generated = trim((string) file_get_contents($pepperFile));
                }
                if ($generated === '') {
                    $generated = bin2hex(random_bytes(32));
                    @file_put_contents($pepperFile, $generated, LOCK_EX);
                }
                define('APP_PASSWORD_PEPPER', $generated);
            }
        }
    }

    /**
     * Value for password_hash() / password_needs_rehash(); PHP may use int or string per version/build.
     */
    function app_password_algo() {
        return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_DEFAULT;
    }

    function app_password_hash(string $plain): string {
        return password_hash($plain . APP_PASSWORD_PEPPER, app_password_algo());
    }

    /**
     * @return array{ok: bool, upgrade_hash: ?string}
     */
    function app_password_login_check(string $plain, string $stored_hash): array {
        $pepper = APP_PASSWORD_PEPPER;
        $algo = app_password_algo();

        if ($pepper !== '') {
            if (password_verify($plain . $pepper, $stored_hash)) {
                $upgrade = password_needs_rehash($stored_hash, $algo) ? app_password_hash($plain) : null;
                return ['ok' => true, 'upgrade_hash' => $upgrade];
            }
        }

        if (password_verify($plain, $stored_hash)) {
            $needs_pepper_upgrade = ($pepper !== '');
            $needs_rehash = password_needs_rehash($stored_hash, $algo);
            if ($needs_pepper_upgrade || $needs_rehash) {
                return ['ok' => true, 'upgrade_hash' => app_password_hash($plain)];
            }
            return ['ok' => true, 'upgrade_hash' => null];
        }

        return ['ok' => false, 'upgrade_hash' => null];
    }

    function itsec_session_start(): void {
        if (session_status() === PHP_SESSION_NONE) {
            $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
            session_set_cookie_params([
                'lifetime' => 0,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $secure,
            ]);
            session_start();
        }
    }
}

// --- Password helpers only (e.g. register via config.php) ---
if (defined('ITSEC_PASSWORD_ONLY') && ITSEC_PASSWORD_ONLY) {
    return;
}

itsec_session_start();

// Phase 2: expire logged-in sessions (idle 30 min, absolute 24 h from login markers)
if (isset($_SESSION['username'])) {
    $now = time();
    $idle_max = 1800;
    $life_max = 86400;
    if (!isset($_SESSION['sess_started'])) {
        $_SESSION['sess_started'] = $now;
    }
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
    }
    if (($now - (int) $_SESSION['last_activity'] > $idle_max) || ($now - (int) $_SESSION['sess_started'] > $life_max)) {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
        header('Location: login.php');
        exit;
    }
    $_SESSION['last_activity'] = $now;
}

// --- Login / logout pages: session without auth gate ---
if (defined('ITSEC_SESSION_PUBLIC') && ITSEC_SESSION_PUBLIC) {
    return;
}

// --- Protected pages ---
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit;
}

function checkRole($requiredRole) {
    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $requiredRole) {
        echo "Access denied. You do not have permission to view this page.";
        exit;
    }
}

/** Admin or staff: catalog + order operations (Phase 3B). */
function checkStaffOrAdmin(): void {
    if (!isset($_SESSION['role'])) {
        echo "Access denied. You do not have permission to view this page.";
        exit;
    }
    $r = $_SESSION['role'];
    if ($r !== 'admin' && $r !== 'staff') {
        echo "Access denied. You do not have permission to view this page.";
        exit;
    }
}
