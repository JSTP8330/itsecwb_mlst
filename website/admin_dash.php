<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/audit_log.php';

checkStaffOrAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function admin_dash_csrf_ok(): bool {
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && hash_equals($_SESSION['csrf_token'], $t);
}

$actor = (string) ($_SESSION['username'] ?? 'unknown');
$dash_success = '';
$dash_error = '';

$allowed_statuses = ['pending', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    if (!admin_dash_csrf_ok()) {
        require_once __DIR__ . '/includes/csrf.php';
        itsec_csrf_fail_log('admin_dash.php');
        $dash_error = 'Invalid security token. Please refresh the page and try again.';
    } else {
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $new_status = (string) ($_POST['new_status'] ?? '');
        if ($order_id <= 0 || !in_array($new_status, $allowed_statuses, true)) {
            $dash_error = 'Invalid order or status.';
        } else {
            $stmt = $conn->prepare('UPDATE orders SET status = ? WHERE order_id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $new_status, $order_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    itsec_audit_log($conn, 'orders', 'order_status_changed', $order_id, $actor);
                    $dash_success = 'Order status updated.';
                } else {
                    $dash_error = 'No change applied (order not found or status unchanged).';
                }
                $stmt->close();
            } else {
                $dash_error = 'Database error.';
            }
        }
    }
}

$pending_orders = 0;
$completed_orders = 0;
$total_products = 0;

$r1 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'pending'");
if ($r1) {
    $pending_orders = (int) ($r1->fetch_assoc()['c'] ?? 0);
    $r1->free();
}
$r2 = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE status = 'completed'");
if ($r2) {
    $completed_orders = (int) ($r2->fetch_assoc()['c'] ?? 0);
    $r2->free();
}
$r3 = $conn->query('SELECT COUNT(*) AS c FROM products');
if ($r3) {
    $total_products = (int) ($r3->fetch_assoc()['c'] ?? 0);
    $r3->free();
}

$recent_orders = [];
$ro = $conn->query(
    'SELECT o.order_id, o.user_id, o.status, o.total, o.created_at, u.username
     FROM orders o
     INNER JOIN users u ON u.user_id = o.user_id
     ORDER BY o.created_at DESC
     LIMIT 15'
);
if ($ro) {
    while ($row = $ro->fetch_assoc()) {
        $recent_orders[] = $row;
    }
    $ro->free();
}

$currentPage = basename($_SERVER['PHP_SELF']);
$panelTitle = ($_SESSION['role'] ?? '') === 'admin' ? 'Admin Dashboard' : 'Staff Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($panelTitle) ?></title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="css/admindash.css?v=1.0">
</head>
<body>
  <div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>

    <main class="main-content">
      <h1><?= htmlspecialchars($panelTitle) ?></h1>

      <?php if ($dash_success !== ''): ?>
        <div class="alert alert-success"><?= htmlspecialchars($dash_success) ?></div>
      <?php endif; ?>
      <?php if ($dash_error !== ''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($dash_error) ?></div>
      <?php endif; ?>

      <div class="stats-container">
        <div class="stat-box pending">
          <h3>Pending orders</h3>
          <p class="stat-number"><?= $pending_orders ?></p>
        </div>
        <div class="stat-box completed">
          <h3>Completed orders</h3>
          <p class="stat-number"><?= $completed_orders ?></p>
        </div>
        <div class="stat-box products">
          <h3>Total products</h3>
          <p class="stat-number"><?= $total_products ?></p>
        </div>
      </div>

      <h2 class="h4 mt-5 mb-3">Recent orders</h2>
      <div class="table-responsive bg-white p-3 rounded">
        <table class="table table-sm table-bordered mb-0">
          <thead class="thead-light">
            <tr>
              <th>Order</th>
              <th>Customer</th>
              <th>Placed</th>
              <th>Total</th>
              <th>Status</th>
              <th>Update</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($recent_orders)): ?>
              <tr><td colspan="6" class="text-center text-muted">No orders yet.</td></tr>
            <?php else: ?>
              <?php foreach ($recent_orders as $o): ?>
                <tr>
                  <td>#<?= (int) $o['order_id'] ?></td>
                  <td><?= htmlspecialchars((string) $o['username']) ?></td>
                  <td><?= htmlspecialchars((string) $o['created_at']) ?></td>
                  <td><?= htmlspecialchars(number_format((float) $o['total'], 2)) ?></td>
                  <td><span class="badge badge-secondary"><?= htmlspecialchars((string) $o['status']) ?></span></td>
                  <td>
                    <form method="post" class="form-inline">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                      <input type="hidden" name="order_id" value="<?= (int) $o['order_id'] ?>" />
                      <select name="new_status" class="form-control form-control-sm mr-1">
                        <?php foreach ($allowed_statuses as $st): ?>
                          <option value="<?= htmlspecialchars($st) ?>" <?= $o['status'] === $st ? 'selected' : '' ?>><?= htmlspecialchars($st) ?></option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" name="update_order_status" class="btn btn-sm btn-primary">Save</button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
