<?php
// profile.php
include 'session.php'; // Ensure session is started to access user data
include 'config.php';

// Optional: Redirect to login if not logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$currentPage = "profile";

// Fetch user profile data from database
$username = $_SESSION['username'];

// Prepare and execute SQL query
$query = "SELECT username, role, email, phone_number, profile_picture, created_at FROM users WHERE username = ?";
$stmt = $conn->prepare($query);

// Check if the statement was prepared correctly (Phase 2: generic message when APP_DEBUG off)
if ($stmt === false) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('MySQL prepare error: ' . $conn->error);
    }
    error_log('Profile prepare error: ' . $conn->error);
    die('Unable to load profile.');
}

$stmt->bind_param("s", $username);

if (!$stmt->execute()) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('Execute error: ' . $stmt->error);
    }
    error_log('Profile execute error: ' . $stmt->error);
    die('Unable to load profile.');
}

$result = $stmt->get_result();

// Check if user was found
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die('No user found with that username');
    }
    die('Unable to load profile.');
}
$stmt->close();

$picture_error = '';
$picture_success = '';

/**
 * Remove a previous upload only if it lives under uploads/profile_pictures/ (no path traversal).
 */
function itsec_delete_stored_profile_picture(?string $stored_path): void {
    if ($stored_path === null || $stored_path === '') {
        return;
    }
    if (strpos($stored_path, '..') !== false) {
        return;
    }
    if (strpos($stored_path, 'uploads/profile_pictures/') !== 0) {
        return;
    }
    $full = __DIR__ . '/' . $stored_path;
    if (is_file($full)) {
        @unlink($full);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_profile_picture'])) {
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] === UPLOAD_ERR_NO_FILE) {
        $picture_error = 'Please choose an image file.';
    } else {
        $file = $_FILES['profile_picture'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $picture_error = 'Error uploading file.';
        } else {
            $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowed_extensions = ['bmp', 'jpeg', 'jpg', 'png'];

            if (!in_array($file_extension, $allowed_extensions, true)) {
                $picture_error = 'Invalid file type. Only BMP, JPEG, JPG, and PNG are allowed.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                $allowed_mime_types = ['image/bmp', 'image/x-ms-bmp', 'image/jpeg', 'image/jpg', 'image/png'];

                if (!in_array($mime_type, $allowed_mime_types, true)) {
                    $picture_error = 'Invalid file type. Only BMP, JPEG, JPG, and PNG are allowed.';
                } else {
                    $max_file_size = 5 * 1024 * 1024;
                    if ($file['size'] > $max_file_size) {
                        $picture_error = 'File size too large. Maximum size is 5MB.';
                    } else {
                        $unique_filename = uniqid('profile_', true) . '.' . $file_extension;
                        $upload_directory = 'uploads/profile_pictures/';

                        if (!file_exists($upload_directory)) {
                            mkdir($upload_directory, 0755, true);
                        }

                        $upload_path = $upload_directory . $unique_filename;

                        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                            $picture_error = 'Failed to upload profile picture.';
                        } else {
                            $old_path = $user['profile_picture'];
                            $update = $conn->prepare('UPDATE users SET profile_picture = ? WHERE username = ?');
                            if ($update === false) {
                                itsec_delete_stored_profile_picture($upload_path);
                                $picture_error = 'Database error.';
                            } else {
                                $update->bind_param('ss', $upload_path, $username);
                                if (!$update->execute()) {
                                    itsec_delete_stored_profile_picture($upload_path);
                                    $picture_error = 'Could not update profile.';
                                } else {
                                    itsec_delete_stored_profile_picture($old_path);
                                    $user['profile_picture'] = $upload_path;
                                    $picture_success = 'Profile picture updated.';
                                }
                                $update->close();
                            }
                        }
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>TechShop - Profile</title>
    <link href="https://fonts.googleapis.com/css?family=Montserrat:400,500,700" rel="stylesheet">
    <link type="text/css" rel="stylesheet" href="css/bootstrap.min.css"/>
    <link type="text/css" rel="stylesheet" href="css/slick.css"/>
    <link type="text/css" rel="stylesheet" href="css/slick-theme.css"/>
    <link type="text/css" rel="stylesheet" href="css/nouislider.min.css"/>
    <link rel="stylesheet" href="css/font-awesome.min.css">
    <link type="text/css" rel="stylesheet" href="css/style.css"/>
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>
<body>
    <?php $currentPage = "Profile"; ?>

    <!-- HEADER --> 
    <?php include 'header.php'; ?>
    <!-- /HEADER -->

    <!-- NAVIGATION -->
    <?php include 'navigation.php'; ?>
    <!-- /NAVIGATION -->

    <!-- PROFILE -->

    <div class="section">
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h3 class="title">My Profile</h3><br>
                    <?php if ($picture_success !== ''): ?>
                        <p class="text-success"><?php echo htmlspecialchars($picture_success); ?></p>
                    <?php endif; ?>
                    <?php if ($picture_error !== ''): ?>
                        <p class="text-danger"><?php echo htmlspecialchars($picture_error); ?></p>
                    <?php endif; ?>
                    <div class="profile-info">
                        <div class="profile-picture">
                            <?php
                            $pic_src = !empty($user['profile_picture']) ? $user['profile_picture'] : 'img/default-profile.png';
                            ?>
                            <img src="<?php echo htmlspecialchars($pic_src); ?>" alt="Profile Picture" width="150" height="150">
                        </div>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF'] ?? ''); ?>" enctype="multipart/form-data" class="profile-picture-form" style="margin: 15px 0;">
                            <input type="hidden" name="change_profile_picture" value="1">
                            <label for="profile_picture">Change profile picture</label><br>
                            <input type="file" id="profile_picture" name="profile_picture" accept=".bmp,.jpeg,.jpg,.png" required>
                            <p class="help-block" style="font-size: 12px; color: #8d99ae;">BMP, JPEG, JPG, or PNG. Max 5MB.</p>
                            <button type="submit" class="primary-btn">Upload</button>
                        </form>
                        <br>
                        <div class="profile-details">
                            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($user['phone_number']); ?></p>
                            <p><strong>Role:</strong> <?php echo htmlspecialchars($user['role']); ?></p>
                            <p><strong>Member Since:</strong> <?php echo date("F j, Y", strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <br><br>

    <!-- FOOTER --> 
    <?php include 'footer.php'; ?>
    <!-- /FOOTER --> 

    <!-- jQuery Plugins -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/slick.min.js"></script>
    <script src="js/nouislider.min.js"></script>
    <script src="js/jquery.zoom.min.js"></script>
    <script src="js/main.js"></script>
</body>
</html>
<?php $conn->close(); ?>
