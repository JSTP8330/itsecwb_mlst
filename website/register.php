<?php 
// register.php
include("config.php");

$error_message = ""; // Initialize variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $userpassword = $_POST['password'] ?? '';
    $confirmpassword = $_POST['confirmpassword'] ?? '';
    
    // --- INPUT VALIDATION ---
    if (empty($username) || empty($email) || empty($phone) || empty($userpassword) || empty($confirmpassword)) {
        echo "error: All fields are required";
        $conn->close();
        exit;
    } elseif ($userpassword !== $confirmpassword) {
        echo "error: Passwords do not match";
        $conn->close();
        exit;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "error: Invalid email format";
        $conn->close();
        exit;
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        echo "error: Invalid phone number (must be 10-15 digits)";
        $conn->close();
        exit;
    } else {
        // --- PROFILE PICTURE VALIDATION ---
        $profile_picture = null;
        $upload_error = false;
        
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_picture'];
            
            if ($file['error'] !== UPLOAD_ERR_OK) {
                echo "error: Error uploading file";
                $conn->close();
                exit;
            } else {
                $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed_extensions = ['bmp', 'jpeg', 'jpg', 'png'];
              
                if (!in_array($file_extension, $allowed_extensions)) {
                    echo "error: Invalid file type. Only BMP, JPEG, JPG, and PNG are allowed";
                    $conn->close();
                    exit;
                } else {
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $file['tmp_name']);
                    finfo_close($finfo);
                    
                    $allowed_mime_types = ['image/bmp', 'image/x-ms-bmp', 'image/jpeg', 'image/jpg', 'image/png'];
                    
                    if (!in_array($mime_type, $allowed_mime_types)) {
                        echo "error: Invalid file type. Only BMP, JPEG, JPG, and PNG are allowed";
                        $conn->close();
                        exit;
                    } else {
                        // check file size (5MB limit)
                        $max_file_size = 5 * 1024 * 1024; // 5MB in bytes
                        if ($file['size'] > $max_file_size) {
                            echo "error: File size too large. Maximum size is 5MB";
                            $conn->close();
                            exit;
                        } else {
                            $unique_filename = uniqid('profile_', true) . '.' . $file_extension;
                            $upload_directory = 'uploads/profile_pictures/';
                            
                            if (!file_exists($upload_directory)) {
                                mkdir($upload_directory, 0755, true);
                            }
                            
                            $upload_path = $upload_directory . $unique_filename;
                            
                            if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                                $profile_picture = $upload_path;
                            } else {
                                echo "error: Failed to upload profile picture";
                                $conn->close();
                                exit;
                            }
                        }
                    }
                }
            }
        }
        
        // Proceed with registration only if no upload errors
        // Check if username/email exists
        $check = $conn->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $check->bind_param("ss", $username, $email);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows > 0) {
            $check->close();
            
            if ($profile_picture && file_exists($profile_picture)) {
                unlink($profile_picture);
            }
            
            echo "error: Username or Email already taken";
            $conn->close();
            exit;
        } else {
            $check->close();
            
            $hashed = hash('sha256', $userpassword);
          
            $stmt = $conn->prepare("INSERT INTO users (username, email, phone_number, role, password_hash, profile_picture) VALUES (?, ?, ?, 'customer', ?, ?)");
            
            if ($stmt) {
                $stmt->bind_param("sssss", $username, $email, $phone, $hashed, $profile_picture);
                if ($stmt->execute()) {
                    $stmt->close();
                    $conn->close();
                    // SUCCESS: Return success response for AJAX
                    echo "success";
                    exit;
                } else {
                    if ($profile_picture && file_exists($profile_picture)) {
                        unlink($profile_picture);
                    }
                    
                    $stmt->close();
                    $conn->close();
                    echo "error: Registration failed - " . $stmt->error;
                    exit;
                }
            } else {
                if ($profile_picture && file_exists($profile_picture)) {
                    unlink($profile_picture);
                }
                
                $conn->close();
                echo "error: Database error";
                exit;
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
        <p style="color: red; text-align: center; margin-bottom: 10px;"><?php echo htmlspecialchars($error_message); ?></p>
    <?php endif; ?>

    <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
      <h2>Create An Account</h2>
      
      <div class="form-container">
        <div class="profile-upload-section">
          <div class="profile-preview">
            <img id="preview-image" src="" alt="Profile Preview">
            <span class="placeholder">👤</span>
          </div>
          
          <div class="file-input-wrapper">
            <label for="profile_picture" class="custom-file-upload">
              Choose Photo
            </label>
            <input type="file" id="profile_picture" name="profile_picture" accept=".bmp,.jpeg,.jpg,.png">
            <p class="file-info">BMP, JPEG, JPG, PNG<br>Max 5MB</p>
          </div>
        </div>
    
        <div class="form-fields-section">
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
        </div>
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