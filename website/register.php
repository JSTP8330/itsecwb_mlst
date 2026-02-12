<?php 
// register.php
$servername = "localhost";
$username_db = "root";
$password_db = "";
$database = "online_store";

$conn = new mysqli($servername, $username_db, $password_db, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = ""; // Initialize variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $userpassword = $_POST['password'] ?? '';
    $confirmpassword = $_POST['confirmpassword'] ?? '';
    
    // --- INPUT VALIDATION ---
    if (empty($username) || empty($email) || empty($phone) || empty($userpassword) || empty($confirmpassword)) {
        $error_message = "All fields are required";
    } elseif ($userpassword !== $confirmpassword) {
        $error_message = "Passwords do not match";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format";
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error_message = "Invalid phone number (must be 10-15 digits)";
    } else {
        // Check if username/email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $error_message = "Username or Email already taken";
            $check->close();
        } else {
            $check->close();
            
            //HASH password w/ automatic salt
            $hashed = password_hash($userpassword, PASSWORD_DEFAULT);
            
            // Insert new user
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone_number, role, password_hash) VALUES (?, ?, ?, 'customer', ?)");
            
            if ($stmt) {
                $stmt->bind_param("ssss", $username, $email, $phone, $hashed);
                if ($stmt->execute()) {
                        // SUCCESS: Redirect to login
                        header("Location: login.php?registration=success");
                        exit;
                } else {
                    $error_message = "Registration failed: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Database error";
            }
        }
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register Page</title>
  <link type="text/css" rel="stylesheet" href="css/register.css"/>
</head>
<body>

  <div class="wrapper">
    <?php if(!empty($error_message)): ?>
        <p style="color: red; text-align: center; margin-bottom: 10px;"><?php echo $error_message; ?></p>
    <?php endif; ?>

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
        <input type="tel" id="phone" name="phone" required>
        <label>Enter your phone number</label>
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
  </body>
</html>
