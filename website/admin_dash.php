<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="css/admindash.css?v=1.0">
</head>
<body>
  <?php 
  // Check if user is admin
  include 'session.php'; // session management 
  include 'config.php'; // db connection 

  $currentPage = basename($_SERVER['PHP_SELF']);
  // Fetch statistics
  // $pending_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Pending'")->fetch_assoc()['count'];
  // $completed_orders = $conn->query("SELECT COUNT(*) as count FROM orders WHERE status = 'Completed'")->fetch_assoc()['count'];
  // $total_products = $conn->query("SELECT COUNT(*) as count FROM products")->fetch_assoc()['count'];

  checkRole('admin'); // function from session.php to restrict access
?>

  <div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
      <h1>Admin Dashboard</h1>
      
      <!-- <div class="stats-container">
        <div class="stat-box pending">
          <h3>Pending Orders</h3>
          <p class="stat-number"><?php echo $pending_orders; ?></p>
        </div>
        <div class="stat-box completed">
          <h3>Completed Orders</h3>
          <p class="stat-number"><?php echo $completed_orders; ?></p>
        </div>
        <div class="stat-box products">
          <h3>Total Products</h3>
          <p class="stat-number"><?php echo $total_products; ?></p>
        </div> -->
      </div>
    </main>
  </div>
</body>
</html>