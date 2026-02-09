<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link type="text/css" rel="stylesheet" href="css/login.css"/>
</head>
<body>
  <?php
  // login.php

  session_start();

  $servername = "localhost";
  $username_db = "root";
  $password_db = "";
  $database = "online_store";

  $conn = new mysqli($servername, $username_db, $password_db, $database);
  if ($conn->connect_error) {
      echo "Database connection failed";
      exit;
  }

  if ($_SERVER["REQUEST_METHOD"] === "POST") {
      $username = $_POST['username'] ?? '';
      $password = $_POST['password'] ?? '';

      if (!$username || !$password) {
          echo "Please fill in all fields";
          exit;
      }

      // Prepare statement to get user password hash
      $stmt = $conn->prepare("SELECT password_hash, role FROM users WHERE username = ?");
      if (!$stmt) {
          echo "Database query failed";
          exit;
      }

      $stmt->bind_param("s", $username);
      $stmt->execute();

      $stmt->bind_result($stored_hash, $role);
      if ($stmt->fetch()) {
          // Hash the submitted password with SHA-256
          $hashed_password = hash('sha256', $password);
  
          if ($hashed_password === $stored_hash) {
              $_SESSION['username'] = $username;
              $_SESSION['role'] = $role; // Set role from database
              ob_clean();
              echo "success";
          } else {
              ob_clean();
              echo "Incorrect password";
          }
      } else {
          ob_clean();
          echo "User not found";
      }

      $stmt->close();
      $conn->close();
      exit;
  }
  ?>

  <div class="wrapper">
      <?php if(isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
      <?php endif; ?>
    <form id="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
      <h2>Login</h2>
        <div class="input-field">
        <input type="text" name="username" required>
        <label>Enter your username</label>
      </div>
      <div class="input-field">
        <input type="password" name="password" required>
        <label>Enter your password</label>
      </div>
      <div class="forget">
        <label for="remember">
          <input type="checkbox" id="remember">
          <p>Remember me</p>
        </label>
        <a href="change_password.php" onclick="alert('A password reset link has been sent to your email. Please follow the instructions well.');">Forgot password?</a>
      </div>
      <button type="submit">Log In</button>
      <div class="register">
        <p>Don't have an account? <a href="register.php">Register</a></p>
      </div>
    </form>
  </div>
  <script src="js/login.js"></script>
</body>
</html>