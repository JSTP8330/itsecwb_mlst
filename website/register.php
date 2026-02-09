<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Page</title>
  <link type="text/css" rel="stylesheet" href="css/register.css"/>
</head>
<body>

  <?php 
    $currentPage = basename($_SERVER['PHP_SELF']);
    
    $servername = "localhost";
    $username = "root";
    $password = "";
    $database = "online_store";

    $conn = new mysqli($servername, $username, $password, $database);
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $userpassword = $_POST['password'] ?? '';
        $confirmpassword = $_POST['confirmpassword'] ?? '';
      
      if (!$username || !$email || !$userpassword || !$confirmpassword) {
          echo "error: All fields are required";
          exit;
      }
  
      if ($userpassword !== $confirmpassword) {
          echo "error: Passwords do not match";
          exit;
      }
  
      $hashed = hash('sha256', $userpassword);
  
      $stmt = $conn->prepare("INSERT INTO users (username, email, role, password_hash) VALUES (?, ?, 'customer', ?)");
      if (!$stmt) {
          echo "error: Prepare failed";
          exit;
      }  
      
      $stmt->bind_param("sss", $username, $email, $hashed);
      
      if ($stmt->execute()) {
        ob_clean();
        echo "success";
      } else {
          ob_clean();
          echo "error: Could not register: " . $stmt->error;
      }
  
      $stmt->close();
      $conn->close();
      exit;
    }
  ?>

  <div class="wrapper">
    <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
      <h2>Create An Account</h2>
       <div class="input-field">
        <input type="text" id="username" name="username" required>
        <label>Enter your username</label>
      </div>
      <div class="input-field">
        <input type="text" id="email" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" id="userpassword" name="password" required>
        <label>Enter your password</label>
      </div> 
      <div class="input-field">
        <input type="password" id="confirmpassword" name="confirmpassword" required>
        <label>Confirm your password</label>
      </div>
      <button type="submit">Register</button>
      <div class="login">
        <p>Already have an account? <a href="login.php">Login</a></p>
      </div>
    </form>
  </div>
  <script src="js/register.js"></script>
</body>
</html>

