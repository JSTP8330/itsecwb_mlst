<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);
if ($user_id <= 0) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['order_id']) ? (int) $_GET['order_id'] : 0;
$order = null;
$items = [];

if ($order_id > 0) {
    $stmt = $conn->prepare(
        'SELECT order_id, user_id, status, total, created_at FROM orders WHERE order_id = ? AND user_id = ? LIMIT 1'
    );
    if ($stmt === false) {
        error_log('orderhistory prepare: ' . $conn->error);
    } else {
        $stmt->bind_param('ii', $order_id, $user_id);
        $stmt->execute();
        $order = $stmt->get_result()->fetch_assoc();
        $stmt->close();
    }

    if (!$order) {
        header('Location: orderhistory.php');
        exit;
    }

    $it = $conn->prepare(
        'SELECT oi.item_id, oi.product_id, oi.quantity, oi.unit_price,
                p.name AS product_name
         FROM order_items oi
         INNER JOIN products p ON p.product_id = oi.product_id
         WHERE oi.order_id = ?
         ORDER BY oi.item_id'
    );
    if ($it) {
        $it->bind_param('i', $order_id);
        $it->execute();
        $items = $it->get_result()->fetch_all(MYSQLI_ASSOC);
        $it->close();
    } else {
        error_log('orderhistory items prepare: ' . $conn->error);
    }
}

$list = [];
if ($order_id <= 0) {
    $ls = $conn->prepare(
        'SELECT order_id, status, total, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC'
    );
    if ($ls === false) {
        error_log('orderhistory list prepare: ' . $conn->error);
    } else {
        $ls->bind_param('i', $user_id);
        $ls->execute();
        $list = $ls->get_result()->fetch_all(MYSQLI_ASSOC);
        $ls->close();
    }
}

$currentPage = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop — Order history</title>
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
        <?php if ($order && $order_id > 0): ?>
            <p><a href="orderhistory.php">← All orders</a></p>
            <h1 class="mb-3">Order #<?= (int) $order['order_id'] ?></h1>
            <p>Placed: <?= htmlspecialchars($order['created_at'], ENT_QUOTES, 'UTF-8') ?></p>
            <p>Status: <strong><?= htmlspecialchars($order['status'], ENT_QUOTES, 'UTF-8') ?></strong></p>
            <p>Total: <strong>₱<?= htmlspecialchars(number_format((float) $order['total'], 2), ENT_QUOTES, 'UTF-8') ?></strong></p>
            <h2 class="h4 mt-4">Items</h2>
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
                    <?php foreach ($items as $row): ?>
                        <?php
                        $sub = round((float) $row['unit_price'] * (int) $row['quantity'], 2);
                        ?>
                        <tr>
                            <td><?= htmlspecialchars($row['product_name'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= (int) $row['quantity'] ?></td>
                            <td>₱<?= htmlspecialchars(number_format((float) $row['unit_price'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>₱<?= htmlspecialchars(number_format($sub, 2), ENT_QUOTES, 'UTF-8') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <h1 class="mb-4">Order history</h1>
            <?php if (empty($list)): ?>
                <p>You have no orders yet.</p>
                <a href="store.php" class="primary-btn">Browse store</a>
            <?php else: ?>
                <table class="table table-striped table-bordered">
                    <thead class="thead-dark">
                        <tr>
                            <th>Order</th>
                            <th>Date</th>
                            <th>Status</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $row): ?>
                            <tr>
                                <td><a href="orderhistory.php?order_id=<?= (int) $row['order_id'] ?>">#<?= (int) $row['order_id'] ?></a></td>
                                <td><?= htmlspecialchars($row['created_at'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($row['status'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td>₱<?= htmlspecialchars(number_format((float) $row['total'], 2), ENT_QUOTES, 'UTF-8') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
