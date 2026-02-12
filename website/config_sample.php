<?php
// Set passwords to be hashed
$password_john = 'password123';
$password_admin = 'adminpass';
$password_staff = 'staffpass';

// Hash passwords using PASSWORD_DEFAULT
$hashed_password_john = password_hash($password_john, PASSWORD_DEFAULT);
$hashed_password_admin = password_hash($password_admin, PASSWORD_DEFAULT);
$hashed_password_staff = password_hash($password_staff, PASSWORD_DEFAULT);

echo "<pre>";
echo "Hashed password for john_doe: $hashed_password_john\n";
echo "Hashed password for admin_user: $hashed_password_admin\n";
echo "Hashed password for staff_user: $hashed_password_staff\n";
echo "</pre>";

// Database connection details
$servername = "localhost";  // Change to your database host if necessary
$username = "root";         // Database username (adjust as needed)
$password = "";             // Database password (adjust as needed)
$dbname = "online_store";   // Database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("<strong>Connection failed:</strong> " . $conn->connect_error);
}

// SQL query to insert the hashed passwords into the users table
$sql = "INSERT INTO users (username, password_hash, role, email, phone_number, profile_picture, created_at) 
        VALUES 
        ('john_doe', '$hashed_password_john', 'customer', 'john@example.com', '09971234567', 'uploads/profile_pictures/profile_john.jpg', '2025-07-20 17:18:35'),
        ('admin_user', '$hashed_password_admin', 'admin', 'admin@example.com', '09211239876', 'uploads/profile_pictures/profile_admin.jpg', '2025-07-20 17:18:35'),
        ('staff_user', '$hashed_password_staff', 'staff', 'staff@example.com', '09875215825', 'uploads/profile_pictures/profile_staff.jpg', '2025-07-20 17:18:35')";

// Attempt to execute the query
if ($conn->query($sql) === TRUE) {
    echo "<strong>New records created successfully!</strong>";
} else {
    // Handle unique constraint errors (e.g., duplicate username)
    if ($conn->errno == 1062) {
        echo "<strong>User already exists.</strong>";
    } else {
        echo "<strong>Error:</strong> " . $conn->error . "<br><strong>SQL:</strong> " . $sql;
    }
}

// Close connection
$conn->close();
?>
