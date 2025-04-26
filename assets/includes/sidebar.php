<?php
// File: includes/sidebar.php
?>
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
            <span><?php echo $user['initials']; ?></span>
        </div>
        <div class="user-info">
            <h4><?php echo $user['name']; ?></h4>
            <p><?php echo $user['role']; ?></p>
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
            <li>
                <a href="medical_records.php">
                    <i class='bx bx-folder'></i>
                    <span>Medical Records</span>
                </a>
            </li>
            <li class="active">
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
        <a href="#" class="help-link">
            <i class='bx bx-help-circle'></i>
            <span>Help & Support</span>
        </a>
        <a href="../logout.php" class="logout-link">
            <i class='bx bx-log-out'></i>
            <span>Log Out</span>
        </a>
    </div>
</aside>

<?php