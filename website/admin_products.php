<?php
declare(strict_types=1);

include 'session.php';
include 'config.php';
require_once __DIR__ . '/includes/audit_log.php';

checkStaffOrAdmin();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function admin_products_csrf_ok(): bool {
    $t = $_POST['csrf_token'] ?? '';
    return is_string($t) && hash_equals($_SESSION['csrf_token'], $t);
}

$actor = (string) ($_SESSION['username'] ?? 'unknown');
$flash_success = '';
$flash_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!admin_products_csrf_ok()) {
        require_once __DIR__ . '/includes/csrf.php';
        itsec_csrf_fail_log('admin_products.php');
        $flash_error = 'Invalid security token. Please refresh the page and try again.';
    } elseif (isset($_POST['create_product'])) {
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'general'));
        if ($category === '') {
            $category = 'general';
        }
        $description = trim((string) ($_POST['description'] ?? ''));
        $price_raw = $_POST['price'] ?? '';
        $stock_raw = $_POST['stock'] ?? '';
        $price = is_numeric($price_raw) ? round((float) $price_raw, 2) : null;
        $stock = filter_var($stock_raw, FILTER_VALIDATE_INT);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($name === '' || $price === null || $price < 0 || $stock === false || $stock < 0) {
            $flash_error = 'Please provide valid name, price (≥ 0), and stock (≥ 0).';
        } elseif (strlen($category) > 50 || strlen($name) > 255) {
            $flash_error = 'Name or category exceeds allowed length.';
        } else {
            $stmt = $conn->prepare(
                'INSERT INTO products (name, category, description, price, stock, is_active) VALUES (?, ?, ?, ?, ?, ?)'
            );
            if ($stmt) {
                $stmt->bind_param('sss' . 'dii', $name, $category, $description, $price, $stock, $is_active);
                if ($stmt->execute()) {
                    $new_id = (int) $conn->insert_id;
                    itsec_audit_log($conn, 'products', 'product_created', $new_id, $actor);
                    $flash_success = 'Product created successfully.';
                } else {
                    $flash_error = 'Could not create product.';
                }
                $stmt->close();
            } else {
                $flash_error = 'Database error.';
            }
        }
    } elseif (isset($_POST['update_product'])) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $name = trim((string) ($_POST['name'] ?? ''));
        $category = trim((string) ($_POST['category'] ?? 'general'));
        if ($category === '') {
            $category = 'general';
        }
        $description = trim((string) ($_POST['description'] ?? ''));
        $price_raw = $_POST['price'] ?? '';
        $stock_raw = $_POST['stock'] ?? '';
        $price = is_numeric($price_raw) ? round((float) $price_raw, 2) : null;
        $stock = filter_var($stock_raw, FILTER_VALIDATE_INT);
        $is_active = isset($_POST['is_active']) ? 1 : 0;

        if ($product_id <= 0 || $name === '' || $price === null || $price < 0 || $stock === false || $stock < 0) {
            $flash_error = 'Invalid product update.';
        } elseif (strlen($category) > 50 || strlen($name) > 255) {
            $flash_error = 'Name or category exceeds allowed length.';
        } else {
            $stmt = $conn->prepare(
                'UPDATE products SET name = ?, category = ?, description = ?, price = ?, stock = ?, is_active = ? WHERE product_id = ?'
            );
            if ($stmt) {
                $stmt->bind_param('sss' . 'diii', $name, $category, $description, $price, $stock, $is_active, $product_id);
                if ($stmt->execute()) {
                    itsec_audit_log($conn, 'products', 'product_updated', $product_id, $actor);
                    $flash_success = 'Product updated successfully.';
                } else {
                    $flash_error = 'Could not update product.';
                }
                $stmt->close();
            } else {
                $flash_error = 'Database error.';
            }
        }
    } elseif (isset($_POST['toggle_active'])) {
        $product_id = (int) ($_POST['product_id'] ?? 0);
        $set_active = (int) ($_POST['set_active'] ?? 0);
        if ($product_id <= 0 || ($set_active !== 0 && $set_active !== 1)) {
            $flash_error = 'Invalid activation toggle.';
        } else {
            $stmt = $conn->prepare('UPDATE products SET is_active = ? WHERE product_id = ?');
            if ($stmt) {
                $stmt->bind_param('ii', $set_active, $product_id);
                if ($stmt->execute() && $stmt->affected_rows > 0) {
                    $act = $set_active === 1 ? 'product_activated' : 'product_deactivated';
                    itsec_audit_log($conn, 'products', $act, $product_id, $actor);
                    $flash_success = $set_active === 1 ? 'Product activated.' : 'Product deactivated.';
                } else {
                    $flash_error = 'No changes made.';
                }
                $stmt->close();
            } else {
                $flash_error = 'Database error.';
            }
        }
    }
}

$action = $_GET['action'] ?? 'list';
$edit_id = (int) ($_GET['id'] ?? 0);

$edit_row = null;
if ($action === 'edit' && $edit_id > 0) {
    $est = $conn->prepare('SELECT product_id, name, category, description, price, stock, is_active FROM products WHERE product_id = ?');
    if ($est) {
        $est->bind_param('i', $edit_id);
        $est->execute();
        $res = $est->get_result();
        $edit_row = $res->fetch_assoc();
        $est->close();
    }
    if (!$edit_row) {
        $action = 'list';
        $flash_error = $flash_error ?: 'Product not found.';
    }
}

$products = [];
$plist = $conn->query('SELECT product_id, name, category, price, stock, is_active FROM products ORDER BY product_id DESC');
if ($plist) {
    while ($r = $plist->fetch_assoc()) {
        $products[] = $r;
    }
    $plist->free();
}

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Products — Admin</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <link rel="stylesheet" href="css/admindash.css" />
</head>
<body>
<div class="dashboard-container">
  <?php include 'admin_sidebar.php'; ?>
  <main class="main-content">
    <h1>Products</h1>
    <p><a href="admin_products.php" class="btn btn-sm btn-secondary mb-3">List all</a>
       <a href="admin_products.php?action=add" class="btn btn-sm btn-primary mb-3 ml-2">Add product</a></p>

    <?php if ($flash_success !== ''): ?>
      <div class="alert alert-success"><?= htmlspecialchars($flash_success) ?></div>
    <?php endif; ?>
    <?php if ($flash_error !== ''): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($flash_error) ?></div>
    <?php endif; ?>

    <?php if ($action === 'add'): ?>
      <h2 class="h4 mb-3">New product</h2>
      <form method="post" class="mb-5" style="max-width: 520px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" class="form-control" required maxlength="255" />
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" class="form-control" value="general" maxlength="50" />
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="form-group">
          <label>Price</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0" required />
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input type="number" name="stock" class="form-control" min="0" required />
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" name="is_active" class="form-check-input" id="is_active_new" checked />
          <label class="form-check-label" for="is_active_new">Active (visible in store)</label>
        </div>
        <button type="submit" name="create_product" class="btn btn-success">Create</button>
      </form>

    <?php elseif ($action === 'edit' && $edit_row): ?>
      <h2 class="h4 mb-3">Edit product #<?= (int) $edit_row['product_id'] ?></h2>
      <form method="post" class="mb-5" style="max-width: 520px;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
        <input type="hidden" name="product_id" value="<?= (int) $edit_row['product_id'] ?>" />
        <div class="form-group">
          <label>Name</label>
          <input type="text" name="name" class="form-control" required maxlength="255"
            value="<?= htmlspecialchars((string) $edit_row['name']) ?>" />
        </div>
        <div class="form-group">
          <label>Category</label>
          <input type="text" name="category" class="form-control" maxlength="50"
            value="<?= htmlspecialchars((string) $edit_row['category']) ?>" />
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" rows="3"><?= htmlspecialchars((string) $edit_row['description']) ?></textarea>
        </div>
        <div class="form-group">
          <label>Price</label>
          <input type="number" name="price" class="form-control" step="0.01" min="0" required
            value="<?= htmlspecialchars((string) $edit_row['price']) ?>" />
        </div>
        <div class="form-group">
          <label>Stock</label>
          <input type="number" name="stock" class="form-control" min="0" required
            value="<?= htmlspecialchars((string) $edit_row['stock']) ?>" />
        </div>
        <div class="form-check mb-3">
          <input type="checkbox" name="is_active" class="form-check-input" id="is_active_ed"
            <?= (int) $edit_row['is_active'] ? 'checked' : '' ?> />
          <label class="form-check-label" for="is_active_ed">Active</label>
        </div>
        <button type="submit" name="update_product" class="btn btn-primary">Save changes</button>
      </form>

    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover bg-white">
          <thead class="thead-dark">
            <tr>
              <th>ID</th>
              <th>Name</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Active</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($products)): ?>
              <tr><td colspan="7" class="text-center">No products.</td></tr>
            <?php else: ?>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td><?= (int) $p['product_id'] ?></td>
                  <td><?= htmlspecialchars((string) $p['name']) ?></td>
                  <td><?= htmlspecialchars((string) $p['category']) ?></td>
                  <td><?= htmlspecialchars(number_format((float) $p['price'], 2)) ?></td>
                  <td><?= (int) $p['stock'] ?></td>
                  <td><?= (int) $p['is_active'] ? 'Yes' : 'No' ?></td>
                  <td>
                    <a class="btn btn-sm btn-outline-primary" href="admin_products.php?action=edit&amp;id=<?= (int) $p['product_id'] ?>">Edit</a>
                    <form method="post" class="d-inline" onsubmit="return confirm('Toggle visibility for this product?');">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>" />
                      <input type="hidden" name="product_id" value="<?= (int) $p['product_id'] ?>" />
                      <input type="hidden" name="set_active" value="<?= (int) $p['is_active'] ? '0' : '1' ?>" />
                      <button type="submit" name="toggle_active" class="btn btn-sm btn-outline-secondary">
                        <?= (int) $p['is_active'] ? 'Deactivate' : 'Activate' ?>
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </main>
</div>
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
