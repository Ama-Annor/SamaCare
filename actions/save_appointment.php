<?php
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 0);

session_start();
require_once('../db/db_connect.php');

// Check authentication
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

try {
    // Validate and sanitize inputs
    $appointmentId = filter_var($_POST['appointment_id'], FILTER_VALIDATE_INT);
    $patientId = filter_var($_POST['patient_id'], FILTER_VALIDATE_INT);
    $serviceId = filter_var($_POST['service_id'], FILTER_VALIDATE_INT);
    $locationId = filter_var($_POST['location_id'], FILTER_VALIDATE_INT);
    $appointmentDate = filter_var($_POST['appointment_date'], FILTER_SANITIZE_STRING);
    $startTime = filter_var($_POST['start_time'], FILTER_SANITIZE_STRING);
    $endTime = filter_var($_POST['end_time'], FILTER_SANITIZE_STRING);
    $status = filter_var($_POST['status'], FILTER_SANITIZE_STRING);
    $notes = isset($_POST['notes']) ? filter_var($_POST['notes'], FILTER_SANITIZE_STRING) : '';

    // Begin transaction
    $conn->begin_transaction();

    if ($appointmentId == 0) {
        // INSERT new appointment
        $query = "INSERT INTO appointments (
            patient_id, service_id, location_id, 
            appointment_date, start_time, end_time,
            status, notes, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iiisssss", 
            $patientId,
            $serviceId,
            $locationId,
            $appointmentDate,
            $startTime,
            $endTime,
            $status,
            $notes
        );
    } else {
        // UPDATE existing appointment
        $query = "UPDATE appointments SET 
            patient_id = ?,
            service_id = ?,
            location_id = ?,
            appointment_date = ?,
            start_time = ?,
            end_time = ?,
            status = ?,
            notes = ?,
            updated_at = NOW()
            WHERE appointment_id = ?";

        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iiisssssi", 
            $patientId,
            $serviceId,
            $locationId,
            $appointmentDate,
            $startTime,
            $endTime,
            $status,
            $notes,
            $appointmentId
        );
    }

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Get the appointment ID (for new insertions)
    if ($appointmentId == 0) {
        $appointmentId = $conn->insert_id;
    }

    // Get updated appointment data
    $selectQuery = "SELECT 
        a.*,
        CONCAT(up.first_name, ' ', up.last_name) as patient_name,
        up.first_name as patient_first_name,
        up.last_name as patient_last_name,
        s.name as service_name,
        l.name as location_name
        FROM appointments a
        LEFT JOIN patients p ON a.patient_id = p.patient_id
        LEFT JOIN users up ON p.user_id = up.user_id
        LEFT JOIN services s ON a.service_id = s.service_id
        LEFT JOIN locations l ON a.location_id = l.location_id
        WHERE a.appointment_id = ?";

    $stmt = $conn->prepare($selectQuery);
    if (!$stmt) {
        throw new Exception("Select prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $appointmentId);
    
    if (!$stmt->execute()) {
        throw new Exception("Select execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();

    if (!$appointment) {
        throw new Exception("Could not find updated appointment");
    }

    // Commit transaction
    $conn->commit();

    // Send success response
    echo json_encode([
        'success' => true,
        'message' => 'Appointment ' . ($appointmentId == 0 ? 'created' : 'updated') . ' successfully',
        'appointment' => $appointment
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($conn) && $conn->ping()) {
        $conn->rollback();
    }

    error_log("Save appointment error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}