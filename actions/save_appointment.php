<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

require_once('../db/db_connect.php');

// Get POST data
$appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : null;
$patientId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : null;
$doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : null;
$serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : null;
$locationId = isset($_POST['location_id']) ? intval($_POST['location_id']) : null;
$appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : null;
$startTime = isset($_POST['start_time']) ? $_POST['start_time'] : null;
$endTime = isset($_POST['end_time']) ? $_POST['end_time'] : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
$status = isset($_POST['status']) ? $_POST['status'] : 'scheduled';

try {
    // Input validation
    if (!$patientId || !$doctorId || !$serviceId || !$locationId || !$appointmentDate || !$startTime || !$endTime) {
        throw new Exception('Missing required fields');
    }

    // Validate date format
    if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $appointmentDate)) {
        throw new Exception('Invalid date format');
    }

    // Validate time format
    if (!preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $startTime) || 
        !preg_match("/^(?:2[0-3]|[01][0-9]):[0-5][0-9]$/", $endTime)) {
        throw new Exception('Invalid time format');
    }

    // Begin transaction
    $conn->begin_transaction();

    // Check for scheduling conflicts
    $conflictSql = "SELECT COUNT(*) as conflict_count FROM appointments 
                    WHERE doctor_id = ? 
                    AND appointment_date = ? 
                    AND ((start_time <= ? AND end_time > ?) 
                    OR (start_time < ? AND end_time >= ?))
                    AND status != 'cancelled'";
    
    if ($appointmentId) {
        $conflictSql .= " AND appointment_id != ?";
    }

    $conflictStmt = $conn->prepare($conflictSql);
    
    if ($appointmentId) {
        $conflictStmt->bind_param("issssssi", $doctorId, $appointmentDate, $endTime, $startTime, $endTime, $startTime, $appointmentId);
    } else {
        $conflictStmt->bind_param("isssss", $doctorId, $appointmentDate, $endTime, $startTime, $endTime, $startTime);
    }
    
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result()->fetch_assoc();
    
    if ($conflictResult['conflict_count'] > 0) {
        throw new Exception('Time slot is not available');
    }

    if ($appointmentId) {
        // Update existing appointment
        $sql = "UPDATE appointments SET 
                patient_id = ?, 
                doctor_id = ?,
                service_id = ?,
                location_id = ?,
                appointment_date = ?,
                start_time = ?,
                end_time = ?,
                notes = ?,
                status = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE appointment_id = ?";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiisssssi", $patientId, $doctorId, $serviceId, $locationId, 
                         $appointmentDate, $startTime, $endTime, $notes, $status, $appointmentId);
    } else {
        // Insert new appointment
        $sql = "INSERT INTO appointments (patient_id, doctor_id, service_id, location_id, 
                appointment_date, start_time, end_time, notes, status, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiiiissss", $patientId, $doctorId, $serviceId, $locationId, 
                         $appointmentDate, $startTime, $endTime, $notes, $status);
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to save appointment: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();



    // After successful save, fetch the complete appointment data
    // After successful save, fetch the complete appointment data
    $appointmentSql = "SELECT 
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

    $fetchStmt = $conn->prepare($appointmentSql);
    $fetchId = $appointmentId ?? $stmt->insert_id;
    $fetchStmt->bind_param("i", $fetchId);
    $fetchStmt->execute();
    $appointmentData = $fetchStmt->get_result()->fetch_assoc();
    $fetchStmt->close();

    // Send single JSON response with all data
    echo json_encode([
        'success' => true,
        'message' => $appointmentId ? 'Appointment updated successfully' : 'Appointment scheduled successfully',
        'appointment' => $appointmentData
    ]);
} catch (Exception $e) {
    // Rollback transaction on error
    if ($conn->connect_errno != 0) {
        $conn->rollback();
    }
    
    error_log($e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);

} finally {
    // Clean up
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conflictStmt)) {
        $conflictStmt->close();
    }
    $conn->close();
}