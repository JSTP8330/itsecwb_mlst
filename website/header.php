<?php 
include 'config.php';

// Check connection
if ($conn->connect_error) {
    error_log("Connection failed: " . $conn->connect_error);
} 
?>

<!-- HEADER -->
<header>
    <!-- TOP HEADER -->
    <div id="top-header">
        <div class="container">
            <ul class="header-links pull-left">
                <li><a href="#"><i class="fa fa-phone"></i> (632) 8634-1111</a></li>
                <li><a href="#"><i class="fa fa-envelope-o"></i> bytech@email.com</a></li>
                <li><a href="#"><i class="fa fa-map-marker"></i> De La Salle University, Manila</a></li>
            </ul>
            <ul class="header-links pull-right">
                <!-- Account Dropdown -->
                <li class="dropdown">
                    <a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                        <i class="fa fa-user-o"></i> My Account <i class="fa fa-caret-down"></i>
                    </a>
                    <ul class="dropdown-menu">
                        <?php if (!isset($_SESSION['username'])): ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php else: ?>
                            <li><a href="orderhistory.php">Order History</a></li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                <li><a href="admin_dash.php">Admin Panel</a></li>
                            <?php endif; ?>
                             <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
                                <li><a href="staff_dash.php">Staff Panel</a></li>
                            <?php endif; ?>
                            <li><a href="profile.php">Profile Page</a></li>
                            <li><a href="logout.php">Logout</a></li>
                        <?php endif; ?>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
    <!-- /TOP HEADER -->

    <!-- MAIN HEADER -->
    <div id="header">
        <!-- container -->
        <div class="container">
            <!-- row -->
            <div class="row">
                <!-- LOGO -->
                <div class="col-md-3">
                    <div class="header-logo">
                        <a href="index.php" class="logo">
                            <img src="./img/bytechlogo.png" alt="">
                        </a>
                    </div>
                </div>
                <!-- /LOGO -->

                <!-- SEARCH BAR -->
                <div class="col-md-6">
                    <div class="header-search">
                        <form>
                            <select class="input-select">
                                <option value="0">All Categories</option>
                                <option value="1">Keyboards</option>
                                <option value="1">Headphones</option>
                                <option value="1">Monitors</option>
                                <option value="1">Mice</option>
                            </select>
                            <input class="input" placeholder="Search here">
                            <button class="search-btn">Search</button>
                        </form>
                    </div>
                </div>
                <!-- /SEARCH BAR -->

                <!-- ACCOUNT -->
                <div class="col-md-3 clearfix">
                    <div class="header-ctn">

                        <!-- Cart -->
                        <div class="dropdown">
                            <a class="dropdown-toggle" data-toggle="dropdown" aria-expanded="true">
                                <i class="fa fa-shopping-cart"></i>
                                <span>Your Cart</span>
                            </a>
                            <div class="cart-dropdown">
                                <br>  
                                <div class="cart-btns">
                                    <a href="cart.php">View Cart</a>
                                    <a href="checkout.php">Checkout <i class="fa fa-arrow-circle-right"></i></a>
                                </div>
                            </div>
                        </div>
                        <!-- /Cart -->

                        <!-- Menu Toggle -->
                        <div class="menu-toggle">
                            <a href="#">
                                <i class="fa fa-bars"></i>
                                <span>Menu</span>
                            </a>
                        </div>
                        <!-- /Menu Toggle -->
                    </div>
                </div>
                <!-- /ACCOUNT -->
            </div>
            <!-- row -->
        </div>
        <!-- container -->
    </div>
    <!-- /MAIN HEADER -->
</header>
<!-- /HEADER -->