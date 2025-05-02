<?php
// Start session for user authentication
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Redirect to login page if not logged in or not an admin
    header('Location: login.php');
    exit();
}

// Initialize filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';
$doctorFilter = isset($_GET['doctor']) ? $_GET['doctor'] : 'all';
$serviceFilter = isset($_GET['service']) ? $_GET['service'] : 'all';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';

// Prepare base SQL query
$sql = "SELECT a.*, 
        u_patient.first_name as patient_first_name, u_patient.last_name as patient_last_name,
        u_doctor.first_name as doctor_first_name, u_doctor.last_name as doctor_last_name,
        s.name as service_name, l.name as location_name
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

// Fetch all doctors for the filter dropdown
$doctorsSql = "SELECT d.doctor_id, u.first_name, u.last_name 
               FROM doctors d
               JOIN users u ON d.user_id = u.user_id
               ORDER BY u.last_name, u.first_name";
$doctorsResult = $conn->query($doctorsSql);

// Fetch all services for the filter dropdown
$servicesSql = "SELECT service_id, name FROM services ORDER BY name";
$servicesResult = $conn->query($servicesSql);

// Fetch all locations for the appointment form
$locationsSql = "SELECT location_id, name FROM locations WHERE is_active = 1 ORDER BY name";
$locationsResult = $conn->query($locationsSql);

// Get current date for calendar highlighting
$currentDate = date('Y-m-d');
$currentMonth = date('F Y');
$currentYear = date('Y');
$currentMonthNum = date('m');

// For pagination
$totalAppointments = $result ? $result->num_rows : 0;
$appointmentsPerPage = 10;
$totalPages = ceil($totalAppointments / $appointmentsPerPage);
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $appointmentsPerPage;

// Add limit to the query for pagination
$sqlWithLimit = $sql . " LIMIT $offset, $appointmentsPerPage";
$paginatedResult = $conn->query($sqlWithLimit);

// Function to get initials from name
function getInitials($firstName, $lastName) {
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

// Function to format date
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Function to format time
function formatTime($time) {
    return date('g:i A', strtotime($time));
}

// Function to determine if an appointment is in the past
function isPastAppointment($date, $time) {
    $appointmentDateTime = strtotime($date . ' ' . $time);
    return $appointmentDateTime < time();
}

// Group appointments by date for calendar view
$appointmentsByDate = [];
if ($result) {
    mysqli_data_seek($result, 0); // Reset result pointer
    while ($row = $result->fetch_assoc()) {
        $date = $row['appointment_date'];
        if (!isset($appointmentsByDate[$date])) {
            $appointmentsByDate[$date] = [];
        }
        $appointmentsByDate[$date][] = $row;
    }
}

// Get admin user details
$adminId = $_SESSION['user_id'];
$adminQuery = "SELECT first_name, last_name FROM users WHERE user_id = $adminId";
$adminResult = mysqli_query($conn, $adminQuery);

$adminName = 'Admin User';
$adminInitials = 'AD';

if ($adminResult && $adminRow = mysqli_fetch_assoc($adminResult)) {
    $adminName = $adminRow['first_name'] . ' ' . $adminRow['last_name'];
    $adminInitials = strtoupper(substr($adminRow['first_name'], 0, 1) . substr($adminRow['last_name'], 0, 1));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments Management - SamaCare Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_appointments.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
<!-- Dashboard Layout -->
<div class="dashboard-container">
    <!-- Sidebar Navigation -->
    <aside class="sidebar admin-sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class='bx bx-plus-medical'></i>SAMA<span>CARE</span>
            </div>
            <button class="close-sidebar">
                <i class='bx bx-x'></i>
            </button>
        </div>

        <div class="user-profile">
            <div class="user-avatar admin">
                <span><?= htmlspecialchars($adminInitials) ?></span>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars($adminName) ?></h4>
                <p>Administrator</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5>Main</h5>
                <ul>
                    <li>
                        <a href="admin_dashboard.php">
                            <i class='bx bx-grid-alt'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5>Management</h5>
                <ul>
                    <li>
                        <a href="admin_users.php">
                            <i class='bx bx-user'></i>
                            <span>User Management</span>
                        </a>
                    </li>
                    <li>
                        <a href="admin_doctors.php">
                            <i class='bx bx-plus-medical'></i>
                            <span>Doctors</span>
                        </a>
                    </li>
                    <li class="active">
                        <a href="admin_appointments.php">
                            <i class='bx bx-calendar'></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                </ul>
            </div>
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
                <h1>Appointments Management</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search appointments..." id="appointment-search">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small admin">
                                <span><?= htmlspecialchars($adminInitials) ?></span>
                            </div>
                            <span class="user-name"><?= htmlspecialchars($adminName) ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Appointments Management Content -->
        <div class="dashboard-content">
            <!-- Action Bar -->
            <section class="action-bar">
                <div class="action-filters">
                    <div class="action-left">


                        <form id="filter-form" method="GET" action="">
                            <div class="filter-dropdown">
                                <select class="filter-select" id="status-filter" name="status" onchange="this.form.submit()">
                                    <option value="all" <?php echo ($statusFilter == 'all') ? 'selected' : ''; ?>>All Status</option>
                                    <option value="confirmed" <?php echo ($statusFilter == 'confirmed') ? 'selected' : ''; ?>>Confirmed</option>
                                    <option value="pending" <?php echo ($statusFilter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                    <option value="completed" <?php echo ($statusFilter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                    <option value="cancelled" <?php echo ($statusFilter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>

                            <div class="filter-dropdown">
                                <select class="filter-select" id="doctor-filter" name="doctor" onchange="this.form.submit()">
                                    <option value="all">All Doctors</option>
                                    <?php
                                    // Reset doctor result pointer
                                    mysqli_data_seek($doctorsResult, 0);
                                    if ($doctorsResult && $doctorsResult->num_rows > 0) {
                                        while ($doctor = $doctorsResult->fetch_assoc()) {
                                            $selected = ($doctorFilter == $doctor['doctor_id']) ? 'selected' : '';
                                            echo "<option value='".$doctor['doctor_id']."' $selected>Dr. ".$doctor['first_name']." ".$doctor['last_name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="filter-dropdown">
                                <select class="filter-select" id="service-filter" name="service" onchange="this.form.submit()">
                                    <option value="all">All Services</option>
                                    <?php
                                    // Reset service result pointer
                                    mysqli_data_seek($servicesResult, 0);
                                    if ($servicesResult && $servicesResult->num_rows > 0) {
                                        while ($service = $servicesResult->fetch_assoc()) {
                                            $selected = ($serviceFilter == $service['service_id']) ? 'selected' : '';
                                            echo "<option value='".$service['service_id']."' $selected>".$service['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="secondary-filters">
                    <div class="date-filter">
                        <form id="date-filter-form" method="GET" action="">
                            <div class="date-inputs">
                                <input type="date" id="date-start" name="start_date" value="<?php echo $startDate; ?>" class="date-input">
                                <span>to</span>
                                <input type="date" id="date-end" name="end_date" value="<?php echo $endDate; ?>" class="date-input">
                                <button type="submit" class="btn apply-date-btn">Apply</button>
                            </div>
                            <!-- Hidden inputs to preserve other filters -->
                            <input type="hidden" name="status" value="<?php echo $statusFilter; ?>">
                            <input type="hidden" name="doctor" value="<?php echo $doctorFilter; ?>">
                            <input type="hidden" name="service" value="<?php echo $serviceFilter; ?>">
                        </form>
                    </div>

                    <div class="view-toggle">
                        <button class="view-btn active" data-view="list">
                            <i class='bx bx-list-ul'></i>
                        </button>
                    </div>
                </div>
            </section>

            <!-- Appointments List View -->
            <section class="appointments-section view-section active" id="list-view">
                <div class="content-card">
                    <div class="card-header with-actions">
                        <h3>All Appointments</h3>
                        <div class="header-actions">
                            <button class="btn icon-btn" onclick="window.location.reload()">
                                <i class='bx bx-refresh'></i>
                            </button>
                            <button class="btn icon-btn" id="export-appointments">
                                <i class='bx bx-download'></i>
                            </button>
                        </div>
                    </div>

                    <div class="table-container">
                        <table class="appointments-table">
                            <thead>
                            <tr>
                                <th>Date & Time</th>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Service</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            if ($paginatedResult && $paginatedResult->num_rows > 0) {
                                while ($row = $paginatedResult->fetch_assoc()) {
                                    $isPast = isPastAppointment($row['appointment_date'], $row['start_time']);
                                    $pastClass = $isPast ? "past-appointment" : "";
                                    $patientInitials = getInitials($row['patient_first_name'], $row['patient_last_name']);
                                    $doctorInitials = getInitials($row['doctor_first_name'], $row['doctor_last_name']);
                                    ?>
                                    <tr class="<?php echo $pastClass; ?>" data-appointment-id="<?php echo $row['appointment_id']; ?>">
                                        <td>
                                            <div class="date-time">
                                                <div class="date"><?php echo formatDate($row['appointment_date']); ?></div>
                                                <div class="time"><?php echo formatTime($row['start_time']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar"><?php echo $patientInitials; ?></div>
                                                <span><?php echo $row['patient_first_name'] . ' ' . $row['patient_last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar doctor"><?php echo $doctorInitials; ?></div>
                                                <span>Dr. <?php echo $row['doctor_first_name'] . ' ' . $row['doctor_last_name']; ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo $row['service_name']; ?></td>
                                        <td><?php echo $row['location_name']; ?></td>
                                        <td><span class="status-badge <?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn icon-btn sm view-appointment" title="View Details" data-id="<?php echo $row['appointment_id']; ?>">
                                                    <i class='bx bx-show'></i>
                                                </button>
                                                <?php if (!$isPast && $row['status'] != 'completed' && $row['status'] != 'cancelled') { ?>
                                                    <button class="btn icon-btn sm edit-appointment" title="Edit" data-id="<?php echo $row['appointment_id']; ?>">
                                                        <i class='bx bx-edit'></i>
                                                    </button>
                                                <?php } ?>
                                                <button class="btn icon-btn sm more-options" title="More Options" data-id="<?php echo $row['appointment_id']; ?>">
                                                    <i class='bx bx-dots-vertical-rounded'></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                echo '<tr><td colspan="7" class="no-data">No appointments found</td></tr>';
                            }
                            ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalAppointments > 0) { ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                <span>Showing <?php echo ($offset + 1); ?>-<?php echo min($offset + $appointmentsPerPage, $totalAppointments); ?> of <?php echo $totalAppointments; ?> appointments</span>
                            </div>
                            <div class="pagination-controls">
                                <button class="pagination-btn" <?php echo ($currentPage <= 1) ? 'disabled' : ''; ?> onclick="changePage(<?php echo $currentPage - 1; ?>)">
                                    <i class='bx bx-chevron-left'></i>
                                </button>

                                <?php
                                $startPage = max(1, $currentPage - 2);
                                $endPage = min($startPage + 4, $totalPages);

                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    $activeClass = ($i == $currentPage) ? 'active' : '';
                                    echo "<button class='pagination-btn $activeClass' onclick='changePage($i)'>$i</button>";
                                }
                                ?>

                                <button class="pagination-btn" <?php echo ($currentPage >= $totalPages) ? 'disabled' : ''; ?> onclick="changePage(<?php echo $currentPage + 1; ?>)">
                                    <i class='bx bx-chevron-right'></i>
                                </button>
                            </div>
                        </div>
                    <?php } ?>
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
                            <h3><?php echo $currentMonth; ?></h3>
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
                        <div class="calendar-days">
                            <?php
                            // Get first day of the current month
                            $firstDayOfMonth = mktime(0, 0, 0, $currentMonthNum, 1, $currentYear);
                            $firstDayWeekday = date('w', $firstDayOfMonth);

                            // Get last day of the previous month
                            $lastDayPrevMonth = date('t', mktime(0, 0, 0, $currentMonthNum - 1, 1, $currentYear));

                            // Output previous month days
                            for ($i = $firstDayWeekday - 1; $i >= 0; $i--) {
                                $day = $lastDayPrevMonth - $i;
                                echo "<div class='calendar-day other-month'>$day</div>";
                            }

                            // Output current month days
                            $daysInMonth = date('t', $firstDayOfMonth);
                            for ($day = 1; $day <= $daysInMonth; $day++) {
                                $dateKey = sprintf('%04d-%02d-%02d', $currentYear, $currentMonthNum, $day);
                                $hasAppointment = isset($appointmentsByDate[$dateKey]) ? 'has-appointment' : '';
                                $isCurrentDate = ($dateKey == $currentDate) ? 'current-date' : '';
                                $appointmentCount = isset($appointmentsByDate[$dateKey]) ? count($appointmentsByDate[$dateKey]) : 0;

                                echo "<div class='calendar-day $hasAppointment $isCurrentDate' data-date='$dateKey'>";
                                echo "<span>$day</span>";
                                if ($appointmentCount > 0) {
                                    echo "<span class='appointment-count'>$appointmentCount</span>";
                                }
                                echo "</div>";
                            }

                            // Calculate how many next month days we need to show
                            $totalDaysShown = $firstDayWeekday + $daysInMonth;
                            $nextMonthDays = 42 - $totalDaysShown; // 42 = 6 rows of 7 days

                            // Output next month days
                            for ($day = 1; $day <= $nextMonthDays; $day++) {
                                echo "<div class='calendar-day other-month'>$day</div>";
                            }
                            ?>
                        </div>
                    </div>

                    <div class="selected-date-appointments">
                        <h4><?php echo date('F j, Y'); ?> Appointments</h4>
                        <div class="day-appointments-list" id="selected-day-appointments">
                            <?php
                            // Show appointments for the current date by default
                            if (isset($appointmentsByDate[$currentDate])) {
                                foreach ($appointmentsByDate[$currentDate] as $appointment) {
                                    $patientName = $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name'];
                                    $doctorName = 'Dr. ' . $appointment['doctor_first_name'] . ' ' . $appointment['doctor_last_name'];
                                    ?>
                                    <div class="day-appointment-item" data-id="<?php echo $appointment['appointment_id']; ?>">
                                        <div class="appointment-time"><?php echo formatTime($appointment['start_time']); ?></div>
                                        <div class="appointment-info">
                                            <div class="appointment-main">
                                                <h5><?php echo $appointment['service_name']; ?></h5>
                                                <span class="status-badge <?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                            </div>
                                            <div class="appointment-people">
                                                <p><i class='bx bx-user'></i> <?php echo $patientName; ?></p>
                                                <p><i class='bx bx-user-circle'></i> <?php echo $doctorName; ?></p>
                                            </div>
                                        </div>
                                        <div class="appointment-actions">
                                            <button class="btn icon-btn sm view-appointment" title="View Details" data-id="<?php echo $appointment['appointment_id']; ?>">
                                                <i class='bx bx-show'></i>
                                            </button>
                                            <?php if ($appointment['status'] != 'completed' && $appointment['status'] != 'cancelled') { ?>
                                                <button class="btn icon-btn sm edit-appointment" title="Edit" data-id="<?php echo $appointment['appointment_id']; ?>">
                                                    <i class='bx bx-edit'></i>
                                                </button>
                                            <?php } ?>
                                        </div>
                                    </div>
                                    <?php
                                }
                            } else {
                                echo '<div class="no-appointments">No appointments scheduled for this date</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Appointment Details Modal -->
        <div class="modal" id="appointment-details-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Appointment Details</h2>
                    <button class="modal-close"><i class='bx bx-x'></i></button>
                </div>
                <div class="modal-body" id="appointment-details-content">
                    <!-- Content will be loaded dynamically via AJAX -->
                    <div class="loading-spinner">
                        <i class='bx bx-loader-alt bx-spin'></i>
                        <p>Loading appointment details...</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Appointment Edit/Create Modal -->
        <div class="modal" id="appointment-edit-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="appointment-modal-title">Schedule Appointment</h2>
                    <button class="modal-close"><i class='bx bx-x'></i></button>
                </div>
                <div class="modal-body">
                    <form id="appointment-form" method="post" action="../actions/process_appointment.php">
                        <input type="hidden" id="appointment_id" name="appointment_id" value="">

                        <div class="form-row">
                            <div class="form-group">
                                <label>Patient</label>
                                <select class="form-control" id="patient_id" name="patient_id" required>
                                    <option value="">Select Patient</option>
                                    <?php
                                    // Fetch all patients
                                    $patientsSql = "SELECT p.patient_id, u.first_name, u.last_name 
                                                      FROM patients p
                                                      JOIN users u ON p.user_id = u.user_id
                                                      ORDER BY u.last_name, u.first_name";
                                    $patientsResult = $conn->query($patientsSql);

                                    if ($patientsResult && $patientsResult->num_rows > 0) {
                                        while ($patient = $patientsResult->fetch_assoc()) {
                                            echo "<option value='".$patient['patient_id']."'>".$patient['first_name']." ".$patient['last_name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Doctor</label>
                                <select class="form-control" id="doctor_id" name="doctor_id" required>
                                    <option value="">Select Doctor</option>
                                    <?php
                                    // Reset doctor result pointer
                                    mysqli_data_seek($doctorsResult, 0);
                                    if ($doctorsResult && $doctorsResult->num_rows > 0) {
                                        while ($doctor = $doctorsResult->fetch_assoc()) {
                                            echo "<option value='".$doctor['doctor_id']."'>Dr. ".$doctor['first_name']." ".$doctor['last_name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <!-- Continue from where the second document left off -->
                        <div class="form-row">
                            <div class="form-group">
                                <label>Service</label>
                                <select class="form-control" id="service_id" name="service_id" required>
                                    <option value="">Select Service</option>
                                    <?php
                                    // Reset service result pointer
                                    mysqli_data_seek($servicesResult, 0);
                                    if ($servicesResult && $servicesResult->num_rows > 0) {
                                        while ($service = $servicesResult->fetch_assoc()) {
                                            echo "<option value='".$service['service_id']."'>".$service['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Location</label>
                                <select class="form-control" id="location_id" name="location_id" required>
                                    <option value="">Select Location</option>
                                    <?php
                                    // Reset location result pointer
                                    mysqli_data_seek($locationsResult, 0);
                                    if ($locationsResult && $locationsResult->num_rows > 0) {
                                        while ($location = $locationsResult->fetch_assoc()) {
                                            echo "<option value='".$location['location_id']."'>".$location['name']."</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" required>
                            </div>
                            <div class="form-group">
                                <label>Time</label>
                                <input type="time" class="form-control" id="appointment_time" name="appointment_time" required>
                            </div>
                            <div class="form-group">
                                <label>Duration</label>
                                <select class="form-control" id="duration" name="duration" required>
                                    <option value="15">15 minutes</option>
                                    <option value="30" selected>30 minutes</option>
                                    <option value="45">45 minutes</option>
                                    <option value="60">60 minutes</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Status</label>
                                <select class="form-control" id="status" name="status" required>
                                    <option value="confirmed">Confirmed</option>
                                    <option value="pending">Pending</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea class="form-control" id="notes" name="notes" placeholder="Add appointment notes..."></textarea>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn secondary-btn" id="cancel-btn">Cancel</button>
                            <button type="submit" class="btn primary-btn">Save Appointment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Appointment Actions Context Menu -->
        <div class="context-menu" id="appointment-actions-menu">
            <ul>
                <li data-action="view"><i class='bx bx-show'></i> View Details</li>
                <li data-action="edit"><i class='bx bx-edit'></i> Edit Appointment</li>
                <li data-action="complete"><i class='bx bx-check-circle'></i> Mark as Completed</li>
                <li class="with-divider danger" data-action="cancel"><i class='bx bx-x-circle'></i> Cancel Appointment</li>
            </ul>
        </div>
    </main>
</div>


<script src="../assets/js/admin_appointments.js"></script>
<script>
    function changePage(page) {
        // Get current URL parameters
        const urlParams = new URLSearchParams(window.location.search);

        // Set page parameter
        urlParams.set('page', page);

        // Redirect to the new URL
        window.location.href = window.location.pathname + '?' + urlParams.toString();
    }

    // Set current values in the filter form on page load
    document.addEventListener('DOMContentLoaded', function() {
        // For date filter form
        const urlParams = new URLSearchParams(window.location.search);

        // Set values for hidden inputs
        document.querySelector('input[name="status"]').value =
            urlParams.get('status') || 'all';
        document.querySelector('input[name="doctor"]').value =
            urlParams.get('doctor') || 'all';
        document.querySelector('input[name="service"]').value =
            urlParams.get('service') || 'all';

        // Set selected options for dropdowns
        const statusFilter = urlParams.get('status') || 'all';
        const doctorFilter = urlParams.get('doctor') || 'all';
        const serviceFilter = urlParams.get('service') || 'all';

        if (document.getElementById('status-filter')) {
            document.getElementById('status-filter').value = statusFilter;
        }
        if (document.getElementById('doctor-filter')) {
            document.getElementById('doctor-filter').value = doctorFilter;
        }
        if (document.getElementById('service-filter')) {
            document.getElementById('service-filter').value = serviceFilter;
        }
    });
</script>
</body>
</html>