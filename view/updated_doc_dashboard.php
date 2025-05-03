<?php

// start a session 
session_start();

//check if user as right access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    //take user back to login
    header('Location: login.php');
    exit();
}

// Database connection
require_once '../db/db_connect.php';

// Query to get information
$user_id = $_SESSION['user_id'];

$doctor_query = "SELECT d.doctor_id, u.first_name, u.last_name, u.profile_image, s.name as specialty
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                WHERE d.user_id = ?";

//run query to connection in database
$stmt = $conn->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();

if ($doctor_result->num_rows === 0) {
    // This should happen if the user is logged in but doesn't have a doctor record
    echo "Error! doctor not found : Meaning user hasn't registered as a doctor";
    exit();
}

$doctor = $doctor_result->fetch_assoc();
$doctor_id = $doctor['doctor_id'];
$has_specialty = !empty($doctor['specialty']);

// Fetch all specialties for the dropdown
$specialties_query = "SELECT specialty_id, name FROM specialties ORDER BY name ASC";
$specialties_result = $conn->query($specialties_query);
$specialties = [];
while ($row = $specialties_result->fetch_assoc()) {
    $specialties[] = $row;
}

// Get today's date
$today = date('Y-m-d');

// Get today's appointments
$appointments_query = "SELECT a.*, p.user_id as patient_user_id, 
                      u.first_name as patient_first_name, u.last_name as patient_last_name, 
                      s.name as service_name, l.name as location_name
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN users u ON p.user_id = u.user_id
                      JOIN services s ON a.service_id = s.service_id
                      JOIN locations l ON a.location_id = l.location_id
                      WHERE a.doctor_id = ? AND a.appointment_date = ?
                      ORDER BY a.start_time ASC";

$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Count today's appointments
$total_appointments = count($appointments);
$completed_appointments = 0;

foreach ($appointments as $appt) {
    if ($appt['status'] == 'completed') {
        $completed_appointments++;
    }
}

$remaining_appointments = $total_appointments - $completed_appointments;

// Get total patients count
$patients_query = "SELECT COUNT(DISTINCT patient_id) as total_patients 
                  FROM appointments 
                  WHERE doctor_id = ?";
$stmt = $conn->prepare($patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_result = $stmt->get_result();
$patients_data = $patients_result->fetch_assoc();
$total_patients = $patients_data['total_patients'];

// Get new patients this week
$week_start = date('Y-m-d', strtotime('monday this week'));
$new_patients_query = "SELECT COUNT(DISTINCT a.patient_id) as new_patients
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN users u ON p.user_id = u.user_id
                      WHERE a.doctor_id = ? AND a.created_at >= ?";
$stmt = $conn->prepare($new_patients_query);
$stmt->bind_param("is", $doctor_id, $week_start);
$stmt->execute();
$new_patients_result = $stmt->get_result();
$new_patients_data = $new_patients_result->fetch_assoc();
$new_patients = $new_patients_data['new_patients'];

// Get next appointment time
$next_appointment_time = "";
$next_appointment_in_minutes = 0;
$current_time = date('H:i:s');

foreach ($appointments as $appt) {
    if ($appt['status'] == 'pending' || $appt['status'] == 'confirmed') {
        $appointment_time = $appt['start_time'];
        if ($appointment_time > $current_time) {
            $next_appointment_time = $appointment_time;
            $next_appointment_in_minutes = round((strtotime($appointment_time) - strtotime($current_time)) / 60);
            break;
        }
    }
}

// NEW: Calculate total completed appointments (patients seen)
$completed_appointments_query = "SELECT COUNT(*) as total_completed
                               FROM appointments
                               WHERE doctor_id = ? AND status = 'completed'";
$stmt = $conn->prepare($completed_appointments_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();
$patients_seen = $result->fetch_assoc()['total_completed'];
$stmt->close();

// NEW: Calculate average consultation time
$avg_consultation_query = "SELECT AVG(TIME_TO_SEC(TIMEDIFF(end_time, start_time)))/60 as avg_time
                         FROM appointments
                         WHERE doctor_id = ? AND status = 'completed'";
$stmt = $conn->prepare($avg_consultation_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$avg_data = $result->fetch_assoc();
$avg_consultation_time = $avg_data['avg_time'] !== NULL ? round($avg_data['avg_time']) : 0;
$stmt->close();

// Performance metrics array (with removed patient satisfaction)
$performance_metrics = [
    [
        'title' => 'Patients Seen',
        'value' => $patients_seen,
        'trend' => '+0%', // You would calculate this based on previous data
        'trend_direction' => 'neutral',
        'period' => 'All-time total',
        'icon' => 'bx-user-check'
    ],
    [
        'title' => 'Avg. Consultation Time',
        'value' => $avg_consultation_time . ' min',
        'trend' => '0%', // You would calculate this based on previous data
        'trend_direction' => 'neutral',
        'period' => 'Based on completed appointments',
        'icon' => 'bx-time-five'
    ]
];

// Format doctor's initials for avatar
$initials = substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/doctors_dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal {
            background-color: white;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
            padding: 2rem;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }
        
        .modal-header {
            margin-bottom: 1.5rem;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #333;
            font-size: 1.5rem;
        }
        
        .modal-content {
            margin-bottom: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
        }
        
        .modal-footer {
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            padding: 0.7rem 1.5rem;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: background-color 0.3s;
        }
        
        .primary-btn {
            background-color: #4361ee;
            color: white;
        }
        
        .primary-btn:hover {
            background-color: #3a56d4;
        }
        
        .btn[disabled] {
            opacity: 0.6;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
<!-- Dashboard Layout -->
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class='bx bx-plus-medical'></i>SAMA<span>CARE</span>
            </div>
            <button class="close-sidebar">
                <i class='bx bx-x'></i>
            </button>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <span><?php echo $initials; ?></span>
            </div>
            <div class="user-info">
                <h4>Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></h4>
                <p><?php echo $doctor['specialty'] ?: 'No specialty set'; ?></p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="doctor_dashboard.php">
                        <i class='bx bx-home-alt'></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_appointment.php">
                        <i class='bx bx-calendar'></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="doctor_patients.php">
                        <i class='bx bx-user'></i>
                        <span>Patients</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="../actions/logout.php" class="logout-link">
                <i class='bx bx-log-out'></i>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="menu-toggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Doctor Dashboard</h1>
            </div>
            <div class="header-right">
                <div class="header-actions">
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small">
                                <span><?php echo $initials; ?></span>
                            </div>
                            <span class="user-name">Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Welcome back, Dr. <?php echo $doctor['last_name']; ?>!</h2>
                        <p>You have <?php echo $total_appointments; ?> patients scheduled for today.
                            <?php if ($next_appointment_in_minutes > 0): ?>
                                Your first appointment starts in <?php echo $next_appointment_in_minutes; ?> minutes.
                            <?php else: ?>
                                You have no more appointments scheduled for today.
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="welcome-actions">
                        <a href="doctor_appointment.php" class="btn primary-btn">
                            <i class='bx bx-calendar-plus'></i>
                            <span>View Appointments</span>
                        </a>
                    </div>
                </div>
            </section>

            <!-- Stats Cards Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class='bx bx-calendar-check'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Today's Appointments</h3>
                            <p class="stat-value"><?php echo $total_appointments; ?></p>
                            <p class="stat-description"><?php echo $completed_appointments; ?> completed, <?php echo $remaining_appointments; ?> remaining</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class='bx bx-user'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Patients</h3>
                            <p class="stat-value"><?php echo $total_patients; ?></p>
                            <p class="stat-description"><?php echo $new_patients; ?> new this week</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Today's Schedule Section -->
            <section class="dashboard-two-columns">
                <!-- Today's Schedule -->
                <div class="dashboard-column full-width">
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Today's Schedule</h3>
                            <a href="doctor_appointment.php" class="view-all">View Full Calendar</a>
                        </div>
                        <div class="appointments-list">
                            <?php if (count($appointments) > 0): ?>
                                <?php foreach(array_slice($appointments, 0, 3) as $appointment): ?>
                                    <div class="appointment-item">
                                        <div class="appointment-date">
                                            <span class="day"><?php echo date('h:i', strtotime($appointment['start_time'])); ?></span>
                                            <span class="month"><?php echo date('A', strtotime($appointment['start_time'])); ?></span>
                                        </div>
                                        <div class="appointment-details">
                                            <h4><?php echo $appointment['service_name']; ?></h4>
                                            <p><i class='bx bx-user'></i> <?php echo $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']; ?></p>
                                            <p><i class='bx bx-file'></i> <?php echo $appointment['notes'] ? $appointment['notes'] : 'No notes available'; ?></p>
                                            <p><i class='bx bx-map'></i> <?php echo $appointment['location_name']; ?></p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-appointments">
                                    <p>No appointments scheduled for today.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Performance Metrics Section -->
            <section class="health-metrics-section">
                <div class="content-card full-width">
                    <div class="card-header">
                        <h3>Performance Metrics</h3>
                    </div>
                    <div class="metrics-grid">
                        <?php foreach($performance_metrics as $metric): ?>
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h4><?php echo $metric['title']; ?></h4>
                                    <div class="metric-icon">
                                        <i class='bx <?php echo $metric['icon']; ?>'></i>
                                    </div>
                                </div>
                                <div class="metric-value">
                                    <span class="current-value"><?php echo $metric['value']; ?></span>
                                    <span class="metric-trend <?php echo $metric['trend_direction']; ?>">
                                            <i class='bx bx-<?php echo $metric['trend_direction'] === 'positive' ? 'up' : 'down'; ?>-arrow'></i>
                                            <span><?php echo $metric['trend']; ?></span>
                                        </span>
                                </div>
                                <div class="metric-chart">
                                    <!-- Placeholder for chart -->
                                    <div class="chart-placeholder"></div>
                                </div>
                                <div class="metric-footer">
                                    <span><?php echo $metric['period']; ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> SamaCare. All rights reserved.</p>
            <div class="footer-links">
                <a href="../privacy-policy.php">Privacy Policy</a>
                <a href="../terms-of-service.php">Terms of Service</a>
                <a href="../help.php">Help & Support</a>
            </div>
        </footer>
    </main>
    
    <!-- Specialty Modal -->
    <?php if (!$has_specialty): ?>
    <div class="modal-overlay" id="specialtyModal">
        <div class="modal">
            <div class="modal-header">
                <h2>Set Your Medical Specialty</h2>
            </div>
            <div class="modal-content">
                <p>Please select your medical specialty to complete your profile. This helps us tailor your experience and connect you with appropriate patients.</p>
                <form id="specialtyForm" action="../actions/update_specialty.php" method="POST">
                    <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                    <div class="form-group">
                        <label for="specialty">Select Specialty:</label>
                        <select name="specialty_id" id="specialty" required>
                            <option value="">-- Select Specialty --</option>
                            <?php foreach($specialties as $specialty): ?>
                                <option value="<?php echo $specialty['specialty_id']; ?>"><?php echo $specialty['name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn primary-btn" id="saveSpecialty">
                            <i class='bx bx-check'></i>
                            Save Specialty
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="../assets/js/dashboard.js"></script>
<script>
    // Script for handling specialty modal
    document.addEventListener('DOMContentLoaded', function() {
        const specialtyModal = document.getElementById('specialtyModal');
        const specialtyForm = document.getElementById('specialtyForm');
        const specialtySelect = document.getElementById('specialty');
        const saveButton = document.getElementById('saveSpecialty');
        
        // Function to validate and enable/disable save button
        function validateSpecialty() {
            if (specialtySelect && specialtySelect.value) {
                saveButton.removeAttribute('disabled');
            } else {
                saveButton.setAttribute('disabled', 'disabled');
            }
        }
        
        // Initialize validation on load
        if (specialtySelect) {
            validateSpecialty();
            
            // Add event listener for select change
            specialtySelect.addEventListener('change', validateSpecialty);
        }
        
        // Prevent closing the modal by clicking outside
        if (specialtyModal) {
            specialtyModal.addEventListener('click', function(e) {
                if (e.target === specialtyModal) {
                    e.preventDefault();
                    // Alert user they must select a specialty
                    alert('Please select a specialty to continue.');
                }
            });
        }
    });
</script>
</body>
</html>