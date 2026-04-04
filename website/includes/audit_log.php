<?php
/**
 * Insert a row into audit_logs (matches online_store.sql schema).
 */
declare(strict_types=1);

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
    return $ok;
}
