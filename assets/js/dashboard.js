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
        if (!sidebar || !menuToggle) return;
        
        const isSidebarActive = sidebar.classList.contains('active');
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedOnMenuToggle = menuToggle.contains(event.target);
        
        if (isSidebarActive && !clickedInsideSidebar && !clickedOnMenuToggle) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });
    
    // User dropdown and notification button - show toast messages
    const userBtn = document.querySelector('.user-btn');
    const notificationBtn = document.querySelector('.notification-btn');
    
    if (userBtn) {
        userBtn.addEventListener('click', function() {
            showToast('User profile options would be implemented with backend integration');
        });
    }
    
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            showToast('Notifications feature would be implemented with backend integration');
        });
    }
    
    // Add Appointment interaction
    const addAppointmentBtn = document.querySelector('.add-appointment');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Appointment scheduling would redirect to appointments page');
        });
    }
    
    // Simple toast message function
    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.querySelector('.toast-message');
        
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-message';
            document.body.appendChild(toast);
            
            // Add style for the toast
            const style = document.createElement('style');
            style.textContent = `
                .toast-message {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background-color: var(--dark-color);
                    color: white;
                    padding: 12px 20px;
                    border-radius: var(--border-radius);
                    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
                    z-index: 1000;
                    opacity: 0;
                    transform: translateY(20px);
                    transition: all 0.3s ease;
                }
                
                .toast-message.show {
                    opacity: 1;
                    transform: translateY(0);
                }
            `;
            document.head.appendChild(style);
        }
        
        // Set message and show toast
        toast.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Simulate charts loading - simplified version
    simulateCharts();
});

// Function to simulate chart loading with animation (simplified)
function simulateCharts() {
    const chartPlaceholders = document.querySelectorAll('.chart-placeholder');
    
    if (chartPlaceholders.length > 0) {
        chartPlaceholders.forEach((placeholder, index) => {
            // Create a simple SVG chart
            const svg = document.createElementNS("http://www.w3.org/2000/svg", "svg");
            svg.setAttribute("width", "100%");
            svg.setAttribute("height", "100%");
            svg.style.position = "absolute";
            svg.style.top = "0";
            svg.style.left = "0";
            
            // Generate different paths for different chart placeholders
            let path;
            if (index === 0) {
                // Blood pressure - downward trend (good)
                path = "M0,60 L60,40 L120,30 L180,20 L240,10";
            } else {
                // Weight - slight upward trend
                path = "M0,40 L60,38 L120,36 L180,42 L240,45";
            }
            
            const pathElement = document.createElementNS("http://www.w3.org/2000/svg", "path");
            pathElement.setAttribute("d", path);
            pathElement.setAttribute("stroke", "#2a9d8f");
            pathElement.setAttribute("stroke-width", "2");
            pathElement.setAttribute("fill", "none");
            
            svg.appendChild(pathElement);
            
            // Add dots at key points
            const points = [
                {x: 0, y: path === "M0,60 L60,40 L120,30 L180,20 L240,10" ? 60 : 40},
                {x: 60, y: path === "M0,60 L60,40 L120,30 L180,20 L240,10" ? 40 : 38},
                {x: 120, y: path === "M0,60 L60,40 L120,30 L180,20 L240,10" ? 30 : 36},
                {x: 180, y: path === "M0,60 L60,40 L120,30 L180,20 L240,10" ? 20 : 42},
                {x: 240, y: path === "M0,60 L60,40 L120,30 L180,20 L240,10" ? 10 : 45}
            ];
            
            points.forEach(point => {
                const circle = document.createElementNS("http://www.w3.org/2000/svg", "circle");
                circle.setAttribute("cx", point.x);
                circle.setAttribute("cy", point.y);
                circle.setAttribute("r", "3");
                circle.setAttribute("fill", "#2a9d8f");
                svg.appendChild(circle);
            });
            
            // Clear placeholder and add SVG
            placeholder.style.background = "white";
            placeholder.innerHTML = "";
            placeholder.appendChild(svg);
        });
    }
}