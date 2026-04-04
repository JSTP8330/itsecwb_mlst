<?php
require_once __DIR__ . '/session.php';

$servername = "localhost";
$username = "root";
$password = "";
$database = "online_store";

$conn = new mysqli($servername, $username, $password, $database);

// Phase 2: hide DB details when APP_DEBUG is false (set env APP_DEBUG=0 for production-style)
if ($conn->connect_error) {
    if (defined('APP_DEBUG') && APP_DEBUG) {
        die("Connection failed: " . $conn->connect_error);
    }
    error_log("DB connection failed: " . $conn->connect_error);
    die("Database unavailable. Please try again later.");
}
?>
