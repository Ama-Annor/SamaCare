<?php
require_once '../../db/db_connect.php';
header('Content-Type: application/json');

echo "This is a test.";exit();

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'Appointment ID is required']);
    exit;
}

$appointmentId = intval($_GET['id']);

try {
    $stmt = $pdo->prepare("SELECT 
        a.*, 
        p.first_name as patient_first_name,
        p.last_name as patient_last_name,
        p.email as patient_email,
        p.phone as patient_phone,
        s.name as service_name,
        l.name as location_name
    FROM appointments a
    LEFT JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN services s ON a.service_id = s.service_id
    LEFT JOIN locations l ON a.location_id = l.location_id
    WHERE a.appointment_id = ?");
    
    $stmt->execute([$appointmentId]);
    $appointment = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($appointment) {
        echo json_encode(['appointment' => $appointment]);
    } else {
        echo json_encode(['error' => 'Appointment not found']);
    }
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error']);
}