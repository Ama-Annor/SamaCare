<?php
// Include database connection
require_once '../db/db_connect.php';
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header('Location: login.php');
    exit();
}

// Fetch all doctors with their specialties
$query = "SELECT d.doctor_id, u.first_name, u.last_name, u.email, u.phone, u.profile_image,
         s.name AS specialty_name, d.license_number, d.biography, d.years_experience, d.rating,
         CASE WHEN u.status = 'active' THEN 1 ELSE 0 END AS is_active
         FROM doctors d
         JOIN users u ON d.user_id = u.user_id
         LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
         ORDER BY u.first_name, u.last_name";

$result = mysqli_query($conn, $query);
$doctors = [];

if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Get appointment count for each doctor
        $appointmentQuery = "SELECT COUNT(*) as appointment_count FROM appointments WHERE doctor_id = " . $row['doctor_id'];
        $appointmentResult = mysqli_query($conn, $appointmentQuery);
        $appointmentCount = 0;

        if ($appointmentResult && $appointmentRow = mysqli_fetch_assoc($appointmentResult)) {
            $appointmentCount = $appointmentRow['appointment_count'];
        }

        $row['appointment_count'] = $appointmentCount;

        // Get schedule for each doctor
        $scheduleQuery = "SELECT * FROM doctor_schedules WHERE doctor_id = " . $row['doctor_id'];
        $scheduleResult = mysqli_query($conn, $scheduleQuery);
        $schedule = [];

        if ($scheduleResult) {
            while ($scheduleRow = mysqli_fetch_assoc($scheduleResult)) {
                $schedule[$scheduleRow['day_of_week']] = [
                    'start_time' => $scheduleRow['start_time'],
                    'end_time' => $scheduleRow['end_time'],
                    'is_available' => $scheduleRow['is_available']
                ];
            }
        }

        $row['schedule'] = $schedule;
        $doctors[] = $row;
    }
}

// Get all specialties for filter dropdown
$specialtyQuery = "SELECT specialty_id, name FROM specialties ORDER BY name";
$specialtyResult = mysqli_query($conn, $specialtyQuery);
$specialties = [];

if ($specialtyResult) {
    while ($specialtyRow = mysqli_fetch_assoc($specialtyResult)) {
        $specialties[] = $specialtyRow;
    }
}

// Handle doctor form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['success' => false, 'message' => ''];

    if ($_POST['action'] === 'save_doctor') {
// Get form data
        $doctorId = isset($_POST['doctor_id']) ? (int)$_POST['doctor_id'] : 0;
        $firstName = mysqli_real_escape_string($conn, $_POST['first_name']);
        $lastName = mysqli_real_escape_string($conn, $_POST['last_name']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $phone = mysqli_real_escape_string($conn, $_POST['phone']);
        $specialtyId = (int)$_POST['specialty'];
        $licenseNumber = mysqli_real_escape_string($conn, $_POST['license']);
        $bio = mysqli_real_escape_string($conn, $_POST['bio']);
        $status = $_POST['status'] === 'active' ? 'active' : 'inactive';

// Start transaction
        mysqli_begin_transaction($conn);

        try {
            if ($doctorId > 0) {
// Update existing doctor
                $userId = 0;
                $userQuery = "SELECT user_id FROM doctors WHERE doctor_id = $doctorId";
                $userResult = mysqli_query($conn, $userQuery);

                if ($userResult && $userRow = mysqli_fetch_assoc($userResult)) {
                    $userId = $userRow['user_id'];

// Update user information
                    $updateUserQuery = "UPDATE users SET
first_name = '$firstName',
last_name = '$lastName',
email = '$email',
phone = '$phone',
status = '$status'
WHERE user_id = $userId";

                    mysqli_query($conn, $updateUserQuery);

// Update doctor information
                    $updateDoctorQuery = "UPDATE doctors SET
specialty_id = $specialtyId,
license_number = '$licenseNumber',
biography = '$bio'
WHERE doctor_id = $doctorId";

                    mysqli_query($conn, $updateDoctorQuery);
                }
            } else {
// Create new doctor
// First create user
                $password = password_hash('defaultpassword', PASSWORD_DEFAULT); // Default password
                $insertUserQuery = "INSERT INTO users (role_id, email, password, first_name, last_name, phone, status)
VALUES (2, '$email', '$password', '$firstName', '$lastName', '$phone', '$status')";

                mysqli_query($conn, $insertUserQuery);
                $userId = mysqli_insert_id($conn);

// Then create doctor
                $insertDoctorQuery = "INSERT INTO doctors (user_id, specialty_id, license_number, biography)
VALUES ($userId, $specialtyId, '$licenseNumber', '$bio')";

                mysqli_query($conn, $insertDoctorQuery);
                $doctorId = mysqli_insert_id($conn);
            }

// Handle schedule
            if ($doctorId > 0) {
// Delete existing schedules
                $deleteScheduleQuery = "DELETE FROM doctor_schedules WHERE doctor_id = $doctorId";
                mysqli_query($conn, $deleteScheduleQuery);

// Add new schedules
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

                foreach ($days as $day) {
                    if (isset($_POST["workday_$day"]) && $_POST["workday_$day"] === 'on') {
                        $startTime = mysqli_real_escape_string($conn, $_POST["start_time_$day"]);
                        $endTime = mysqli_real_escape_string($conn, $_POST["end_time_$day"]);

                        $insertScheduleQuery = "INSERT INTO doctor_schedules (doctor_id, day_of_week, start_time, end_time, is_available)
VALUES ($doctorId, '$day', '$startTime', '$endTime', 1)";

                        mysqli_query($conn, $insertScheduleQuery);
                    }
                }
            }

// Commit transaction
            mysqli_commit($conn);

            $response['success'] = true;
            $response['message'] = 'Doctor saved successfully!';
        } catch (Exception $e) {
// Rollback transaction on error
            mysqli_rollback($conn);
            $response['message'] = 'Error: ' . $e->getMessage();
        }

// Send JSON response
        header('Content-Type: application/json');
        echo json_encode($response);
        exit();
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


// Function to get doctor initials
function getInitials($firstName, $lastName) {
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Management - SamaCare Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_doctors.css">
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
                    <li class="active">
                        <a href="admin_doctors.php">
                            <i class='bx bx-plus-medical'></i>
                            <span>Doctors</span>
                        </a>
                    </li>
                    <li>
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
                <h1>Doctor Management</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" id="doctor-search" placeholder="Search doctors...">
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

        <!-- Doctor Management Content -->
        <div class="dashboard-content">
            <!-- Actions Bar -->
            <section class="actions-bar">
                <div class="action-buttons">
                    <button class="btn primary-btn" id="add-doctor-btn">
                        <i class='bx bx-plus-medical'></i>
                        <span>Add New Doctor</span>
                    </button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label for="specialty-filter">Specialty:</label>
                        <select id="specialty-filter" class="filter-select">
                            <option value="all">All Specialties</option>
                            <?php foreach ($specialties as $specialty): ?>
                                <option value="<?php echo $specialty['specialty_id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" class="filter-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
            </section>

            <!-- Doctors Cards -->
            <section class="doctors-section">
                <div class="doctors-grid" id="doctors-container">
                    <?php foreach ($doctors as $doctor): ?>
                        <!-- Doctor Card -->
                        <div class="doctor-card<?php echo $doctor['is_active'] ? '' : ' inactive'; ?>"
                             data-id="<?php echo $doctor['doctor_id']; ?>"
                             data-specialty="<?php echo $doctor['specialty_name'] !== null ? htmlspecialchars($doctor['specialty_name']) : ''; ?>"
                             data-name="<?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?>"
                             data-email="<?php echo htmlspecialchars($doctor['email']); ?>"
                             data-status="<?php echo $doctor['is_active'] ? 'active' : 'inactive'; ?>">
                            <div class="doctor-card-header">
                                <div class="doctor-avatar">
                                    <?php echo getInitials($doctor['first_name'], $doctor['last_name']); ?>
                                </div>
                                <div class="doctor-status <?php echo $doctor['is_active'] ? 'active' : 'inactive'; ?>">
                                    <?php echo $doctor['is_active'] ? 'Active' : 'Inactive'; ?>
                                </div>
                            </div>
                            <div class="doctor-card-body">
                                <h3 class="doctor-name">Dr. <?php echo htmlspecialchars($doctor['first_name'] . ' ' . $doctor['last_name']); ?></h3>
                                <p class="doctor-specialty"><?php echo $doctor['specialty_name'] !== null ? htmlspecialchars($doctor['specialty_name']) : 'No Specialty'; ?></p>                                <div class="doctor-contact">
                                    <p><i class='bx bx-envelope'></i> <?php echo htmlspecialchars($doctor['email']); ?></p>
                                    <p><i class='bx bx-phone'></i> <?php echo htmlspecialchars($doctor['phone']); ?></p>
                                </div>
                                <div class="doctor-stats">
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $doctor['appointment_count']; ?></span>
                                        <span class="stat-label">Appointments</span>
                                    </div>
                                    <div class="stat">
                                        <span class="stat-value"><?php echo $doctor['rating'] !== null ? number_format($doctor['rating'], 1) : '0.0'; ?></span>
                                        <span class="stat-label">Rating</span>
                                    </div>
                                </div>
                            </div>
                            <div class="doctor-card-footer">
                                <button class="btn icon-btn edit-btn" data-id="<?php echo $doctor['doctor_id']; ?>">
                                    <i class='bx bx-edit'></i>
                                </button>
                                <button class="btn secondary-btn view-btn" data-id="<?php echo $doctor['doctor_id']; ?>">
                                    View Details
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Add Doctor Card -->
                    <div class="doctor-card add-card" id="add-doctor-card">
                        <div class="add-doctor-content">
                            <div class="add-icon">
                                <i class='bx bx-plus-medical'></i>
                            </div>
                            <p>Add New Doctor</p>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info">
                        <span>Showing 1-<?php echo min(count($doctors), 6); ?> of <?php echo count($doctors); ?> doctors</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled>
                            <i class='bx bx-chevron-left'></i>
                        </button>
                        <button class="pagination-btn active">1</button>
                        <?php if (count($doctors) > 6): ?>
                            <button class="pagination-btn">2</button>
                            <button class="pagination-btn">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- Doctor Add/Edit Modal -->
        <div class="modal" id="doctor-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Add New Doctor</h2>
                    <button class="modal-close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="doctor-form">
                        <input type="hidden" id="doctor-id" name="doctor_id" value="0">
                        <input type="hidden" id="action" name="action" value="save_doctor">
                        <input type="hidden" id="is-view-only" value="0">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor-first-name">First Name</label>
                                <input type="text" id="doctor-first-name" name="first_name" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label for="doctor-last-name">Last Name</label>
                                <input type="text" id="doctor-last-name" name="last_name" placeholder="Enter last name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor-email">Email Address</label>
                                <input type="email" id="doctor-email" name="email" placeholder="Enter email address" required>
                            </div>
                            <div class="form-group">
                                <label for="doctor-phone">Phone Number</label>
                                <input type="tel" id="doctor-phone" name="phone" placeholder="Enter phone number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor-specialty">Specialty</label>
                                <select id="doctor-specialty" name="specialty" required>
                                    <option value="">Select specialty</option>
                                    <?php foreach ($specialties as $specialty): ?>
                                        <option value="<?php echo $specialty['specialty_id']; ?>"><?php echo htmlspecialchars($specialty['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="doctor-license">License Number</label>
                                <input type="text" id="doctor-license" name="license" placeholder="Enter license number" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="doctor-status">Status</label>
                                <select id="doctor-status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group full-width">
                            <label for="doctor-bio">Biography</label>
                            <textarea id="doctor-bio" name="bio" rows="4" placeholder="Enter doctor's short biography"></textarea>
                        </div>

                        <div class="form-section">
                            <h3>Work Schedule</h3>
                            <div class="schedule-container">
                                <?php
                                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                $defaultWorkDays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

                                foreach ($days as $day):
                                    $isDefaultWorkDay = in_array($day, $defaultWorkDays);
                                    ?>
                                    <div class="schedule-day">
                                        <label>
                                            <input type="checkbox" name="workday_<?php echo $day; ?>"
                                                <?php echo $isDefaultWorkDay ? 'checked' : ''; ?>>
                                            <?php echo $day; ?>
                                        </label>
                                        <div class="time-slots">
                                            <input type="time" name="start_time_<?php echo $day; ?>" value="08:00">
                                            <span>to</span>
                                            <input type="time" name="end_time_<?php echo $day; ?>" value="17:00">
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn secondary-btn" id="cancel-btn">Cancel</button>
                            <button type="submit" class="btn primary-btn" id="save-btn">Save Doctor</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer">
            <p>&copy; 2024 SamaCare. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help & Support</a>
            </div>
        </footer>
    </main>
</div>

<!-- Toast notification -->
<div class="toast-message" id="toast-message"></div>

<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/admin_dashboard.js"></script>
<script src="../assets/js/admin_doctors.js"></script>
</body>
</html>