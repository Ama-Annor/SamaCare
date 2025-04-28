<?php
// Start session for user authentication
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once('../db/db_connect.php');

// Enable error logging
error_log("process_appointment.php called");

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    error_log("Unauthorized access attempt");
    exit();
}

// Get form data with validation
$appointmentId = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
$patientId = isset($_POST['patient_id']) ? intval($_POST['patient_id']) : 0;
$doctorId = isset($_POST['doctor_id']) ? intval($_POST['doctor_id']) : 0;
$serviceId = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$locationId = isset($_POST['location_id']) ? intval($_POST['location_id']) : 0;
$appointmentDate = isset($_POST['appointment_date']) ? $_POST['appointment_date'] : '';
$appointmentTime = isset($_POST['appointment_time']) ? $_POST['appointment_time'] : '';
$duration = isset($_POST['duration']) ? intval($_POST['duration']) : 30; // Default 30 minutes
$status = isset($_POST['status']) ? $_POST['status'] : 'pending';
$notes = isset($_POST['notes']) ? $_POST['notes'] : '';

// Log the received data
error_log("Received data: id=$appointmentId, patient=$patientId, doctor=$doctorId, service=$serviceId, location=$locationId, date=$appointmentDate, time=$appointmentTime, duration=$duration, status=$status");

// Validate inputs
if ($patientId <= 0 || $doctorId <= 0 || $serviceId <= 0 || $locationId <= 0 ||
    empty($appointmentDate) || empty($appointmentTime) || $duration <= 0) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'All required fields must be filled']);
    error_log("Validation failed: Missing required fields");
    exit();
}

// Validate date format (YYYY-MM-DD)
if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $appointmentDate)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid date format']);
    error_log("Invalid date format: $appointmentDate");
    exit();
}

// Validate time format (HH:MM)
if (!preg_match("/^\d{2}:\d{2}$/", $appointmentTime)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid time format']);
    error_log("Invalid time format: $appointmentTime");
    exit();
}

// Verify status is one of the allowed values
$allowedStatuses = ['pending', 'confirmed', 'completed', 'cancelled'];
if (!in_array($status, $allowedStatuses)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid status value']);
    error_log("Invalid status value: $status");
    exit();
}

// Calculate end time based on start time and duration
$startTime = $appointmentTime;
$endTime = date('H:i:s', strtotime("$appointmentTime + $duration minutes"));

// Check for doctor availability
$isAvailable = checkDoctorAvailability($conn, $doctorId, $appointmentDate, $startTime, $endTime, $appointmentId);
if (!$isAvailable) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Doctor is not available at the selected time. Please choose a different time or doctor.']);
    error_log("Doctor $doctorId not available on $appointmentDate at $startTime");
    exit();
}

// Begin transaction
$conn->begin_transaction();

try {
    if ($appointmentId > 0) {
        // Update existing appointment
        $sql = "UPDATE appointments SET 
                patient_id = ?, 
                doctor_id = ?, 
                service_id = ?, 
                location_id = ?, 
                appointment_date = ?, 
                start_time = ?, 
                end_time = ?, 
                status = ?, 
                notes = ?, 
                updated_at = NOW()
                WHERE appointment_id = ?";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iiissssssi", $patientId, $doctorId, $serviceId, $locationId,
            $appointmentDate, $startTime, $endTime, $status, $notes, $appointmentId);

        $executeResult = $stmt->execute();
        if (!$executeResult) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        if ($stmt->affected_rows < 1) {
            error_log("No rows updated for appointment ID: $appointmentId");

            // Check if appointment exists
            $checkSql = "SELECT appointment_id FROM appointments WHERE appointment_id = ?";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bind_param("i", $appointmentId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult->num_rows == 0) {
                throw new Exception("Appointment not found for update");
            }

            $checkStmt->close();
        }

        $message = 'Appointment updated successfully';
        $logMessage = "Updated appointment #$appointmentId";
        error_log($logMessage);
    } else {
        // Create new appointment
        $sql = "INSERT INTO appointments 
                (patient_id, doctor_id, service_id, location_id, appointment_date, start_time, end_time, status, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }

        $stmt->bind_param("iiisssss", $patientId, $doctorId, $serviceId, $locationId,
            $appointmentDate, $startTime, $endTime, $status, $notes);

        $executeResult = $stmt->execute();
        if (!$executeResult) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $appointmentId = $conn->insert_id;
        if (!$appointmentId) {
            throw new Exception("Failed to get insert ID");
        }

        $message = 'Appointment scheduled successfully';
        $logMessage = "Created new appointment #$appointmentId";
        error_log($logMessage);
    }

    // Log the activity
    $activitySql = "INSERT INTO user_activities (user_id, activity_type, related_id, description) 
                   VALUES (?, ?, ?, ?)";
    $activityStmt = $conn->prepare($activitySql);
    $activityType = $appointmentId > 0 ? 'appointment_update' : 'appointment_create';

    $activityStmt->bind_param("isis", $_SESSION['user_id'], $activityType, $appointmentId, $logMessage);
    $activityStmt->execute();
    $activityStmt->close();

    // Send appointment notification to patient and doctor if status is confirmed
    if ($status === 'confirmed') {
        sendAppointmentNotification($conn, $appointmentId);
    }

    // Commit transaction
    $conn->commit();

    // Return success JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => $message, 'appointment_id' => $appointmentId]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    // Return error JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Error processing appointment: ' . $e->getMessage()]);
    error_log("Error processing appointment: " . $e->getMessage());
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
}

$conn->close();

// Function to check doctor availability
// Function to check doctor availability
function checkDoctorAvailability($conn, $doctorId, $appointmentDate, $startTime, $endTime, $excludeAppointmentId = 0) {
    // Check if doctor has existing appointments at the same time (excluding current appointment being edited)
    $sql = "SELECT COUNT(*) as count FROM appointments 
            WHERE doctor_id = ? 
            AND appointment_date = ? 
            AND ((start_time <= ? AND end_time > ?) OR (start_time < ? AND end_time >= ?))
            AND status != 'cancelled'";

    // If editing an existing appointment, exclude it from the check
    if ($excludeAppointmentId > 0) {
        $sql .= " AND appointment_id != ?";
    }

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed in checkDoctorAvailability: " . $conn->error);
        return false;
    }

    if ($excludeAppointmentId > 0) {
        $stmt->bind_param("isssssi", $doctorId, $appointmentDate, $endTime, $startTime, $startTime, $endTime, $excludeAppointmentId);
    } else {
        $stmt->bind_param("isssss", $doctorId, $appointmentDate, $endTime, $startTime, $startTime, $endTime);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    $stmt->close();

    // If any overlapping appointments exist, doctor is not available
    if ($data['count'] > 0) {
        error_log("Doctor $doctorId has overlapping appointments on $appointmentDate at $startTime");
        return false;
    }

    // For now, return true to indicate the doctor is available
    return true;
}

// Function to send appointment notification
function sendAppointmentNotification($conn, $appointmentId) {
    // Get appointment details
    $sql = "SELECT a.*, 
            u_patient.user_id as patient_user_id, u_patient.first_name as patient_first_name, 
            u_patient.last_name as patient_last_name,
            u_doctor.user_id as doctor_user_id, u_doctor.first_name as doctor_first_name, 
            u_doctor.last_name as doctor_last_name,
            s.name as service_name
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_id
            JOIN users u_patient ON p.user_id = u_patient.user_id
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u_doctor ON d.user_id = u_doctor.user_id
            JOIN services s ON a.service_id = s.service_id
            WHERE a.appointment_id = ?";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed in sendAppointmentNotification: " . $conn->error);
        return;
    }

    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $appointment = $result->fetch_assoc();

        // Format date and time for notification
        $appointmentDate = date('l, F j, Y', strtotime($appointment['appointment_date']));
        $appointmentTime = date('g:i A', strtotime($appointment['start_time']));

        // Create notification for patient
        $patientUserId = $appointment['patient_user_id'];
        $patientName = $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'];
        $doctorName = 'Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name'];

        $patientNotificationTitle = 'Appointment Confirmed';
        $patientNotificationDesc = "Your appointment for {$appointment['service_name']} with $doctorName on $appointmentDate at $appointmentTime has been confirmed.";

        createNotification($conn, $patientUserId, $patientNotificationTitle, $patientNotificationDesc, 'bx-calendar-check', 'green', 'white', "appointments.php?id=$appointmentId");

        // Create notification for doctor
        $doctorUserId = $appointment['doctor_user_id'];

        $doctorNotificationTitle = 'New Appointment';
        $doctorNotificationDesc = "Appointment with $patientName for {$appointment['service_name']} on $appointmentDate at $appointmentTime has been scheduled.";

        createNotification($conn, $doctorUserId, $doctorNotificationTitle, $doctorNotificationDesc, 'bx-calendar-plus', 'blue', 'white', "doctor_appointments.php?id=$appointmentId");
    }

    $stmt->close();
}

// Function to create a notification
function createNotification($conn, $userId, $title, $description, $icon, $colorBg, $colorIcon, $link) {
    $sql = "INSERT INTO notifications (user_id, title, description, icon, color_bg, color_icon, link) 
            VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log("Prepare failed in createNotification: " . $conn->error);
        return;
    }

    $stmt->bind_param("issssss", $userId, $title, $description, $icon, $colorBg, $colorIcon, $link);
    $result = $stmt->execute();

    if (!$result) {
        error_log("Failed to create notification: " . $stmt->error);
    }

    $stmt->close();
}
?>