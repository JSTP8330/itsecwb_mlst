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
