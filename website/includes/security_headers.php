<?php
/**
 * Global security headers (CSP in report-only so existing CDNs keep working while violations are visible in DevTools).
 */
declare(strict_types=1);

if (headers_sent()) {
    return;
}

$csp = implode(
    '; ',
    [
        "default-src 'self'",
        "script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://stackpath.bootstrapcdn.com https://cdnjs.cloudflare.com https://oss.maxcdn.com",
        "style-src 'self' 'unsafe-inline' https://stackpath.bootstrapcdn.com https://fonts.googleapis.com https://cdnjs.cloudflare.com",
        "font-src 'self' https://fonts.gstatic.com https://cdnjs.cloudflare.com data:",
        "img-src 'self' data: blob:",
        "connect-src 'self'",
        "frame-ancestors 'self'",
        "base-uri 'self'",
        "form-action 'self'",
    ]
);

header('Content-Security-Policy-Report-Only: ' . $csp);
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: strict-origin-when-cross-origin');
