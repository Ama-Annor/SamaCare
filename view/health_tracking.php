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
                header("Refresh:0");
                exit;
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
            header("Refresh:0");
            exit;
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
        case 'custom':
            if ($startDate && $endDate) {
                $dateCondition = "recorded_at BETWEEN ? AND ?";
                $params = [$startDate, $endDate];
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

    // Group for overview
    $groupedMetrics = [];
    foreach ($allMetrics as $metric) {
        $typeId = $metric['metric_type_id'];
        if (!isset($groupedMetrics[$typeId])) {
            $groupedMetrics[$typeId] = $metric;
        }
    }
    $healthMetrics = array_values($groupedMetrics);

} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

// Helpers
function formatDate($date) { return date('M j, Y', strtotime($date)); }
function formatTime($time) { return date('g:i A', strtotime($time)); }
function getStatus($metric) {
    if ($metric['metric_name'] === 'Blood Pressure') {
        if ($metric['systolic'] < 120 && $metric['diastolic'] < 80) return 'normal';
        if ($metric['systolic'] < 130 || $metric['diastolic'] < 80) return 'elevated';
        return 'high';
    }
    return '';
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
            </ul>
        </nav>

        <div class="sidebar-footer">
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
                <div class="user-dropdown">
                    <button class="user-btn">
                        <div class="user-avatar small">
                            <span><?= strtoupper(substr($patientData['first_name'], 0, 1) . substr($patientData['last_name'], 0, 1)) ?></span>
                        </div>
                        <span class="user-name"><?= htmlspecialchars($patientData['first_name'] . ' ' . $patientData['last_name']) ?></span>
                    </button>
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
                                    '90' => 'Last 90 Days',
                                    'custom' => 'Custom Range',
                                    default => 'Last 30 Days'
                                } ?>
                            </span>
                        </button>
                    </div>
                </div>
            </section>

            <section class="metrics-overview">
                <div class="metrics-grid">
                    <?php if (!empty($healthMetrics)): ?>
                        <?php foreach ($healthMetrics as $metric):
                            $status = getStatus($metric);
                            $value = $metric['metric_name'] === 'Blood Pressure'
                                ? $metric['systolic'] . '/' . $metric['diastolic'] . ' ' . $metric['unit']
                                : $metric['weight'] . ' ' . $metric['unit'];
                            ?>
                            <div class="metric-card">
                                <div class="metric-header">
                                    <h3><?= htmlspecialchars($metric['metric_name']) ?></h3>
                                    <div class="metric-icon">
                                        <i class='bx <?= $metric['metric_name'] === 'Weight' ? 'bx-dumbbell' : 'bx-heart' ?>'></i>
                                    </div>
                                </div>
                                <div class="metric-body">
                                    <div class="current-reading">
                                        <div class="reading-value"><?= htmlspecialchars($value) ?></div>
                                        <?php if ($metric['metric_name'] === 'Blood Pressure'): ?>
                                            <div class="reading-status <?= $status ?>"><?= ucfirst($status) ?></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="reading-time">
                                        <?= formatDate($metric['recorded_at']) ?> at <?= formatTime($metric['recorded_at']) ?>
                                    </div>
                                </div>
                                <div class="metric-chart">
                                    <canvas id="<?= strtolower(str_replace(' ', '-', $metric['metric_name'])) ?>-chart"></canvas>
                                </div>
                                <div class="metric-actions">
                                    <button class="btn secondary-btn view-history-btn" data-type="<?= $metric['metric_type_id'] ?>">
                                        View History
                                    </button>
                                    <button class="btn primary-btn add-reading-btn" data-type="<?= $metric['metric_type_id'] ?>">
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
                                <tbody>
                                <?php foreach ($allMetrics as $metric): ?>
                                    <tr>
                                        <td><?= formatDate($metric['recorded_at']) ?></td>
                                        <td><?= formatTime($metric['recorded_at']) ?></td>
                                        <td>
                                            <?= $metric['metric_name'] === 'Blood Pressure'
                                                ? $metric['systolic'] . '/' . $metric['diastolic'] . ' ' . $metric['unit']
                                                : $metric['weight'] . ' ' . $metric['unit'] ?>
                                        </td>
                                        <td>
                                            <span class="status-badge <?= getStatus($metric) ?>">
                                                <?= ucfirst(getStatus($metric)) ?>
                                            </span>
                                        </td>
                                        <td><?= htmlspecialchars($metric['notes']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
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
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Date</label>
                                    <input type="date" name="recorded_date" value="<?= date('Y-m-d') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Time</label>
                                    <input type="time" name="recorded_time" value="<?= date('H:i') ?>" required>
                                </div>
                            </div>

                            <div id="blood-pressure-fields">
                                <div class="form-row">
                                    <div class="form-group">
                                        <label>Systolic (mmHg)</label>
                                        <input type="number" name="systolic" min="50" max="250" required>
                                    </div>
                                    <div class="form-group">
                                        <label>Diastolic (mmHg)</label>
                                        <input type="number" name="diastolic" min="30" max="150" required>
                                    </div>
                                </div>
                            </div>

                            <div id="weight-fields" style="display: none;">
                                <div class="form-group">
                                    <label>Weight (kg)</label>
                                    <input type="number" step="0.1" name="weight" min="20" max="300" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" rows="3"></textarea>
                            </div>
                        </div>
                        <div class="form-footer">
                            <button type="button" class="btn secondary-btn cancel-btn">Cancel</button>
                            <button type="submit" name="add_reading" class="btn primary-btn">Save</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Initialize charts
        const charts = {};
        <?php foreach ($healthMetrics as $metric): ?>
        initChart('<?= strtolower(str_replace(' ', '-', $metric['metric_name'])) ?>-chart',
            '<?= $metric['metric_name'] ?>');
        <?php endforeach; ?>

        // View History
        document.querySelectorAll('.view-history-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const metricType = this.dataset.type;
                document.getElementById('metric-history').classList.add('active');
                initHistoryChart(metricType);
            });
        });

        // Add Reading
        document.querySelectorAll('.add-reading-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const metricType = this.dataset.type;
                showAddForm(metricType);
            });
        });

        // Close modals
        document.querySelectorAll('.close-form, .close-history, .cancel-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.add-reading-form, .metric-history-section')
                    .forEach(el => el.classList.remove('active'));
            });
        });

        function showAddForm(metricType) {
            const form = document.getElementById('add-reading-form');
            form.querySelector('#form-metric-type').value = metricType;

            // Toggle fields
            document.getElementById('blood-pressure-fields').style.display =
                metricType === '1' ? 'block' : 'none';
            document.getElementById('weight-fields').style.display =
                metricType === '2' ? 'block' : 'none';

            form.classList.add('active');
        }

        function initChart(canvasId, label) {
            const ctx = document.getElementById(canvasId).getContext('2d');
            charts[canvasId] = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['6am', '9am', '12pm', '3pm', '6pm'],
                    datasets: [{
                        label: label,
                        data: Array.from({length: 5}, () => Math.random() * 40 + 80),
                        borderColor: '#2a9d8f',
                        tension: 0.4
                    }]
                }
            });
        }

        function initHistoryChart(metricType) {
            const ctx = document.getElementById('history-chart').getContext('2d');
            if (window.historyChart) window.historyChart.destroy();

            window.historyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri'],
                    datasets: [{
                        label: 'Weekly Trend',
                        data: Array.from({length: 5}, () => Math.random() * 40 + 80),
                        borderColor: '#2a9d8f',
                        tension: 0.4
                    }]
                }
            });
        }

        // Auto-remove toasts
        setTimeout(() => {
            document.querySelectorAll('.toast-message').forEach(toast => toast.remove());
        }, 3000);
    });
</script>
</body>
</html>