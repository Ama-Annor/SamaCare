<?php
session_start();
require_once '../db/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$patientData = [];
$upcomingAppointment = [];
$recentActivities = [];
$healthMetrics = [];
$medicalRecordsCount = 0;
$lastRecordUpdate = null;

try {
    // Fetch patient details
    $stmt = $conn->prepare("
        SELECT u.*, p.patient_id, p.gender, p.blood_type
        FROM users u
        LEFT JOIN patients p ON u.user_id = p.user_id
        WHERE u.user_id = ?
    ");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $patientData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Fetch upcoming appointment
    if ($patientData && isset($patientData['patient_id'])) {
        $stmt = $conn->prepare("
            SELECT a.*, u.first_name, u.last_name, s.name AS service_name,
                   l.name AS location_name, a.appointment_date, a.start_time
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.user_id
            JOIN services s ON a.service_id = s.service_id
            JOIN locations l ON a.location_id = l.location_id
            WHERE a.patient_id = ?
            ORDER BY a.appointment_date ASC, a.start_time ASC
            LIMIT 1
        ");
        $stmt->bind_param("i", $patientData['patient_id']);
        $stmt->execute();
        $upcomingAppointment = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Fetch medical records count
        $stmt = $conn->prepare("
            SELECT COUNT(record_id) AS total_records, MAX(created_at) AS last_update
            FROM medical_records
            WHERE patient_id = ?
        ");
        $stmt->bind_param("i", $patientData['patient_id']);
        $stmt->execute();
        $recordsResult = $stmt->get_result()->fetch_assoc();
        $medicalRecordsCount = $recordsResult['total_records'] ?? 0;
        $lastRecordUpdate = $recordsResult['last_update'] ?? null;
        $stmt->close();

        // Fetch recent activities
        $stmt = $conn->prepare("
            SELECT activity_type, description, created_at
            FROM user_activities
            WHERE user_id = ?
            ORDER BY created_at DESC
            LIMIT 2
        ");
        $stmt->bind_param("i", $_SESSION["user_id"]);
        $stmt->execute();
        $recentActivities = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Fetch health metrics
        $stmt = $conn->prepare("
            SELECT hml.*, mt.name AS metric_name, mt.unit
            FROM health_metric_logs hml
            JOIN metric_types mt ON hml.metric_type_id = mt.metric_type_id
            WHERE hml.patient_id = ?
            ORDER BY hml.recorded_at DESC
            LIMIT 2
        ");
        $stmt->bind_param("i", $patientData['patient_id']);
        $stmt->execute();
        $healthMetrics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// Function to format dates
function formatDate($dateString) {
    return $dateString ? date('M j, Y', strtotime($dateString)) : 'N/A';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<body>
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
        <span><?=
            strtoupper(
                substr($patientData['first_name'] ?? '', 0, 1) .
                substr($patientData['last_name'] ?? '', 0, 1)
            )
            ?></span>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars(
                        ($patientData['first_name'] ?? '') . ' ' .
                        ($patientData['last_name'] ?? '')
                    ) ?></h4>
                <p>Patient</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li class="active">
                    <a href="dashboard.html">
                        <i class='bx bx-home-alt'></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="medical_records.html">
                        <i class='bx bx-folder'></i>
                        <span>Medical Records</span>
                    </a>
                </li>
                <li>
                    <a href="appointments.html">
                        <i class='bx bx-calendar'></i>
                        <span>Appointments</span>
                    </a>
                </li>
                <li>
                    <a href="health_tracking.html">
                        <i class='bx bx-line-chart'></i>
                        <span>Health Tracking</span>
                    </a>
                </li>
                <li>
                    <a href="health-chat.html">
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
            <a href="../index.html" class="logout-link">
                <i class='bx bx-log-out'></i>
                <span>Log Out</span>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="menu-toggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Dashboard</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">2</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small">
            <span><?=
                strtoupper(
                    substr($patientData['first_name'] ?? '', 0, 1) .
                    substr($patientData['last_name'] ?? '', 0, 1)
                )
                ?></span>
                            </div>
                            <span class="user-name"><?= htmlspecialchars(
                                    ($patientData['first_name'] ?? '') . ' ' .
                                    ($patientData['last_name'] ?? '')
                                ) ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-card">
                    <div class="welcome-text">
                        <h2>Welcome back, <?= htmlspecialchars($patientData['first_name'] ?? 'User') ?>!</h2>
                        <p>Here's your health summary</p>
                    </div>
                </div>
            </section>

            <!-- Stats Section -->
            <section class="stats-section">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon"><i class='bx bx-calendar-check'></i></div>
                        <div class="stat-info">
                            <h3>Upcoming Appointments</h3>
                            <p class="stat-value"><?= $upcomingAppointment ? 1 : 0 ?></p>
                            <p class="stat-description">
                                <?= $upcomingAppointment ?
                                    'Next: ' . formatDate($upcomingAppointment['appointment_date']) :
                                    'No upcoming appointments' ?>
                            </p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class='bx bx-file'></i></div>
                        <div class="stat-info">
                            <h3>Medical Records</h3>
                            <p class="stat-value"><?= $medicalRecordsCount ?></p>
                            <p class="stat-description">
                                <?= $lastRecordUpdate ?
                                    'Last updated: ' . formatDate($lastRecordUpdate) :
                                    'No records available' ?>
                            </p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Appointments & Activities Section -->
            <section class="dashboard-two-columns">
                <!-- Upcoming Appointment -->
                <div class="dashboard-column">
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Upcoming Appointment</h3>
                            <a href="appointments.php" class="view-all">View All</a>
                        </div>
                        <div class="appointments-list">
                            <?php if ($upcomingAppointment): ?>
                                <div class="appointment-item">
                                    <div class="appointment-date">
                                        <span class="day"><?= date('d', strtotime($upcomingAppointment['appointment_date'])) ?></span>
                                        <span class="month"><?= date('M', strtotime($upcomingAppointment['appointment_date'])) ?></span>
                                    </div>
                                    <div class="appointment-details">
                                        <h4><?= htmlspecialchars($upcomingAppointment['service_name']) ?></h4>
                                        <p><i class='bx bx-time'></i> <?= date('g:i A', strtotime($upcomingAppointment['start_time'])) ?></p>
                                        <p><i class='bx bx-user'></i> Dr. <?= htmlspecialchars($upcomingAppointment['first_name'] . ' ' . $upcomingAppointment['last_name']) ?></p>
                                        <p><i class='bx bx-map'></i> <?= htmlspecialchars($upcomingAppointment['location_name']) ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="no-appointments">
                                    <p>No upcoming appointments scheduled</p>
                                    <a href="appointments.php" class="add-appointment">
                                        <i class='bx bx-plus'></i>
                                        <span>Schedule New Appointment</span>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Recent Activities -->
                <div class="dashboard-column">
                    <div class="content-card">
                        <div class="card-header">
                            <h3>Recent Activities</h3>
                        </div>
                        <div class="activities-list">
                            <?php if (!empty($recentActivities)): ?>
                                <?php foreach ($recentActivities as $activity): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon">
                                            <i class='bx bx-<?= match($activity['activity_type']) {
                                                'appointment_booked' => 'calendar-check',
                                                'record_added' => 'file',
                                                default => 'info-circle'
                                            } ?>'></i>
                                        </div>
                                        <div class="activity-details">
                                            <h4><?= ucfirst(str_replace('_', ' ', $activity['activity_type'])) ?></h4>
                                            <p><?= htmlspecialchars($activity['description']) ?></p>
                                            <span class="activity-time"><?= formatDate($activity['created_at']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="no-activities">
                                    <p>No recent activities to show</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Health Metrics Section -->
            <section class="health-metrics-section">
                <div class="content-card full-width">
                    <div class="card-header">
                        <h3>Health Metrics</h3>
                        <a href="health_tracking.php" class="view-all">View Details</a>
                    </div>
                    <div class="metrics-grid">
                        <?php if (!empty($healthMetrics)): ?>
                            <?php foreach ($healthMetrics as $metric): ?>
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4><?= htmlspecialchars($metric['metric_name']) ?></h4>
                                        <div class="metric-icon">
                                            <i class='bx bx-<?= match($metric['metric_name']) {
                                                'Blood Pressure' => 'heart',
                                                'Weight' => 'dumbbell',
                                                default => 'line-chart'
                                            } ?>'></i>
                                        </div>
                                    </div>
                                    <div class="metric-value">
                                        <span class="current-value">
                                            <?= htmlspecialchars($metric['value_numeric'] ?? $metric['value_text']) ?>
                                            <?= $metric['unit'] ? htmlspecialchars($metric['unit']) : '' ?>
                                        </span>
                                    </div>
                                    <div class="metric-footer">
                                        <span><?= formatDate($metric['recorded_at']) ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="add-metric">
                                <div class="add-metric-content">
                                    <div class="add-icon"><i class='bx bx-line-chart'></i></div>
                                    <h4>No Health Data</h4>
                                    <p>Start tracking your health metrics</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<script src="../assets/js/dashboard.js"></script>
</body>
</html>