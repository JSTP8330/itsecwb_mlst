<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/store_bootstrap.php';

itsec_csrf_ensure();

/** @return list<array{product_id:int,name:string,price:float,stock:int,qty:int,line_total:float}> */
function itsec_checkout_load_lines(mysqli $conn, array $cart): array {
    if (empty($cart)) {
        return [];
    }
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT product_id, name, price, stock, is_active FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('checkout IN prepare: ' . $conn->error);
        return [];
    }
    $stmt->bind_param($types, ...$ids);
    $stmt->execute();
    $res = $stmt->get_result();
    $by_id = [];
    while ($row = $res->fetch_assoc()) {
        $by_id[(int) $row['product_id']] = $row;
    }
    $stmt->close();

    $lines = [];
    foreach ($cart as $pid => $qty) {
        if (!isset($by_id[$pid])) {
            continue;
        }
        $p = $by_id[$pid];
        if (!(int) $p['is_active']) {
            continue;
        }
        $max = (int) $p['stock'];
        if ($qty > $max) {
            $qty = $max;
        }
        if ($qty <= 0) {
            continue;
        }
        $lines[] = [
            'product_id' => $pid,
            'name' => $p['name'],
            'price' => (float) $p['price'],
            'stock' => $max,
            'qty' => $qty,
            'line_total' => round((float) $p['price'] * $qty, 2),
        ];
    }
    return $lines;
}

$error_message = '';
$cart = itsec_cart_get();
$lines = itsec_checkout_load_lines($conn, $cart);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!itsec_csrf_validate()) {
        itsec_csrf_fail_log('checkout.php');
        $error_message = 'Invalid security token. Please refresh and try again.';
    } elseif (empty($lines)) {
        header('Location: cart.php');
        exit;
    } else {
        $user_id = (int) ($_SESSION['user_id'] ?? 0);
        if ($user_id <= 0) {
            $error_message = 'Your session is invalid. Please log in again.';
        } else {
            $sorted = $lines;
            usort($sorted, static function ($a, $b) {
                return $a['product_id'] <=> $b['product_id'];
            });

            $conn->begin_transaction();
            try {
                $resolved = [];
                $total = 0.0;

                foreach ($sorted as $ln) {
                    $pid = $ln['product_id'];
                    $want = $ln['qty'];
                    $st = $conn->prepare(
                        'SELECT product_id, price, stock, is_active FROM products WHERE product_id = ? FOR UPDATE'
                    );
                    if ($st === false) {
                        throw new RuntimeException('prepare_failed');
                    }
                    $st->bind_param('i', $pid);
                    $st->execute();
                    $row = $st->get_result()->fetch_assoc();
                    $st->close();

                    if (!$row || !(int) $row['is_active']) {
                        throw new RuntimeException('product_unavailable');
                    }
                    $stock = (int) $row['stock'];
                    if ($want > $stock) {
                        throw new RuntimeException('insufficient_stock');
                    }
                    $price = (float) $row['price'];
                    $resolved[] = ['product_id' => $pid, 'qty' => $want, 'price' => $price];
                    $total += round($price * $want, 2);
                }
                $total = round($total, 2);

                $ins_o = $conn->prepare(
                    "INSERT INTO orders (user_id, status, total) VALUES (?, 'pending', ?)"
                );
                if ($ins_o === false) {
                    throw new RuntimeException('order_prepare_failed');
                }
                $ins_o->bind_param('id', $user_id, $total);
                if (!$ins_o->execute()) {
                    $ins_o->close();
                    throw new RuntimeException('order_insert_failed');
                }
                $order_id = (int) $conn->insert_id;
                $ins_o->close();

                $ins_oi = $conn->prepare(
                    'INSERT INTO order_items (order_id, product_id, quantity, unit_price) VALUES (?, ?, ?, ?)'
                );
                $upd = $conn->prepare('UPDATE products SET stock = stock - ? WHERE product_id = ?');
                if ($ins_oi === false || $upd === false) {
                    throw new RuntimeException('items_prepare_failed');
                }

                $oid_bind = $order_id;
                $p_bind = 0;
                $q_bind = 0;
                $pr_bind = 0.0;
                $ins_oi->bind_param('iiid', $oid_bind, $p_bind, $q_bind, $pr_bind);

                $q_up = 0;
                $p_up = 0;
                $upd->bind_param('ii', $q_up, $p_up);

                foreach ($resolved as $r) {
                    $oid_bind = $order_id;
                    $p_bind = $r['product_id'];
                    $q_bind = $r['qty'];
                    $pr_bind = $r['price'];
                    if (!$ins_oi->execute()) {
                        throw new RuntimeException('order_item_insert_failed');
                    }
                    $q_up = $r['qty'];
                    $p_up = $r['product_id'];
                    if (!$upd->execute()) {
                        throw new RuntimeException('stock_update_failed');
                    }
                }
                $ins_oi->close();
                $upd->close();

                $conn->commit();
                itsec_cart_clear();

                $actor = isset($_SESSION['username']) ? (string) $_SESSION['username'] : '';
                itsec_audit_log($conn, 'orders', 'order_placed', $order_id, $actor);

                header('Location: orderhistory.php?order_id=' . $order_id);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                error_log('checkout transaction: ' . $e->getMessage());
                $actor = isset($_SESSION['username']) ? (string) $_SESSION['username'] : '';
                itsec_audit_log($conn, 'orders', 'checkout_failed', null, $actor);

                if ($e->getMessage() === 'insufficient_stock') {
                    $error_message = 'Stock changed while checking out. Return to your cart and adjust quantities.';
                } elseif (defined('APP_DEBUG') && APP_DEBUG) {
                    $error_message = 'Checkout failed: ' . $e->getMessage();
                } else {
                    $error_message = 'Checkout could not be completed. Please review your cart and try again.';
                }
            }
        }
    }
    $cart = itsec_cart_get();
    $lines = itsec_checkout_load_lines($conn, $cart);
}

if (empty($lines) && $error_message === '') {
    header('Location: cart.php');
    exit;
}

$grand = 0.0;
foreach ($lines as $ln) {
    $grand += $ln['line_total'];
}
$grand = round($grand, 2);

$currentPage = 'cart';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop — Checkout</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="css/bootstrap.min.css"/>
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="css/style.css"/>
</head>
<body>
<?php include 'header.php'; ?>
<?php include 'navigation.php'; ?>

<div class="section">
    <div class="container">
        <h1 class="mb-4">Checkout</h1>
        <?php if ($error_message): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        <p>Review your order. Payment is out of scope — placing the order reserves stock and records the sale.</p>
        <table class="table table-bordered">
            <thead class="thead-light">
                <tr>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Unit price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lines as $ln): ?>
                    <tr>
                        <td><?= htmlspecialchars($ln['name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= (int) $ln['qty'] ?></td>
                        <td>₱<?= htmlspecialchars(number_format($ln['price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>₱<?= htmlspecialchars(number_format($ln['line_total'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><strong>Total: ₱<?= htmlspecialchars(number_format($grand, 2), ENT_QUOTES, 'UTF-8') ?></strong></p>
        <form method="post" class="mb-3">
            <?= itsec_csrf_field() ?>
            <input type="hidden" name="place_order" value="1">
            <button type="submit" class="primary-btn">Place order</button>
            <a href="cart.php" class="btn btn-link">Back to cart</a>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
