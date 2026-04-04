<?php
if (!isset($currentPage)) {
    $currentPage = basename($_SERVER['PHP_SELF'] ?? '');
}
$sidebarRole = $_SESSION['role'] ?? '';
$isAdminSidebar = ($sidebarRole === 'admin');
?>
<aside class="sidebar">
  <h2><?= $isAdminSidebar ? 'Admin Panel' : 'Staff Panel' ?></h2>
  <ul>
    <li><a href="admin_dash.php" class="<?php echo ($currentPage == 'admin_dash.php') ? 'active' : ''; ?>">Dashboard</a></li>
    <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Homepage</a></li>
    <li><a href="admin_products.php" class="<?php echo ($currentPage == 'admin_products.php') ? 'active' : ''; ?>">Products</a></li>
    <hr class="rounded"><br>

    <?php if ($isAdminSidebar): ?>
    <li><a href="admin_auditlogs.php" class="<?php echo ($currentPage == 'admin_auditlogs.php') ? 'active' : ''; ?>">Audit Logs</a></li>
    <li><a href="admin_roleassignment.php" class="<?php echo ($currentPage == 'admin_roleassignment.php') ? 'active' : ''; ?>">Role Assignment</a></li>
    <?php endif; ?>

    <hr class="rounded"><br>
    <li><a href="logout.php" class="<?php echo ($currentPage == 'logout.php') ? 'active' : ''; ?>">Logout</a></li>
  </ul>
</aside>
