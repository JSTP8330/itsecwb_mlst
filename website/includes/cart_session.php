<?php
/**
 * Session-backed shopping cart: product_id (int) => quantity (int).
 */
declare(strict_types=1);

function itsec_cart_init(): void {
    if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
}

/** @return array<int, int> */
function itsec_cart_get(): array {
    itsec_cart_init();
    $out = [];
    foreach ($_SESSION['cart'] as $pid => $qty) {
        $pid = (int) $pid;
        $qty = (int) $qty;
        if ($pid > 0 && $qty > 0) {
            $out[$pid] = $qty;
        }
    }
    return $out;
}

function itsec_cart_set_qty(int $product_id, int $quantity): void {
    itsec_cart_init();
    if ($product_id <= 0) {
        return;
    }
    if ($quantity <= 0) {
        unset($_SESSION['cart'][$product_id]);
        return;
    }
    $_SESSION['cart'][$product_id] = $quantity;
}

function itsec_cart_remove(int $product_id): void {
    itsec_cart_init();
    unset($_SESSION['cart'][$product_id]);
}

function itsec_cart_clear(): void {
    $_SESSION['cart'] = [];
}

function itsec_cart_count_items(): int {
    $n = 0;
    foreach (itsec_cart_get() as $qty) {
        $n += $qty;
    }
    return $n;
}
