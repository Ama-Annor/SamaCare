<?php
// Start session for user authentication
session_start();

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Redirect to login page
    header('Location: ../login.php?redirect=admin');
    exit();
}

// Initialize filters (same as in admin_appointments.php)
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$doctorFilter = isset($_GET['doctor']) ? $_GET['doctor'] : 'all';
$serviceFilter = isset($_GET['service']) ? $_GET['service'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare base SQL query
$sql = "SELECT a.appointment_id, a.appointment_date, a.start_time, a.end_time, a.status,
        u_patient.first_name as patient_first_name, u_patient.last_name as patient_last_name,
        u_patient.email as patient_email, u_patient.phone as patient_phone,
        u_doctor.first_name as doctor_first_name, u_doctor.last_name as doctor_last_name,
        s.name as service_name, l.name as location_name, a.notes
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u_patient ON p.user_id = u_patient.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u_doctor ON d.user_id = u_doctor.user_id
        JOIN services s ON a.service_id = s.service_id
        JOIN locations l ON a.location_id = l.location_id
        WHERE 1=1";

// Add filters to query
if ($statusFilter != 'all') {
    $sql .= " AND a.status = '" . $conn->real_escape_string($statusFilter) . "'";
}

if ($doctorFilter != 'all') {
    $sql .= " AND a.doctor_id = " . $conn->real_escape_string($doctorFilter);
}

if ($serviceFilter != 'all') {
    $sql .= " AND a.service_id = " . $conn->real_escape_string($serviceFilter);
}

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND a.appointment_date BETWEEN '" . $conn->real_escape_string($startDate) . "' AND '" . $conn->real_escape_string($endDate) . "'";
}

// Order by date and time
$sql .= " ORDER BY a.appointment_date, a.start_time";

// Execute the query
$result = $conn->query($sql);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="appointments_export_' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Set column headers
fputcsv($output, [
    'Appointment ID',
    'Date',
    'Start Time',
    'End Time',
    'Patient Name',
    'Patient Email',
    'Patient Phone',
    'Doctor',
    'Service',
    'Location',
    'Status',
    'Notes'
]);

// Fetch and output each row
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Format date and times
        $date = date('m/d/Y', strtotime($row['appointment_date']));
        $startTime = date('g:i A', strtotime($row['start_time']));
        $endTime = date('g:i A', strtotime($row['end_time']));

        // Combine patient first and last name
        $patientName = $row['patient_first_name'] . ' ' . $row['patient_last_name'];

        // Combine doctor first and last name with "Dr." prefix
        $doctorName = 'Dr. ' . $row['doctor_first_name'] . ' ' . $row['doctor_last_name'];

        // Capitalize status
        $status = ucfirst($row['status']);

        // Write the data to CSV
        fputcsv($output, [
            $row['appointment_id'],
            $date,
            $startTime,
            $endTime,
            $patientName,
            $row['patient_email'],
            $row['patient_phone'],
            $doctorName,
            $row['service_name'],
            $row['location_name'],
            $status,
            $row['notes']
        ]);
    }
}

// Close file pointer
fclose($output);

// Log the export activity
$adminId = $_SESSION['user_id'];
$sql = "INSERT INTO user_activities (user_id, activity_type, description) 
        VALUES (?, 'appointment_export', ?)";

$stmt = $conn->prepare($sql);
$description = "Exported appointments " . ($statusFilter != 'all' ? "with status: $statusFilter " : "") .
    ($doctorFilter != 'all' ? "for doctor ID: $doctorFilter " : "") .
    ($serviceFilter != 'all' ? "for service ID: $serviceFilter " : "") .
    (!empty($startDate) && !empty($endDate) ? "from $startDate to $endDate" : "");

$stmt->bind_param("is", $adminId, $description);
$stmt->execute();
$stmt->close();

$conn->close();
exit();
?>