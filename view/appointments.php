<?php
// Start session if not already started
session_start();


// Check if user is logged in, redirect to login page if not
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}


// Get user information from session
$user = [
    'name' => $_SESSION['name'] ?? 'Adwoa Afari',
    'role' => $_SESSION['role'] ?? 'Patient',
    'initials' => $_SESSION['initials'] ?? 'JD'
];


// Initialize empty arrays for appointments
$upcomingAppointments = [];
$pastAppointments = [];

// Calendar data - for current month
$now = new DateTime();
$currentMonth = $now->format('F Y');
$selectedDate = $now->format('F d, Y');

// Safely establish database connection - with error handling
try {
    $db_host = "localhost";
    $db_user = "root";  // Adjust if needed
    $db_pass = "";      // Adjust if needed
    $db_name = "samacare";

    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

    // Check connection
    if ($conn && !$conn->connect_error) {
        // Get user details from database - with error handling
        try {
            $user_query = "SELECT u.first_name, u.last_name, r.role_name 
                           FROM users u 
                           JOIN roles r ON u.role_id = r.role_id 
                           WHERE u.user_id = ?";
            $stmt = $conn->prepare($user_query);
            
            if ($stmt) {
                $stmt->bind_param("i", $_SESSION['user_id']);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result && $result->num_rows > 0) {
                    $user_data = $result->fetch_assoc();
                    
                    // Update user information
                    $user = [
                        'name' => $user_data['first_name'] . ' ' . $user_data['last_name'],
                        'role' => $user_data['role_name'],
                        'initials' => substr($user_data['first_name'], 0, 1) . substr($user_data['last_name'], 0, 1)
                    ];
                    
                    // Store role for later use
                    $user_role = $user_data['role_name'];
                    
                    // Get the appropriate ID based on role (patient_id or doctor_id)
                    $role_specific_id = null;
                    $role_field = '';
                    $role_table = '';
                    
                    if (strtolower($user_role) == 'patient') {
                        $role_field = 'patient_id';
                        $role_table = 'patients';
                    } elseif (strtolower($user_role) == 'doctor') {
                        $role_field = 'doctor_id';
                        $role_table = 'doctors';
                    }
                    
                    if (!empty($role_field)) {
                        $id_query = "SELECT {$role_field} FROM {$role_table} WHERE user_id = ?";
                        $stmt = $conn->prepare($id_query);
                        
                        if ($stmt) {
                            $stmt->bind_param("i", $_SESSION['user_id']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            if ($result && $result->num_rows > 0) {
                                $id_data = $result->fetch_assoc();
                                $role_specific_id = $id_data[$role_field];
                                
                                // Check if required tables exist
                                $check_tables_query = "SHOW TABLES LIKE 'appointments'";
                                $tables_result = $conn->query($check_tables_query);
                                
                                if ($tables_result && $tables_result->num_rows > 0) {
                                    // Check if services table exists
                                    $check_services = "SHOW TABLES LIKE 'services'";
                                    $services_result = $conn->query($check_services);
                                    
                                    if ($services_result && $services_result->num_rows > 0) {
                                        // Fetch appointments if we have a valid role ID
                                        if ($role_specific_id) {
                                            // Query to get upcoming appointments
                                            try {
                                                $upcoming_query = "
                                                    SELECT 
                                                        a.appointment_id, 
                                                        a.appointment_date, 
                                                        a.start_time, 
                                                        a.status,
                                                        s.name AS service_name,
                                                        l.name AS location_name,
                                                        doc_user.first_name AS doctor_first_name,
                                                        doc_user.last_name AS doctor_last_name
                                                    FROM 
                                                        appointments a
                                                    JOIN 
                                                        services s ON a.service_id = s.service_id
                                                    JOIN 
                                                        locations l ON a.location_id = l.location_id
                                                    JOIN 
                                                        doctors d ON a.doctor_id = d.doctor_id
                                                    JOIN 
                                                        users doc_user ON d.user_id = doc_user.user_id
                                                    WHERE 
                                                        a.{$role_field} = ? 
                                                        AND a.appointment_date >= CURDATE()
                                                        AND a.status IN ('pending', 'confirmed')
                                                    ORDER BY 
                                                        a.appointment_date ASC, 
                                                        a.start_time ASC
                                                    LIMIT 5
                                                ";
                                                
                                                $stmt = $conn->prepare($upcoming_query);
                                                
                                                if ($stmt) {
                                                    $stmt->bind_param("i", $role_specific_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    
                                                    // Process upcoming appointments
                                                    if ($result && $result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            $appointment_date = new DateTime($row['appointment_date']);
                                                            
                                                            $upcomingAppointments[] = [
                                                                'day' => $appointment_date->format('d'),
                                                                'month' => $appointment_date->format('M'),
                                                                'title' => $row['service_name'],
                                                                'time' => date('g:i A', strtotime($row['start_time'])),
                                                                'doctor' => 'Dr. ' . $row['doctor_first_name'] . ' ' . $row['doctor_last_name'],
                                                                'location' => $row['location_name'],
                                                                'status' => $row['status']
                                                            ];
                                                            
                                                            // If there's at least one upcoming appointment, use its date for the selected date
                                                            if (count($upcomingAppointments) == 1) {
                                                                $selectedDate = $appointment_date->format('F d, Y');
                                                            }
                                                        }
                                                    }
                                                }
                                                
                                                // Query to get past appointments
                                                $past_query = "
                                                    SELECT 
                                                        a.appointment_id, 
                                                        a.appointment_date, 
                                                        a.start_time, 
                                                        a.status,
                                                        s.name AS service_name,
                                                        l.name AS location_name,
                                                        doc_user.first_name AS doctor_first_name,
                                                        doc_user.last_name AS doctor_last_name
                                                    FROM 
                                                        appointments a
                                                    JOIN 
                                                        services s ON a.service_id = s.service_id
                                                    JOIN 
                                                        locations l ON a.location_id = l.location_id
                                                    JOIN 
                                                        doctors d ON a.doctor_id = d.doctor_id
                                                    JOIN 
                                                        users doc_user ON d.user_id = doc_user.user_id
                                                    WHERE 
                                                        a.{$role_field} = ? 
                                                        AND (a.appointment_date < CURDATE() OR a.status = 'completed')
                                                    ORDER BY 
                                                        a.appointment_date DESC, 
                                                        a.start_time DESC
                                                    LIMIT 5
                                                ";
                                                
                                                $stmt = $conn->prepare($past_query);
                                                
                                                if ($stmt) {
                                                    $stmt->bind_param("i", $role_specific_id);
                                                    $stmt->execute();
                                                    $result = $stmt->get_result();
                                                    
                                                    // Process past appointments
                                                    if ($result && $result->num_rows > 0) {
                                                        while ($row = $result->fetch_assoc()) {
                                                            $appointment_date = new DateTime($row['appointment_date']);
                                                            
                                                            $pastAppointments[] = [
                                                                'day' => $appointment_date->format('d'),
                                                                'month' => $appointment_date->format('M'),
                                                                'title' => $row['service_name'],
                                                                'time' => date('g:i A', strtotime($row['start_time'])),
                                                                'doctor' => 'Dr. ' . $row['doctor_first_name'] . ' ' . $row['doctor_last_name'],
                                                                'location' => $row['location_name'],
                                                                'status' => $row['status']
                                                            ];
                                                        }
                                                    }
                                                }
                                            } catch (Exception $e) {
                                                // Failed to query appointments - continue with empty arrays
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            // Failed to get user details - continue with defaults
        }
        
        // Close connection
        $conn->close();
    }
} catch (Exception $e) {
    // Database connection error - continue with defaults
}

// If all database operations failed, use this sample data
if (empty($upcomingAppointments)) {
    $upcomingAppointments = [
        [
            'day' => '19',
            'month' => 'Jun',
            'title' => 'General Checkup',
            'time' => '10:30 AM',
            'doctor' => 'Dr. Ama Mensah',
            'location' => 'SamaCare Main Clinic',
            'status' => 'confirmed'
        ]
    ];
}

if (empty($pastAppointments)) {
    $pastAppointments = [
        [
            'day' => '10',
            'month' => 'May',
            'title' => 'Regular Checkup',
            'time' => '1:00 PM',
            'doctor' => 'Dr. McNobert Amoah',
            'location' => 'SamaCare West Branch',
            'status' => 'completed'
        ],
        [
            'day' => '15',
            'month' => 'Apr',
            'title' => 'Lab Tests',
            'time' => '9:30 AM',
            'doctor' => 'Lab Department',
            'location' => 'SamaCare Diagnostics',
            'status' => 'completed'
        ]
    ];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/appointments.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
    <!-- Dashboard Layout -->
    <div class="dashboard-container">
        <!-- Sidebar Navigation -->
        <?php include '../assets/includes/sidebar.php'; ?>
        
        <!-- Main Content Area -->
        <main class="main-content">
            <!-- Top Header -->
            <?php include '../assets/includes/header.php'; ?>
            
            <!-- Appointments Content -->
            <div class="dashboard-content">
                <!-- Action Bar -->
                <section class="action-bar">
                    <div class="action-left">
                        <button class="btn primary-btn schedule-btn">
                            <i class='bx bx-plus'></i>
                            <span>New Appointment</span>
                        </button>
                        <div class="filter-dropdown">
                            <button class="btn secondary-btn">
                                <i class='bx bx-filter'></i>
                                <span>Status: All</span>
                                <i class='bx bx-chevron-down'></i>
                            </button>
                        </div>
                    </div>
                    <div class="action-right">
                        <div class="view-toggle">
                            <button class="view-btn active" data-view="list">
                                <i class='bx bx-list-ul'></i>
                            </button>
                            <button class="view-btn" data-view="calendar">
                                <i class='bx bx-calendar'></i>
                            </button>
                        </div>
                    </div>
                </section>

                <!-- Appointments List View -->
                <section class="appointments-section view-section active" id="list-view">
                    <div class="appointments-container">
                        <!-- Upcoming Appointments -->
                        <div class="content-card">
                            <div class="card-header">
                                <h3>Upcoming Appointments</h3>
                            </div>
                            <div class="appointments-list">
                                <?php if (empty($upcomingAppointments)): ?>
                                    <p class="no-appointments">No upcoming appointments.</p>
                                <?php else: ?>
                                    <?php foreach ($upcomingAppointments as $appointment): ?>
                                        <div class="appointment-item">
                                            <div class="appointment-date">
                                                <span class="day"><?php echo $appointment['day']; ?></span>
                                                <span class="month"><?php echo $appointment['month']; ?></span>
                                            </div>
                                            <div class="appointment-details">
                                                <h4><?php echo $appointment['title']; ?></h4>
                                                <p><i class='bx bx-time'></i> <?php echo $appointment['time']; ?></p>
                                                <p><i class='bx bx-user'></i> <?php echo $appointment['doctor']; ?></p>
                                                <p><i class='bx bx-map'></i> <?php echo $appointment['location']; ?></p>
                                                <span class="appointment-status <?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                            </div>
                                            <div class="appointment-actions">
                                                <button class="action-btn" title="Reschedule">
                                                    <i class='bx bx-calendar-edit'></i>
                                                </button>
                                                <button class="action-btn" title="Cancel">
                                                    <i class='bx bx-x-circle'></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Past Appointments -->
                        <div class="content-card">
                            <div class="card-header">
                                <h3>Past Appointments</h3>
                            </div>
                            <div class="appointments-list">
                                <?php if (empty($pastAppointments)): ?>
                                    <p class="no-appointments">No past appointments.</p>
                                <?php else: ?>
                                    <?php foreach ($pastAppointments as $appointment): ?>
                                        <div class="appointment-item past">
                                            <div class="appointment-date">
                                                <span class="day"><?php echo $appointment['day']; ?></span>
                                                <span class="month"><?php echo $appointment['month']; ?></span>
                                            </div>
                                            <div class="appointment-details">
                                                <h4><?php echo $appointment['title']; ?></h4>
                                                <p><i class='bx bx-time'></i> <?php echo $appointment['time']; ?></p>
                                                <p><i class='bx bx-user'></i> <?php echo $appointment['doctor']; ?></p>
                                                <p><i class='bx bx-map'></i> <?php echo $appointment['location']; ?></p>
                                                <span class="appointment-status <?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                            </div>
                                            <div class="appointment-actions">
                                                <button class="action-btn" title="View Details">
                                                    <i class='bx bx-detail'></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Appointments Calendar View -->
                <section class="appointments-section view-section" id="calendar-view">
                    <div class="content-card">
                        <div class="calendar-header">
                            <div class="calendar-navigation">
                                <button class="calendar-nav-btn">
                                    <i class='bx bx-chevron-left'></i>
                                </button>
                                <h3><?php echo $currentMonth; ?></h3>
                                <button class="calendar-nav-btn">
                                    <i class='bx bx-chevron-right'></i>
                                </button>
                            </div>
                            <div class="calendar-view-options">
                                <button class="calendar-view-btn active" data-view="month">Month</button>
                                <button class="calendar-view-btn" data-view="week">Week</button>
                                <button class="calendar-view-btn" data-view="day">Day</button>
                            </div>
                        </div>
                        <div class="calendar-container">
                            <?php include '../assets/includes/calendar.php'; ?>
                        </div>
                        <div class="selected-date-appointments">
                            <h4><?php echo $selectedDate; ?></h4>
                            <?php foreach ($upcomingAppointments as $appointment): ?>
                                <div class="day-appointment-item">
                                    <div class="appointment-time"><?php echo $appointment['time']; ?></div>
                                    <div class="appointment-info">
                                        <h5><?php echo $appointment['title']; ?></h5>
                                        <p><?php echo $appointment['doctor']; ?> - <?php echo $appointment['location']; ?></p>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Appointment Booking Modal -->
            <?php include '../assets/includes/booking_modal.php'; ?>

            <!-- Booking Confirmation Modal -->
            <?php include '../assets/includes/confirmation_modal.php'; ?>
            
            <!-- Dashboard Footer -->
            <?php include '../assets/includes/footer.php'; ?>
        </main>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/appointments.js"></script>
</body>
</html>