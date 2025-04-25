<!-- PHP Changes -->
<?php
session_start();

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

require_once '../db/db_connect.php';

// Get patient ID based on user ID
$user_id = $_SESSION["user_id"];
$patient_id = null;

$stmt = $conn->prepare("SELECT patient_id FROM patients WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $patient_id = $row["patient_id"];
}
$stmt->close();

// Fetch latest health metrics (blood pressure and weight)
$bloodPressure = "N/A";
$weight = "N/A";
$bloodPressureDate = "";
$weightDate = "";

if($patient_id) {
    // Get latest blood pressure
    $stmt = $conn->prepare("
        SELECT systolic, diastolic, recorded_at 
        FROM health_metric_logs 
        WHERE patient_id = ? AND systolic IS NOT NULL AND diastolic IS NOT NULL 
        ORDER BY recorded_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $bloodPressure = $row["systolic"] . "/" . $row["diastolic"];
        $bloodPressureDate = date("M d, Y", strtotime($row["recorded_at"]));
    }
    $stmt->close();

    // Get latest weight
    $stmt = $conn->prepare("
        SELECT weight, recorded_at 
        FROM health_metric_logs 
        WHERE patient_id = ? AND weight IS NOT NULL 
        ORDER BY recorded_at DESC LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $weight = $row["weight"] . " kg";
        $weightDate = date("M d, Y", strtotime($row["recorded_at"]));
    }
    $stmt->close();
}

// Fetch next appointment
$nextAppointment = null;
$lastAppointment = null;

if($patient_id) {
    // Get next appointment
    $stmt = $conn->prepare("
        SELECT a.appointment_date, a.start_time, s.name as service_name, 
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        JOIN services s ON a.service_id = s.service_id
        WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() AND a.status != 'cancelled'
        ORDER BY a.appointment_date, a.start_time ASC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $nextAppointment = $result->fetch_assoc();
    }
    $stmt->close();

    // Get last appointment
    $stmt = $conn->prepare("
        SELECT a.appointment_date, a.start_time, s.name as service_name, 
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.doctor_id
        JOIN users u ON d.user_id = u.user_id
        JOIN services s ON a.service_id = s.service_id
        WHERE a.patient_id = ? AND a.appointment_date < CURDATE() AND a.status = 'completed'
        ORDER BY a.appointment_date DESC, a.start_time DESC
        LIMIT 1
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $lastAppointment = $result->fetch_assoc();
    }
    $stmt->close();
}

// Fetch medical records
$records = [];
if($patient_id) {
    $stmt = $conn->prepare("
        SELECT mr.record_id, mr.title, mr.description, mr.record_date, rc.name as category_name,
               CONCAT(u.first_name, ' ', u.last_name) as doctor_name
        FROM medical_records mr
        LEFT JOIN record_categories rc ON mr.category_id = rc.category_id
        LEFT JOIN doctors d ON mr.doctor_id = d.doctor_id
        LEFT JOIN users u ON d.user_id = u.user_id
        WHERE mr.patient_id = ?
        ORDER BY mr.record_date DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();
}

// Helper function to generate a consistent color based on the first letter
function getColorForLetter($letter) {
    $letter = strtoupper($letter);
    $colors = [
        'A' => '#4caf50', 'B' => '#2196f3', 'C' => '#ff9800', 'D' => '#9c27b0',
        'E' => '#e91e63', 'F' => '#3f51b5', 'G' => '#009688', 'H' => '#f44336',
        'I' => '#cddc39', 'J' => '#ffeb3b', 'K' => '#ffc107', 'L' => '#795548',
        'M' => '#607d8b', 'N' => '#00bcd4', 'O' => '#8bc34a', 'P' => '#673ab7',
        'Q' => '#ff5722', 'R' => '#03a9f4', 'S' => '#4caf50', 'T' => '#9c27b0',
        'U' => '#2196f3', 'V' => '#ff9800', 'W' => '#e91e63', 'X' => '#f44336',
        'Y' => '#3f51b5', 'Z' => '#009688'
    ];

    return isset($colors[$letter]) ? $colors[$letter] : '#757575'; // Default gray for special characters
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Records - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/medical_records.css">
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        /* Dynamic color styles will be added inline */
        .letter-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: 600;
            color: white;
            width: 50px;
            height: 50px;
            border-radius: 10px;
        }
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
        <span><?=
            strtoupper(
                substr($_SESSION['first_name'] ?? '', 0, 1) .
                substr($_SESSION['last_name'] ?? '', 0, 1)
            )
            ?></span>
            </div>
            <div class="user-info">
                <h4><?= htmlspecialchars(
                        ($_SESSION['first_name'] ?? '') . ' ' .
                        ($_SESSION['last_name'] ?? '')
                    ) ?></h4>
                <p>Patient</p>
            </div>
        </div>

        <nav class="sidebar-nav">
            <ul>
                <li>
                    <a href="dashboard.php">
                        <i class='bx bx-home-alt'></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li class="active">
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

    <!-- Main Content Area -->
    <main class="main-content">
        <!-- Top Header -->
        <header class="dashboard-header">
            <div class="header-left">
                <button class="menu-toggle">
                    <i class='bx bx-menu'></i>
                </button>
                <h1>Medical Records</h1>
            </div>
            <div class="header-right">
                <div class="search-bar">
                    <i class='bx bx-search'></i>
                    <input type="text" placeholder="Search medical records...">
                </div>
                <div class="header-actions">
                    <button class="notification-btn">
                        <i class='bx bx-bell'></i>
                        <span class="notification-badge">3</span>
                    </button>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <div class="user-avatar small">
                                <span><?php echo substr($_SESSION["first_name"], 0, 1) . substr($_SESSION["last_name"], 0, 1); ?></span>
                            </div>
                            <span class="user-name"><?php echo $_SESSION["first_name"] . " " . $_SESSION["last_name"]; ?></span>
                            <i class='bx bx-chevron-down'></i>
                        </button>
                    </div>
                </div>
            </div>
        </header>

        <!-- Medical Records Content -->
        <div class="dashboard-content">
            <!-- Health Summary Section (Simplified) -->
            <section class="health-summary">
                <div class="summary-card">
                    <div class="summary-header">
                        <h2>Health Summary</h2>
                        <span class="last-update">Last updated: <?php echo date("F j, Y"); ?></span>
                    </div>
                    <div class="summary-content">
                        <div class="summary-metrics">
                            <div class="metric-item">
                                <div class="metric-icon">
                                    <i class='bx bx-heart'></i>
                                </div>
                                <div class="metric-info">
                                    <span class="metric-label">Blood Pressure</span>
                                    <span class="metric-value"><?php echo $bloodPressure; ?></span>
                                    <span class="metric-date"><?php echo $bloodPressureDate; ?></span>
                                </div>
                            </div>
                            <div class="metric-item">
                                <div class="metric-icon">
                                    <i class='bx bx-line-chart'></i>
                                </div>
                                <div class="metric-info">
                                    <span class="metric-label">Weight</span>
                                    <span class="metric-value"><?php echo $weight; ?></span>
                                    <span class="metric-date"><?php echo $weightDate; ?></span>
                                </div>
                            </div>
                        </div>
                        <div class="summary-alerts">
                            <?php if($nextAppointment): ?>
                                <div class="alert-item">
                                    <div class="alert-icon">
                                        <i class='bx bx-calendar-check'></i>
                                    </div>
                                    <div class="alert-info">
                                        <span class="alert-title">Next Appointment</span>
                                        <span class="alert-description">
                                            <?php
                                            echo date("M j, Y", strtotime($nextAppointment["appointment_date"])) .
                                                " - " . date("h:i A", strtotime($nextAppointment["start_time"])) .
                                                " - " . $nextAppointment["service_name"] .
                                                " with Dr. " . $nextAppointment["doctor_name"];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <?php if($lastAppointment): ?>
                                <div class="alert-item">
                                    <div class="alert-icon">
                                        <i class='bx bx-time-five'></i>
                                    </div>
                                    <div class="alert-info">
                                        <span class="alert-title">Last Appointment</span>
                                        <span class="alert-description">
                                            <?php
                                            echo date("M j, Y", strtotime($lastAppointment["appointment_date"])) .
                                                " - " . $lastAppointment["service_name"] .
                                                " with Dr. " . $lastAppointment["doctor_name"];
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Records List (Simplified - No Filters) -->
            <section class="medical-records-list">
                <div class="records-container">
                    <?php if(empty($records)): ?>
                        <div class="no-records-message">
                            <i class='bx bx-folder-open'></i>
                            <h3>No Medical Records Found</h3>
                            <p>Your medical records will appear here once they are added to the system.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach($records as $record): ?>
                            <?php
                            $firstLetter = substr(trim($record["title"]), 0, 1);
                            $color = getColorForLetter($firstLetter);
                            ?>
                            <div class="record-item" style="border-left-color: <?php echo $color; ?>">
                                <div class="letter-icon" style="background-color: <?php echo $color; ?>">
                                    <?php echo strtoupper($firstLetter); ?>
                                </div>
                                <div class="record-details">
                                    <h3 class="record-title"><?php echo htmlspecialchars($record["title"]); ?></h3>
                                    <div class="record-meta">
                                        <span class="record-date"><i class='bx bx-calendar'></i> <?php echo date("M j, Y", strtotime($record["record_date"])); ?></span>
                                        <?php if($record["doctor_name"]): ?>
                                            <span class="record-provider"><i class='bx bx-user-voice'></i> Dr. <?php echo htmlspecialchars($record["doctor_name"]); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="record-description"><?php echo htmlspecialchars($record["description"]); ?></p>
                                    <div class="record-tags">
                                        <span class="record-tag" style="background-color: <?php echo $color; ?>20; color: <?php echo $color; ?>">
                                            <?php echo htmlspecialchars($record["category_name"] ?? 'Uncategorized'); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <?php if(count($records) > 10): ?>
                    <!-- Simple Pagination (would need to be implemented properly) -->
                    <div class="pagination">
                        <button class="pagination-btn" disabled>
                            <i class='bx bx-chevron-left'></i>
                        </button>
                        <button class="pagination-btn active">1</button>
                        <button class="pagination-btn">2</button>
                        <button class="pagination-btn">
                            <i class='bx bx-chevron-right'></i>
                        </button>
                    </div>
                <?php endif; ?>
            </section>
        </div>

        <!-- Dashboard Footer -->
        <footer class="dashboard-footer">
            <p>&copy; <?php echo date("Y"); ?> SamaCare. All rights reserved.</p>
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
    document.addEventListener('DOMContentLoaded', function() {
        // Search functionality (basic, client-side)
        const searchInput = document.querySelector('.search-bar input');
        const recordItems = document.querySelectorAll('.record-item');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                recordItems.forEach(item => {
                    const recordTitle = item.querySelector('.record-title').textContent.toLowerCase();
                    const recordDescription = item.querySelector('.record-description').textContent.toLowerCase();

                    if (recordTitle.includes(searchTerm) || recordDescription.includes(searchTerm)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }

        // Notification button functionality
        const notificationBtn = document.querySelector('.notification-btn');
        if (notificationBtn) {
            notificationBtn.addEventListener('click', function() {
                alert('Notifications would be displayed here');
            });
        }

        // User dropdown functionality
        const userBtn = document.querySelector('.user-btn');
        if (userBtn) {
            userBtn.addEventListener('click', function() {
                alert('User profile options would be displayed here');
            });
        }
    });
</script>
</body>
</html>