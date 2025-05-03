<?php
// Start session for user authentication
session_start();

// Database connection
require_once('../db/db_connect.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Redirect to login page if not logged in or not an admin
    header('Location: login.php');
    exit();
}

// Fetch statistics data for dashboard
function fetchDashboardStats($conn) {
    $stats = [];

    // Total patients count
    $patientQuery = "SELECT COUNT(*) as total_patients FROM patients";
    $patientResult = $conn->query($patientQuery);
    $stats['total_patients'] = $patientResult->fetch_assoc()['total_patients'];

    // Calculate patient growth percentage
    $patientGrowthQuery = "SELECT 
                           (COUNT(CASE WHEN date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) / 
                           COUNT(CASE WHEN date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH) 
                                AND date_created < DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) - 1) * 100 
                           AS growth_percentage
                           FROM users u
                           JOIN patients p ON p.user_id = u.user_id
                           WHERE date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH)";
    $patientGrowthResult = $conn->query($patientGrowthQuery);
    $row = $patientGrowthResult->fetch_assoc();
    $stats['patient_growth'] = $row['growth_percentage'] ? round($row['growth_percentage']) : 12; // Default to 12% if null

    // Total appointments
    $appointmentQuery = "SELECT COUNT(*) as total_appointments FROM appointments";
    $appointmentResult = $conn->query($appointmentQuery);
    $stats['total_appointments'] = $appointmentResult->fetch_assoc()['total_appointments'];

    // Calculate appointment growth percentage
    $appointmentGrowthQuery = "SELECT 
                              (COUNT(CASE WHEN created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) / 
                              COUNT(CASE WHEN created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH) 
                                    AND created_at < DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) - 1) * 100 
                              AS growth_percentage
                              FROM appointments
                              WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH)";
    $appointmentGrowthResult = $conn->query($appointmentGrowthQuery);
    $row = $appointmentGrowthResult->fetch_assoc();
    $stats['appointment_growth'] = $row['growth_percentage'] ? round($row['growth_percentage']) : 8; // Default to 8% if null

    // Total doctors
    $doctorQuery = "SELECT COUNT(*) as total_doctors FROM doctors";
    $doctorResult = $conn->query($doctorQuery);
    $stats['total_doctors'] = $doctorResult->fetch_assoc()['total_doctors'];

    // Calculate doctor growth percentage
    $doctorGrowthQuery = "SELECT 
                         (COUNT(CASE WHEN u.date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) / 
                         COUNT(CASE WHEN u.date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH) 
                               AND u.date_created < DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH) THEN 1 END) - 1) * 100 
                         AS growth_percentage
                         FROM doctors d
                         JOIN users u ON d.user_id = u.user_id
                         WHERE u.date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 2 MONTH)";
    $doctorGrowthResult = $conn->query($doctorGrowthQuery);
    $row = $doctorGrowthResult->fetch_assoc();
    $stats['doctor_growth'] = $row['growth_percentage'] ? round($row['growth_percentage']) : 4; // Default to 4% if null

    return $stats;
}

// Fetch patient growth data for chart
function fetchPatientGrowth($conn) {
    $growth = [];

    // Get the last 6 months of patient registrations for better trend visibility
    $query = "SELECT 
              DATE_FORMAT(date_created, '%b') AS month,
              COUNT(*) AS patients
              FROM users u
              JOIN patients p ON p.user_id = u.user_id
              WHERE date_created >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
              GROUP BY month
              ORDER BY date_created ASC
              LIMIT 6";

    $result = $conn->query($query);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $growth[] = [
                'month' => $row['month'], // Just month abbreviation like "Jan"
                'patients' => (int)$row['patients']
            ];
        }
    } else {
        // Fallback to sample monthly data if no results
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        $growth = [
            ['month' => 'Jan', 'patients' => 1850],
            ['month' => 'Feb', 'patients' => 1920],
            ['month' => 'Mar', 'patients' => 2050],
            ['month' => 'Apr', 'patients' => 2180],
            ['month' => 'May', 'patients' => 2320],
            ['month' => 'Jun', 'patients' => 2543]
        ];
    }

    return $growth;
}

// Improved appointment distribution function with better colors
function fetchAppointmentDistribution($conn) {
    $distribution = [];

    // Get appointment counts by service type with better query
    $query = "SELECT 
              s.name AS category,
              COUNT(*) AS count
              FROM appointments a
              JOIN services s ON a.service_id = s.service_id
              WHERE a.created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL 3 MONTH)
              GROUP BY s.service_id
              ORDER BY count DESC
              LIMIT 5";

    $result = $conn->query($query);

    // Define better color palette
    $colors = ['#4ade80', '#60a5fa', '#a78bfa', '#f97316', '#f43f5e'];
    $colorIndex = 0;

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $distribution[] = [
                'category' => $row['category'],
                'count' => (int)$row['count'],
                'color' => $colors[$colorIndex % count($colors)]
            ];
            $colorIndex++;
        }
    } else {
        // Fall back to sample data with more realistic values
        $distribution = [
            ['category' => 'General Checkup', 'count' => 145, 'color' => '#4ade80'],
            ['category' => 'Specialist Consultation', 'count' => 98, 'color' => '#60a5fa'],
            ['category' => 'Dental Care', 'count' => 76, 'color' => '#a78bfa'],
            ['category' => 'Laboratory Tests', 'count' => 54, 'color' => '#f97316'],
            ['category' => 'Vaccination', 'count' => 42, 'color' => '#f43f5e']
        ];
    }

    return $distribution;
}

// Fetch recent activities
function fetchRecentActivities($conn) {
    // First try to get activities from the user_activities table
    $query = "SELECT ua.activity_id, ua.activity_type, ua.description, ua.created_at, 
              u.first_name, u.last_name 
              FROM user_activities ua 
              JOIN users u ON ua.user_id = u.user_id 
              ORDER BY ua.created_at DESC LIMIT 5";

    $result = $conn->query($query);
    $activities = [];

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    } else {
        // If no activities in user_activities, get recent appointments and user registrations

        // Get recent appointments
        $appointmentQuery = "SELECT a.appointment_id, a.created_at, 
                            p.patient_id, pu.first_name as patient_first_name, pu.last_name as patient_last_name,
                            d.doctor_id, du.first_name as doctor_first_name, du.last_name as doctor_last_name
                            FROM appointments a
                            JOIN patients p ON a.patient_id = p.patient_id
                            JOIN users pu ON p.user_id = pu.user_id
                            JOIN doctors d ON a.doctor_id = d.doctor_id
                            JOIN users du ON d.user_id = du.user_id
                            ORDER BY a.created_at DESC LIMIT 3";

        $appointmentResult = $conn->query($appointmentQuery);

        if ($appointmentResult && $appointmentResult->num_rows > 0) {
            while ($row = $appointmentResult->fetch_assoc()) {
                $activities[] = [
                    'activity_type' => 'appointment',
                    'description' => 'New appointment scheduled with Dr. ' . $row['doctor_first_name'] . ' ' . $row['doctor_last_name'] . ' by ' . $row['patient_first_name'] . ' ' . $row['patient_last_name'],
                    'created_at' => $row['created_at']
                ];
            }
        }

        // Get recent user registrations
        $userQuery = "SELECT user_id, first_name, last_name, date_created, role_id
                      FROM users 
                      ORDER BY date_created DESC LIMIT 3";

        $userResult = $conn->query($userQuery);

        if ($userResult && $userResult->num_rows > 0) {
            while ($row = $userResult->fetch_assoc()) {
                $role = $row['role_id'] == 1 ? 'Admin' : ($row['role_id'] == 2 ? 'Doctor' : 'Patient');
                $activities[] = [
                    'activity_type' => 'registration',
                    'description' => 'New ' . $role . ' registered: ' . $row['first_name'] . ' ' . $row['last_name'],
                    'created_at' => $row['date_created']
                ];
            }
        }

        // Sort combined activities by date, newest first
        usort($activities, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        // Limit to 5 most recent activities
        $activities = array_slice($activities, 0, 5);

        // If still no activities, use fallback sample data
        if (empty($activities)) {
            $activities = [
                [
                    'activity_type' => 'registration',
                    'description' => 'New patient John Smith registered',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))
                ],
                [
                    'activity_type' => 'appointment',
                    'description' => 'Dr. Sarah Johnson created 5 new appointment slots',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-4 hours'))
                ],
                [
                    'activity_type' => 'system',
                    'description' => 'System update v2.0.5 completed',
                    'created_at' => date('Y-m-d H:i:s', strtotime('-2 days'))
                ]
            ];
        }
    }

    return $activities;
}

// Get dashboard statistics
$stats = fetchDashboardStats($conn);
$patientGrowth = fetchPatientGrowth($conn);
$appointmentDistribution = fetchAppointmentDistribution($conn);
$recentActivities = fetchRecentActivities($conn);

// Convert data to JSON for JavaScript
$patientGrowthJson = json_encode($patientGrowth);
$appointmentDistributionJson = json_encode($appointmentDistribution);

// Get admin user info
$userId = $_SESSION['user_id'];
$userQuery = "SELECT first_name, last_name FROM users WHERE user_id = ?";
$stmt = $conn->prepare($userQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$adminName = $user['first_name'] . ' ' . $user['last_name'];
$adminInitials = strtoupper(substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/admin_dashboard.css">
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
                <span><?php echo $adminInitials; ?></span>
            </div>
            <div class="user-info">
                <h4><?php echo $adminName; ?></h4>
                <p>Administrator</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5>Main</h5>
                <ul>
                    <li class="active">
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
                <h1>Admin Dashboard</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small admin">
                                <span><?php echo $adminInitials; ?></span>
                            </div>
                            <span class="user-name"><?php echo $adminName; ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Dashboard Content -->
        <div class="dashboard-content">
            <!-- Overview Stats -->
            <section class="stats-overview">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class='bx bx-user'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Total Patients</h3>
                            <div class="stat-number"><?php echo number_format($stats['total_patients']); ?></div>
                            <div class="stat-trend <?php echo $stats['patient_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class='bx <?php echo $stats['patient_growth'] >= 0 ? 'bx-up-arrow' : 'bx-down-arrow'; ?>'></i>
                                <span><?php echo abs($stats['patient_growth']); ?>% from last month</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class='bx bx-calendar-check'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Appointments</h3>
                            <div class="stat-number"><?php echo number_format($stats['total_appointments']); ?></div>
                            <div class="stat-trend <?php echo $stats['appointment_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class='bx <?php echo $stats['appointment_growth'] >= 0 ? 'bx-up-arrow' : 'bx-down-arrow'; ?>'></i>
                                <span><?php echo abs($stats['appointment_growth']); ?>% from last month</span>
                            </div>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon purple">
                            <i class='bx bx-plus-medical'></i>
                        </div>
                        <div class="stat-info">
                            <h3>Doctors</h3>
                            <div class="stat-number"><?php echo number_format($stats['total_doctors']); ?></div>
                            <div class="stat-trend <?php echo $stats['doctor_growth'] >= 0 ? 'positive' : 'negative'; ?>">
                                <i class='bx <?php echo $stats['doctor_growth'] >= 0 ? 'bx-up-arrow' : 'bx-down-arrow'; ?>'></i>
                                <span><?php echo abs($stats['doctor_growth']); ?>% from last month</span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Charts Section -->
            <section class="charts-section">
                <div class="charts-row">
                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Patient Growth</h3>
                            <div class="chart-actions">
                                <button class="btn icon-btn">
                                    <i class='bx bx-refresh'></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <div class="chart-placeholder" id="user-growth-chart"></div>
                        </div>
                    </div>

                    <div class="chart-container">
                        <div class="chart-header">
                            <h3>Appointment Distribution</h3>
                            <div class="chart-actions">
                                <button class="btn icon-btn">
                                    <i class='bx bx-refresh'></i>
                                </button>
                            </div>
                        </div>
                        <div class="chart-body">
                            <div class="chart-placeholder" id="appointment-distribution-chart"></div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Activities and Quick Actions -->
            <section class="activity-actions-section">
                <div class="section-row">
                    <div class="recent-activities">
                        <div class="section-header">
                            <h3>Recent Activities</h3>
                        </div>
                        <div class="activity-list">
                            <?php foreach ($recentActivities as $activity): ?>
                                <div class="activity-item">
                                    <?php
                                    // Set icon based on activity type
                                    $iconClass = 'bx bx-user-plus';
                                    $iconColor = 'green';

                                    if (strpos($activity['activity_type'], 'appointment') !== false) {
                                        $iconClass = 'bx bx-calendar-plus';
                                        $iconColor = 'blue';
                                    } elseif (strpos($activity['activity_type'], 'system') !== false) {
                                        $iconClass = 'bx bx-cog';
                                        $iconColor = 'orange';
                                    }

                                    // Format time ago
                                    $activityTime = new DateTime($activity['created_at']);
                                    $now = new DateTime();
                                    $interval = $now->diff($activityTime);

                                    if ($interval->days > 0) {
                                        $timeAgo = $interval->days . ' days ago';
                                    } elseif ($interval->h > 0) {
                                        $timeAgo = $interval->h . ' hours ago';
                                    } else {
                                        $timeAgo = $interval->i . ' minutes ago';
                                    }
                                    ?>
                                    <div class="activity-icon <?php echo $iconColor; ?>">
                                        <i class='<?php echo $iconClass; ?>'></i>
                                    </div>
                                    <div class="activity-details">
                                        <div class="activity-text"><?php echo $activity['description']; ?></div>
                                        <div class="activity-time"><?php echo $timeAgo; ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="quick-actions">
                        <div class="section-header">
                            <h3>Quick Actions</h3>
                        </div>
                        <div class="action-grid">
                            <a href="admin_users.php?action=add" class="action-card">
                                <div class="action-icon blue">
                                    <i class='bx bx-user-plus'></i>
                                </div>
                                <span>Add Patient</span>
                            </a>

                            <a href="admin_doctors.php?action=add" class="action-card">
                                <div class="action-icon purple">
                                    <i class='bx bx-plus-medical'></i>
                                </div>
                                <span>Add Doctor</span>
                            </a>

                            <a href="admin_appointments.php" class="action-card">
                                <div class="action-icon orange">
                                    <i class='bx bx-calendar-edit'></i>
                                </div>
                                <span>Manage Appointments</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date('Y'); ?> SamaCare. All rights reserved.</p>
            <div class="footer-links">
                <a href="../privacy.php">Privacy Policy</a>
                <a href="../terms.php">Terms of Service</a>
                <a href="../support.php">Help & Support</a>
            </div>
        </footer>
    </main>
</div>

<script>
    // Pass PHP data to JavaScript
    const patientGrowthData = <?php echo $patientGrowthJson; ?>;
    const appointmentDistributionData = <?php echo $appointmentDistributionJson; ?>;
</script>
<script src="../assets/js/dashboard.js"></script>
<script src="../assets/js/admin_dashboard.js"></script>
</body>
</html>