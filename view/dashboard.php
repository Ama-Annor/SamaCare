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
$chartData = []; // Initialize chart data array

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

    // Fetch upcoming appointment - only future dates
    if ($patientData && isset($patientData['patient_id'])) {
        $stmt = $conn->prepare("
            SELECT a.*, u.first_name, u.last_name, s.name AS service_name,
                   l.name AS location_name, a.appointment_date, a.start_time
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.doctor_id
            JOIN users u ON d.user_id = u.user_id
            JOIN services s ON a.service_id = s.service_id
            JOIN locations l ON a.location_id = l.location_id
            WHERE a.patient_id = ? AND a.appointment_date >= CURDATE()
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

        // First fetch distinct metric types we want to show (Blood Pressure and Weight)
        $stmt = $conn->prepare("
            SELECT DISTINCT mt.metric_type_id, mt.name AS metric_name, mt.unit
            FROM metric_types mt
            WHERE mt.name IN ('Blood Pressure', 'Weight')
            ORDER BY FIELD(mt.name, 'Blood Pressure', 'Weight')
            LIMIT 2
        ");
        $stmt->execute();
        $metricTypes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Then fetch the latest reading for each metric type
        $healthMetrics = [];
        foreach ($metricTypes as $metricType) {
            $stmt = $conn->prepare("
                SELECT hml.*, mt.name AS metric_name, mt.unit
                FROM health_metric_logs hml
                JOIN metric_types mt ON hml.metric_type_id = mt.metric_type_id
                WHERE hml.patient_id = ? AND hml.metric_type_id = ?
                ORDER BY hml.recorded_at DESC
                LIMIT 1
            ");
            $stmt->bind_param("ii", $patientData['patient_id'], $metricType['metric_type_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result) {
                $healthMetrics[] = $result;
            }
            $stmt->close();
        }

        $confirmedAppointmentsCount = 0;
        if ($patientData && isset($patientData['patient_id'])) {
            $stmt = $conn->prepare("
            SELECT COUNT(*) as confirmed_count
            FROM appointments a
            WHERE a.patient_id = ? 
            AND a.appointment_date >= CURDATE()
            AND a.status = 'confirmed'  
        ");
            $stmt->bind_param("i", $patientData['patient_id']);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $confirmedAppointmentsCount = $result['confirmed_count'] ?? 0;
            $stmt->close();
        }

        // Fetch historical chart data for each metric type (last 5 readings)
        if (!empty($healthMetrics)) {
            foreach ($healthMetrics as $metric) {
                $metricTypeId = $metric['metric_type_id'];
                $metricName = $metric['metric_name'];

                // Prepare query based on metric type to get the appropriate columns
                // Different metrics might use different columns (systolic/diastolic for BP, weight for weight, etc.)
                $valueColumns = "hml.value_numeric, hml.value_text";

                switch ($metricName) {
                    case 'Blood Pressure':
                        $valueColumns = "hml.systolic, hml.diastolic";
                        break;
                    case 'Weight':
                        $valueColumns = "hml.weight";
                        break;
                    case 'Heart Rate':
                        $valueColumns = "hml.heart_rate";
                        break;
                    case 'Blood Glucose':
                        $valueColumns = "hml.glucose";
                        break;
                    case 'Temperature':
                        $valueColumns = "hml.temperature";
                        break;
                    // Add other specific metric types as needed
                }

                $stmt = $conn->prepare("
                    SELECT $valueColumns, 
                           DATE_FORMAT(hml.recorded_at, '%b %d') as date_label,
                           hml.status,
                           hml.recorded_at
                    FROM health_metric_logs hml
                    WHERE hml.patient_id = ? AND hml.metric_type_id = ?
                    ORDER BY hml.recorded_at ASC
                    LIMIT 5
                ");
                $stmt->bind_param("ii", $patientData['patient_id'], $metricTypeId);
                $stmt->execute();
                $result = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Calculate trend and percentage change
                $percentChange = 0;
                $trend = "neutral";

                if (count($result) >= 2) {
                    $firstValue = null;
                    $lastValue = null;

                    // Extract the appropriate values based on metric type
                    switch ($metricName) {
                        case 'Blood Pressure':
                            // For blood pressure, we'll use systolic to determine trend
                            if (isset($result[0]['systolic']) && isset($result[count($result) - 1]['systolic'])) {
                                $firstValue = $result[0]['systolic'];
                                $lastValue = $result[count($result) - 1]['systolic'];
                            }
                            break;
                        case 'Weight':
                            if (isset($result[0]['weight']) && isset($result[count($result) - 1]['weight'])) {
                                $firstValue = $result[0]['weight'];
                                $lastValue = $result[count($result) - 1]['weight'];
                            }
                            break;
                        case 'Heart Rate':
                            if (isset($result[0]['heart_rate']) && isset($result[count($result) - 1]['heart_rate'])) {
                                $firstValue = $result[0]['heart_rate'];
                                $lastValue = $result[count($result) - 1]['heart_rate'];
                            }
                            break;
                        case 'Blood Glucose':
                            if (isset($result[0]['glucose']) && isset($result[count($result) - 1]['glucose'])) {
                                $firstValue = $result[0]['glucose'];
                                $lastValue = $result[count($result) - 1]['glucose'];
                            }
                            break;
                        default:
                            // Generic handling for other metrics using value_numeric
                            if (isset($result[0]['value_numeric']) && isset($result[count($result) - 1]['value_numeric'])) {
                                $firstValue = $result[0]['value_numeric'];
                                $lastValue = $result[count($result) - 1]['value_numeric'];
                            }
                    }

                    // Calculate percentage change if we have valid values
                    if ($firstValue !== null && $lastValue !== null && $firstValue > 0) {
                        $percentChange = round((($lastValue - $firstValue) / $firstValue) * 100);

                        // Determine if trend is positive or negative based on metric type
                        // For some metrics (like blood pressure), lower might be better
                        // For others (like heart rate), it depends on the context
                        switch ($metricName) {
                            case 'Blood Pressure':
                            case 'Blood Glucose':
                                $trend = ($percentChange < 0) ? "positive" : (($percentChange > 0) ? "negative" : "neutral");
                                break;
                            default:
                                // For other metrics, determine based on status or specific rules
                                // This is simplified and might need medical expertise for accurate assessment
                                $trend = ($percentChange < 0) ? "negative" : (($percentChange > 0) ? "positive" : "neutral");
                        }
                    }
                }

                // Format data for charts based on metric type
                $labels = [];
                $dataPoints = [];
                $dataPoints2 = []; // For second value (e.g., diastolic in blood pressure)

                foreach ($result as $reading) {
                    $labels[] = $reading['date_label'];

                    switch ($metricName) {
                        case 'Blood Pressure':
                            $dataPoints[] = $reading['systolic'] ?? 0;
                            $dataPoints2[] = $reading['diastolic'] ?? 0;
                            break;
                        case 'Weight':
                            $dataPoints[] = $reading['weight'] ?? 0;
                            break;
                        case 'Heart Rate':
                            $dataPoints[] = $reading['heart_rate'] ?? 0;
                            break;
                        case 'Blood Glucose':
                            $dataPoints[] = $reading['glucose'] ?? 0;
                            break;
                        case 'Temperature':
                            $dataPoints[] = $reading['temperature'] ?? 0;
                            break;
                        default:
                            $dataPoints[] = $reading['value_numeric'] ?? 0;
                    }
                }

                $chartData[$metric['metric_type_id']] = [
                    'name' => $metricName,
                    'labels' => $labels,
                    'data' => $dataPoints,
                    'data2' => $dataPoints2, // For secondary data series if needed
                    'percentChange' => abs($percentChange),
                    'trend' => $trend,
                    'unit' => $metric['unit'] ?? '',
                    'hasDualValues' => $metricName === 'Blood Pressure'
                ];
            }
        }
    }
} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
}

// Function to format dates
function formatDate($dateString) {
    return $dateString ? date('M j, Y', strtotime($dateString)) : 'N/A';
}

// Function to determine the appropriate icon for a metric
function getMetricIcon($metricName) {
    return match($metricName) {
        'Blood Pressure' => 'bx-heart',
        'Weight' => 'bx-dumbbell',
        'Heart Rate' => 'bx-pulse',
        'Blood Glucose' => 'bx-droplet',
        'Temperature' => 'bx-thermometer',
        default => 'bx-line-chart'
    };
}

// Function to format metric values for display
function formatMetricValue($metric) {
    if ($metric['metric_name'] === 'Blood Pressure' && isset($metric['systolic']) && isset($metric['diastolic'])) {
        return $metric['systolic'] . '/' . $metric['diastolic'];
    } elseif (isset($metric['weight'])) {
        return $metric['weight'] . ' ' . ($metric['unit'] ?? 'kg');
    } elseif (isset($metric['heart_rate'])) {
        return $metric['heart_rate'] . ' ' . ($metric['unit'] ?? 'bpm');
    } elseif (isset($metric['glucose'])) {
        return $metric['glucose'] . ' ' . ($metric['unit'] ?? 'mg/dL');
    } elseif (isset($metric['temperature'])) {
        return $metric['temperature'] . ' ' . ($metric['unit'] ?? '°C');
    } elseif (isset($metric['value_numeric'])) {
        return $metric['value_numeric'] . ' ' . ($metric['unit'] ?? '');
    } else {
        return $metric['value_text'] ?? 'N/A';
    }
}

function formatTimeAgo($dateString) {
    if (!$dateString) return 'N/A';

    $date = new DateTime($dateString);
    $now = new DateTime();
    $diff = $now->diff($date);

    // Check if same day
    if ($date->format('Y-m-d') === $now->format('Y-m-d')) {
        return 'Today';
    }

    // Check if yesterday
    $yesterday = clone $now;
    $yesterday->modify('-1 day');
    if ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
        return 'Yesterday';
    }

    // Return days ago for recent dates (within 30 days)
    if ($diff->days < 30) {
        return $diff->days . ' days ago';
    }

    // Fall back to regular date format for older dates
    return date('M j, Y', strtotime($dateString));
}

function outputChartData($chartData, $metricId) {
    if (!isset($chartData[$metricId])) return;

    $data = $chartData[$metricId];

    // Make sure we have all required fields
    if (!isset($data['trend'])) {
        $data['trend'] = 'neutral';
    }

    if (!isset($data['percentChange'])) {
        $data['percentChange'] = 0;
    }

    // Make sure we have proper formatted labels
    if (empty($data['labels'])) {
        $data['labels'] = ['', '', '', '', ''];
    }

    // Output clean, complete data
    echo '<script id="chart-data-' . $metricId . '" type="application/json">';
    echo json_encode($data);
    echo '</script>';
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
    <!-- Add Chart.js for the health metrics charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                <li>
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
            <a href="../actions/logout.php" class="logout-link">
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
                    <div class="welcome-actions">
                        <a href="appointments.php" class="btn primary-btn">
                            <i class='bx bx-calendar-plus'></i>
                            <span>Book Appointment</span>
                        </a>
                        <a href="health-chat.php" class="btn secondary-btn">
                            <i class='bx bx-chat'></i>
                            <span>Ask Health Assistant</span>
                        </a>
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
                            <p class="stat-value"><?= $confirmedAppointmentsCount ?></p>
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
                        <h3>Health Metrics Overview</h3>
                        <a href="health_tracking.php" class="view-all">View Details</a>
                    </div>
                    <div class="metrics-grid">
                        <?php if (!empty($healthMetrics)): ?>
                            <?php foreach ($healthMetrics as $metric): ?>
                                <?php
                                $metricId = $metric['metric_type_id'];
                                $metricData = $chartData[$metricId] ?? null;

                                // Determine icon class based on metric name
                                $iconClass = getMetricIcon($metric['metric_name']);

                                // Format the trend direction and class
                                $trendClass = $metricData['trend'] ?? 'neutral';
                                $trendArrow = $trendClass === 'positive' ? 'down' : 'up';
                                $percentChange = $metricData['percentChange'] ?? 0;

                                // Output chart data for JavaScript
                                outputChartData($chartData, $metricId);
                                ?>
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4><?= htmlspecialchars($metric['metric_name']) ?></h4>
                                        <div class="metric-icon">
                                            <i class='bx <?= $iconClass ?>'></i>
                                        </div>
                                    </div>
                                    <div class="metric-value">
                            <span class="current-value">
                                <?= formatMetricValue($metric) ?>
                            </span>
                                        <?php if ($metricData && isset($metricData['percentChange']) && $metricData['percentChange'] > 0): ?>
                                            <span class="metric-trend <?= $trendClass ?>">
                                    <i class='bx bx-<?= $trendArrow ?>-arrow'></i>
                                    <?= $percentChange ?>%
                                </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="metric-chart" id="chart-container-<?= $metricId ?>">
                                        <canvas id="chart-<?= $metricId ?>" height="120"></canvas>
                                    </div>
                                    <div class="metric-footer">
                                        <span>Last updated: <?= formatTimeAgo($metric['recorded_at']) ?></span>
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
</body>
</html>