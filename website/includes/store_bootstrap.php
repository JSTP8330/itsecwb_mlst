<?php
/**
 * Phase 3A store helpers (CHOICE A — Option 1): load all store-related includes in one place.
 * Requires session to be started before use (pages must include session.php first).
 */
declare(strict_types=1);

require_once __DIR__ . '/store_common.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/cart_session.php';
require_once __DIR__ . '/audit_log.php';
