<?php
// File: includes/header.php
?>
<header class="dashboard-header">
    <div class="header-left">
        <button class="menu-toggle">
            <i class='bx bx-menu'></i>
        </button>
        <h1>Appointments</h1>
    </div>
    <div class="header-right">
        <div class="search-bar">
            <i class='bx bx-search'></i>
            <input type="text" placeholder="Search appointments...">
        </div>
        <div class="header-actions">
            <button class="notification-btn">
                <i class='bx bx-bell'></i>
                <span class="notification-badge">2</span>
            </button>
            <div class="user-dropdown">
                <button class="user-btn">
                    <div class="user-avatar small">
                        <span><?php echo $user['initials']; ?></span>
                    </div>
                    <span class="user-name"><?php echo $user['name']; ?></span>
                    <i class='bx bx-chevron-down'></i>
                </button>
            </div>
        </div>
    </div>
</header>
