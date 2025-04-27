<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // 👈 MOVE THIS TO THE VERY TOP

/**
 * API Endpoint: Get Calendar Appointments
 * 
 * Fetches appointments within a specified date range for calendar views
 * Required parameters:
 * - start_date: YYYY-MM-DD format
 * - end_date: YYYY-MM-DD format
 */

// Initialize response array
$response = ['success' => false, 'appointments' => [], 'error' => null];

// Include database connection
require_once '../../db/db_connect.php';
require_once '../includes/functions.php';
require_once '../includes/auth_check.php';

// Check if user is authenticated
if (!isLoggedIn()) {
    $response['error'] = 'Authentication required';
    echo json_encode($response);
    exit;
}

// Validate required parameters
if (!isset($_GET['start_date']) || !isset($_GET['end_date'])) {
    $response['error'] = 'Missing required parameters: start_date and end_date';
    echo json_encode($response);
    exit;
}

// Sanitize input
$startDate = sanitizeInput($_GET['start_date']);
$endDate = sanitizeInput($_GET['end_date']);

// Validate date format (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
    $response['error'] = 'Invalid date format. Use YYYY-MM-DD';
    echo json_encode($response);
    exit;
}

try {
    // Prepare SQL query to fetch appointments
    $sql = "SELECT 
                a.appointment_id, 
                a.patient_id, 
                a.service_id, 
                a.location_id, 
                a.appointment_date, 
                a.start_time, 
                a.end_time, 
                a.status, 
                a.notes,
                p.first_name AS patient_first_name, 
                p.last_name AS patient_last_name, 
                p.email AS patient_email, 
                p.phone AS patient_phone,
                s.name AS service_name,
                l.name AS location_name
            FROM 
                appointments a
            JOIN 
                patients p ON a.patient_id = p.patient_id
            JOIN 
                services s ON a.service_id = s.service_id
            JOIN 
                locations l ON a.location_id = l.location_id
            WHERE 
                a.appointment_date BETWEEN ? AND ?
            ORDER BY 
                a.appointment_date, a.start_time";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $startDate, $endDate);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Fetch all appointments
        while ($row = $result->fetch_assoc()) {
            $response['appointments'][] = $row;
        }
    }
    
    $response['success'] = true;
    
} catch (Exception $e) {
    $response['error'] = 'Database error: ' . $e->getMessage();
} finally {
    // Close connection if it exists
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

// Set appropriate headers
// header('Content-Type: application/json');
echo json_encode($response);
exit;