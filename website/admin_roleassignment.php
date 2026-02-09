<?php
include 'session.php';
include 'config.php'; // database connection
checkRole('admin'); // Ensure only admins can access

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id = $conn->real_escape_string($_POST['user_id']);
    $new_role = $conn->real_escape_string($_POST['new_role']);
    
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE user_id = ?");
    $stmt->bind_param("si", $new_role, $user_id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        $success_message = "Role updated successfully!";
    } else {
        $error_message = "Failed to update role or no changes made.";
    }
    
    $stmt->close();
}

// Fetch all users from database
$users = [];
$query = "SELECT user_id, username, email, role, created_at FROM users ORDER BY created_at DESC";
$result = $conn->query($query);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    $result->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Admin - Role Assignment</title>

  <!-- Bootstrap + FontAwesome -->
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
  <link rel="stylesheet" href="css/admindash.css" />
  <style>
    .role-select {
      width: 120px;
      display: inline-block;
    }
    .role-form {
      display: inline-block;
    }
  </style>
</head>
<body>
<?php $currentPage = basename($_SERVER['PHP_SELF']); ?>
<div class="dashboard-container">

  <!-- Sidebar -->
  <?php include 'admin_sidebar.php'; ?>

  <!-- Main Content -->
  <div class="main-content">
    <h1>User Role Assignment</h1>

    <?php if (isset($success_message)): ?>
      <div class="alert alert-success">
        <?= htmlspecialchars($success_message) ?>
      </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
      <div class="alert alert-danger">
        <?= htmlspecialchars($error_message) ?>
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table table-bordered table-hover">
        <thead class="thead-dark">
          <tr>
            <th>User ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Current Role</th>
            <th>Account Created</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($users)): ?>
            <tr>
              <td colspan="6" class="text-center">No users found</td>
            </tr>
          <?php else: ?>
            <?php foreach ($users as $user): ?>
              <tr>
                <td>#<?= htmlspecialchars($user['user_id']) ?></td>
                <td><?= htmlspecialchars($user['username']) ?></td>
                <td><?= htmlspecialchars($user['email'] ?? 'N/A') ?></td>
                <td>
                  <span class="badge 
                    <?= $user['role'] == 'admin' ? 'badge-danger' : 
                       ($user['role'] == 'staff' ? 'badge-info' : 'badge-secondary') ?>">
                    <?= htmlspecialchars(ucfirst($user['role'])) ?>
                  </span>
                </td>
                <td><?= htmlspecialchars($user['created_at']) ?></td>
                <td>
                  <form method="POST" class="role-form">
                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                    <select name="new_role" class="form-control form-control-sm role-select">
                      <option value="admin" <?= $user['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
                      <option value="staff" <?= $user['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
                      <option value="customer" <?= $user['role'] == 'customer' ? 'selected' : '' ?>>Customer</option>
                    </select>
                    <button type="submit" name="update_role" class="btn btn-sm btn-primary mt-1">Update</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Optional JS -->
<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>