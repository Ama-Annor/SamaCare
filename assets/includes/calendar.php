<?php
// File: includes/calendar.php
?>
<div class="calendar-weekdays">
    <div>Sun</div>
    <div>Mon</div>
    <div>Tue</div>
    <div>Wed</div>
    <div>Thu</div>
    <div>Fri</div>
    <div>Sat</div>
</div>
<div class="calendar-days">
    <?php
    // Previous month days (simplified - in a real app, would calculate based on actual date)
    for ($i = 26; $i <= 31; $i++) {
        echo '<div class="calendar-day other-month">' . $i . '</div>';
    }
    
    // Current month days
    for ($i = 1; $i <= 30; $i++) {
        $class = ($i == 19) ? 'calendar-day current-date has-appointment' : 'calendar-day';
        echo '<div class="' . $class . '">' . $i . '</div>';
    }
    
    // Next month days (simplified)
    for ($i = 1; $i <= 6; $i++) {
        echo '<div class="calendar-day other-month">' . $i . '</div>';
    }
    ?>
</div>