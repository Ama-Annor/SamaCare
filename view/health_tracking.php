<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


require_once '../db/db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

// Initialize variables
$patientData = [];
$healthMetrics = [];
$allMetrics = [];
$error = '';
$success = '';
$dateFilter = $_POST['date_filter'] ?? '30';
$startDate = $_POST['start_date'] ?? '';
$endDate = $_POST['end_date'] ?? '';

// Process form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Handle new reading
        if (isset($_POST['add_reading'])) {
            // Verify patient exists
            $stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
            $stmt->bind_param("i", $_SESSION["user_id"]);
            $stmt->execute();
            $patient = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            if (!$patient) throw new Exception("Patient record not found");

            $metric_type = (int)$_POST['metric_type'];
            $recorded_at = date('Y-m-d H:i:s', strtotime(
                $_POST['recorded_date'] . ' ' . $_POST['recorded_time']
            ));
            $notes = $conn->real_escape_string($_POST['notes'] ?? '');

            // Validate based on metric type
            if ($metric_type === 1) { // Blood Pressure
                $systolic = (int)$_POST['systolic'];
                $diastolic = (int)$_POST['diastolic'];

                if ($systolic < 50 || $systolic > 250) {
                    throw new Exception("Systolic must be between 50-250");
                }
                if ($diastolic < 30 || $diastolic > 150) {
                    throw new Exception("Diastolic must be between 30-150");
                }

                $stmt = $conn->prepare("INSERT INTO health_metric_logs 
                    (patient_id, metric_type_id, systolic, diastolic, recorded_at, notes)
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("iiisss",
                    $patient['patient_id'],
                    $metric_type,
                    $systolic,
                    $diastolic,
                    $recorded_at,
                    $notes
                );
            }
            elseif ($metric_type === 2) { // Weight
                $weight = (float)$_POST['weight'];

                if ($weight < 20 || $weight > 300) {
                    throw new Exception("Weight must be between 20-300 kg");
                }

                $stmt = $conn->prepare("INSERT INTO health_metric_logs 
                    (patient_id, metric_type_id, weight, recorded_at, notes)
                    VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iidss",
                    $patient['patient_id'],
                    $metric_type,
                    $weight,
                    $recorded_at,
                    $notes
                );
            }

            if ($stmt->execute()) {
                $success = "Reading added successfully!";
                // Don't refresh the page instantly, let the success message display
            } else {
                throw new Exception("Database error: " . $stmt->error);
            }
        }

        // Handle date filter
        if (isset($_POST['apply_date_filter'])) {
            $dateFilter = $_POST['date_range'];
            if ($dateFilter === 'custom') {
                $startDate = $_POST['start_date'];
                $endDate = $_POST['end_date'];
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch data
try {
    // Get patient data
    $stmt = $conn->prepare("SELECT p.*, u.* FROM patients p 
                          JOIN users u ON p.user_id = u.user_id 
                          WHERE u.user_id = ?");
    $stmt->bind_param("i", $_SESSION["user_id"]);
    $stmt->execute();
    $patientData = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$patientData) throw new Exception("Patient not found");

    // Build date filter
    $dateCondition = "1=1";
    $params = [];
    switch ($dateFilter) {
        case '7': $dateCondition = "recorded_at >= CURDATE() - INTERVAL 7 DAY"; break;
        case '30': $dateCondition = "recorded_at >= CURDATE() - INTERVAL 30 DAY"; break;
        case '90': $dateCondition = "recorded_at >= CURDATE() - INTERVAL 90 DAY"; break;
        case '365': $dateCondition = "recorded_at >= CURDATE() - INTERVAL 365 DAY"; break;
        case 'custom':
            if ($startDate && $endDate) {
                $dateCondition = "recorded_at BETWEEN ? AND ?";
                $params = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];
            }
            break;
    }

    // Get metrics
    $query = "SELECT hml.*, mt.name AS metric_name, mt.unit 
            FROM health_metric_logs hml
            JOIN metric_types mt ON hml.metric_type_id = mt.metric_type_id
            WHERE hml.patient_id = ? AND $dateCondition
            ORDER BY hml.recorded_at DESC";

    $stmt = $conn->prepare($query);

    if ($dateFilter === 'custom' && !empty($params)) {
        $stmt->bind_param("iss", $patientData['patient_id'], ...$params);
    } else {
        $stmt->bind_param("i", $patientData['patient_id']);
    }

    $stmt->execute();
    $allMetrics = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Group metrics by type
    $groupedMetrics = [];
    foreach ($allMetrics as $metric) {
        $typeId = $metric['metric_type_id'];
        if (!isset($groupedMetrics[$typeId])) {
            $groupedMetrics[$typeId] = [];
        }
        $groupedMetrics[$typeId][] = $metric;
    }

    // Get latest reading for each metric type
    $latestMetrics = [];
    foreach ($groupedMetrics as $typeId => $metrics) {
        $latestMetrics[$typeId] = $metrics[0]; // First item is the latest due to DESC order
    }

    // Format data for charts
    $chartData = [];
    foreach ($groupedMetrics as $typeId => $metrics) {
        $chartData[$typeId] = [
            'labels' => [],
            'datasets' => []
        ];

        if ($typeId == 1) { // Blood Pressure
            $systolicData = [];
            $diastolicData = [];

            // Get the latest 10 readings in chronological order
            $metrics = array_slice(array_reverse($metrics), 0, 10);

            foreach ($metrics as $metric) {
                $chartData[$typeId]['labels'][] = date('M j', strtotime($metric['recorded_at']));
                $systolicData[] = $metric['systolic'];
                $diastolicData[] = $metric['diastolic'];
            }

            $chartData[$typeId]['datasets'][] = [
                'label' => 'Systolic',
                'data' => $systolicData,
                'borderColor' => '#2a9d8f'
            ];

            $chartData[$typeId]['datasets'][] = [
                'label' => 'Diastolic',
                'data' => $diastolicData,
                'borderColor' => '#e76f51'
            ];
        } else if ($typeId == 2) { // Weight
            $weightData = [];

            // Get the latest 10 readings in chronological order
            $metrics = array_slice(array_reverse($metrics), 0, 10);

            foreach ($metrics as $metric) {
                $chartData[$typeId]['labels'][] = date('M j', strtotime($metric['recorded_at']));
                $weightData[] = $metric['weight'];
            }

            $chartData[$typeId]['datasets'][] = [
                'label' => 'Weight',
                'data' => $weightData,
                'borderColor' => '#2a9d8f'
            ];
        }
    }

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Helper functions
function formatDate($date) { return date('M j, Y', strtotime($date)); }
function formatTime($time) { return date('g:i A', strtotime($time)); }

function getStatus($metric) {
    if ($metric['metric_name'] === 'Blood Pressure') {
        $systolic = $metric['systolic'];
        $diastolic = $metric['diastolic'];

        if ($systolic < 120 && $diastolic < 80) {
            return 'normal';
        } elseif (($systolic >= 120 && $systolic < 130) && $diastolic < 80) {
            return 'elevated';
        } elseif (($systolic >= 130 && $systolic < 140) || ($diastolic >= 80 && $diastolic < 90)) {
            return 'high';
        } elseif ($systolic >= 140 || $diastolic >= 90) {
            return 'very-high';
        }
        return 'normal';
    } elseif ($metric['metric_name'] === 'Weight') {
        // Implement weight status logic if needed
        return 'normal';
    }
    return '';
}

function getStatusText($status) {
    switch ($status) {
        case 'normal': return 'Normal';
        case 'elevated': return 'Elevated';
        case 'high': return 'High (Stage 1)';
        case 'very-high': return 'High (Stage 2)';
        default: return 'Normal';
    }
}

function getTrendPercentage($metrics, $typeId) {
    if (empty($metrics) || count($metrics) < 2) {
        return ['value' => 0, 'direction' => 'stable'];
    }

    $latest = $metrics[0];
    $oldest = end($metrics);

    if ($typeId == 1) { // Blood Pressure - use systolic for trend
        $currentValue = $latest['systolic'];
        $previousValue = $oldest['systolic'];
    } elseif ($typeId == 2) { // Weight
        $currentValue = $latest['weight'];
        $previousValue = $oldest['weight'];
    } else {
        return ['value' => 0, 'direction' => 'stable'];
    }

    if ($previousValue == 0) return ['value' => 0, 'direction' => 'stable'];

    $change = $currentValue - $previousValue;
    $percentChange = round(($change / $previousValue) * 100, 1);

    $direction = 'stable';
    if ($percentChange > 0) {
        $direction = 'up';
    } elseif ($percentChange < 0) {
        $direction = 'down';
        $percentChange = abs($percentChange);
    }

    return ['value' => $percentChange, 'direction' => $direction];
}

// Determine if a trend is positive or negative for health
function isTrendPositive($trend, $typeId) {
    if ($typeId == 1) { // Blood Pressure - lower is better
        return $trend['direction'] == 'down';
    } elseif ($typeId == 2) { // Weight - depends on person's goals, assume stable is best
        return $trend['direction'] == 'stable';
    }
    return false;
}

// Function to get the human-readable time elapsed
function timeElapsed($datetime) {
    $now = new DateTime();
    $then = new DateTime($datetime);
    $diff = $now->diff($then);

    if ($diff->d == 0) {
        if ($diff->h == 0) {
            if ($diff->i == 0) {
                return "Just now";
            }
            return $diff->i . " minute" . ($diff->i > 1 ? "s" : "") . " ago";
        }
        return $diff->h . " hour" . ($diff->h > 1 ? "s" : "") . " ago";
    } elseif ($diff->d == 1) {
        return "Yesterday";
    } elseif ($diff->d <= 7) {
        return $diff->d . " day" . ($diff->d > 1 ? "s" : "") . " ago";
    } else {
        return formatDate($datetime);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Health Tracking - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/health_tracking.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="dashboard-container">
    <!-- Sidebar -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class='bx bx-plus-medical'></i>SAMA<span>CARE</span>
            </div>
            <button class="close-sidebar"><i class='bx bx-x'></i></button>
        </div>

        <div class="user-profile">
            <div class="user-avatar">
                <span><?= strtoupper(substr($patientData['first_name'], 0, 1) . substr($patientData['last_name'], 0, 1)) ?></span>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars($patientData['first_name'] . ' ' . $patientData['last_name']) ?></h4>
                <p>Patient</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li><a href="dashboard.php"><i class='bx bx-home-alt'></i><span>Dashboard</span></a></li>
                <li><a href="medical_records.php"><i class='bx bx-folder'></i><span>Medical Records</span></a></li>
                <li><a href="appointments.php"><i class='bx bx-calendar'></i><span>Appointments</span></a></li>
                <li><a href="health_tracking.php" class="active"><i class='bx bx-line-chart'></i><span>Health Tracking</span></a></li>
                <li><a href="health_assistant.php"><i class='bx bx-chat'></i><span>Health Assistant</span></a></li>
            </ul>
        </nav>

        <div class="sidebar-footer">
            <a href="help.php"><i class='bx bx-help-circle'></i><span>Help & Support</span></a>
            <a href="../actions/logout.php"><i class='bx bx-log-out'></i><span>Log Out</span></a>
        </div>
    </aside>

    <main class="main-content">
        <header class="dashboard-header">
            <div class="header-left">
                <button class="menu-toggle"><i class='bx bx-menu'></i></button>
                <h1>Health Tracking</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search metrics...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">2</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small">
                                <span><?= strtoupper(substr($patientData['first_name'], 0, 1) . substr($patientData['last_name'], 0, 1)) ?></span>
                            </div>
                            <span class="user-name"><?= htmlspecialchars($patientData['first_name'] . ' ' . $patientData['last_name']) ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <div class="dashboard-content">
            <?php if ($error): ?>
                <div class="toast-message error"><?= $error ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="toast-message success"><?= $success ?></div>
            <?php endif; ?>

            <section class="action-bar">
                <div class="action-left">
                    <button class="btn primary-btn" id="addNewMeasurementBtn">
                        <i class='bx bx-plus'></i>
                        <span>Add New Measurement</span>
                    </button>
                    <div class="date-filter">
                        <button class="btn secondary-btn" id="openDateFilter">
                            <i class='bx bx-calendar'></i>
                            <span>
                                <?= match($dateFilter) {
                                    '7' => 'Last 7 Days',
                                    '30' => 'Last 30 Days',
                                    '90' => 'Last 3 Months',
                                    '365' => 'Last Year',
                                    'custom' => 'Custom Range',
                                    default => 'Last 30 Days'
                                } ?>
                            </span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </section>

            <section class="metrics-overview">
                <div class="metrics-grid">
                    <?php if (!empty($latestMetrics)): ?>
                        <?php foreach ($latestMetrics as $typeId => $metric):
                            $allMetricsOfType = $groupedMetrics[$typeId] ?? [];
                            $status = getStatus($metric);
                            $trend = getTrendPercentage($allMetricsOfType, $typeId);
                            $isTrendPositive = isTrendPositive($trend, $typeId);

                            // Format the value display
                            if ($typeId == 1) { // Blood Pressure
                                $value = $metric['systolic'] . '/' . $metric['diastolic'] . ' ' . $metric['unit'];
                            } elseif ($typeId == 2) { // Weight
                                $value = $metric['weight'] . ' ' . $metric['unit'];
                            } else {
                                $value = 'N/A';
                            }

                            // Format the time elapsed
                            $timeElapsed = timeElapsed($metric['recorded_at']);
                            ?>
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h3><?= htmlspecialchars($metric['metric_name']) ?></h3>
                                    <div class="metric-icon">
                                        <i class='bx <?= $typeId == 1 ? 'bx-heart' : 'bx-dumbbell' ?>'></i>
                                    </div>
                                </div>
                                <div class="metric-body">
                                    <div class="current-reading">
                                        <div class="reading-value"><?= htmlspecialchars($value) ?></div>
                                        <div class="reading-status <?= $status ?>"><?= getStatusText($status) ?></div>
                                    </div>
                                    <div class="reading-trend">
                                        <span class="trend-label">Trend: </span>
                                        <span class="trend-value <?= $isTrendPositive ? 'positive' : 'negative' ?>">
                                        <i class='bx bx-<?= $trend['direction'] == 'up' ? 'up' : 'down' ?>-arrow'></i>
                                        <?= $trend['value'] ?>% in last <?= match($dateFilter) {
                                                '7' => '7 days',
                                                '30' => '30 days',
                                                '90' => '3 months',
                                                '365' => 'year',
                                                'custom' => 'period',
                                                default => '30 days'
                                            } ?>
                                    </span>
                                    </div>
                                    <div class="reading-time">
                                        Last reading: <?= $timeElapsed ?>
                                    </div>
                                </div>
                                <div class="metric-chart">
                                    <canvas id="<?= $typeId == 1 ? 'bp' : 'weight' ?>-chart"></canvas>
                                </div>
                                <div class="metric-actions">
                                    <button class="btn secondary-btn view-history-btn" data-metric="<?= $typeId ?>">
                                        View History
                                    </button>
                                    <button class="btn primary-btn add-reading-btn" data-metric="<?= $typeId ?>">
                                        <i class='bx bx-plus'></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="no-metrics">
                            <div class="add-metric">
                                <div class="add-metric-content">
                                    <div class="add-icon"><i class='bx bx-line-chart'></i></div>
                                    <h4>No Health Metrics Found</h4>
                                    <p>Add your first reading to get started</p>
                                    <button class="btn primary-btn" id="addFirstReadingBtn">
                                        <i class='bx bx-plus'></i>
                                        <span>Add First Reading</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>

            <!-- History Section -->
            <section class="metric-history-section" id="metric-history">
                <div class="content-card">
                    <div class="card-header">
                        <h3>Health History</h3>
                        <button class="close-history"><i class='bx bx-x'></i></button>
                    </div>
                    <div class="history-content">
                        <div class="history-chart">
                            <canvas id="history-chart"></canvas>
                        </div>
                        <div class="history-table">
                            <table>
                                <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Reading</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                </tr>
                                </thead>
                                <tbody id="history-table-body">
                                <?php if (!empty($allMetrics)): ?>
                                    <?php foreach ($allMetrics as $index => $metric):
                                        $status = getStatus($metric);
                                        if ($metric['metric_type_id'] == 1) {
                                            $reading = $metric['systolic'] . '/' . $metric['diastolic'] . ' ' . $metric['unit'];
                                        } else {
                                            $reading = $metric['weight'] . ' ' . $metric['unit'];
                                        }
                                        ?>
                                        <tr data-metric-type="<?= $metric['metric_type_id'] ?>">
                                            <td><?= formatDate($metric['recorded_at']) ?></td>
                                            <td><?= formatTime($metric['recorded_at']) ?></td>
                                            <td><?= htmlspecialchars($reading) ?></td>
                                            <td>
                                                <span class="status-badge <?= $status ?>"><?= getStatusText($status) ?></span>
                                            </td>
                                            <td>
                                                <?php if (!empty($metric['notes'])): ?>
                                                    <?= nl2br(htmlspecialchars($metric['notes'])) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">N/A</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="no-data">No records found</td>
                                    </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Add Reading Form -->
            <div class="add-reading-form" id="add-reading-form">
                <div class="form-card">
                    <form method="POST">
                        <div class="form-header">
                            <h3>Add Reading</h3>
                            <button type="button" class="close-form"><i class='bx bx-x'></i></button>
                        </div>
                        <input type="hidden" name="metric_type" id="form-metric-type" value="1">
                        <div class="form-body">
                            <div class="form-group">
                                <label for="recorded_date">Date</label>
                                <input type="date" id="recorded_date" name="recorded_date" value="<?= date('Y-m-d') ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="recorded_time">Time</label>
                                <input type="time" id="recorded_time" name="recorded_time" value="<?= date('H:i') ?>" required>
                            </div>

                            <div id="blood-pressure-fields">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="systolic">Systolic (mmHg)</label>
                                        <input type="number" id="systolic" name="systolic" min="50" max="250" placeholder="120" >
                                    </div>
                                    <div class="form-group">
                                        <label for="diastolic">Diastolic (mmHg)</label>
                                        <input type="number" id="diastolic" name="diastolic" min="30" max="150" placeholder="80" >
                                    </div>
                                </div>
                            </div>

                            <div id="weight-fields" style="display: none;">
                                <div class="form-group">
                                    <label for="weight">Weight (kg)</label>
                                    <input type="number" id="weight" step="0.1" name="weight" min="20" max="300" placeholder="70" >
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="notes">Notes (Optional)</label>
                                <textarea id="notes" name="notes" rows="3" placeholder="Add any notes or context about this reading"></textarea>
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="button" class="btn secondary-btn cancel-btn">Cancel</button>
                            <button type="submit" name="add_reading" class="btn primary-btn">Save Reading</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Date Filter Modal -->
            <div class="date-filter-modal" id="date-filter-modal">
                <div class="form-card">
                    <form method="POST">
                        <div class="form-header">
                            <h3>Select Date Range</h3>
                            <button type="button" class="close-form" id="close-date-filter">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                        <div class="form-body">
                            <div class="form-group">
                                <label>Preset Ranges</label>
                                <div class="date-preset-options">
                                    <button type="button" class="preset-btn <?= $dateFilter == '7' ? 'active' : '' ?>" data-days="7">Last 7 Days</button>
                                    <button type="button" class="preset-btn <?= $dateFilter == '30' || $dateFilter == '' ? 'active' : '' ?>" data-days="30">Last 30 Days</button>
                                    <button type="button" class="preset-btn <?= $dateFilter == '90' ? 'active' : '' ?>" data-days="90">Last 3 Months</button>
                                    <button type="button" class="preset-btn <?= $dateFilter == '365' ? 'active' : '' ?>" data-days="365">Last Year</button>
                                </div>
                            </div>

                            <input type="hidden" name="date_range" id="date_range" value="<?= $dateFilter ?>">

                            <div class="form-group">
                                <label>Custom Range</label>
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="start_date">Start Date</label>
                                        <input type="date" id="start_date" name="start_date" value="<?= $startDate ?>">
                                    </div>
                                    <div class="form-group">
                                        <label for="end_date">End Date</label>
                                        <input type="date" id="end_date" name="end_date" value="<?= $endDate ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="button" class="btn secondary-btn" id="cancel-date-filter">Cancel</button>
                            <button type="submit" name="apply_date_filter" class="btn primary-btn" id="apply-date-filter">Apply Filter</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <footer class="dashboard-footer">
            <p>&copy; 2024 SamaCare. All rights reserved.</p>
            <div class="footer-links">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Help & Support</a>
            </div>
        </footer>
    </main>

<script src="../assets/js/health_tracking.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Parse chart data from PHP
        const chartData = <?= json_encode($chartData) ?>;
        const charts = {};

        // Initialize available charts based on data
        if (chartData[1]) { // Blood Pressure
            initBPChart('bp-chart', chartData[1]);
        }

        if (chartData[2]) { // Weight
            initWeightChart('weight-chart', chartData[2]);
        }

        // View History button handlers
        document.querySelectorAll('.view-history-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const metricId = this.dataset.metric;
                document.getElementById('metric-history').classList.add('active');

                // Filter table rows based on selected metric type
                document.querySelectorAll('#history-table-body tr').forEach(row => {
                    if (row.dataset.metricType === metricId || !metricId) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Initialize history chart with the right data
                initHistoryChart(metricId);
            });
        });

        // Add Reading button handlers
        document.querySelectorAll('.add-reading-btn, #addNewMeasurementBtn, #addFirstReadingBtn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Use data-metric attribute if available, default to blood pressure (1)
                const metricType = this.dataset.metric || '1';
                showAddForm(metricType);
            });
        });

        // Close modals
        document.querySelectorAll('.close-form, .close-history, .cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.add-reading-form, .metric-history-section, .date-filter-modal')
                    .forEach(el => el.classList.remove('active'));
            });
        });

        // Date filter modal handlers
        document.getElementById('openDateFilter').addEventListener('click', () => {
            document.getElementById('date-filter-modal').classList.add('active');
        });

        document.getElementById('close-date-filter').addEventListener('click', () => {
            document.getElementById('date-filter-modal').classList.remove('active');
        });

        document.getElementById('cancel-date-filter').addEventListener('click', () => {
            document.getElementById('date-filter-modal').classList.remove('active');
        });

        // Date preset buttons
        document.querySelectorAll('.preset-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('date_range').value = this.dataset.days;

                // Clear custom date fields if a preset is selected
                if (this.dataset.days !== 'custom') {
                    document.getElementById('start_date').value = '';
                    document.getElementById('end_date').value = '';
                }
            });
        });

        // If start_date and end_date are filled, switch to custom mode
        const startDate = document.getElementById('start_date').value;
        const endDate = document.getElementById('end_date').value;
        if (startDate && endDate) {
            document.getElementById('date_range').value = 'custom';
            document.querySelectorAll('.preset-btn').forEach(b => b.classList.remove('active'));
        }

        // Function to show the add form with appropriate fields
        function showAddForm(metricType) {
            const form = document.getElementById('add-reading-form');
            form.querySelector('#form-metric-type').value = metricType;

            // Toggle fields based on metric type
            document.getElementById('blood-pressure-fields').style.display =
                metricType === '1' ? 'block' : 'none';
            document.getElementById('weight-fields').style.display =
                metricType === '2' ? 'block' : 'none';

            // Clear form fields
            form.querySelectorAll('input[type="number"]').forEach(input => {
                input.value = '';
            });
            form.querySelector('#notes').value = '';

            form.classList.add('active');
        }

        // Initialize Blood Pressure chart
        function initBPChart(canvasId, data) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            charts[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.borderColor,
                        backgroundColor: ds.borderColor + '20',
                        tension: 0.4,
                        fill: false,
                        pointBackgroundColor: ds.borderColor
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#f0f0f0'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }

        // Initialize Weight chart
        function initWeightChart(canvasId, data) {
            const ctx = document.getElementById(canvasId);
            if (!ctx) return;

            charts[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.borderColor,
                        backgroundColor: ds.borderColor + '20',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: ds.borderColor
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#f0f0f0'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }

        // Initialize history chart with filtered data
        function initHistoryChart(metricId) {
            const ctx = document.getElementById('history-chart');
            if (!ctx) return;

            // Destroy previous chart if exists
            if (window.historyChart) {
                window.historyChart.destroy();
            }

            // Get data for selected metric type
            const data = chartData[metricId];
            if (!data) return;

            window.historyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: data.labels,
                    datasets: data.datasets.map(ds => ({
                        label: ds.label,
                        data: ds.data,
                        borderColor: ds.borderColor,
                        backgroundColor: ds.borderColor + '20',
                        tension: 0.4,
                        fill: metricId === '2', // Fill only for weight chart
                        pointBackgroundColor: ds.borderColor
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: false,
                            grid: {
                                color: '#f0f0f0'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                pointStyle: 'circle'
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    }
                }
            });
        }

        // Toggle mobile sidebar
        document.querySelector('.menu-toggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.add('active');
        });

        document.querySelector('.close-sidebar').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.remove('active');
        });

        // Auto-remove toast messages after 3 seconds
        setTimeout(() => {
            document.querySelectorAll('.toast-message').forEach(toast => {
                toast.classList.add('fade-out');
                setTimeout(() => toast.remove(), 500);
            });
        }, 3000);
    });
</script>
</body>
</html>