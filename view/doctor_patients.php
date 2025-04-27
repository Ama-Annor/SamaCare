<?php

// Start session
session_start();



//check if user as right access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    //take user back to login
    header('Location: login.php');
    exit();
}


// Database connection
require_once '../db/db_connect.php';



$doctor_user_id = $_SESSION['user_id'];

// Get doctor details from database
$query = "SELECT u.first_name, u.last_name, s.name as specialty 
          FROM users u 
          JOIN doctors d ON u.user_id = d.user_id 
          LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
          WHERE u.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_user_id);
$stmt->execute();
$result = $stmt->get_result();
$doctor = $result->fetch_assoc();

// Get patients for this doctor
// First, get patients who have appointments with this doctor
$query = "SELECT DISTINCT p.patient_id, u.user_id, u.first_name, u.last_name, u.email, u.status,
          MAX(a.appointment_date) as last_visit_date, MAX(a.start_time) as last_visit_time
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN appointments a ON p.patient_id = a.patient_id AND a.doctor_id = ?
          WHERE a.doctor_id = ? OR a.doctor_id IS NULL
          GROUP BY p.patient_id
          ORDER BY last_visit_date DESC, last_visit_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $doctor_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

// Get status filter value if set
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Pagination
$patients_per_page = 6;
$total_patients = $result->num_rows;
$total_pages = ceil($total_patients / $patients_per_page);

$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
if ($current_page > $total_pages && $total_pages > 0) $current_page = $total_pages;

$start_index = ($current_page - 1) * $patients_per_page;

// Get paginated patient list
$query = "SELECT DISTINCT p.patient_id, u.user_id, u.first_name, u.last_name, u.email, u.status,
          MAX(a.appointment_date) as last_visit_date, MAX(a.start_time) as last_visit_time
          FROM patients p
          JOIN users u ON p.user_id = u.user_id
          LEFT JOIN appointments a ON p.patient_id = a.patient_id AND a.doctor_id = ?
          WHERE (a.doctor_id = ? OR a.doctor_id IS NULL)";

// Add status filter if applicable
if ($status_filter !== 'all') {
    $query .= " AND u.status = ?";
}

$query .= " GROUP BY p.patient_id
            ORDER BY last_visit_date DESC, last_visit_time DESC
            LIMIT ?, ?";

if ($status_filter !== 'all') {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iisis", $doctor_id, $doctor_id, $status_filter, $start_index, $patients_per_page);
} else {
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiii", $doctor_id, $doctor_id, $start_index, $patients_per_page);
}

$stmt->execute();
$result = $stmt->get_result();
$patients = [];

while ($row = $result->fetch_assoc()) {
    $patients[] = $row;
}

// Get count of unread notifications for the doctor
$query = "SELECT COUNT(*) as unread_count FROM notifications WHERE user_id = ? AND is_read = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $doctor_user_id);
$stmt->execute();
$notification_result = $stmt->get_result();
$notification_count = $notification_result->fetch_assoc()['unread_count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients Management - SamaCare Doctor</title>
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_users.css">
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                    <span><?php echo substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1); ?></span>
                </div>
                <div class="user-info">
                    <h4>Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></h4>
                    <p><?php echo $doctor['specialty'] ?? 'Doctor'; ?></p>
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
                    <li>
                        <a href="doctor_appointment.php">
                            <i class='bx bx-calendar'></i>
                            <span>Appointments</span>
                        </a>
                    </li>
                    <li class="active">
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
                    <h1>Patient Management</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search Patients..." id="patient-search">
                    </div>
                    <div class="header-actions">
                        <button class="notification-btn">
                            <i class='bx bx-bell'></i>
                            <?php if ($notification_count > 0): ?>
                            <span class="notification-badge"><?php echo $notification_count; ?></span>
                            <?php endif; ?>
                        </button>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <div class="user-avatar small">
                                    <span><?php echo substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1); ?></span>
                                </div>
                                <span class="user-name">Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></span>
                                <i class='bx bx-chevron-down'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- User Management Content -->
            <div class="dashboard-content">
                <!-- Actions Bar -->
                <section class="actions-bar">
                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" class="filter-select" onchange="applyFilters() = this.value;">
                            <option value="doctors_patients.php" <?php echo $status_filter == 'all' ? 'selected' : ''; ?>>All Status</option>
                            <option value="doctors_patients.php?status=active" <?php echo $status_filter == 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="doctors_patients.php?status=inactive" <?php echo $status_filter == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </section>
                
                <!-- Users Table -->
                <section class="users-section">
                    <div class="table-container">
                        <table class="users-table">
                            <thead>
                                <tr>
                                    <th>
                                        <input type="checkbox" id="select-all">
                                    </th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Last Visit</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($patients as $patient): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" class="user-select" data-id="<?php echo $patient['patient_id']; ?>">
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1); ?>
                                            </div>
                                            <span><?php echo $patient['first_name'] . ' ' . $patient['last_name']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $patient['email']; ?></td>
                                    <td>
                                        <?php 
                                        if ($patient['last_visit_date']) {
                                            $visit_date = new DateTime($patient['last_visit_date']);
                                            $visit_time = new DateTime($patient['last_visit_time']);
                                            $now = new DateTime();
                                            $diff = $now->diff($visit_date);
                                            
                                            if ($diff->days == 0) {
                                                echo 'Today, ' . $visit_time->format('g:i A');
                                            } elseif ($diff->days == 1) {
                                                echo 'Yesterday, ' . $visit_time->format('g:i A');
                                            } elseif ($diff->days < 7) {
                                                echo $diff->days . ' days ago';
                                            } else {
                                                echo $visit_date->format('M j, Y');
                                            }
                                        } else {
                                            echo 'No visits yet';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower($patient['status']); ?>">
                                            <?php echo ucfirst($patient['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn icon-btn sm edit-btn" data-id="<?php echo $patient['patient_id']; ?>">
                                                <i class='bx bx-pencil'></i>
                                            </button>
                                            <button class="btn icon-btn sm more-btn" data-id="<?php echo $patient['patient_id']; ?>">
                                                <i class='bx bx-dots-vertical-rounded'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($patients)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">No patients found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="pagination">
                        <div class="pagination-info">
                            <span>Showing <?php echo $start_index + 1; ?>-<?php echo min($start_index + $patients_per_page, $total_patients); ?> of <?php echo $total_patients; ?> patients</span>
                        </div>
                        <div class="pagination-controls">
                            <button class="pagination-btn" <?php echo ($current_page <= 1) ? 'disabled' : ''; ?> onclick="location.href='doctors_patients.php?page=<?php echo $current_page - 1; ?><?php echo ($status_filter != 'all') ? '&status=' . $status_filter : ''; ?>'">
                                <i class='bx bx-chevron-left'></i>
                            </button>
                            
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <button class="pagination-btn <?php echo ($i == $current_page) ? 'active' : ''; ?>" onclick="location.href='doctors_patients.php?page=<?php echo $i; ?><?php echo ($status_filter != 'all') ? '&status=' . $status_filter : ''; ?>'">
                                <?php echo $i; ?>
                            </button>
                            <?php endfor; ?>
                            
                            <button class="pagination-btn" <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?> onclick="location.href='doctors_patients.php?page=<?php echo $current_page + 1; ?><?php echo ($status_filter != 'all') ? '&status=' . $status_filter : ''; ?>'">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- User Actions Menu -->
            <div class="context-menu" id="user-actions-menu">
                <ul>
                    <li data-action="view-profile">
                        <i class='bx bx-user'></i>
                        <span>View Profile</span>
                    </li>
                    <li data-action="view-records">
                        <i class='bx bx-folder'></i>
                        <span>View Medical Records</span>
                    </li>
                    <li data-action="edit-records">
                        <i class='bx bx-edit'></i>
                        <span>Edit Medical Records</span>
                    </li>
                    <li data-action="remove-patient" class="danger">
                        <i class='bx bx-trash'></i>
                        <span>Remove Patient</span>
                    </li>
                </ul>
            </div>
            
            <!-- Dashboard Footer -->
            <footer class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> SamaCare. All rights reserved.</p>
                <div class="footer-links">
                    <a href="#">Privacy Policy</a>
                    <a href="#">Terms of Service</a>
                    <a href="#">Help & Support</a>
                </div>
            </footer>
        </main>
    </div>

    <script src="../assets/js/dashboard.js"></script>
    <script>
        // Patient search functionality
        document.getElementById('patient-search').addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('.users-table tbody tr');
            
            rows.forEach(row => {
                const name = row.querySelector('.user-info span').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (name.includes(searchValue) || email.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Context menu for patient actions
        const moreButtons = document.querySelectorAll('.more-btn');
        const contextMenu = document.getElementById('user-actions-menu');
        let currentPatientId = null;

        moreButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Hide any open context menu first
                contextMenu.style.display = 'none';
                
                // Get patient ID from data attribute
                currentPatientId = this.getAttribute('data-id');
                
                // Position the context menu near the clicked button
                const rect = this.getBoundingClientRect();
                contextMenu.style.top = `${rect.bottom + window.scrollY}px`;
                contextMenu.style.left = `${rect.left}px`;
                contextMenu.style.display = 'block';
            });
        });

        // Handle context menu actions
        const contextMenuItems = contextMenu.querySelectorAll('li');
        contextMenuItems.forEach(item => {
            item.addEventListener('click', function() {
                const action = this.getAttribute('data-action');
                
                if (action === 'view-profile') {
                    window.location.href = 'patient_profile.php?id=' + currentPatientId;
                } else if (action === 'view-records') {
                    window.location.href = 'patient_records.php?id=' + currentPatientId;
                } else if (action === 'edit-records') {
                    window.location.href = 'edit_patient_records.php?id=' + currentPatientId;
                } else if (action === 'remove-patient') {
                    if (confirm('Are you sure you want to remove this patient from your list?')) {
                        // Send AJAX request to remove patient
                        // ...
                    }
                }
                
                contextMenu.style.display = 'none';
            });
        });

        // Close context menu when clicking elsewhere
        document.addEventListener('click', function() {
            contextMenu.style.display = 'none';
        });

        // Select all functionality
        document.getElementById('select-all').addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.user-select');
            checkboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });
    </script>
</body>
</html>