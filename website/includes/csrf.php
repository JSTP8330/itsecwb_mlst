<?php
/**
 * CSRF helpers for state-changing POST forms (session must be started).
 */
declare(strict_types=1);

function itsec_csrf_ensure(): void {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
}

function itsec_csrf_field(): string {
    itsec_csrf_ensure();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') . '">';
}

function itsec_csrf_validate(): bool {
    $t = $_POST['csrf_token'] ?? '';
    return isset($_SESSION['csrf_token'])
        && is_string($t)
        && hash_equals($_SESSION['csrf_token'], $t);
}

/** Log CSRF failure (file + optional syslog); call when validate fails. */
function itsec_csrf_fail_log(string $page): void {
    require_once __DIR__ . '/audit_log.php';
    itsec_app_log('security', 'csrf_validation_failed', [
        'page' => $page,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
    ]);
}
