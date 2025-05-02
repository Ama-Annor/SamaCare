<?php
// Start session
session_start();
// Database connection
require_once '../db/db_connect.php';

// Check if user has right access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    // Take user back to login
    header('Location: ../view/login.php');
    exit();
}

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../view/doctor_dashboard.php');
    exit();
}

// Check if specialty_id was provided
if (!isset($_POST['specialty_id']) || empty($_POST['specialty_id'])) {
    $_SESSION['error'] = "Please select a specialty.";
    header('Location: ../view/doctor_dashboard.php');
    exit();
}



// Get doctor_id and specialty_id
$doctor_id = $_POST['doctor_id'];
$specialty_id = $_POST['specialty_id'];

// Verify doctor_id belongs to current user
$user_id = $_SESSION['user_id'];
$verify_query = "SELECT doctor_id FROM doctors WHERE user_id = ? AND doctor_id = ?";
$stmt = $conn->prepare($verify_query);
$stmt->bind_param("ii", $user_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // Doctor ID doesn't match this user
    $_SESSION['error'] = "Unauthorized access.";
    header('Location: ../view/doctor_dashboard.php');
    exit();
}

// Update doctor's specialty
$update_query = "UPDATE doctors SET specialty_id = ? WHERE doctor_id = ?";
$stmt = $conn->prepare($update_query);
$stmt->bind_param("ii", $specialty_id, $doctor_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Your specialty has been updated successfully.";
} else {
    $_SESSION['error'] = "Failed to update specialty. Please try again.";
}

// Close connection
$stmt->close();
$conn->close();

// Redirect back to dashboard
header('Location: ../view/doctor_dashboard.php');
exit();
?>