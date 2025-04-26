<?php
// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'samacare';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

//enable reporting
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8mb4");
