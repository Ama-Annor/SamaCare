<?php
session_start();
header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}

// Database connection
require_once('../db/db_connect.php');

// Get the appointment ID parameter
$appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointmentId <= 0) {
    echo json_encode(['error' => 'Invalid appointment ID']);
    exit();
}

try {
    // Prepare SQL query to get appointment data with joins
    $sql = "SELECT 
        a.*,
    CONCAT(up.first_name, ' ', up.last_name) as patient_name,
    up.first_name as patient_first_name,
    up.last_name as patient_last_name,
    up.email as patient_email,
    up.phone as patient_phone,
    s.name as service_name,
    l.name as location_name,
    CONCAT(ud.first_name, ' ', ud.last_name) as doctor_name,
    ud.first_name as doctor_first_name,
    ud.last_name as doctor_last_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN users up ON p.user_id = up.user_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    LEFT JOIN users ud ON d.user_id = ud.user_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN locations l ON a.location_id = l.location_id
    WHERE a.appointment_id = ?";

    // Prepare statement
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameter and execute
    if (!$stmt->bind_param("i", $appointmentId)) {
        throw new Exception("Failed to bind parameter: " . $stmt->error);
    }

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    // Get result
    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Failed to get result: " . $stmt->error);
    }

    if ($result->num_rows > 0) {
        $appointment = $result->fetch_assoc();

        // Format dates and times
        $appointment['appointment_date'] = date('Y-m-d', strtotime($appointment['appointment_date']));
        $appointment['start_time'] = date('H:i', strtotime($appointment['start_time']));
        $appointment['end_time'] = date('H:i', strtotime($appointment['end_time']));

        echo json_encode([
            'success' => true,
            'appointment' => $appointment
        ]);
    } else {
        echo json_encode([
            'error' => 'Appointment not found'
        ]);
    }

} catch (Exception $e) {
    error_log($e->getMessage());
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage()
    ]);

} finally {
    // Clean up
    if (isset($stmt) && $stmt !== false) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}