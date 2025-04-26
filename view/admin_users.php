<?php
// Start session
session_start();

// Database connection
require_once '../db/db_connect.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] !== 1) {
    header("Location: ../login.php");
    exit();
}

// Function to get all users with their roles and statuses
function getUsers($conn) {
    $sql = "SELECT u.user_id, u.first_name, u.last_name, u.email, r.role_name, 
            u.last_login, u.status, u.profile_image
            FROM users u
            JOIN roles r ON u.role_id = r.role_id
            ORDER BY u.user_id DESC";

    $result = mysqli_query($conn, $sql);
    $users = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            // Double-check role for doctors by checking the doctors table
            if ($row['role_name'] !== 'Doctor') {
                // Check if this user exists in the doctors table
                $doctor_check_sql = "SELECT * FROM doctors WHERE user_id = " . $row['user_id'];
                $doctor_result = mysqli_query($conn, $doctor_check_sql);

                if (mysqli_num_rows($doctor_result) > 0) {
                    // This user is a doctor but has wrong role_id in users table
                    $row['role_name'] = 'Doctor';

                    // Update the role_id in the users table to fix it permanently
                    $update_sql = "UPDATE users SET role_id = 3 WHERE user_id = " . $row['user_id'];
                    mysqli_query($conn, $update_sql);
                }
            }

            $users[] = $row;
        }
    }

    return $users;
}

// Function to get roles
function getRoles($conn) {
    $sql = "SELECT role_id, role_name FROM roles";
    $result = mysqli_query($conn, $sql);
    $roles = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $roles[] = $row;
        }
    }

    return $roles;
}

// Function to get specialties
function getSpecialties($conn) {
    $sql = "SELECT specialty_id, name FROM specialties";
    $result = mysqli_query($conn, $sql);
    $specialties = [];

    if (mysqli_num_rows($result) > 0) {
        while ($row = mysqli_fetch_assoc($result)) {
            $specialties[] = $row;
        }
    }

    return $specialties;
}

// Add new user
if (isset($_POST['add_user']) || (isset($_GET['action']) && $_GET['action'] == 'add_user')) {
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash password
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Insert into users table
        $sql = "INSERT INTO users (role_id, email, password, first_name, last_name, phone, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "issssss", $role_id, $email, $password, $first_name, $last_name, $phone, $status);
        mysqli_stmt_execute($stmt);

        $user_id = mysqli_insert_id($conn);

        // If role is doctor, insert into doctors table
        if ($role_id == 3) {
            $specialty_id = isset($_POST['specialty']) ? mysqli_real_escape_string($conn, $_POST['specialty']) : null;
            $license = mysqli_real_escape_string($conn, $_POST['license']);

            $sql = "INSERT INTO doctors (user_id, specialty_id, license_number) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iis", $user_id, $specialty_id, $license);
            mysqli_stmt_execute($stmt);
        }

        // If role is patient, insert into patients table
        if ($role_id == 2) {
            $sql = "INSERT INTO patients (user_id) VALUES (?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
        }

        // Commit transaction
        mysqli_commit($conn);

        $_SESSION['success_message'] = "User added successfully!";
    } catch (Exception $e) {
        // Roll back transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error adding user: " . $e->getMessage();
    }

    header("Location: admin_users.php");
    exit();
}

// Edit user
if (isset($_POST['edit_user']) || (isset($_GET['action']) && $_GET['action'] == 'update_user')) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $first_name = mysqli_real_escape_string($conn, $_POST['first_name']);
    $last_name = mysqli_real_escape_string($conn, $_POST['last_name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $role_id = mysqli_real_escape_string($conn, $_POST['role_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Update users table
        $sql = "UPDATE users SET role_id = ?, email = ?, first_name = ?, last_name = ?, phone = ?, status = ? WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "isssssi", $role_id, $email, $first_name, $last_name, $phone, $status, $user_id);
        mysqli_stmt_execute($stmt);

        // If password was provided, update it
        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "si", $password, $user_id);
            mysqli_stmt_execute($stmt);
        }

        // If role is doctor, update or insert into doctors table
        if ($role_id == 3) {
            $specialty_id = isset($_POST['specialty']) ? mysqli_real_escape_string($conn, $_POST['specialty']) : null;
            $license = mysqli_real_escape_string($conn, $_POST['license']);

            // Check if doctor record exists
            $sql = "SELECT * FROM doctors WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) > 0) {
                // Update existing record
                $sql = "UPDATE doctors SET specialty_id = ?, license_number = ? WHERE user_id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "isi", $specialty_id, $license, $user_id);
                mysqli_stmt_execute($stmt);
            } else {
                // Insert new record
                $sql = "INSERT INTO doctors (user_id, specialty_id, license_number) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iis", $user_id, $specialty_id, $license);
                mysqli_stmt_execute($stmt);
            }
        }

        // If role is patient, ensure patient record exists
        if ($role_id == 2) {
            $sql = "SELECT * FROM patients WHERE user_id = ?";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "i", $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if (mysqli_num_rows($result) == 0) {
                $sql = "INSERT INTO patients (user_id) VALUES (?)";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
            }
        }

        // Commit transaction
        mysqli_commit($conn);

        $_SESSION['success_message'] = "User updated successfully!";
    } catch (Exception $e) {
        // Roll back transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error updating user: " . $e->getMessage();
    }

    header("Location: admin_users.php");
    exit();
}

// Delete user
if (isset($_POST['delete_user'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);

    // Begin transaction
    mysqli_begin_transaction($conn);

    try {
        // Delete from doctors table if exists
        $sql = "DELETE FROM doctors WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        // Delete from patients table if exists
        $sql = "DELETE FROM patients WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        // Delete from users table
        $sql = "DELETE FROM users WHERE user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        mysqli_stmt_execute($stmt);

        // Commit transaction
        mysqli_commit($conn);

        $_SESSION['success_message'] = "User deleted successfully!";
    } catch (Exception $e) {
        // Roll back transaction on error
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error deleting user: " . $e->getMessage();
    }

    header("Location: admin_users.php");
    exit();
}

// Change user status
if (isset($_POST['change_status'])) {
    $user_id = mysqli_real_escape_string($conn, $_POST['user_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    $sql = "UPDATE users SET status = ? WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "User status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating user status: " . mysqli_error($conn);
    }

    header("Location: admin_users.php");
    exit();
}

// Get users, roles, and specialties
$users = getUsers($conn);
$roles = getRoles($conn);
$specialties = getSpecialties($conn);

// Count total users
$total_users = count($users);

// Get current user info
$current_user_id = $_SESSION['user_id'];
$sql = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $current_user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current_user = mysqli_fetch_assoc($result);

// Get doctor information for doctors
function getDoctorInfo($conn, $user_id) {
    $sql = "SELECT d.specialty_id, d.license_number, s.name as specialty_name 
            FROM doctors d 
            LEFT JOIN specialties s ON d.specialty_id = s.specialty_id 
            WHERE d.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        return mysqli_fetch_assoc($result);
    }

    return null;
}

// Function to format last login time
function formatLastLogin($timestamp) {
    if (!$timestamp) return 'Never';

    $now = time();
    $loginTime = strtotime($timestamp);
    $timeDiff = $now - $loginTime;

    if ($timeDiff < 86400) { // Less than 24 hours
        return 'Today, ' . date('g:i A', $loginTime);
    } else if ($timeDiff < 172800) { // Less than 48 hours
        return 'Yesterday, ' . date('g:i A', $loginTime);
    } else if ($timeDiff < 604800) { // Less than a week
        return floor($timeDiff / 86400) . ' days ago';
    } else {
        return date('M j, Y', $loginTime);
    }
}

// Function to get user initials from name
function getInitials($firstName, $lastName) {
    return strtoupper(substr($firstName, 0, 1) . substr($lastName, 0, 1));
}

// Handle AJAX requests for user details
if (isset($_GET['action']) && $_GET['action'] == 'get_user_details') {
    $user_id = $_GET['user_id'];

    $sql = "SELECT u.*, r.role_name FROM users u 
            JOIN roles r ON u.role_id = r.role_id 
            WHERE u.user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // Get doctor info if applicable
        if ($user['role_name'] == 'Doctor') {
            $doctor_info = getDoctorInfo($conn, $user_id);
            if ($doctor_info) {
                $user = array_merge($user, $doctor_info);
            }
        }

        echo json_encode($user);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - SamaCare Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_users.css">
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
                <span><?php echo getInitials($current_user['first_name'], $current_user['last_name']); ?></span>
            </div>
            <div class="user-info">
                <h4><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></h4>
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
                    <li class="active">
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
                <h1>User Management</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" id="search-input" placeholder="Search users...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small admin">
                                <span><?php echo getInitials($current_user['first_name'], $current_user['last_name']); ?></span>
                            </div>
                            <span class="user-name"><?php echo $current_user['first_name'] . ' ' . $current_user['last_name']; ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Toast Messages -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="toast-message show">
                <?php echo $_SESSION['success_message']; ?>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="toast-message error show">
                <?php echo $_SESSION['error_message']; ?>
            </div>
            <?php unset($_SESSION['error_message']); ?>
        <?php endif; ?>

        <!-- User Management Content -->
        <div class="dashboard-content">
            <!-- Actions Bar -->
            <section class="actions-bar">
                <div class="action-buttons">
                    <button class="btn primary-btn" id="add-user-btn">
                        <i class='bx bx-user-plus'></i>
                        <span>Add New User</span>
                    </button>
                </div>

                <div class="filters">
                    <div class="filter-group">
                        <label for="role-filter">Role:</label>
                        <select id="role-filter" class="filter-select">
                            <option value="all">All Roles</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?php echo strtolower($role['role_name']); ?>"><?php echo $role['role_name']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label for="status-filter">Status:</label>
                        <select id="status-filter" class="filter-select">
                            <option value="all">All Status</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
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
                            <th>Role</th>
                            <th>Last Login</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr data-role="<?php echo strtolower($user['role_name']); ?>" data-status="<?php echo strtolower($user['status']); ?>">
                                    <td>
                                        <input type="checkbox" class="user-select">
                                    </td>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar <?php echo strtolower($user['role_name']) === 'doctor' ? 'doctor' : (strtolower($user['role_name']) === 'admin' ? 'admin' : ''); ?>">
                                                <?php echo getInitials($user['first_name'], $user['last_name']); ?>
                                            </div>
                                            <span><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></span>
                                        </div>
                                    </td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['role_name']; ?></td>
                                    <td><?php echo formatLastLogin($user['last_login']); ?></td>
                                    <td><span class="status-badge <?php echo strtolower($user['status']); ?>"><?php echo ucfirst($user['status']); ?></span></td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn icon-btn sm edit-btn" data-id="<?php echo $user['user_id']; ?>">
                                                <i class='bx bx-pencil'></i>
                                            </button>
                                            <button class="btn icon-btn sm more-btn" data-id="<?php echo $user['user_id']; ?>">
                                                <i class='bx bx-dots-vertical-rounded'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <div class="pagination-info">
                        <span>Showing 1-<?php echo min($total_users, 10); ?> of <?php echo $total_users; ?> users</span>
                    </div>
                    <div class="pagination-controls">
                        <button class="pagination-btn" disabled>
                            <i class='bx bx-chevron-left'></i>
                        </button>
                        <button class="pagination-btn active">1</button>
                        <?php
                        $total_pages = ceil($total_users / 10);
                        for ($i = 2; $i <= min($total_pages, 5); $i++):
                            ?>
                            <button class="pagination-btn"><?php echo $i; ?></button>
                        <?php endfor; ?>
                        <?php if ($total_pages > 1): ?>
                            <button class="pagination-btn">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        <?php else: ?>
                            <button class="pagination-btn" disabled>
                                <i class='bx bx-chevron-right'></i>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>

        <!-- User Add/Edit Modal -->
        <div class="modal" id="user-modal">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 id="modal-title">Add New User</h2>
                    <button class="modal-close">
                        <i class='bx bx-x'></i>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="user-form" method="post" action="">
                        <input type="hidden" id="user_id" name="user_id">
                        <input type="hidden" id="form_action" name="form_action" value="add_user">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" placeholder="Enter first name" required>
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" placeholder="Enter last name" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" placeholder="Enter email address" required>
                            </div>
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" placeholder="Enter phone number">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="role_id">Role</label>
                                <select id="role_id" name="role_id" required>
                                    <option value="">Select role</option>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?php echo $role['role_id']; ?>"><?php echo $role['role_name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="password">Password</label>
                                <div class="password-input">
                                    <input type="password" id="password" name="password" placeholder="Enter password">
                                    <button type="button" class="password-toggle">
                                        <i class='bx bx-hide'></i>
                                    </button>
                                </div>
                                <small id="password-help" class="form-text">Leave blank to keep current password when editing.</small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                    <option value="suspended">Suspended</option>
                                </select>
                            </div>
                        </div>

                        <!-- Doctor-specific fields (shown only when role is doctor) -->
                        <div class="doctor-fields" style="display: none;">
                            <h3>Doctor Information</h3>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="specialty">Specialty</label>
                                    <select id="specialty" name="specialty">
                                        <option value="">Select specialty</option>
                                        <?php foreach ($specialties as $specialty): ?>
                                            <option value="<?php echo $specialty['specialty_id']; ?>"><?php echo $specialty['name']; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="license">License Number</label>
                                    <input type="text" id="license" name="license" placeholder="Enter license number">
                                </div>
                            </div>
                        </div>

                        <div class="form-actions">
                            <button type="button" class="btn secondary-btn" id="cancel-btn">Cancel</button>
                            <button type="submit" class="btn primary-btn" id="save-btn">Save User</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- User Actions Menu -->
        <div class="context-menu" id="user-actions-menu">
            <ul>
                <li data-action="view">
                    <i class='bx bx-user'></i>
                    <span>View Profile</span>
                </li>
                <li data-action="edit">
                    <i class='bx bx-edit'></i>
                    <span>Edit User</span>
                </li>
                <li data-action="reset-password">
                    <i class='bx bx-lock-open'></i>
                    <span>Reset Password</span>
                </li>
                <li data-action="disable" class="danger">
                    <i class='bx bx-user-x'></i>
                    <span>Disable User</span>
                </li>
                <li data-action="delete" class="danger">
                    <i class='bx bx-trash'></i>
                    <span>Delete User</span>
                </li>
            </ul>
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

<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/admin_dashboard.js"></script>
<script src="../assets/js/admin_users.js"></script>
</body>
</html>
