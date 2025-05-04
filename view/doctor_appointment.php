<?php
// Start session for user authentication
session_start();

// Check if doctor is logged in, redirect to login page if not
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header('Location: login.php');
    exit();
}

// Database connection

require_once '../db/db_connect.php';

// Get doctor information
$doctor_id = null;
$doctor_info = null;

// Get doctor ID from user ID
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT d.doctor_id, u.first_name, u.last_name, u.profile_image, s.name as specialty
                        FROM doctors d
                        JOIN users u ON d.user_id = u.user_id
                        LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                        WHERE d.user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result(); // gather doctor information fro db

// confirm if prompt fetched any results 
if ($result->num_rows > 0) {
    $doctor_info = $result->fetch_assoc();
    $doctor_id = $doctor_info['doctor_id'];
    $doctor_name = "Dr. " . $doctor_info['first_name'] . " " . $doctor_info['last_name']; // fetch doctor name 
    $doctor_specialty = $doctor_info['specialty'] ?? 'Doctor'; // speciality column is not in database yet 
    $profile_image = $doctor_info['profile_image'];
    // Get doctor's initials for avatar if no profile image
    $initials = strtoupper(substr($doctor_info['first_name'], 0, 1) . substr($doctor_info['last_name'], 0, 1));
} else {
    // Redirect if no doctor information is found
    header('Location: ../error.php?msg=doctor_not_found');
    exit();
}

// Get appointment data with filters
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$service_filter = isset($_GET['service']) ? $_GET['service'] : 'all';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';

// Base query for selecting patient data
$query = "SELECT a.*, 
          p.patient_id, 
          u_patient.first_name as patient_first_name, 
          u_patient.last_name as patient_last_name,
          s.name as service_name,
          l.name as location_name
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_id
          JOIN users u_patient ON p.user_id = u_patient.user_id
          JOIN services s ON a.service_id = s.service_id
          JOIN locations l ON a.location_id = l.location_id
          WHERE a.doctor_id = ?";

// Add filters
$params = [$doctor_id];
$types = "i";

// add specific filter to fetch desired results  
if ($status_filter != 'all') {
    $query .= " AND a.status = ?";
    $params[] = $status_filter;
    $types .= "s";
}

if ($service_filter != 'all') {
    $query .= " AND a.service_id = ?";
    $params[] = $service_filter;
    $types .= "i";
}

if (!empty($date_start) && !empty($date_end)) {
    $query .= " AND a.appointment_date BETWEEN ? AND ?";
    $params[] = $date_start;
    $params[] = $date_end;
    $types .= "ss";
} elseif (!empty($date_start)) {
    $query .= " AND a.appointment_date >= ?";
    $params[] = $date_start;
    $types .= "s";
} elseif (!empty($date_end)) {
    $query .= " AND a.appointment_date <= ?";
    $params[] = $date_end;
    $types .= "s";
}

$query .= " ORDER BY a.appointment_date ASC, a.start_time ASC";

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;


// Instead of using str_replace, create a dedicated count query
$count_query = "SELECT COUNT(*) as total 
                FROM appointments a
                JOIN patients p ON a.patient_id = p.patient_id
                JOIN users u_patient ON p.user_id = u_patient.user_id
                JOIN services s ON a.service_id = s.service_id
                JOIN locations l ON a.location_id = l.location_id
                WHERE a.doctor_id = ?";

// Add the same filters as the main query
if ($status_filter != 'all') {
    $count_query .= " AND a.status = ?";
}

if ($service_filter != 'all') {
    $count_query .= " AND a.service_id = ?";
}

if (!empty($date_start) && !empty($date_end)) {
    $count_query .= " AND a.appointment_date BETWEEN ? AND ?";
} elseif (!empty($date_start)) {
    $count_query .= " AND a.appointment_date >= ?";
} elseif (!empty($date_end)) {
    $count_query .= " AND a.appointment_date <= ?";
}

// Now prepare and execute the count query
$stmt = $conn->prepare($count_query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total_result = $stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_appointments = $total_row['total'];
$total_pages = ceil($total_appointments / $limit);

// Add pagination to query
$query .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$types .= "ii";

// Execute final query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$appointments_result = $stmt->get_result();

// Get services for dropdown
$services_query = "SELECT service_id, name FROM services ORDER BY name";
$services_result = $conn->query($services_query);
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}

// Get locations for dropdown
$locations_query = "SELECT location_id, name FROM locations WHERE is_active = 1 ORDER BY name";
$locations_result = $conn->query($locations_query);
$locations = [];
while ($row = $locations_result->fetch_assoc()) {
    $locations[] = $row;
}

// Get patients for dropdown
$patients_query = "SELECT p.patient_id, u.first_name, u.last_name 
                   FROM patients p 
                   JOIN users u ON p.user_id = u.user_id 
                   ORDER BY u.last_name, u.first_name";
$patients_result = $conn->query($patients_query);
$patients = [];
while ($row = $patients_result->fetch_assoc()) {
    $patients[] = $row;
}

// Function to generate initials from name
function getInitials($firstName, $lastName) {
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

// Function to format date
function formatAppointmentDate($date) {
    return date("M d, Y", strtotime($date));
}

// Function to format time
function formatAppointmentTime($time) {
    return date("g:i A", strtotime($time));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointments - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_appointments.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <!-- insert inline css -->
     <style>
        /* Appointment Modal Specific Styles */
        #appointment-edit-modal.modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }

        #appointment-edit-modal.modal.active {
            display: flex;
        }

        #appointment-edit-modal .modal-content {
            background: white;
            padding: 24px;
            border-radius: 8px;
            width: 90%;
            max-width: 800px; /* Wider for the appointment form */
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
            margin: 20px auto;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transform: translateX(0); /* Ensure modal is not affected by sidebar */
        }

        #appointment-edit-modal .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        #appointment-edit-modal .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 16px;
            margin-bottom: 16px;
        }

        #appointment-edit-modal .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
        }

        /* Ensure modal appears above sidebar */
        .sidebar {
            z-index: 1000;
        }

        .main-content {
            z-index: 1;
        }
/* 
                .context-menu {
            display: none;
            position: fixed;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            min-width: 160px;
        }

        .context-menu ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .context-menu li {
            padding: 8px 12px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .context-menu li:hover {
            background-color: #f5f5f5;
        }

        .context-menu li.text-danger {
            color: #dc3545;
        }

        .context-menu li.text-danger:hover {
            background-color: #fff5f5;
        }

        .context-menu i {
            font-size: 1.2em;
        } */
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
                    <?php if ($profile_image): ?>
                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($initials); ?></span>
                    <?php endif; ?>
                </div>
                <div class="user-info">
                    <h4><?php echo htmlspecialchars($doctor_name); ?></h4>
                    <p><?php echo htmlspecialchars($doctor_specialty); ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li>
                        <a href="doctor_dashboard.php">
                            <i class='bx bx-home-alt'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h1>Appointments Management</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search appointments..." id="search-appointments">
                    </div>
                    <div class="header-actions">
                        <button class="notification-btn">
                            <i class='bx bx-bell'></i>
                            <?php 
                            // Count unread notifications
                            $notification_query = "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0";
                            $stmt = $conn->prepare($notification_query);
                            $stmt->bind_param("i", $user_id);
                            $stmt->execute();
                            $notification_result = $stmt->get_result();
                            $unread_count = $notification_result->fetch_assoc()['unread'];
                            
                            if ($unread_count > 0): 
                            ?>
                            <span class="notification-badge"><?php echo $unread_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <div class="user-avatar small">
                                    <?php if ($profile_image): ?>
                                        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile">
                                    <?php else: ?>
                                        <span><?php echo htmlspecialchars($initials); ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="user-name"><?php echo htmlspecialchars($doctor_name); ?></span>
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
                    <div class="action-left">
                        <button class="btn primary-btn" id="add-appointment-btn">
                            <i class='bx bx-plus'></i>
                            <span>Schedule Appointment</span>  
                        </button>

                        <div class="filter-dropdown">
                            <select class="filter-select" id="status-filter">
                                <option value="all" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                                <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                                <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>

                        <div class="filter-dropdown">
                            <select class="filter-select" id="service-filter">
                                <option value="all" <?php echo $service_filter == 'all' ? 'selected' : ''; ?>>All Services</option>
                                <?php foreach ($services as $service): ?>
                                    <option value="<?php echo $service['service_id']; ?>" <?php echo $service_filter == $service['service_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($service['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    
                    <div class="action-right">
                        <div class="date-filter">
                            <div class="date-inputs">
                                <input type="date" id="date-start" class="date-input" value="<?php echo $date_start; ?>">
                                <span>to</span>
                                <input type="date" id="date-end" class="date-input" value="<?php echo $date_end; ?>">
                                <button class="btn apply-date-btn" onclick="applyFilters()">Apply</button>
                            </div>
                            <div class="view-toggle">
                                <button class="view-btn active" data-view="list">
                                    <i class='bx bx-list-ul'></i>
                                </button>
                            </div>
                        </div>
                        

                    </div>
                </section>
                
                <!-- Appointments List View -->
                <section class="appointments-section view-section active" id="list-view">
                    <div class="content-card">
                        <div class="card-header with-actions">
                            <h3>All Appointments</h3>
                        </div>
                        
                        <div class="table-container">
                            <table class="appointments-table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Patient</th>
                                        <th>Service</th>
                                        <th>Time</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $current_date = date('Y-m-d');
                                    if ($appointments_result->num_rows > 0):
                                        while ($appointment = $appointments_result->fetch_assoc()):
                                            $is_past = $appointment['appointment_date'] < $current_date;
                                            $row_class = $is_past ? 'past-appointment' : '';
                                            $patient_initials = getInitials($appointment['patient_first_name'], $appointment['patient_last_name']);
                                            $appointment_id = htmlspecialchars($appointment['appointment_id']);
                                    ?>
                                    <tr class="<?php echo $row_class; ?>" 
                                        data-appointment-id="<?php echo $appointment_id; ?>"
                                        data-patient-name="<?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?>"
                                        data-appointment-date="<?php echo htmlspecialchars($appointment['appointment_date']); ?>"
                                        data-appointment-status="<?php echo htmlspecialchars($appointment['status']); ?>">
<<<<<<< HEAD
                                        <!-- Find this section in your table and replace it -->
                                    <td>
                                        <div class="date-time">
                                            <div class="date"><?php echo formatAppointmentDate($appointment['appointment_date']); ?></div>
                                            <div class="time"><?php echo formatAppointmentTime($appointment['start_time']); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar"><?php echo htmlspecialchars($patient_initials); ?></div>
                                            <span><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                    <td><?php 
                                        // Changed from location_name to time range
                                        echo formatAppointmentTime($appointment['start_time']) . ' - ' . formatAppointmentTime($appointment['end_time']); 
                                    ?></td>
                                    <td><span class="status-badge <?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn icon-btn sm view-appointment" 
                                                    title="View Details" 
                                                    data-id="<?php echo $appointment_id; ?>"
                                                    onclick="viewAppointmentDetails('<?php echo $appointment_id; ?>')">
                                                <i class='bx bx-show'></i>
                                            </button>
                                            <button class="btn icon-btn sm edit-appointment" 
                                                    title="Edit" 
                                                    data-id="<?php echo $appointment_id; ?>"
                                                    onclick="editAppointment('<?php echo $appointment_id; ?>')">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                        </div>
                                    </td>
=======
                                        <td>
                                            <div class="date-time">
                                                <div class="date"><?php echo formatAppointmentDate($appointment['appointment_date']); ?></div>
                                                <div class="time"><?php echo formatAppointmentTime($appointment['start_time']); ?></div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="user-info">
                                                <div class="user-avatar"><?php echo htmlspecialchars($patient_initials); ?></div>
                                                <span><?php echo htmlspecialchars($appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                                        <td><?php echo htmlspecialchars($appointment['location_name']); ?></td>
                                        <td><span class="status-badge <?php echo $appointment['status']; ?>"><?php echo ucfirst($appointment['status']); ?></span></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn icon-btn sm view-appointment"
                                                        title="View Details"
                                                        data-id="<?php echo $appointment_id; ?>"
                                                        onclick="viewAppointmentDetails('<?php echo $appointment_id; ?>')">
                                                    <i class='bx bx-show'></i>
                                                </button>
                                                <button class="btn icon-btn sm edit-appointment"
                                                        title="Edit"
                                                        data-id="<?php echo $appointment_id; ?>"
                                                        onclick="editAppointment('<?php echo $appointment_id; ?>')">
                                                    <i class='bx bx-edit'></i>
                                                </button>
                                            </div>
                                        </td>
>>>>>>> 5223378338a2e67ff9d906e9a90026021a4734b1
                                    </tr>
                                    <?php 
                                        endwhile;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="no-data">No appointments found.</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Pagination -->
                        <?php if ($total_pages > 0): ?>
                        <div class="pagination">
                            <div class="pagination-info">
                                <span>Showing <?php echo ($page - 1) * $limit + 1; ?>-<?php echo min($page * $limit, $total_appointments); ?> of <?php echo $total_appointments; ?> appointments</span>
                            </div>
                            <div class="pagination-controls">
                                <button class="pagination-btn" <?php echo $page <= 1 ? 'disabled' : ''; ?> onclick="goToPage(<?php echo $page - 1; ?>)">
                                    <i class='bx bx-chevron-left'></i>
                                </button>
                                
                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <button class="pagination-btn <?php echo $i == $page ? 'active' : ''; ?>" onclick="goToPage(<?php echo $i; ?>)"><?php echo $i; ?></button>
                                <?php endfor; ?>
                                
                                <button class="pagination-btn" <?php echo $page >= $total_pages ? 'disabled' : ''; ?> onclick="goToPage(<?php echo $page + 1; ?>)">
                                    <i class='bx bx-chevron-right'></i>
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
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
                    <div class="modal-body">
                        <form id="appointment-details-form">
                            <input type="hidden" name="appointment_id" id="view_appointment_id" value="">
                            <input type="hidden" name="doctor_id" id="view_doctor_id" value="<?php echo $doctor_id; ?>">

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Patient</label>
                                    <select class="form-control" id="view_patient_id" disabled>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                            <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Doctor</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor_name); ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Service</label>
                                    <select class="form-control" id="view_service_id" disabled>
                                        <option value="">Select Service</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <select class="form-control" id="view_location_id" disabled>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                            <option value="<?php echo $location['location_id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" id="view_appointment_date" readonly>
                                </div>
                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" class="form-control" id="view_start_time" readonly>
                                </div>
                                <div class="form-group">
                                    <label>End Time</label>
                                    <input type="time" class="form-control" id="view_end_time" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" id="view_status" disabled>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea class="form-control" id="view_notes" readonly></textarea>
                                </div>
                            </div>

                            <div class="form-actions">
                                <button type="button" class="btn secondary-btn modal-close">Close</button>
                                <button type="button" class="btn primary-btn" id="edit-from-view-btn" onclick="editAppointment(document.getElementById('view_appointment_id').value)">Edit Appointment</button>
                            </div>
                        </form>
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
                        <form id="appointment-form" method="post" action="../api/save_appointment.php">
                            <input type="hidden" name="appointment_id" id="appointment_id" value="">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Patient</label>
                                    <select class="form-control" name="patient_id" id="patient_id" required>
                                        <option value="">Select Patient</option>
                                        <?php foreach ($patients as $patient): ?>
                                        <option value="<?php echo $patient['patient_id']; ?>"><?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Doctor</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($doctor_name); ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Service</label>
                                    <select class="form-control" name="service_id" id="service_id" required>
                                        <option value="">Select Service</option>
                                        <?php foreach ($services as $service): ?>
                                        <option value="<?php echo $service['service_id']; ?>"><?php echo htmlspecialchars($service['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Location</label>
                                    <select class="form-control" name="location_id" id="location_id" required>
                                        <option value="">Select Location</option>
                                        <?php foreach ($locations as $location): ?>
                                        <option value="<?php echo $location['location_id']; ?>"><?php echo htmlspecialchars($location['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" class="form-control" name="appointment_date" id="appointment_date" min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Start Time</label>
                                    <input type="time" class="form-control" name="start_time" id="start_time" required>
                                </div>
                                <div class="form-group">
                                    <label>End Time</label>
                                    <input type="time" class="form-control" name="end_time" id="end_time" required>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select class="form-control" name="status" id="status" required>
                                        <option value="confirmed">Confirmed</option>
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Notes</label>
                                    <textarea class="form-control" name="notes" id="notes" placeholder="Add appointment notes..."></textarea>
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
            


            <!-- Add this before the closing </main> tag -->
        <!-- View Appointment Modal -->
        <div class="modal" id="appointment-view-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2>Appointment Details</h2>
                    <button type="button" class="modal-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="appointment-details">
                        <div class="detail-group">
                            <label>Patient:</label>
                            <span id="view-patient-name"></span>
                        </div>
                        <div class="detail-group">
                            <label>Service:</label>
                            <span id="view-service"></span>
                        </div>
                        <div class="detail-group">
                            <label>Date:</label>
                            <span id="view-date"></span>
                        </div>
                        <div class="detail-group">
                            <label>Time:</label>
                            <span id="view-time"></span>
                        </div>
                        <div class="detail-group">
                            <label>Location:</label>
                            <span id="view-location"></span>
                        </div>
                        <div class="detail-group">
                            <label>Status:</label>
                            <span id="view-status"></span>
                        </div>
                        <div class="detail-group">
                            <label>Notes:</label>
                            <p id="view-notes"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </main>
    </div>
    
    <script src="../assets/js/dashboard.js"></script>
    <script src="../assets/js/doctor_appointment.js"></script>
</body>
</html>

