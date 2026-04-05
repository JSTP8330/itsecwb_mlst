<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/store_bootstrap.php';

$product_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($product_id <= 0) {
    header('Location: store.php');
    exit;
}

$flash_ok = '';
$flash_err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!itsec_csrf_validate()) {
        itsec_csrf_fail_log('product.php');
        $flash_err = 'Invalid security token. Please refresh and try again.';
    } else {
        $qty = isset($_POST['quantity']) ? (int) $_POST['quantity'] : 0;
        if ($qty < 1 || $qty > 999) {
            $flash_err = 'Choose a quantity between 1 and 999.';
        } else {
            $chk = $conn->prepare(
                'SELECT product_id, stock, is_active FROM products WHERE product_id = ? LIMIT 1'
            );
            if ($chk && $chk->bind_param('i', $product_id) && $chk->execute()) {
                $row = $chk->get_result()->fetch_assoc();
                $chk->close();
                if (!$row || !(int) $row['is_active']) {
                    $flash_err = 'This product is not available.';
                } elseif ($qty > (int) $row['stock']) {
                    $flash_err = 'Not enough stock for that quantity.';
                } else {
                    $cart = itsec_cart_get();
                    $existing = $cart[$product_id] ?? 0;
                    $new_qty = $existing + $qty;
                    if ($new_qty > (int) $row['stock']) {
                        $flash_err = 'Cannot add that many — stock limit reached.';
                    } else {
                        itsec_cart_set_qty($product_id, $new_qty);
                        $flash_ok = 'Added to cart.';
                    }
                }
            } else {
                error_log('product add_to_cart check failed');
                $flash_err = 'Something went wrong. Please try again.';
            }
        }
    }
}

$stmt = $conn->prepare(
    'SELECT product_id, name, category, description, price, stock, created_at
     FROM products
     WHERE product_id = ? AND is_active = 1
     LIMIT 1'
);
if ($stmt === false) {
    error_log('product prepare: ' . $conn->error);
    die('Unable to load product.');
}
$stmt->bind_param('i', $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) {
    header('Location: store.php');
    exit;
}

itsec_csrf_ensure();
$currentPage = 'store';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?> — TechShop</title>
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
        <p><a href="store.php">← Back to store</a><?php if ($product['category']): ?>
            | <a href="store.php?category_id=<?= htmlspecialchars(urlencode((string) $product['category']), ENT_QUOTES, 'UTF-8') ?>">More in <?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></a>
        <?php endif; ?></p>
        <h1><?= htmlspecialchars($product['name'], ENT_QUOTES, 'UTF-8') ?></h1>
        <?php if ($flash_ok): ?><div class="alert alert-success"><?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($flash_err): ?><div class="alert alert-danger"><?= htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <p class="text-muted"><?= htmlspecialchars($product['category'], ENT_QUOTES, 'UTF-8') ?></p>
        <h3>₱<?= htmlspecialchars(number_format((float) $product['price'], 2), ENT_QUOTES, 'UTF-8') ?></h3>
        <p>Stock: <strong><?= (int) $product['stock'] ?></strong></p>
        <div class="mb-4"><?= nl2br(htmlspecialchars((string) $product['description'], ENT_QUOTES, 'UTF-8')) ?></div>

        <?php if ((int) $product['stock'] > 0): ?>
            <form method="post" class="form-inline">
                <?= itsec_csrf_field() ?>
                <input type="hidden" name="add_to_cart" value="1">
                <label class="mr-2">Qty</label>
                <input type="number" name="quantity" class="form-control mr-2" min="1" max="<?= min(999, (int) $product['stock']) ?>" value="1" style="width:5rem">
                <button type="submit" class="primary-btn">Add to cart</button>
            </form>
        <?php else: ?>
            <p class="text-warning">Out of stock.</p>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
