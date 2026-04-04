<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/store_bootstrap.php';

$currentPage = 'store';
$category_slug = itsec_store_category_slug(isset($_GET['category_id']) ? (string) $_GET['category_id'] : null);

if ($category_slug !== null) {
    $stmt = $conn->prepare(
        'SELECT product_id, name, category, description, price, stock
         FROM products
         WHERE is_active = 1 AND category = ?
         ORDER BY name'
    );
    if ($stmt === false) {
        error_log('store prepare failed: ' . $conn->error);
        $products = [];
        $db_error = true;
    } else {
        $stmt->bind_param('s', $category_slug);
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db_error = false;
    }
} else {
    $stmt = $conn->prepare(
        'SELECT product_id, name, category, description, price, stock
         FROM products
         WHERE is_active = 1
         ORDER BY name'
    );
    if ($stmt === false) {
        error_log('store prepare failed: ' . $conn->error);
        $products = [];
        $db_error = true;
    } else {
        $stmt->execute();
        $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        $db_error = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop — Store</title>
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
        <h1 class="mb-4">Store<?php if ($category_slug): ?> — <?= htmlspecialchars(ucfirst($category_slug), ENT_QUOTES, 'UTF-8') ?><?php endif; ?></h1>
        <?php if (!empty($db_error)): ?>
            <p class="text-danger">Unable to load products. Please try again later.</p>
        <?php elseif (empty($products)): ?>
            <p>No products in this category yet.</p>
            <p><a href="store.php">View all products</a></p>
        <?php else: ?>
            <div class="row">
                <?php foreach ($products as $p): ?>
                    <div class="col-md-4 col-sm-6 mb-4">
                        <div class="product">
                            <div class="product-body">
                                <p class="product-category"><?= htmlspecialchars($p['category'], ENT_QUOTES, 'UTF-8') ?></p>
                                <h3 class="product-name"><a href="product.php?id=<?= (int) $p['product_id'] ?>"><?= htmlspecialchars($p['name'], ENT_QUOTES, 'UTF-8') ?></a></h3>
                                <h4 class="product-price">₱<?= htmlspecialchars(number_format((float) $p['price'], 2), ENT_QUOTES, 'UTF-8') ?></h4>
                                <p class="product-available">Stock: <?= (int) $p['stock'] ?></p>
                                <a class="primary-btn cta-btn" href="product.php?id=<?= (int) $p['product_id'] ?>">View details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
