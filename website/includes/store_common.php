<?php
/**
 * Store category slugs (aligned with navigation.php and products.category).
 */
declare(strict_types=1);

const ITSEC_STORE_CATEGORIES = ['keyboards', 'headphones', 'monitors', 'mice', 'general'];

function itsec_store_category_slug(?string $raw): ?string {
    if ($raw === null || $raw === '') {
        return null;
    }
    $s = strtolower(trim($raw));
    return in_array($s, ITSEC_STORE_CATEGORIES, true) ? $s : null;
}
