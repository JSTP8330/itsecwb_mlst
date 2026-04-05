<?php
// login.php
define('ITSEC_SESSION_PUBLIC', true);
require_once __DIR__ . '/session.php';

include("config.php");
require_once __DIR__ . '/includes/audit_log.php';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $ip_address = $_SERVER['REMOTE_ADDR'];


    // --- BRUTE FORCE PROTECTION START ---
    // Check failed attempts in the last 15 minutes
    $check_stmt = $conn->prepare("SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > (NOW() - INTERVAL 15 MINUTE)");
    $check_stmt->bind_param("s", $ip_address);
    $check_stmt->execute();
    $check_stmt->bind_result($failed_attempts);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($failed_attempts >= 5) {
        // Block request
        $error_message = "Too many failed login attempts. Please try again in 15 minutes.";
        itsec_app_log('auth', 'login_blocked', [
            'ip' => $ip_address,
            'username' => (string) ($_POST['username'] ?? ''),
        ]);
    } else {
        // --- LOGIN ---
        if (!$username || !$password) {
            $error_message = "Please fill in all fields";
        } else {
            // Get user password hash
            $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($user_id, $stored_hash, $role);
                
                if ($stmt->fetch()) {
                    $auth = app_password_login_check($password, $stored_hash);
                    if ($auth['ok']) {
                        // SUCCESS: Login (new session id mitigates session fixation)
                        session_regenerate_id(true);
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['user_id'] = $user_id;
                        // Phase 2: session timeout anchors (see session.php)
                        $t = time();
                        $_SESSION['sess_started'] = $t;
                        $_SESSION['last_activity'] = $t;

                        if ($auth['upgrade_hash'] !== null) {
                            $up = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
                            $up->bind_param("si", $auth['upgrade_hash'], $user_id);
                            $up->execute();
                            $up->close();
                        }

                        // Optional: Clear failed attempts on success
                        $stmt->close(); // Close previous statement first
                        $clear_stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
                        $clear_stmt->bind_param("s", $ip_address);
                        $clear_stmt->execute();
                        $clear_stmt->close();

                        itsec_audit_log($conn, 'users', 'user_login', (int) $user_id, $username);
                        itsec_app_log('auth', 'login_success', [
                            'user_id' => (int) $user_id,
                            'username' => $username,
                            'ip' => $ip_address,
                        ]);

                        header("Location: index.php"); // Redirect to home/dashboard
                        exit;
                    } else {
                        // FAILURE: Incorrect password
                        $error_message = "Incorrect username or password";
                        // Log the failure
                        // Note: We do this AFTER fetching to avoid "commands out of sync"
                    }
                } else {
                    // FAILURE: User not found
                    $error_message = "Incorrect username or password";
                }
                $stmt->close();
            }
        }

        // If we set an error message (login failed), verify it wasn't a "Too many attempts" error first
        if (isset($error_message) && $failed_attempts < 5) {
            // Insert failed attempt into DB
            $log_stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
            $log_stmt->bind_param("ss", $ip_address, $username);
            $log_stmt->execute();
            $log_stmt->close();
            if (isset($error_message) && $error_message === 'Incorrect username or password') {
                itsec_app_log('auth', 'login_failed', [
                    'ip' => $ip_address,
                    'username' => $username,
                ]);
            }
        }
    }
    // --- BRUTE FORCE PROTECTION END ---
}
?>

<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Page</title>
  <link type="text/css" rel="stylesheet" href="css/login.css"/>
</head>
<body>

  <div class="wrapper">
    <?php if (!empty($error_message)): ?>
        <div style="color: red; text-align: center; margin-bottom: 10px;">
            <?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?>
        </div>
    <?php endif;?>
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
        <a href="change_password.php">Forgot password?</a>
      </div>
      <button type="submit">Log In</button>
      <div class="register">
        <p>Don't have an account? <a href="register.php">Register</a></p>
      </div>
    </form>
  </div>
</body>
</html>
