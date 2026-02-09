<aside class="sidebar">
  <h2>Admin Panel</h2>
  <ul>
    <li><a href="admin_dash.php" class="<?php echo ($currentPage == 'admin_dash.php') ? 'active' : ''; ?>">Dashboard</a></li>
    <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Homepage</a></li>
    <hr class="rounded"><br> 
    
    <!-- Product, Order, Tables Management for Later  
    <li>
      <a href="#" class="<?php echo (in_array($currentPage, ['admin_products_add.php', 'admin_products_edit.php'])) ? 'active' : ''; ?>">Product Management</a>
      <ul class="dropdown">
        <li><a href="admin_products_add.php" class="<?php echo ($currentPage == 'admin_products_add.php') ? 'active' : ''; ?>">Add Product</a></li>
        <li><a href="admin_products_edit.php" class="<?php echo ($currentPage == 'admin_products_edit.php') ? 'active' : ''; ?>">Edit Product</a></li>
      </ul>
    
    </li>
    <li><a href="orders.php" class="<?php echo ($currentPage == 'admin_orders.php') ? 'active' : ''; ?>">Orders</a></li>-->
    
    <li><a href="admin_tables.php" class="<?php echo ($currentPage == 'admin_tables.php') ? 'active' : ''; ?>">Tables</a></li>
    <li><a href="admin_auditlogs.php">Audit Logs</a></li>
    <li><a href="admin_roleassignment.php">Role Assignment</a></li>
    <hr class="rounded"><br>

    <li><a href="logout.php" class="<?php echo ($currentPage == 'logout.php') ? 'active' : ''; ?>">Logout</a></li>
  </ul>
</aside>