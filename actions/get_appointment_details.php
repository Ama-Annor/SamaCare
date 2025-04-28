<?php
// Start session for user authentication
session_start();

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Return error
    http_response_code(401);
    echo "Unauthorized access";
    exit();
}

// Get the appointment ID parameter
$appointmentId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($appointmentId <= 0) {
    echo "<div class='error-message'>Invalid appointment ID</div>";
    exit();
}

// Prepare SQL query to get appointment details
$sql = "SELECT a.*, 
        u_patient.first_name as patient_first_name, u_patient.last_name as patient_last_name,
        u_patient.email as patient_email, u_patient.phone as patient_phone, u_patient.date_of_birth as patient_dob,
        u_doctor.first_name as doctor_first_name, u_doctor.last_name as doctor_last_name,
        s.name as service_name, s.description as service_description, s.duration, s.default_cost,
        l.name as location_name, l.address as location_address, l.city as location_city, l.phone as location_phone
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        JOIN users u_patient ON p.user_id = u_patient.user_id
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u_doctor ON d.user_id = u_doctor.user_id
        JOIN services s ON a.service_id = s.service_id
        JOIN locations l ON a.location_id = l.location_id
        WHERE a.appointment_id = ?";

// Use prepared statement to prevent SQL injection
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointmentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $appointment = $result->fetch_assoc();

    // Format dates and times
    $appointmentDate = date('F j, Y', strtotime($appointment['appointment_date']));
    $startTime = date('g:i A', strtotime($appointment['start_time']));
    $endTime = date('g:i A', strtotime($appointment['end_time']));
    $patientDOB = !empty($appointment['patient_dob']) ? date('F j, Y', strtotime($appointment['patient_dob'])) : 'Not provided';
    $patientAge = !empty($appointment['patient_dob']) ? calculateAge($appointment['patient_dob']) : 'Unknown';
    $createdAt = date('M d, Y \a\t g:i A', strtotime($appointment['created_at']));
    $updatedAt = !empty($appointment['updated_at']) ? date('M d, Y \a\t g:i A', strtotime($appointment['updated_at'])) : 'Never updated';

    // Get status class for styling
    $statusClass = $appointment['status'];

    // Check if appointment is in the past
    $isPast = (strtotime($appointment['appointment_date'] . ' ' . $appointment['end_time']) < time());

    // Generate HTML for the appointment details
    ?>
    <div class="appointment-details" data-appointment-id="<?php echo $appointmentId; ?>">
        <div class="appointment-header">
            <div class="appointment-title">
                <h3><?php echo htmlspecialchars($appointment['service_name']); ?></h3>
                <span class="status-badge <?php echo $statusClass; ?>"><?php echo ucfirst($appointment['status']); ?></span>
            </div>
            <div class="appointment-datetime">
                <p><i class='bx bx-calendar'></i> <?php echo $appointmentDate; ?></p>
                <p><i class='bx bx-time'></i> <?php echo $startTime; ?> - <?php echo $endTime; ?> (<?php echo $appointment['duration']; ?> minutes)</p>
            </div>
        </div>

        <div class="appointment-section">
            <h4>Patient Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['patient_email']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['patient_phone'] ?? 'Not provided'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value"><?php echo $patientDOB; ?> (Age: <?php echo $patientAge; ?>)</span>
                </div>
            </div>
        </div>

        <div class="appointment-section">
            <h4>Doctor Information</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Doctor:</span>
                    <span class="detail-value">Dr. <?php echo htmlspecialchars($appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name']); ?></span>
                </div>
            </div>
        </div>

        <div class="appointment-section">
            <h4>Service Details</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Service:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['service_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['service_description'] ?? 'No description available'); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Cost:</span>
                    <span class="detail-value">$<?php echo number_format($appointment['default_cost'], 2); ?></span>
                </div>
            </div>
        </div>

        <div class="appointment-section">
            <h4>Location</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Facility:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['location_name']); ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value">
                        <?php echo htmlspecialchars($appointment['location_address']); ?>,
                        <?php echo isset($appointment['location_city']) && $appointment['location_city'] !== null ? htmlspecialchars($appointment['location_city']) : 'N/A'; ?>
                    </span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($appointment['location_phone'] ?? 'N/A'); ?></span>
                </div>
            </div>
        </div>

        <?php if (!empty($appointment['notes'])) { ?>
            <div class="appointment-section">
                <h4>Notes</h4>
                <div class="notes-box">
                    <p><?php echo nl2br(htmlspecialchars($appointment['notes'])); ?></p>
                </div>
            </div>
        <?php } ?>

        <div class="appointment-section">
            <h4>Appointment History</h4>
            <div class="detail-grid">
                <div class="detail-item">
                    <span class="detail-label">Created:</span>
                    <span class="detail-value"><?php echo $createdAt; ?></span>
                </div>
                <div class="detail-item">
                    <span class="detail-label">Last Updated:</span>
                    <span class="detail-value"><?php echo $updatedAt; ?></span>
                </div>
            </div>
        </div>

        <?php if (!$isPast && $appointment['status'] != 'cancelled' && $appointment['status'] != 'completed') { ?>
            <div class="appointment-actions">
                <div class="status-actions">
                    <?php if ($appointment['status'] != 'completed') { ?>
                        <button class="btn primary-btn" data-action="complete" data-appointment-id="<?php echo $appointmentId; ?>">
                            <i class='bx bx-check-circle'></i> Mark as Completed
                        </button>
                    <?php } ?>

                    <button class="btn secondary-btn" data-action="reschedule" data-appointment-id="<?php echo $appointmentId; ?>">
                        <i class='bx bx-calendar-edit'></i> Reschedule
                    </button>

                    <button class="btn info-btn" data-action="reminder" data-appointment-id="<?php echo $appointmentId; ?>">
                        <i class='bx bx-bell'></i> Send Reminder
                    </button>

                    <?php if ($appointment['status'] != 'cancelled') { ?>
                        <button class="btn danger-btn" data-action="cancel" data-appointment-id="<?php echo $appointmentId; ?>">
                            <i class='bx bx-x-circle'></i> Cancel Appointment
                        </button>
                    <?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
    <?php
} else {
    echo "<div class='error-message'>Appointment not found</div>";
}

$stmt->close();
$conn->close();

// Helper function to calculate age from date of birth
function calculateAge($dob) {
    $dobDate = new DateTime($dob);
    $now = new DateTime();
    $interval = $now->diff($dobDate);
    return $interval->y;
}
?>