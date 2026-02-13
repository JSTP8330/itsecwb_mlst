<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin - Manage Tables</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"/>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"/>
  <link rel="stylesheet" href="css/admindash.css"/>
  <style>
    .table-editable th, .table-editable td {
      padding: 8px;
      vertical-align: middle;
    }
    .table-editable th {
      background-color: #343a40;
      color: white;
      text-align: left;
    }
    #edit-columns th:nth-child(1), #edit-columns td:nth-child(1) {
      width: 60%;
    }
    #edit-columns th:nth-child(2), #edit-columns td:nth-child(2) {
      width: 40%;
    }
    #edit-rows-table th, #edit-rows-table td {
      width: auto;
      min-width: 100px;
    }
    #edit-rows-table th:first-child, #edit-rows-table td:first-child {
      width: 10%;
    }
    #edit-rows-table th:last-child, #edit-rows-table td:last-child {
      width: 10%;
      text-align: center;
    }
    .table-editable input, .table-editable select {
      width: 100%;
      box-sizing: border-box;
      margin: 0;
    }
    .remove-btn {
      cursor: pointer;
      color: red;
      font-size: 16px;
    }
    .btn-sm {
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <?php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  $currentPage = basename($_SERVER['PHP_SELF']);
  include 'session.php';
  include 'config.php';
  checkRole('admin');
  ?>
  <div class="dashboard-container">
    <?php include 'admin_sidebar.php'; ?>
    <div class="main-content">
      <h1>Database Tables</h1>
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($_GET['success']); ?></div>
      <?php endif; ?>
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
      <?php endif; ?>
      <div class="mb-3 text-right">
        <button class="btn btn-success" data-toggle="modal" data-target="#addTableModal">
          <i class="fas fa-plus-circle"></i> Add Table
        </button>
      </div>
      <table class="table table-bordered table-striped">
        <thead class="thead-dark">
          <tr>
            <th>Table Name</th>
            <th>Columns</th>
            <th>Rows</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $result = $conn->query("SHOW TABLES");
          if ($result) {
              while ($row = $result->fetch_array()) {
                  $table = $row[0];
                  
                  // SECURITY: Validate table name to prevent SQL injection
                  // Only allow alphanumeric characters and underscores
                  if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
                      continue; // Skip invalid table names
                  }
                  
                  // Use parameterized query with information_schema for columns
                  $col_stmt = $conn->prepare("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?");
                  if ($col_stmt) {
                      $col_stmt->bind_param("s", $table);
                      $col_stmt->execute();
                      $col_result = $col_stmt->get_result();
                      $column_count = $col_result->num_rows;
                      $col_stmt->close();
                  } else {
                      $column_count = 0;
                  }
                  
                  // Query row count using escaped table name
                  $row_result = $conn->query("SELECT COUNT(*) as row_count FROM `" . $conn->real_escape_string($table) . "`");
                  $row_count = $row_result ? $row_result->fetch_assoc()['row_count'] : 0;
                  
                  echo "<tr>";
                  echo "<td>" . htmlspecialchars($table) . "</td>";
                  echo "<td>$column_count</td>";
                  echo "<td>$row_count</td>";
                  echo "<td>
                          <button class='btn btn-sm btn-primary' onclick='editTable(" . json_encode($table) . ")'><i class='fas fa-pen'></i> Edit</button>
                          <button class='btn btn-sm btn-info' onclick='addColumn(" . json_encode($table) . ")'><i class='fas fa-columns'></i> Add Column</button>
                          <button class='btn btn-sm btn-warning' onclick='addRow(" . json_encode($table) . ")'><i class='fas fa-plus'></i> Add Row</button>
                          <button class='btn btn-sm btn-danger' onclick='deleteTable(" . json_encode($table) . ")'><i class='fas fa-trash'></i> Delete</button>
                        </td>";
                  echo "</tr>";
                  if ($row_result) $row_result->free();
              }
              $result->free();
          } else {
              echo "<tr><td colspan='4'>An error occurred while fetching tables.</td></tr>";
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="modal fade" id="addTableModal" tabindex="-1" role="dialog" aria-labelledby="addTableModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form class="modal-content" method="POST" action="create_table.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addTableModalLabel">Create New Table</h5>
          <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Table Name</label>
            <input type="text" name="table_name" class="form-control" placeholder="e.g. orders" required>
          </div>
          <div class="form-group">
            <label>Number of Initial Rows (optional)</label>
            <input type="number" name="num_rows" class="form-control" min="0" value="0" placeholder="e.g. 0">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Create Table</button>
        </div>
      </form>
    </div>
  </div>
  <div class="modal fade" id="editTableModal" tabindex="-1" role="dialog" aria-labelledby="editTableModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <form class="modal-content" id="editTableForm" method="POST" action="edit_table.php">
        <div class="modal-header">
          <h5 class="modal-title" id="editTableModalLabel">Edit Table</h5>
          <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Current Table Name</label>
            <input type="text" name="current_table_name" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>New Table Name</label>
            <input type="text" name="new_table_name" class="form-control" placeholder="Enter new table name" required>
          </div>
          <div class="form-group">
            <label>Columns</label>
            <table class="table table-bordered table-editable">
              <thead>
                <tr>
                  <th>Column Name</th>
                  <th>Data Type</th>
                </tr>
              </thead>
              <tbody id="edit-columns"></tbody>
            </table>
          </div>
          <div class="form-group">
            <label>Row Data</label>
            <table class="table table-bordered table-editable" id="edit-rows-table">
              <thead id="edit-rows-headers"></thead>
              <tbody id="edit-rows"></tbody>
            </table>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-danger" onclick="deleteTableFromModal()">Delete Table</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>
  <div class="modal fade" id="addColumnModal" tabindex="-1" role="dialog" aria-labelledby="addColumnModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form class="modal-content" method="POST" action="add_column.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addColumnModalLabel">Add Column</h5>
          <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Table Name</label>
            <input type="text" name="table_name" class="form-control" readonly>
          </div>
          <div class="form-group">
            <label>Column Name</label>
            <input type="text" name="column_name" class="form-control" placeholder="e.g. new_column" required>
          </div>
          <div class="form-group">
            <label>Data Type</label>
            <select name="data_type" class="form-control" required>
              <option value="VARCHAR(255)">VARCHAR(255)</option>
              <option value="INT">INT</option>
              <option value="DECIMAL(10,2)">DECIMAL(10,2)</option>
              <option value="TEXT">TEXT</option>
              <option value="DATETIME">DATETIME</option>
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-info">Add Column</button>
        </div>
      </form>
    </div>
  </div>
  <div class="modal fade" id="addRowModal" tabindex="-1" role="dialog" aria-labelledby="addRowModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <form class="modal-content" method="POST" action="add_row.php">
        <div class="modal-header">
          <h5 class="modal-title" id="addRowModalLabel">Add Row</h5>
          <button type="button" class="close" data-dismiss="modal"><span>×</span></button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label>Table Name</label>
            <input type="text" name="table_name" class="form-control" readonly>
          </div>
          <div id="row-inputs"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning">Add Row</button>
        </div>
      </form>
    </div>
  </div>
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    <?php if (isset($_GET['success']) || isset($_GET['error'])): ?>
      $(document).ready(function() {
        const message = <?php echo isset($_GET['success']) ? json_encode(htmlspecialchars($_GET['success'])) : json_encode(htmlspecialchars($_GET['error'])); ?>;
        const alertClass = <?php echo isset($_GET['success']) ? '"alert-success"' : '"alert-danger"'; ?>;
        const alertHtml = `<div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                            ${message}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                              <span aria-hidden="true">&times;</span>
                            </button>
                          </div>`;
        $('body').append(alertHtml);
        history.replaceState(null, '', 'tables.php');
        setTimeout(() => $('.alert').alert('close'), 5000);
      });
    <?php endif; ?>
    function editTable(tableName) {
      $('#editTableModal').find('input[name="current_table_name"]').val(tableName);
      $('#editTableModal').find('input[name="new_table_name"]').val(tableName);
      $.ajax({
        url: 'get_table_data.php',
        method: 'POST',
        data: { table_name: tableName },
        dataType: 'json',
        success: function(data) {
          if (data.error) {
            console.error('Error from get_table_data.php:', data.error);
            $('#edit-columns').html('<tr><td colspan="2">Error: ' + data.error + '</td></tr>');
            $('#edit-rows').html('<tr><td>Error: ' + data.error + '</td></tr>');
            $('#editTableModal').modal('show');
            return;
          }
          const normalizeType = (type) => {
            if (type.match(/^int/)) return 'INT';
            if (type.match(/^varchar/)) return 'VARCHAR(255)';
            if (type.match(/^decimal/)) return 'DECIMAL(10,2)';
            if (type === 'text') return 'TEXT';
            if (type === 'datetime') return 'DATETIME';
            return 'VARCHAR(255)';
          };
          $('#edit-columns').html('');
          data.columns.forEach(col => {
            const [name, rawType] = col.split(':');
            const type = normalizeType(rawType.toLowerCase());
            const row = `
              <tr>
                <td><input type="text" name="column_names[]" class="form-control" value="${name}" required></td>
                <td>
                  <select name="column_types[]" class="form-control" disabled>
                    <option value="VARCHAR(255)" ${type === 'VARCHAR(255)' ? 'selected' : ''}>VARCHAR(255)</option>
                    <option value="INT" ${type === 'INT' ? 'selected' : ''}>INT</option>
                    <option value="DECIMAL(10,2)" ${type === 'DECIMAL(10,2)' ? 'selected' : ''}>DECIMAL(10,2)</option>
                    <option value="TEXT" ${type === 'TEXT' ? 'selected' : ''}>TEXT</option>
                    <option value="DATETIME" ${type === 'DATETIME' ? 'selected' : ''}>DATETIME</option>
                  </select>
                </td>
              </tr>`;
            $('#edit-columns').append(row);
          });
          $('#edit-rows-headers').html(`
            <tr>
              <th>${data.primary_key}</th>
              ${data.columns
                .filter(col => col.split(':')[0] !== data.primary_key)
                .map(col => `<th>${col.split(':')[0]}</th>`).join('')}
              <th>Action</th>
            </tr>
          `);
          $('#edit-rows').html('');
          data.rows.forEach(row => {
            if (!row) return;
            const values = row.split(',');
            const pkValue = values.shift() || '';
            const rowHtml = `
              <tr>
                <td><input type="text" name="row_ids[]" class="form-control" value="${pkValue}" readonly></td>
                ${values.map(val => `<td><input type="text" name="row_values[${pkValue}][]" class="form-control" value="${val === null ? '' : val}"></td>`).join('')}
                <td><i class="fas fa-trash remove-btn" onclick="deleteRow(event, '${tableName}', '${pkValue}', '${data.primary_key}', this)"></i></td>
              </tr>`;
            $('#edit-rows').append(rowHtml);
          });
          $('#editTableModal').modal('show');
        },
        error: function(xhr, status, error) {
          console.error('AJAX error:', status, error, xhr.responseText);
          $('#edit-columns').html('<tr><td colspan="2">Error loading columns: ${xhr.responseText}</td></tr>');
          $('#edit-rows').html('<tr><td>Error loading rows: ${xhr.responseText}</td></tr>');
          $('#editTableModal').modal('show');
        }
      });
    }
    function deleteRow(event, tableName, pkValue, primaryKey, element) {
      event.preventDefault();
      event.stopPropagation();
      if (confirm(`Are you sure you want to delete the row with ${primaryKey} ${pkValue}? This action cannot be undone.`)) {
        $.ajax({
          url: 'delete_row.php',
          method: 'POST',
          data: { table_name: tableName, id: pkValue, primary_key: primaryKey },
          dataType: 'json',
          success: function(response) {
            console.log('delete_row.php response:', response);
            if (response.success) {
              $(element).closest('tr').remove();
              alert('Row deleted successfully');
              editTable(tableName);
            } else {
              console.error('Error from delete_row.php:', response.error);
              alert('Error deleting row: ' + response.error);
            }
          },
          error: function(xhr, status, error) {
            console.error('AJAX error in deleteRow:', status, error, xhr.responseText);
            alert('Error deleting row: AJAX request failed');
          }
        });
      }
    }
    function addColumn(tableName) {
      $('#addColumnModal').find('input[name="table_name"]').val(tableName);
      $('#addColumnModal').modal('show');
    }
    function addRow(tableName) {
      $('#addRowModal').find('input[name="table_name"]').val(tableName);
      $.ajax({
        url: 'get_columns.php',
        method: 'POST',
        data: { table_name: tableName },
        success: function(response) {
          $('#row-inputs').html(response);
          $('#addRowModal').modal('show');
        },
        error: function() {
          $('#row-inputs').html('<p class="text-danger">Error loading columns.</p>');
          $('#addRowModal').modal('show');
        }
      });
    }
    function deleteTable(tableName) {
      if (confirm('Are you sure you want to delete the table ' + tableName + '?')) {
        window.location.href = 'delete_table.php?table=' + encodeURIComponent(tableName);
      }
    }
    function deleteTableFromModal() {
      const tableName = $('#editTableModal').find('input[name="current_table_name"]').val();
      if (confirm('Are you sure you want to delete the table ' + tableName + '? This action cannot be undone.')) {
        window.location.href = 'delete_table.php?table=' + encodeURIComponent(tableName);
        $('#editTableModal').modal('hide');
      }
    }
    $('#editTableForm').on('submit', function(e) {
      e.preventDefault();
      $.ajax({
        url: 'edit_table.php',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(response) {
          console.log('edit_table.php response:', response);
          if (response.success) {
            alert('Table updated successfully');
            const newTableName = $('#editTableModal').find('input[name="new_table_name"]').val();
            editTable(newTableName); 
            location.reload(); // Refresh the page after success
          } else {
            console.error('Error from edit_table.php:', response.error);
            alert('Error updating table: ' + response.error);
          }
        },
        error: function(xhr, status, error) {
          console.error('AJAX error in editTableForm:', status, error, xhr.responseText);
          alert('Error updating table: AJAX request failed');
        }
      });
    });
  </script>
</body>
</html>