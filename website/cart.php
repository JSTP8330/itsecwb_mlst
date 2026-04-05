<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/store_bootstrap.php';

itsec_csrf_ensure();
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!itsec_csrf_validate()) {
        itsec_csrf_fail_log('cart.php');
        $flash = 'Invalid security token. Please refresh and try again.';
    } elseif (isset($_POST['update_cart'])) {
        $qtys = $_POST['qty'] ?? [];
        if (!is_array($qtys)) {
            $qtys = [];
        }
        foreach ($qtys as $pid => $q) {
            $pid = (int) $pid;
            $q = (int) $q;
            if ($pid <= 0) {
                continue;
            }
            if ($q <= 0) {
                itsec_cart_remove($pid);
            } else {
                itsec_cart_set_qty($pid, min(999, $q));
            }
        }
        $flash = 'Cart updated.';
    } elseif (isset($_POST['remove_item'])) {
        $pid = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
        if ($pid > 0) {
            itsec_cart_remove($pid);
            $flash = 'Item removed.';
        }
    }
}

$cart = itsec_cart_get();
$lines = [];
if (!empty($cart)) {
    $ids = array_keys($cart);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    $sql = "SELECT product_id, name, price, stock, is_active FROM products WHERE product_id IN ($placeholders)";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param($types, ...$ids);
        $stmt->execute();
        $res = $stmt->get_result();
        $by_id = [];
        while ($row = $res->fetch_assoc()) {
            $by_id[(int) $row['product_id']] = $row;
        }
        $stmt->close();
        foreach ($cart as $pid => $qty) {
            if (!isset($by_id[$pid])) {
                itsec_cart_remove($pid);
                continue;
            }
            $p = $by_id[$pid];
            if (!(int) $p['is_active']) {
                itsec_cart_remove($pid);
                continue;
            }
            $max = (int) $p['stock'];
            if ($qty > $max) {
                $qty = $max;
                itsec_cart_set_qty($pid, $qty);
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
    } else {
        error_log('cart IN query prepare failed: ' . $conn->error);
    }
}

$grand = 0.0;
foreach ($lines as $ln) {
    $grand += $ln['line_total'];
}
$grand = round($grand, 2);

$currentPage = 'cart';
$form_id = 'cart-update-form';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop — Cart</title>
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
        <h1 class="mb-4">Shopping cart</h1>
        <?php if ($flash): ?><div class="alert alert-info"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>

        <?php if (empty($lines)): ?>
            <p>Your cart is empty.</p>
            <a href="store.php" class="primary-btn">Continue shopping</a>
        <?php else: ?>
            <table class="table table-bordered">
                <thead class="thead-light">
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Qty</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($lines as $ln): ?>
                        <tr>
                            <td><a href="product.php?id=<?= (int) $ln['product_id'] ?>"><?= htmlspecialchars($ln['name'], ENT_QUOTES, 'UTF-8') ?></a></td>
                            <td>₱<?= htmlspecialchars(number_format($ln['price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="max-width:6rem">
                                <input type="number" class="form-control" form="<?= htmlspecialchars($form_id, ENT_QUOTES, 'UTF-8') ?>" name="qty[<?= (int) $ln['product_id'] ?>]" min="0" max="<?= (int) $ln['stock'] ?>" value="<?= (int) $ln['qty'] ?>">
                            </td>
                            <td>₱<?= htmlspecialchars(number_format($ln['line_total'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <form method="post" class="d-inline">
                                    <?= itsec_csrf_field() ?>
                                    <input type="hidden" name="product_id" value="<?= (int) $ln['product_id'] ?>">
                                    <button type="submit" name="remove_item" value="1" class="btn btn-sm btn-outline-danger">Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form id="<?= htmlspecialchars($form_id, ENT_QUOTES, 'UTF-8') ?>" method="post" class="mb-3">
                <?= itsec_csrf_field() ?>
                <input type="hidden" name="update_cart" value="1">
                <p><strong>Total: ₱<?= htmlspecialchars(number_format($grand, 2), ENT_QUOTES, 'UTF-8') ?></strong></p>
                <button type="submit" class="primary-btn">Update cart</button>
                <a href="checkout.php" class="primary-btn ml-2">Checkout</a>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
