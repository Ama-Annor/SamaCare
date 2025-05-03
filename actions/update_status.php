<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once('../db/db_connect.php');

try {
    if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
        throw new Exception('Missing required fields');
    }

    $appointmentId = intval($_POST['appointment_id']);
    $status = $_POST['status'];

    // Validate status
    $validStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (!in_array(strtolower($status), $validStatuses)) {
        throw new Exception('Invalid status value');
    }

    // Update appointment status
    $stmt = $conn->prepare("UPDATE appointments SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE appointment_id = ?");
    $stmt->bind_param("si", $status, $appointmentId);
    
    if (!$stmt->execute()) {
        throw new Exception("Failed to update status: " . $stmt->error);
    }

    echo json_encode([
        'success' => true,
        'message' => 'Status updated successfully'
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();
}