<?php
// Start session for user authentication
session_start();

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Get the appointment ID parameter
$appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointmentId <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid appointment ID']);
    exit();
}

// Prepare SQL query to get appointment data
$sql = "SELECT * FROM appointments WHERE appointment_id = ?";

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $appointment = $result->fetch_assoc();

    // Format date for form
    $appointment['appointment_date'] = date('Y-m-d', strtotime($appointment['appointment_date']));
    $appointment['start_time'] = date('H:i', strtotime($appointment['start_time']));

    // Calculate duration in minutes (difference between start and end time)
    $startTime = strtotime($appointment['start_time']);
    $endTime = strtotime($appointment['end_time']);
    $durationSeconds = $endTime - $startTime;
    $durationMinutes = round($durationSeconds / 60);

    $appointment['duration'] = $durationMinutes;

    // Return appointment data as JSON
    header('Content-Type: application/json');
    echo json_encode($appointment);
} else {
    // Appointment not found
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Appointment not found']);
}

$stmt->close();
$conn->close();
?>