<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Change Password Page</title>
  <link type="text/css" rel="stylesheet" href="css/login.css"/>
</head>
<body>
<?php
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
        $email = $_POST['email'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (!$email || !$new_password || !$confirm_password) {
            echo "Please fill in all fields.";
            exit;
        }

        if ($new_password !== $confirm_password) {
            echo "New passwords do not match.";
            exit;
        }

        $stmt = $conn->prepare("SELECT email FROM users WHERE email = ?");
        if (!$stmt) {
            echo "Database query failed.";
            exit;
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $stmt->bind_result($stored_hash);
        if ($stmt->fetch()) {
            $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->close();
            $update = $conn->prepare("UPDATE users SET password_hash = ? WHERE email = ?");
            $update->bind_param("ss", $new_hashed, $email);
            if ($update->execute()) {
                ob_clean();
                echo "success";
                exit;
            } else {
                ob_clean();
                echo "Failed to update password.";
                exit;
            }

            $update->close();
        } else {
            ob_clean();
            echo "User not found.";
        }

        $stmt->close();
        $conn->close();
        ob_clean();
        exit;
    }
?>

<div class="wrapper">
    <form id="change-password-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
      <h2>Change Password</h2>
      <div class="input-field">
        <input type="email" name="email" required>
        <label>Enter your email</label>
      </div>
      <div class="input-field">
        <input type="password" name="new_password" required>
        <label>Enter your new password</label>
      </div>
      <div class="input-field">
        <input type="password" name="confirm_password" required>
        <label>Confirm new password</label>
      </div>
      <button type="submit">Change Password</button>
    </form>
  </div>
  <script>
    document.getElementById("change-password-form").addEventListener("submit", function (e) {
      e.preventDefault();

      const formData = new FormData(this);

      fetch("change_password.php", {
        method: "POST",
        body: formData
      })
      .then(res => res.text())
      .then(text => {
        text = text.trim();
        if (text === "success") {
          alert("Password changed successfully!");
          window.location.href = "login.php";
        } else {
          alert("Error: " + text);
        }
      })
      .catch(error => {
        console.error("Error:", error);
        alert("An error occurred.");
      });
    });
  </script>
</body>
</html>

