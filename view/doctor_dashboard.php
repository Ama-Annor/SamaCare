<?php

// start a session 
session_start();


//check if user as right access
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 3) {
    //take user back to login
    header('Location: login.php');
    exit();
}

// Database connection
require_once '../assets/config/db_connect.php';


// querry to get information 
$user_id = $_SESSION['user_id'];

$doctor_query = "SELECT d.doctor_id, u.first_name, u.last_name, u.profile_image, s.name as specialty
                FROM doctors d
                JOIN users u ON d.user_id = u.user_id
                LEFT JOIN specialties s ON d.specialty_id = s.specialty_id
                WHERE d.user_id = ?";


//run querry to connection in database 
$stmt = $conn->prepare($doctor_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();


if ($doctor_result->num_rows === 0) {
    // This should happen if the user is logged in but doesn't have a doctor record
    echo "Error! doctor not found : Meaning user hasn't registered as a doctor";
    exit();
}

// echo "End here for now"; die();

$doctor = $doctor_result->fetch_assoc();
$doctor_id = $doctor['doctor_id'];

// Get today's date
$today = date('Y-m-d');

// Get today's appointments
$appointments_query = "SELECT a.*, p.user_id as patient_user_id, 
                      u.first_name as patient_first_name, u.last_name as patient_last_name, 
                      s.name as service_name, l.name as location_name
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN users u ON p.user_id = u.user_id
                      JOIN services s ON a.service_id = s.service_id
                      JOIN locations l ON a.location_id = l.location_id
                      WHERE a.doctor_id = ? AND a.appointment_date = ?
                      ORDER BY a.start_time ASC";

$stmt = $conn->prepare($appointments_query);
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$appointments_result = $stmt->get_result();
$appointments = [];
while ($row = $appointments_result->fetch_assoc()) {
    $appointments[] = $row;
}

// Count today's appointments
$total_appointments = count($appointments);
$completed_appointments = 0;

foreach ($appointments as $appt) {
    if ($appt['status'] == 'completed') {
        $completed_appointments++;
    }
}

$remaining_appointments = $total_appointments - $completed_appointments;

// Get total patients count
$patients_query = "SELECT COUNT(DISTINCT patient_id) as total_patients 
                  FROM appointments 
                  WHERE doctor_id = ?";
$stmt = $conn->prepare($patients_query);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$patients_result = $stmt->get_result();
$patients_data = $patients_result->fetch_assoc();
$total_patients = $patients_data['total_patients'];

// Get new patients this week
$week_start = date('Y-m-d', strtotime('monday this week'));
$new_patients_query = "SELECT COUNT(DISTINCT a.patient_id) as new_patients
                      FROM appointments a
                      JOIN patients p ON a.patient_id = p.patient_id
                      JOIN users u ON p.user_id = u.user_id
                      WHERE a.doctor_id = ? AND a.created_at >= ?";
$stmt = $conn->prepare($new_patients_query);
$stmt->bind_param("is", $doctor_id, $week_start);
$stmt->execute();
$new_patients_result = $stmt->get_result();
$new_patients_data = $new_patients_result->fetch_assoc();
$new_patients = $new_patients_data['new_patients'];


// Get pending messages from the messages table
$query_pending_messages = "SELECT COUNT(*) as count FROM messages 
                          WHERE receiver_id = ? 
                          AND is_read = 0";
$stmt = $conn->prepare($query_pending_messages);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_pending = $stmt->get_result();
$pending_messages = $result_pending->fetch_assoc()['count'];
$stmt->close();

// Get urgent messages count
$query_urgent_messages = "SELECT COUNT(*) as count FROM messages 
                         WHERE receiver_id = ? 
                         AND is_read = 0 
                         AND is_urgent = 1";
$stmt = $conn->prepare($query_urgent_messages);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result_urgent = $stmt->get_result();
$urgent_messages = $result_urgent->fetch_assoc()['count'];
$stmt->close();


// Get total tasks count for the doctor
$query_total_tasks = "SELECT COUNT(*) as count FROM tasks 
                     WHERE doctor_id = ? 
                     AND status IN ('pending', 'in_progress')";
$stmt = $conn->prepare($query_total_tasks);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result_total_tasks = $stmt->get_result();
$total_tasks = $result_total_tasks->fetch_assoc()['count'];
$stmt->close();


// Get high priority tasks count
$query_high_priority = "SELECT COUNT(*) as count FROM tasks 
                       WHERE doctor_id = ? 
                       AND priority = 'high' 
                       AND status IN ('pending', 'in_progress')";
$stmt = $conn->prepare($query_high_priority);
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$result_high_priority = $stmt->get_result();
$high_priority_tasks = $result_high_priority->fetch_assoc()['count'];
$stmt->close();

// Get next appointment time
$next_appointment_time = "";
$next_appointment_in_minutes = 0;
$current_time = date('H:i:s');

foreach ($appointments as $appt) {
    if ($appt['status'] == 'pending' || $appt['status'] == 'confirmed') {
        $appointment_time = $appt['start_time'];
        if ($appointment_time > $current_time) {
            $next_appointment_time = $appointment_time;
            $next_appointment_in_minutes = round((strtotime($appointment_time) - strtotime($current_time)) / 60);
            break;
        }
    }
}

// Get notifications from database
$notifications = [];
if (isset($user_id)) {
    $notificationQuery = "SELECT notification_id, title, description, icon, color_bg, color_icon, 
                         CASE 
                            WHEN TIMESTAMPDIFF(MINUTE, created_at, NOW()) < 60 THEN CONCAT(TIMESTAMPDIFF(MINUTE, created_at, NOW()), ' minutes ago')
                            WHEN TIMESTAMPDIFF(HOUR, created_at, NOW()) < 24 THEN CONCAT(TIMESTAMPDIFF(HOUR, created_at, NOW()), ' hours ago')
                            ELSE DATE_FORMAT(created_at, '%M %d, %Y')
                         END AS time_ago
                         FROM notifications 
                         WHERE user_id = ? AND is_read = 0
                         ORDER BY created_at DESC
                         LIMIT 5";
    
    $stmt = $conn->prepare($notificationQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'notification_id' => $row['notification_id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'time' => $row['time_ago'],
            'icon' => $row['icon'] ?? 'bx-bell', // Default icon if NULL
            'color_bg' => $row['color_bg'] ?? '#E0F8FF', // Default colors if NULL
            'color_icon' => $row['color_icon'] ?? '#0096FF'
        ];
    }
    $stmt->close();
}

// If no notifications found, provide empty array with message
if (empty($notifications)) {
    $notifications = [];
}



// Get tasks for doctors
$tasks = [];
if (isset($user_id) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
    // Get doctor_id from user_id
    $doctorQuery = "SELECT doctor_id FROM doctors WHERE user_id = ?";
    $stmt = $conn->prepare($doctorQuery);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();


    if($result->num_rows > 0){
        $doctor_data = $result-> fetch_assoc();
        
        $doctor_id = $doctor_data['doctor_id'];
        // Get tasks for this doctor
        $taskQuery = "SELECT task_id, title, description, priority, 
                     CASE 
                        WHEN due_date = CURDATE() THEN 'Due Today'
                        WHEN due_date = DATE_ADD(CURDATE(), INTERVAL 1 DAY) THEN 'Due Tomorrow'
                        WHEN due_date < CURDATE() THEN 'Overdue'
                        ELSE CONCAT('Due ', DATE_FORMAT(due_date, '%b %d'))
                     END AS due_date_formatted
                     FROM tasks 
                     WHERE doctor_id = ? AND status IN ('pending', 'in_progress')
                     ORDER BY 
                        CASE priority
                            WHEN 'high' THEN 1
                            WHEN 'medium' THEN 2
                            WHEN 'low' THEN 3
                        END, due_date ASC
                     LIMIT 5";
        
        $stmt = $conn->prepare($taskQuery);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            // Determine icon based on task title keywords
            $icon = 'bx-task';
            $type = 'general';
            
            $title_lower = strtolower($row['title']);
            if (strpos($title_lower, 'lab') !== false || strpos($title_lower, 'test') !== false) {
                $icon = 'bx-file-find';
                $type = 'test';
            } elseif (strpos($title_lower, 'prescription') !== false || strpos($title_lower, 'medication') !== false) {
                $icon = 'bx-capsule';
                $type = 'medication';
            } elseif (strpos($title_lower, 'appointment') !== false || strpos($title_lower, 'consult') !== false) {
                $icon = 'bx-calendar';
                $type = 'appointment';
            } elseif (strpos($title_lower, 'note') !== false || strpos($title_lower, 'report') !== false) {
                $icon = 'bx-clipboard';
                $type = 'notes';
            }
            
            $priority_text = ucfirst($row['priority']) . ' Priority';
            
            $tasks[] = [
                'task_id' => $row['task_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'priority' => $priority_text,
                'due' => $row['due_date_formatted'],
                'icon' => $icon,
                'type' => $type
            ];
        }
        $stmt->close();
    
    }
}

// If no tasks found, provide empty array
if (empty($tasks)) {
    $tasks = [];
}


// Get performance metrics for doctors
$performance_metrics = [];
if (isset($user_id) && isset($_SESSION['role_id']) && $_SESSION['role_id'] == 3) {
    // Get doctor_id from user_id if not already retrieved above
    if (!isset($doctor_id)) {
        $doctorQuery = "SELECT doctor_id FROM doctors WHERE user_id = ?";
        $stmt = $conn->prepare($doctorQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $doctor_data_metric = $result->fetch_assoc();
            $doctor_id = $doctor_data_metric['doctor_id'];
        }
        $stmt->close();
    }
    
    if (isset($doctor_id)) {
        // Get latest metrics for this doctor
        $metricQuery = "SELECT metric_type, value, 
                       (SELECT value FROM performance_metrics pm2 
                        WHERE pm2.doctor_id = pm1.doctor_id AND pm2.metric_type = pm1.metric_type 
                        AND pm2.period_end < pm1.period_end 
                        ORDER BY period_end DESC LIMIT 1) as previous_value,
                       period_start, period_end
                       FROM performance_metrics pm1
                       WHERE doctor_id = ?
                       GROUP BY metric_type
                       ORDER BY created_at DESC";
        
        $stmt = $conn->prepare($metricQuery);
        $stmt->bind_param("i", $doctor_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Define default metrics if none exist in database
        $default_metrics = [
            'patient_satisfaction' => [
                'title' => 'Patient Satisfaction',
                'value' => 'N/A',
                'trend' => '0%',
                'trend_direction' => 'neutral',
                'period' => 'No data available',
                'icon' => 'bx-smile'
            ],
            'patients_seen' => [
                'title' => 'Patients Seen',
                'value' => 'N/A',
                'trend' => '0%',
                'trend_direction' => 'neutral',
                'period' => 'No data available',
                'icon' => 'bx-user-check'
            ],
            'avg_consultation_time' => [
                'title' => 'Avg. Consultation Time',
                'value' => 'N/A',
                'trend' => '0%',
                'trend_direction' => 'neutral',
                'period' => 'No data available',
                'icon' => 'bx-time-five'
            ]
        ];
        
        // Process metrics from database
        while ($row = $result->fetch_assoc()) {
            $metric_type = $row['metric_type'];
            $current_value = $row['value'];
            $previous_value = $row['previous_value'];
            
            // Calculate trend
            $trend = 0;
            $trend_direction = 'neutral';
            
            if ($previous_value && $previous_value > 0) {
                $trend = (($current_value - $previous_value) / $previous_value) * 100;
                $trend_direction = ($trend >= 0) ? 'positive' : 'negative';
                
                // For metrics where lower is better (like consultation time)
                if ($metric_type == 'avg_consultation_time') {
                    $trend_direction = ($trend <= 0) ? 'positive' : 'negative';
                }
                
                $trend = round($trend) . '%';
                if ($trend >= 0) {
                    $trend = '+' . $trend;
                }
            }
            
            // Format period
            $period = 'Based on ' . date('M d', strtotime($row['period_start'])) . ' to ' . date('M d, Y', strtotime($row['period_end']));
            
            // Format value based on metric type
            $formatted_value = $current_value;
            if ($metric_type == 'patient_satisfaction') {
                $formatted_value = number_format($current_value, 1) . '/5';
            } elseif ($metric_type == 'avg_consultation_time') {
                $formatted_value = $current_value . ' min';
            }
            
            // Map database metric_type to our default structure
            $metric_key = '';
            $icon = 'bx-chart';
            
            switch ($metric_type) {
                case 'patient_satisfaction':
                    $metric_key = 'patient_satisfaction';
                    $icon = 'bx-smile';
                    break;
                case 'patients_seen':
                    $metric_key = 'patients_seen';
                    $icon = 'bx-user-check';
                    break;
                case 'avg_consultation_time':
                    $metric_key = 'avg_consultation_time';
                    $icon = 'bx-time-five';
                    break;
                default:
                    $metric_key = $metric_type;
                    break;
            }
            
            if (isset($default_metrics[$metric_key])) {
                $default_metrics[$metric_key] = [
                    'title' => $default_metrics[$metric_key]['title'],
                    'value' => $formatted_value,
                    'trend' => $trend,
                    'trend_direction' => $trend_direction,
                    'period' => $period,
                    'icon' => $icon
                ];
            }
        }
        
        // Use the updated default metrics
        $performance_metrics = array_values($default_metrics);
        $stmt->close();
    }
}

// If no performance metrics found, use placeholder data
if (empty($performance_metrics)) {
    $performance_metrics = [
        [
            'title' => 'Patient Satisfaction',
            'value' => 'N/A',
            'trend' => '0%',
            'trend_direction' => 'neutral',
            'period' => 'No data available',
            'icon' => 'bx-smile'
        ],
        [
            'title' => 'Patients Seen',
            'value' => 'N/A',
            'trend' => '0%',
            'trend_direction' => 'neutral',
            'period' => 'No data available',
            'icon' => 'bx-user-check'
        ],
        [
            'title' => 'Avg. Consultation Time',
            'value' => 'N/A',
            'trend' => '0%',
            'trend_direction' => 'neutral',
            'period' => 'No data available',
            'icon' => 'bx-time-five'
        ]
    ];
}


// Format doctor's initials for avatar
$initials = substr($doctor['first_name'], 0, 1) . substr($doctor['last_name'], 0, 1);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - SamaCare</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/doctors_dashboard.css">
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
                    <span><?php echo $initials; ?></span>
                </div>
                <div class="user-info">
                    <h4>Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></h4>
                    <p><?php echo $doctor['specialty']; ?></p>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul>
                    <li class="active">
                        <a href="doctor_dashboard.php">
                            <i class='bx bx-home-alt'></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <!-- Where we have reached in the nav bar : change to php -->
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
                    <li>
                        <a href="medical_records.php">
                            <i class='bx bx-folder'></i>
                            <span>Medical Records</span>
                        </a>
                    </li>
                    <li>
               
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <a href="../logout.php" class="logout-link">
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
                    <h1>Doctor Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="search-bar">
                        <i class='bx bx-search'></i>
                        <input type="text" placeholder="Search patients...">
                    </div>
                    <div class="header-actions">
                        <button class="notification-btn">
                            <i class='bx bx-bell'></i>
                            <span class="notification-badge"><?php echo $pending_messages; ?></span>
                        </button>
                        <div class="user-dropdown">
                            <button class="user-btn">
                                <div class="user-avatar small">
                                    <span><?php echo $initials; ?></span>
                                </div>
                                <span class="user-name">Dr. <?php echo $doctor['first_name'] . ' ' . $doctor['last_name']; ?></span>
                                <i class='bx bx-chevron-down'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Dashboard Content -->
            <div class="dashboard-content">
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <div class="welcome-card">
                        <div class="welcome-text">
                            <h2>Welcome back, Dr. <?php echo $doctor['last_name']; ?>!</h2>
                            <p>You have <?php echo $total_appointments; ?> patients scheduled for today. 
                            <?php if ($next_appointment_in_minutes > 0): ?>
                                Your first appointment starts in <?php echo $next_appointment_in_minutes; ?> minutes.
                            <?php else: ?>
                                You have no more appointments scheduled for today.
                            <?php endif; ?>
                            </p>
                        </div>
                        <div class="welcome-actions">
                            <a href="doctor_appointment.php" class="btn primary-btn">
                                <i class='bx bx-calendar-plus'></i>
                                <span>View Appointments</span>
                            </a>
                          
                        </div>
                    </div>
                </section>
                
                <!-- Stats Cards Section -->
                <section class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-calendar-check'></i>
                            </div>
                            <div class="stat-info">
                                <h3>Today's Appointments</h3>
                                <p class="stat-value"><?php echo $total_appointments; ?></p>
                                <p class="stat-description"><?php echo $completed_appointments; ?> completed, <?php echo $remaining_appointments; ?> remaining</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-user'></i>
                            </div>
                            <div class="stat-info">
                                <h3>Total Patients</h3>
                                <p class="stat-value"><?php echo $total_patients; ?></p>
                                <p class="stat-description"><?php echo $new_patients; ?> new this week</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-message-detail'></i>
                            </div>
                            <div class="stat-info">
                                <h3>Pending Messages</h3>
                                <p class="stat-value"><?php echo $pending_messages; ?></p>
                                <p class="stat-description"><?php echo $urgent_messages; ?> urgent</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class='bx bx-task'></i>
                            </div>
                            <div class="stat-info">
                                <h3>Tasks</h3>
                                <p class="stat-value"><?php echo $total_tasks; ?></p>
                                <p class="stat-description"><?php echo $high_priority_tasks; ?> high priority</p>
                            </div>
                        </div>
                    </div>
                </section>
                
                <!-- Two Column Layout For Schedule and Recent Activities -->
                <section class="dashboard-two-columns">
                    <!-- Today's Schedule -->
                    <div class="dashboard-column">
                        <div class="content-card">
                            <div class="card-header">
                                <h3>Today's Schedule</h3>
                                <a href="doctor_appointment.php" class="view-all">View Full Calendar</a>
                            </div>
                            <div class="appointments-list">
                                <?php if (count($appointments) > 0): ?>
                                    <?php foreach(array_slice($appointments, 0, 3) as $appointment): ?>
                                        <div class="appointment-item">
                                            <div class="appointment-date">
                                                <span class="day"><?php echo date('h:i', strtotime($appointment['start_time'])); ?></span>
                                                <span class="month"><?php echo date('A', strtotime($appointment['start_time'])); ?></span>
                                            </div>
                                            <div class="appointment-details">
                                                <h4><?php echo $appointment['service_name']; ?></h4>
                                                <p><i class='bx bx-user'></i> <?php echo $appointment['patient_first_name'] . ' ' . $appointment['patient_last_name']; ?></p>
                                                <p><i class='bx bx-file'></i> <?php echo $appointment['notes'] ? $appointment['notes'] : 'No notes available'; ?></p>
                                                <p><i class='bx bx-map'></i> <?php echo $appointment['location_name']; ?></p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="no-appointments">
                                        <p>No appointments scheduled for today.</p>
                                    </div>
                                <?php endif; ?>
                                
            
                            </div>
                        </div>
                    </div>
                    
                    <!-- Notifications & Alerts -->
                    <div class="dashboard-column">
                        <div class="content-card">
                            <div class="card-header">
                                <h3>Notifications & Alerts</h3>
                                <a href="notifications.php" class="view-all">View All</a>
                            </div>
                            <div class="activities-list">
                                <?php foreach($notifications as $notification): ?>
                                    <div class="activity-item">
                                        <div class="activity-icon" style="background-color: <?php echo $notification['color_bg']; ?>; color: <?php echo $notification['color_icon']; ?>;">
                                            <i class='bx <?php echo $notification['icon']; ?>'></i>
                                        </div>
                                        <div class="activity-details">
                                            <h4><?php echo $notification['title']; ?></h4>
                                            <p><?php echo $notification['description']; ?></p>
                                            <span class="activity-time"><?php echo $notification['time']; ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </section>
                

                
                <!-- Performance Metrics Section -->
                <section class="health-metrics-section">
                    <div class="content-card full-width">
                        <div class="card-header">
                            <h3>Performance Metrics</h3>
                            <a href="analytics.php" class="view-all">View Detailed Analytics</a>
                        </div>
                        <div class="metrics-grid">
                            <?php foreach($performance_metrics as $metric): ?>
                                <div class="metric-card">
                                    <div class="metric-header">
                                        <h4><?php echo $metric['title']; ?></h4>
                                        <div class="metric-icon">
                                            <i class='bx <?php echo $metric['icon']; ?>'></i>
                                        </div>
                                    </div>
                                    <div class="metric-value">
                                        <span class="current-value"><?php echo $metric['value']; ?></span>
                                        <span class="metric-trend <?php echo $metric['trend_direction']; ?>">
                                            <i class='bx bx-<?php echo $metric['trend_direction'] === 'positive' ? 'up' : 'down'; ?>-arrow'></i>
                                            <span><?php echo $metric['trend']; ?></span>
                                        </span>
                                    </div>
                                    <div class="metric-chart">
                                        <!-- Placeholder for chart -->
                                        <div class="chart-placeholder"></div>
                                    </div>
                                    <div class="metric-footer">
                                        <span><?php echo $metric['period']; ?></span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </section>
            </div>
            
            <!-- Dashboard Footer -->
            <footer class="dashboard-footer">
                <p>&copy; <?php echo date('Y'); ?> SamaCare. All rights reserved.</p>
                <div class="footer-links">
                    <a href="../privacy-policy.php">Privacy Policy</a>
                    <a href="../terms-of-service.php">Terms of Service</a>
                    <a href="../help.php">Help & Support</a>
                </div>
            </footer>
        </main>
    </div>

    <script src="../assets/js/dashboard.js"></script>
</body>
</html>