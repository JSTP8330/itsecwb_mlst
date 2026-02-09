<?php
session_start();

// Only allow admin access
// if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
//     echo "Access denied. You are not authorized to view this page.";
//     exit;
// }

include 'config.php'; // database connection

// Fetch audit logs
$sql = "SELECT * FROM audit_logs ORDER BY change_time DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Admin - Audit Logs</title>
    <link rel="stylesheet" href="css/admindash.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
<?php include 'admin_sidebar.php'; ?>

<div class="main-content">
    <h1 class="mb-4">Audit Logs</h1>

    <div class="table-responsive">
        <table class="table table-bordered table-hover table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Log ID</th>
                    <th>Table Name</th>
                    <th>Action</th>
                    <th>Record ID</th>
                    <th>Changed By</th>
                    <th>Timestamp</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['log_id']) ?></td>
                            <td><?= htmlspecialchars($row['table_name']) ?></td>
                            <td><?= htmlspecialchars($row['action_type']) ?></td>
                            <td><?= htmlspecialchars($row['record_id']) ?></td>
                            <td><?= htmlspecialchars($row['changed_by']) ?></td>
                            <td><?= htmlspecialchars($row['change_time']) ?></td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">No audit logs found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
