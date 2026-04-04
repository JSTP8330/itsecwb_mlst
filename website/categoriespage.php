<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/store_bootstrap.php';

$currentPage = 'categories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop — Categories</title>
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
        <h1 class="mb-4">Categories</h1>
        <p class="mb-4"><a href="store.php">Browse all products</a></p>
        <ul class="list-group" style="max-width: 28rem;">
            <?php foreach (ITSEC_STORE_CATEGORIES as $cat): ?>
                <?php if ($cat === 'general') {
                    continue;
                } ?>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    <a href="store.php?category_id=<?= rawurlencode($cat) ?>"><?= htmlspecialchars(ucfirst($cat), ENT_QUOTES, 'UTF-8') ?></a>
                    <i class="fa fa-arrow-right text-muted"></i>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<?php include 'footer.php'; ?>
<script src="js/jquery.min.js"></script>
<script src="js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
