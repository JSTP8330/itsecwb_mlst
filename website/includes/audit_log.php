<?php
/**
 * Audit trail: DB rows in audit_logs + append-only JSON lines in storage/logs/app.log (dual-write on success).
 * Optional APP_USE_SYSLOG for selected high-severity lines.
 */
declare(strict_types=1);

/**
 * Resolved absolute path to the append log file (no web exposure of this path in responses).
 */
function itsec_app_log_resolve_path(): string {
    $fromEnv = getenv('ITSEC_APP_LOG');
    if (is_string($fromEnv) && $fromEnv !== '') {
        return $fromEnv;
    }
    return __DIR__ . '/../storage/logs/app.log';
}

/**
 * @param array<string, mixed> $context
 */
function itsec_app_log(string $channel, string $message, array $context = []): void {
    $path = itsec_app_log_resolve_path();
    $dir = dirname($path);
    if (!is_dir($dir)) {
        @mkdir($dir, 0750, true);
    }

    $line = json_encode(
        [
            'ts' => gmdate('c'),
            'channel' => $channel,
            'message' => $message,
            'context' => $context,
        ],
        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );
    if ($line === false) {
        $line = '{"ts":"' . gmdate('c') . '","channel":"' . $channel . '","message":"encode_failed","context":{}}';
    }
    $line .= "\n";

    $written = @file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    if ($written === false) {
        error_log('itsec_app_log: failed to write ' . basename($path));
    }

    if (itsec_app_log_syslog_enabled() && itsec_app_log_should_syslog($channel, $message)) {
        itsec_app_log_syslog_write($line);
    }
}

function itsec_app_log_syslog_enabled(): bool {
    $v = getenv('APP_USE_SYSLOG');
    if ($v === false || $v === '') {
        return false;
    }
    return filter_var($v, FILTER_VALIDATE_BOOLEAN);
}

function itsec_app_log_should_syslog(string $channel, string $message): bool {
    if ($channel === 'exception') {
        return true;
    }
    if ($channel === 'audit' && $message === 'checkout_failed') {
        return true;
    }
    if ($channel === 'security' && $message === 'csrf_validation_failed') {
        return true;
    }
    return false;
}

function itsec_app_log_syslog_write(string $line): void {
    static $opened = false;
    if (!$opened) {
        openlog('itsecwb', LOG_ODELAY, LOG_USER);
        $opened = true;
    }
    syslog(LOG_WARNING, rtrim($line));
}

function itsec_audit_log(mysqli $conn, string $table_name, string $action_type, ?int $record_id, ?string $changed_by): bool {
    if ($record_id === null) {
        $stmt = $conn->prepare(
            'INSERT INTO audit_logs (table_name, action_type, record_id, changed_by) VALUES (?, ?, NULL, ?)'
        );
        if ($stmt === false) {
            error_log('audit_log prepare failed: ' . $conn->error);
            return false;
        }
        $stmt->bind_param('sss', $table_name, $action_type, $changed_by);
    } else {
        $stmt = $conn->prepare(
            'INSERT INTO audit_logs (table_name, action_type, record_id, changed_by) VALUES (?, ?, ?, ?)'
        );
        if ($stmt === false) {
            error_log('audit_log prepare failed: ' . $conn->error);
            return false;
        }
        $stmt->bind_param('ssis', $table_name, $action_type, $record_id, $changed_by);
    }
    $ok = $stmt->execute();
    if (!$ok) {
        error_log('audit_log execute failed: ' . $stmt->error);
    }
    $stmt->close();
    if ($ok) {
        itsec_app_log('audit', $action_type, [
            'table' => $table_name,
            'record_id' => $record_id,
            'changed_by' => $changed_by,
        ]);
    }
    return $ok;
}
