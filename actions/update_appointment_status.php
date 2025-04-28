<?php
// Start session for user authentication
session_start();

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Get the posted data
$appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$status = isset($_POST['status']) ? $_POST['status'] : '';

// For debugging
error_log("Received: appointmentId=$appointmentId, action=$action, status=$status");

// If action is specified but status is not, map the action to a status
if (!empty($action) && empty($status)) {
    switch ($action) {
        case 'complete':
            $status = 'completed';
            break;
        case 'cancel':
            $status = 'cancelled';
            break;
        case 'confirm':
            $status = 'confirmed';
            break;
        default:
            $status = $action; // Use action as status
    }

    error_log("Mapped action to status: $action -> $status");
}

// Validate input
if ($appointmentId <= 0 || empty($status)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid appointment ID or status']);
    error_log("Validation failed: appointmentId=$appointmentId, status=$status");
    exit();
}

// Verify status is one of the allowed values
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    error_log("Invalid status: $status");
    exit();
}

// Prepare SQL query to update appointment status
$sql = "UPDATE appointments SET status = ?, updated_at = NOW() WHERE appointment_id = ?";

try {
    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("si", $status, $appointmentId);

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Check if any rows were affected
    if ($stmt->affected_rows < 1) {
        error_log("No rows affected: appointmentId=$appointmentId, status=$status");

        // Check if appointment exists
        $checkSql = "SELECT appointment_id FROM appointments WHERE appointment_id = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("i", $appointmentId);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();

        if ($checkResult->num_rows == 0) {
            throw new Exception("Appointment not found");
        } else {
            // Status might be already the same
            error_log("Appointment exists but no changes made. It might already have status: $status");
        }

        $checkStmt->close();
    }

    // Generate appropriate success message based on the status
    $message = 'Appointment status updated successfully';

    switch ($status) {
        case 'completed':
            $message = 'Appointment marked as completed';
            break;
        case 'cancelled':
            $message = 'Appointment has been cancelled';
            break;
        case 'confirmed':
            $message = 'Appointment has been confirmed';
            break;
        case 'pending':
            $message = 'Appointment set to pending status';
            break;
    }

    // Log the status change activity
    $activitySql = "INSERT INTO user_activities (user_id, activity_type, related_id, description) 
                    VALUES (?, 'appointment_status_change', ?, ?)";
    $activityDesc = "Changed appointment status to $status";

    $activityStmt = $conn->prepare($activitySql);
    $activityStmt->bind_param("iis", $_SESSION['user_id'], $appointmentId, $activityDesc);
    $activityStmt->execute();
    $activityStmt->close();

    // Return success JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message]);
    error_log("Success: $message");

} catch (Exception $e) {
    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Failed to update appointment status: ' . $e->getMessage()]);
    error_log("Error: " . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

$conn->close();
?>