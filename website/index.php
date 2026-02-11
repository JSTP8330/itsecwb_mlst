<?php

// index.php
session_start(); // Ensure session is started to access user data
include 'config.php';

// Optional: Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentPage = "home";

// Get featured products

// Handle add to cart
// if (isset($_POST['add_to_cart'])) {
//     $product_id = intval($_POST['product_id']);
//     $quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;
    
//     // Check if product exists
//     $product = $conn->query("SELECT * FROM products WHERE product_id = $product_id")->fetch_assoc();
    
//     if ($product) {
//         // Check if product already in cart
//         $check = $conn->query("SELECT * FROM cart WHERE product_id = $product_id");
//         if ($check->num_rows > 0) {
//             // Update quantity if exists
//             $conn->query("UPDATE cart SET quantity = quantity + $quantity WHERE product_id = $product_id");
//         } else {
//             // Add new item to cart
//             $name = $conn->real_escape_string($product['name']);
//             $price = $product['price'];
//             $conn->query("INSERT INTO cart (product_id, name, price, quantity) VALUES ($product_id, '$name', $price, $quantity)");
//         }
//     }
//     header("Location: cart.php");
//     exit;
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop - Home</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="css/bootstrap.min.css"/>
    <link type="text/css" rel="stylesheet" href="css/slick.css"/>
    <link type="text/css" rel="stylesheet" href="css/slick-theme.css"/>
    <link type="text/css" rel="stylesheet" href="css/nouislider.min.css"/>
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="css/style.css"/>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <?php $currentPage = "home"; ?>

    <!-- HEADER --> 
    <?php include 'header.php'; ?>
    <!-- /HEADER -->

    <!-- NAVIGATION -->
    <?php include 'navigation.php'; ?>
    <!-- /NAVIGATION -->

    <!-- SECTION - Category Collections -->
    <div class="section">
        <div class="container">
            <div class="row">
                <!-- Keyboard Collection -->
                <div class="col-md-3 col-xs-6">
                    <div class="shop">
                        <div class="shop-img">
                            <img src="./img/product15.png" alt="Keyboards">
                        </div>
                        <div class="shop-body">
                            <h3>Keyboard<br>Collection</h3>
                            <a href="store.php?category_id=2" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Headphone Collection -->
                <div class="col-md-3 col-xs-6">
                    <div class="shop">
                        <div class="shop-img">
                            <img src="./img/product09.png" alt="Headphones">
                        </div>
                        <div class="shop-body">
                            <h3>Headphone<br>Collection</h3>
                            <a href="store.php?category_id=4" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Monitor Collection -->
                <div class="col-md-3 col-xs-6">
                    <div class="shop">
                        <div class="shop-img">
                            <img src="./img/product02.png" alt="Monitors">
                        </div>
                        <div class="shop-body">
                            <h3>Monitor<br>Collection</h3>
                            <a href="store.php?category_id=3" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
                
                <!-- Mouse Collection -->
                <div class="col-md-3 col-xs-6">
                    <div class="shop">
                        <div class="shop-img">
                            <img src="./img/product05.png" alt="Mice">
                        </div>
                        <div class="shop-body">
                            <h3>Mouse<br>Collection</h3>
                            <a href="store.php?category_id=1" class="cta-btn">Shop now <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- FOOTER --> 
    <?php include 'footer.php'; ?>
    <!-- /FOOTER --> 

    <!-- jQuery Plugins -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/slick.min.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
