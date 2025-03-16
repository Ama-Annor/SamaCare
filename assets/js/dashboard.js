document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const sidebar = document.querySelector('.sidebar');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden'; //Prevent scrolling when sidebar is open
        });
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            document.body.style.overflow = ''; //Restore scrolling
        });
    }
    
    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        const isSidebarActive = sidebar.classList.contains('active');
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedOnMenuToggle = menuToggle.contains(event.target);
        
        if (isSidebarActive && !clickedInsideSidebar && !clickedOnMenuToggle) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // User dropdown toggle
    const userBtn = document.querySelector('.user-btn');
    if (userBtn) {
        userBtn.addEventListener('click', function() {
            // Implementation for user dropdown menu
            console.log('User dropdown clicked');
            // For actual implementation, you would toggle a dropdown menu here
        });
    }
    
    // Notification button
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            // Implementation for notifications panel
            console.log('Notifications clicked');
            // For actual implementation, you would toggle a notifications panel here
        });
    }
    
    // Reminder action buttons
    const actionButtons = document.querySelectorAll('.reminder-actions .action-btn');
    if (actionButtons.length > 0) {
        actionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const reminderItem = this.closest('.reminder-item');
                const action = this.classList.contains('done-btn') ? 'completed' : 
                               this.classList.contains('snooze-btn') ? 'snoozed' : 
                               this.classList.contains('dismiss-btn') ? 'dismissed' : 'viewed';
                               
                console.log(`Reminder ${action}`);
                
                // For demo purposes, show a quick animation and remove or change the reminder
                if (action === 'completed' || action === 'dismissed') {
                    reminderItem.style.transition = 'opacity 0.5s, transform 0.5s';
                    reminderItem.style.opacity = '0';
                    reminderItem.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                        reminderItem.remove();
                    }, 500);
                } else if (action === 'snoozed') {
                    // Change the reminder time to "Snoozed for 1 hour"
                    const reminderTime = reminderItem.querySelector('.reminder-time');
                    reminderTime.textContent = 'Snoozed for 1 hour';
                    
                    // Add a visual indicator
                    reminderItem.style.opacity = '0.7';
                }
            });
        });
    }
    
    // Add Metric card interaction
    const addMetricCard = document.querySelector('.add-metric');
    if (addMetricCard) {
        addMetricCard.addEventListener('click', function() {
            console.log('Add new metric clicked');
            // For actual implementation, you would show a form or modal to add a new metric
        });
    }
    
    // Add Reminder interaction
    const addReminderBtn = document.querySelector('.add-reminder');
    if (addReminderBtn) {
        addReminderBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add new reminder clicked');
            // For actual implementation, you would show a form or modal to add a new reminder
        });
    }
    
    // Add Appointment interaction
    const addAppointmentBtn = document.querySelector('.add-appointment');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Schedule new appointment clicked');
            // For actual implementation, you would redirect to appointment scheduling page
        });
    }
    
    // Simulate charts loading
    simulateCharts();
});

// Function to simulate chart loading with animation
function simulateCharts() {
    const chartPlaceholders = document.querySelectorAll('.chart-placeholder');
    
    if (chartPlaceholders.length > 0) {
        // For an actual implementation, you would replace these placeholders with real charts
        // using a library like Chart.js or a simple SVG-based solution
        
        // Here we're just adding some visual elements to make the placeholders look like charts
        chartPlaceholders.forEach((placeholder, index) => {
            // Create a simulated line chart
            const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("width", "100%");
            svg.setAttribute("height", "100%");
            svg.style.position = "absolute";
            svg.style.top = "0";
            svg.style.left = "0";
            svg.style.zIndex = "2";
            
            // Generate different paths for different chart placeholders
            let path;
            if (index === 0) {
                // Blood pressure - downward trend (good)
                path = "M0,60 Q30,70 60,40 T120,30 T180,20 T240,10";
            } else if (index === 1) {
                // Blood glucose - slight downward trend (good)
                path = "M0,40 Q30,45 60,35 T120,40 T180,30 T240,25";
            } else {
                // Weight - slight upward trend
                path = "M0,40 Q30,35 60,38 T120,36 T180,42 T240,45";
            }
            
            const pathElement = document.createElementNS("http://www.w3.org/2000/svg", "path");
            pathElement.setAttribute("d", path);
            pathElement.setAttribute("stroke", "#2a9d8f");
            pathElement.setAttribute("stroke-width", "2");
            pathElement.setAttribute("fill", "none");
            
            // Add animation to the path
            const length = pathElement.getTotalLength();
            pathElement.style.strokeDasharray = length;
            pathElement.style.strokeDashoffset = length;
            pathElement.style.animation = "dash 1.5s ease-in-out forwards";
            
            // Add style for the animation
            const style = document.createElement("style");
            style.textContent = `
                @keyframes dash {
                    from {
                        stroke-dashoffset: ${length};
                    }
                    to {
                        stroke-dashoffset: 0;
                    }
                }
            `;
            document.head.appendChild(style);
            
            svg.appendChild(pathElement);
            
            // Add dots to the line
            const points = path.split(" ").filter(p => p.includes(",")).map(p => {
                const coords = p.replace(/[A-Z]/g, "").split(",");
                return {x: parseFloat(coords[0]), y: parseFloat(coords[1])};
            });
            
            points.forEach(point => {
                const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                circle.setAttribute("cx", point.x);
                circle.setAttribute("cy", point.y);
                circle.setAttribute("r", "3");
                circle.setAttribute("fill", "#2a9d8f");
                circle.style.opacity = "0";
                circle.style.animation = "fadeIn 0.3s ease-in-out forwards";
                circle.style.animationDelay = "1.5s";
                svg.appendChild(circle);
            });
            
            // Add style for the circle animation
            const circleStyle = document.createElement("style");
            circleStyle.textContent = `
                @keyframes fadeIn {
                    from {
                        opacity: 0;
                    }
                    to {
                        opacity: 1;
                    }
                }
            `;
            document.head.appendChild(circleStyle);
            
            // Clear the "loading" animation and add our SVG
            placeholder.style.background = "white";
            placeholder.style.overflow = "hidden";
            placeholder.innerHTML = "";
            placeholder.appendChild(svg);
        });
    }
}