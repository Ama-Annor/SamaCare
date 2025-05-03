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
    // Get doctor ID
    $userId = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Doctor not found');
    }
    
    $doctor = $result->fetch_assoc();
    $doctorId = $doctor['doctor_id'];

    // Initialize where clause and parameters
    $whereClause = ["a.doctor_id = ?"]; 
    $params = [$doctorId];
    $types = "i";

    // Handle status filter
    if (isset($_GET['status']) && $_GET['status'] !== 'all') {
        $status = strtolower(trim($_GET['status']));
        $whereClause[] = "LOWER(a.status) = ?";
        $params[] = $status;
        $types .= "s";
    }

    // Handle service filter
    if (isset($_GET['service']) && $_GET['service'] !== 'all') {
        $service = intval($_GET['service']);
        $whereClause[] = "a.service_id = ?";
        $params[] = $service;
        $types .= "i";
    }

    // Handle date range
    if (isset($_GET['date_start']) && !empty($_GET['date_start'])) {
        $dateStart = date('Y-m-d', strtotime($_GET['date_start']));
        $whereClause[] = "DATE(a.appointment_date) >= ?";
        $params[] = $dateStart;
        $types .= "s";
    }

    if (isset($_GET['date_end']) && !empty($_GET['date_end'])) {
        $dateEnd = date('Y-m-d', strtotime($_GET['date_end']));
        $whereClause[] = "DATE(a.appointment_date) <= ?";
        $params[] = $dateEnd;
        $types .= "s";
    }

    // Build the query
    $whereString = implode(" AND ", $whereClause);
    $query = "SELECT 
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
        WHERE {$whereString}
        ORDER BY a.appointment_date DESC, a.start_time ASC";

    // Debug logging
    error_log("Query: " . $query);
    error_log("Params: " . json_encode($params));
    error_log("Types: " . $types);

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $appointments = [];
    while ($row = $result->fetch_assoc()) {
        $appointments[] = $row;
    }

    echo json_encode([
        'success' => true,
        'appointments' => $appointments,
        'debug' => [
            'filters' => $_GET,
            'where' => $whereString,
            'params' => $params
        ]
    ]);

} catch (Exception $e) {
    error_log("Error in get_doctor_appointments: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
    
}