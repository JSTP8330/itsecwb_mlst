<?php
/**
 * Uncaught exception handler: full trace to error_log + app log; browser detail only when APP_DEBUG.
 */
declare(strict_types=1);

require_once __DIR__ . '/audit_log.php';

set_exception_handler(static function (Throwable $e): void {
    $msg = $e->getMessage();
    $trace = $e->getTraceAsString();
    error_log('Uncaught ' . get_class($e) . ': ' . $msg . "\n" . $trace);

    itsec_app_log('exception', get_class($e), [
        'message' => $msg,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $trace,
    ]);

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
    }

    if (defined('APP_DEBUG') && APP_DEBUG) {
        echo 'Something went wrong.' . "\n\n";
        echo $msg . "\n";
        echo $e->getFile() . ':' . $e->getLine() . "\n";
    } else {
        echo 'Something went wrong. Please try again later.';
    }
    exit(1);
});
