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

// Check if the statement was prepared correctly
if ($stmt === false) {
    die('MySQL prepare error: ' . $conn->error);
}

$stmt->bind_param("s", $username);

// Execute the query and check for errors
if (!$stmt->execute()) {
    die('Execute error: ' . $stmt->error);
}

$result = $stmt->get_result();

// Check if user was found
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die('No user found with that username');
}
$stmt->close();
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
                    <div class="profile-info">
                        <div class="profile-picture">
                            <img src="<?php echo $user['profile_picture'] ? $user['profile_picture'] : 'img/default-profile.png'; ?>" alt="Profile Picture" width="150" height="150">
                        </div><br>
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
