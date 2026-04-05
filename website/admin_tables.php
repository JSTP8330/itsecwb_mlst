<?php
/**
 * Phase 4: table management UI disabled (no working AJAX/modal backends exposed).
 */
include 'session.php';
include 'config.php';
checkRole('admin');

$currentPage = basename($_SERVER['PHP_SELF'] ?? '');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin - Database Tables</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admindash.css">
</head>
<body>
<div class="dashboard-container">
  <?php include 'admin_sidebar.php'; ?>
  <div class="main-content">
    <h1 class="mb-3">Database Tables</h1>
    <div class="alert alert-secondary">
      This tool is <strong>disabled</strong> for this milestone. Schema changes should be done via SQL migrations (e.g. <code>online_store.sql</code>) and phpMyAdmin, not from the web UI.
    </div>
    <p><a href="admin_dash.php" class="btn btn-primary">Back to dashboard</a></p>
  </div>
</div>
</body>
</html>
<?php
$conn->close();
