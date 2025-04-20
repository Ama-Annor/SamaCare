<?php
session_start();
require_once '../db/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"])) {
    header("Location: login.php");
    exit;
}

// Fetch basic user data from session
$user_id = $_SESSION["user_id"];
$first_name = $_SESSION["first_name"];
$last_name = $_SESSION["last_name"];
$role_id = $_SESSION["role_id"];

// Initialize arrays
$appointments = [];
$upcoming_appointments = [];
$past_appointments = [];

// Fetch available services
$services = [];
try {
    $stmt = $conn->prepare("SELECT service_id, name, description, duration, default_cost, specialty_id FROM services");
    $stmt->execute();
    $services = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching services: " . $e->getMessage());
}

// Fetch specialties for doctor filtering
$specialties = [];
try {
    $stmt = $conn->prepare("SELECT * FROM specialties");
    $stmt->execute();
    $specialties = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching specialties: " . $e->getMessage());
}

// Fetch locations
$locations = [];
try {
    $stmt = $conn->prepare("SELECT * FROM locations");
    $stmt->execute();
    $locations = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Error fetching locations: " . $e->getMessage());
}

// AJAX handlers
if(isset($_GET['action'])) {
    $response = [];

    // Get doctors by specialty
    if($_GET['action'] == 'get_doctors') {
        try {
            $specialty_id = $_GET['specialty_id'];
            $stmt = $conn->prepare("
                SELECT d.doctor_id, CONCAT(u.first_name, ' ', u.last_name) AS full_name, 
                s.name AS specialty, d.rating
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                JOIN specialties s ON d.specialty_id = s.specialty_id
                WHERE d.specialty_id = ?
            ");
            $stmt->bind_param("i", $specialty_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $doctors = $result->fetch_all(MYSQLI_ASSOC);

            // Add initials for display
            foreach($doctors as &$doctor) {
                $name_parts = explode(' ', $doctor['full_name']);
                $doctor['initials'] = strtoupper(substr($name_parts[0], 0, 1) . substr($name_parts[1], 0, 1));
            }

            header('Content-Type: application/json');
            echo json_encode($doctors);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    // Get available time slots for a doctor on a specific date
    if($_GET['action'] == 'get_slots') {
        try {
            $doctor_id = $_GET['doctor_id'];
            $date = $_GET['date'];

            // First get the doctor's schedule
            $stmt = $conn->prepare("
                SELECT start_time, end_time 
                FROM doctor_schedules
                WHERE doctor_id = ?
            ");
            $stmt->bind_param("i", $doctor_id);
            $stmt->execute();
            $schedule = $stmt->get_result()->fetch_assoc();

            if(!$schedule) {
                // Default schedule if not found
                $schedule = [
                    'work_start' => '09:00:00',
                    'work_end' => '17:00:00',
                    'slot_duration' => 30 // minutes
                ];
            }

            // Get existing appointments for that day
            $stmt = $conn->prepare("
                SELECT start_time, end_time
                FROM appointments
                WHERE doctor_id = ? AND appointment_date = ?
            ");
            $stmt->bind_param("is", $doctor_id, $date);
            $stmt->execute();
            $booked_slots = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Generate available slots
            $slots = [];
            $current_slot = new DateTime($schedule['work_start']);
            $end_time = new DateTime($schedule['work_end']);

            $morning_slots = ['times' => []];
            $afternoon_slots = ['times' => []];

            while($current_slot < $end_time) {
                $slot_start = $current_slot->format('H:i');

                // Increment by slot duration
                $current_slot->add(new DateInterval('PT' . $schedule['slot_duration'] . 'M'));
                $slot_end = $current_slot->format('H:i');

                // Check if slot is already booked
                $booked = false;
                foreach($booked_slots as $booked_slot) {
                    if($slot_start == substr($booked_slot['start_time'], 0, 5)) {
                        $booked = true;
                        break;
                    }
                }

                $slot = [
                    'start' => $slot_start,
                    'end' => $slot_end,
                    'booked' => $booked
                ];

                // Sort into morning and afternoon
                if(intval(explode(':', $slot_start)[0]) < 12) {
                    $morning_slots['times'][] = $slot;
                } else {
                    $afternoon_slots['times'][] = $slot;
                }
            }

            $slots = [$morning_slots, $afternoon_slots];

            header('Content-Type: application/json');
            echo json_encode($slots);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }

    if($_GET['action'] == 'cancel_appointment') {
        try {
            $appointment_id = $_GET['appointment_id'];
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointment_id);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                header('Content-Type: application/json');
                echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'error' => 'No appointment found with that ID']);
            }
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }

    // Get calendar data for a specific month
    if($_GET['action'] == 'get_calendar') {
        try {
            $month = $_GET['month'];
            $year = $_GET['year'];

            // First get patient ID from user
            $stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
            $stmt_patient->bind_param("i", $user_id);
            $stmt_patient->execute();
            $patient = $stmt_patient->get_result()->fetch_assoc();
            $patient_id = $patient['patient_id'];

            // Get all appointments for this patient in the specified month
            $stmt = $conn->prepare("
                SELECT DAY(appointment_date) as day, appointment_id
                FROM appointments
                WHERE patient_id = ? AND MONTH(appointment_date) = ? AND YEAR(appointment_date) = ?
            ");
            $stmt->bind_param("iii", $patient_id, $month, $year);
            $stmt->execute();
            $result = $stmt->get_result();
            $appointments = $result->fetch_all(MYSQLI_ASSOC);

            header('Content-Type: application/json');
            echo json_encode($appointments);
            exit;
        } catch (Exception $e) {
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // First get patient ID from user
        $stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
        $stmt_patient->bind_param("i", $user_id);
        $stmt_patient->execute();
        $patient = $stmt_patient->get_result()->fetch_assoc();
        $patient_id = $patient['patient_id'];

        // Parse time slot
        $time_parts = explode('-', $_POST['time_slot']);
        $start_time = trim($time_parts[0]);
        $end_time = trim($time_parts[1]);

        $stmt = $conn->prepare("INSERT INTO appointments 
            (patient_id, doctor_id, service_id, location_id, appointment_date, start_time, end_time, status, notes)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");

        $stmt->bind_param("iiiissss",
            $patient_id,
            $_POST['doctor_id'],
            $_POST['service_id'],
            $_POST['location_id'],
            $_POST['appointment_date'],
            $start_time,
            $end_time,
            $_POST['notes']
        );

        $stmt->execute();
        $response = ['success' => true, 'message' => 'Appointment booked successfully'];
    } catch (Exception $e) {
        $response = ['success' => false, 'error' => 'Error booking appointment: ' . $e->getMessage()];
    }
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

try {
    // First get patient ID from users->patients relationship
    $stmt_patient = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
    $stmt_patient->bind_param("i", $user_id);
    $stmt_patient->execute();
    $result_patient = $stmt_patient->get_result();

    if ($result_patient->num_rows === 0) {
        throw new Exception("No patient record found for this user.");
    }

    $patient = $result_patient->fetch_assoc();
    $patient_id = $patient['patient_id'];
    $stmt_patient->close();

    // Now fetch appointments with proper joins
    $stmt = $conn->prepare("
        SELECT 
            a.appointment_id,
            a.appointment_date,
            a.start_time,
            a.end_time,
            a.status,
            a.notes,
            CONCAT(u.first_name, ' ', u.last_name) AS doctor_name,
            s.name AS service_name,
            l.name AS location_name,
            l.address AS location_address
        FROM appointments a
        INNER JOIN doctors d ON a.doctor_id = d.doctor_id
        INNER JOIN users u ON d.user_id = u.user_id
        INNER JOIN services s ON a.service_id = s.service_id
        INNER JOIN locations l ON a.location_id = l.location_id
        WHERE a.patient_id = ?
        ORDER BY a.appointment_date DESC, a.start_time DESC
    ");

    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Initialize cancelled appointments array
    $cancelled_appointments = [];

    // Process appointments
    while ($row = $result->fetch_assoc()) {
        // If status is cancelled, add to cancelled appointments
        if (strtolower($row['status']) === 'cancelled') {
            $cancelled_appointments[] = $row;
        } else {
            $appointment_date = new DateTime($row['appointment_date']);
            $now = new DateTime();

            // Add time component to appointment date for accurate comparison
            $appointment_time = new DateTime($row['appointment_date'] . ' ' . $row['start_time']);

            if ($appointment_time >= $now) {
                $upcoming_appointments[] = $row;
            } else {
                $past_appointments[] = $row;
            }
        }
    }

    $stmt->close();

} catch (Exception $e) {
    error_log("Database error: " . $e->getMessage());
    $error_message = "Error retrieving appointments. Please try again later.";
}

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit;
}

// Close database connection
$conn->close();
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
                <span><?php echo strtoupper(substr($first_name, 0, 1) . substr($last_name, 0, 1)); ?></span>
            </div>
            <div class="user-info">
                <h4><?php echo htmlspecialchars("$first_name $last_name"); ?></h4>
                <p><?php echo ($role_id == 2) ? 'Patient' : 'Doctor'; ?></p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class='bx bx-home-alt'></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="medical_records.php">
                        <i class='bx bx-folder'></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li class="active">
                    <a href="appointments.php">
                        <i class='bx bx-calendar'></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="health_tracking.php">
                        <i class='bx bx-line-chart'></i>
                        <span>Health Tracking</span>
                    </a>
                </li>
                <li>
                    <a href="health-chat.php">
                        <i class='bx bx-chat'></i>
                        <span>Health Assistant</span>
                    </a>
                </li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="#" class="help-link">
                <i class='bx bx-help-circle'></i>
                <span>Help & Support</span>
            </a>
            <a href="?logout" class="logout-link">
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
                <h1>Appointments</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search appointments...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">2</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small">
                                <span><?php echo strtoupper(substr($first_name, 0, 1)); ?></span>
                            </div>
                            <span class="user-name"><?php echo htmlspecialchars($first_name); ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

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
                        <button class="btn secondary-btn status-filter-btn" onclick="toggleDropdown()">
                            <i class='bx bx-filter'></i>
                            <span>Status: All</span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                        <div class="filter-dropdown-content" id="statusDropdown">
                            <a href="#" data-status="all">All</a>
                            <a href="#" data-status="pending">Pending</a>
                            <a href="#" data-status="confirmed">Confirmed</a>
                            <a href="#" data-status="completed">Completed</a>
                            <a href="#" data-status="cancelled">Cancelled</a>
                        </div>
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
                        <div class="appointments-list" id="upcoming-appointments">
                            <?php foreach ($upcoming_appointments as $appointment): ?>
                                <div class="appointment-item" data-id="<?php echo $appointment['appointment_id']; ?>" data-status="<?php echo strtolower($appointment['status']); ?>">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                        <p><i class='bx bx-time'></i> <?php echo date('h:i A', strtotime($appointment['start_time'])); ?></p>
                                        <p><i class='bx bx-user'></i> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                        <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($appointment['location_name']); ?></p>
                                        <span class="appointment-status <?php echo strtolower($appointment['status']); ?>"><?php echo ucfirst($appointment['status']); ?></span>
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
                            <?php if (count($upcoming_appointments) === 0): ?>
                                <div class="no-appointments">
                                    <p>No upcoming appointments found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Past Appointments -->
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Past Appointments</h3>
                        </div>
                        <div class="appointments-list" id="past-appointments">
                            <?php foreach ($past_appointments as $appointment): ?>
                                <div class="appointment-item past" data-id="<?php echo $appointment['appointment_id']; ?>" data-status="<?php echo strtolower($appointment['status']); ?>">
                                    <div class="appointment-date">
                                        <span class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></span>
                                        <span class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                        <p><i class='bx bx-time'></i> <?php echo date('h:i A', strtotime($appointment['start_time'])); ?></p>
                                        <p><i class='bx bx-user'></i> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                        <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($appointment['location_name']); ?></p>
                                        <span class="appointment-status completed">Completed</span>
                                    </div>
                                    <div class="appointment-actions">
                                        <button class="action-btn" title="View Details">
                                            <i class='bx bx-detail'></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($past_appointments) === 0): ?>
                                <div class="no-appointments">
                                    <p>No past appointments found.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Cancelled Appointments -->
                <div class="content-card">
                    <div class="card-header">
                        <h3>Cancelled Appointments</h3>
                    </div>
                    <div class="appointments-list" id="cancelled-appointments">
                        <?php foreach ($cancelled_appointments as $appointment): ?>
                            <div class="appointment-item cancelled" data-id="<?php echo $appointment['appointment_id']; ?>" data-status="cancelled">
                                <div class="appointment-date">
                                    <span class="day"><?php echo date('d', strtotime($appointment['appointment_date'])); ?></span>
                                    <span class="month"><?php echo date('M', strtotime($appointment['appointment_date'])); ?></span>
                                </div>
                                <div class="appointment-details">
                                    <h4><?php echo htmlspecialchars($appointment['service_name']); ?></h4>
                                    <p><i class='bx bx-time'></i> <?php echo date('h:i A', strtotime($appointment['start_time'])); ?></p>
                                    <p><i class='bx bx-user'></i> Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?></p>
                                    <p><i class='bx bx-map'></i> <?php echo htmlspecialchars($appointment['location_name']); ?></p>
                                    <span class="appointment-status cancelled">Cancelled</span>
                                </div>
                                <div class="appointment-actions">
                                    <button class="action-btn" title="View Details">
                                        <i class='bx bx-detail'></i>
                                    </button>
                                    <button class="action-btn" title="Reschedule">
                                        <i class='bx bx-calendar-edit'></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($cancelled_appointments) === 0): ?>
                            <div class="no-appointments">
                                <p>No cancelled appointments found.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>

            <!-- Appointments Calendar View -->
            <section class="appointments-section view-section" id="calendar-view">
                <div class="content-card">
                    <div class="calendar-header">
                        <div class="calendar-navigation">
                            <button class="calendar-nav-btn" id="prev-month">
                                <i class='bx bx-chevron-left'></i>
                            </button>
                            <h3 id="calendar-month-year">April 2025</h3>
                            <button class="calendar-nav-btn" id="next-month">
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
                        <div class="calendar-weekdays">
                            <div>Sun</div>
                            <div>Mon</div>
                            <div>Tue</div>
                            <div>Wed</div>
                            <div>Thu</div>
                            <div>Fri</div>
                            <div>Sat</div>
                        </div>
                        <div class="calendar-days" id="calendar-days">
                            <!-- Calendar days will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="selected-date-appointments" id="selected-date-appointments">
                        <h4>Select a date to view appointments</h4>
                    </div>
                </div>
            </section>
        </div>
        <!-- Appointment Booking Modal -->
        <div class="booking-modal" id="booking-modal">
            <div class="form-card">
                <div class="form-header">
                    <h3>Book an Appointment</h3>
                    <button class="close-form" id="close-booking-modal">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="form-body">
                    <!-- Step 1: Service Selection -->
                    <div class="booking-step" id="step-1">
                        <h4 class="step-title">1. Select Service</h4>
                        <div class="form-group">
                            <label for="service-type">Service Type</label>
                            <select id="service-type" class="form-control">
                                <option value="">Select a service</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['service_id']; ?>"
                                            data-specialty="<?php echo $service['specialty_id']; ?>">
                                        <?php echo htmlspecialchars($service['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-navigation">
                            <div></div> <!-- Empty div for spacing -->
                            <button class="btn primary-btn next-step" data-step="1">Continue</button>
                        </div>
                    </div>

                    <!-- Step 2: Doctor Selection -->
                    <div class="booking-step" id="step-2" style="display: none;">
                        <h4 class="step-title">2. Select Doctor</h4>
                        <div class="form-group">
                            <label for="doctor">Select Doctor</label>
                            <select id="doctor" class="form-control">
                                <option value="">Select a doctor</option>
                                <!-- Will be populated via JavaScript -->
                            </select>
                        </div>
                        <div class="doctor-info" id="doctor-info" style="display: none;">
                            <!-- Will be populated via JavaScript -->
                        </div>
                        <div class="form-navigation">
                            <button class="btn secondary-btn prev-step" data-step="2">Back</button>
                            <button class="btn primary-btn next-step" data-step="2">Continue</button>
                        </div>
                    </div>

                    <!-- Step 3: Date & Time Selection -->
                    <div class="booking-step" id="step-3" style="display: none;">
                        <h4 class="step-title">3. Select Date & Time</h4>
                        <div class="form-group">
                            <label for="appointment-date">Date</label>
                            <input type="date" id="appointment-date" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Available Time Slots</label>
                            <div class="time-slots" id="time-slots">
                                <!-- Will be populated via JavaScript -->
                                <p>Please select a date to view available time slots.</p>
                            </div>
                        </div>
                        <div class="form-navigation">
                            <button class="btn secondary-btn prev-step" data-step="3">Back</button>
                            <button class="btn primary-btn next-step" data-step="3">Continue</button>
                        </div>
                    </div>

                    <!-- Step 4: Additional Information -->
                    <div class="booking-step" id="step-4" style="display: none;">
                        <h4 class="step-title">4. Additional Information</h4>
                        <div class="form-group">
                            <label for="appointment-reason">Reason for Visit</label>
                            <textarea id="appointment-reason" class="form-control" rows="3" placeholder="Please describe your symptoms or reason for this appointment"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="appointment-location">Clinic Location</label>
                            <select id="appointment-location" class="form-control">
                                <?php foreach ($locations as $location): ?>
                                    <option value="<?php echo $location['location_id']; ?>">
                                        <?php echo htmlspecialchars($location['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-navigation">
                            <button class="btn secondary-btn prev-step" data-step="4">Back</button>
                            <button class="btn primary-btn next-step" data-step="4">Review</button>
                        </div>
                    </div>

                    <!-- Step 5: Review and Confirm -->
                    <div class="booking-step" id="step-5" style="display: none;">
                        <h4 class="step-title">5. Review and Confirm</h4>
                        <div class="booking-summary">
                            <div class="summary-item">
                                <span class="summary-label">Service:</span>
                                <span class="summary-value" id="summary-service"></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Doctor:</span>
                                <span class="summary-value" id="summary-doctor"></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Date:</span>
                                <span class="summary-value" id="summary-date"></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Time:</span>
                                <span class="summary-value" id="summary-time"></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Location:</span>
                                <span class="summary-value" id="summary-location"></span>
                            </div>
                            <div class="summary-item">
                                <span class="summary-label">Reason:</span>
                                <span class="summary-value" id="summary-reason"></span>
                            </div>
                        </div>
                        <div class="form-navigation">
                            <button class="btn secondary-btn prev-step" data-step="5">Back</button>
                            <button class="btn primary-btn" id="confirm-booking">Confirm Booking</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </main>
</div>

<script>
    function toggleDropdown() {
        document.getElementById("statusDropdown").classList.toggle("show");
    }

    // Close dropdown if clicked outside
    window.onclick = function(event) {
        if (!event.target.closest('.filter-dropdown')) {
            let dropdowns = document.querySelectorAll(".filter-dropdown-content");
            dropdowns.forEach(dd => dd.classList.remove("show"));
        }
    }

    // Automatically close dropdown when an option is clicked
    document.querySelectorAll('#statusDropdown a').forEach(item => {
        item.addEventListener('click', () => {
            document.getElementById("statusDropdown").classList.remove("show");
        });
    });
</script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/appointments.js"></script>
</body>
</html>