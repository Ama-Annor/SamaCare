document.addEventListener('DOMContentLoaded', function() {
    // Initialize date filters
    initDateFilters();
    
    // Handle chart view toggles
    initChartViewToggles();
    
    // Handle mobile menu toggle
    setupMobileMenu();
    
    // Handle dropdown toggles
    setupDropdowns();
});

// Function to initialize date filters
function initDateFilters() {
    const datePresets = document.querySelectorAll('.date-preset');
    const startDateInput = document.getElementById('date-start');
    const endDateInput = document.getElementById('date-end');
    const applyDateBtn = document.querySelector('.apply-date-btn');
    
    // Set default dates (last 7 days)
    const today = new Date();
    const sevenDaysAgo = new Date();
    sevenDaysAgo.setDate(today.getDate() - 7);
    
    if (startDateInput && endDateInput) {
        startDateInput.valueAsDate = sevenDaysAgo;
        endDateInput.valueAsDate = today;
    }
    
    // Handle date preset buttons
    if (datePresets) {
        datePresets.forEach(preset => {
            preset.addEventListener('click', function() {
                // Remove active class from all presets
                datePresets.forEach(p => p.classList.remove('active'));
                
                // Add active class to clicked preset
                this.classList.add('active');
                
                // Show feedback to user
                showToast('Date range updated');
            });
        });
    }
    
    // Handle apply button for custom date range
    if (applyDateBtn) {
        applyDateBtn.addEventListener('click', function() {
            const startDate = startDateInput.valueAsDate;
            const endDate = endDateInput.valueAsDate;
            
            if (startDate && endDate) {
                // Remove active class from all presets
                datePresets.forEach(p => p.classList.remove('active'));
                
                // Show feedback to user
                showToast('Custom date range applied');
            } else {
                showToast('Please select valid start and end dates');
            }
        });
    }
}

// Function to initialize chart view toggles
function initChartViewToggles() {
    const viewOptions = document.querySelectorAll('.view-option');
    
    if (viewOptions) {
        viewOptions.forEach(option => {
            option.addEventListener('click', function() {
                const viewContainer = this.closest('.chart-view-options');
                const allOptions = viewContainer.querySelectorAll('.view-option');
                
                // Remove active class from all options
                allOptions.forEach(opt => opt.classList.remove('active'));
                
                // Add active class to clicked option
                this.classList.add('active');
                
                // Get the selected view
                const selectedView = this.dataset.view;
                
                // Show feedback to user
                showToast(`View changed to: ${selectedView}`);
            });
        });
    }
}

// Function to setup mobile menu
function setupMobileMenu() {
    const menuToggle = document.querySelector('.menu-toggle');
    const sidebar = document.querySelector('.sidebar');
    const closeSidebar = document.querySelector('.close-sidebar');
    
    if (menuToggle && sidebar && closeSidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
        
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }
}

// Function to setup dropdowns
function setupDropdowns() {
    const userBtn = document.querySelector('.user-btn');
    
    if (userBtn) {
        userBtn.addEventListener('click', function() {
            // Toggle user dropdown
            showToast('User menu would open here');
        });
    }
}

// Toast notification function
function showToast(message) {
    // Create toast element if it doesn't exist
    let toast = document.querySelector('.toast-message');
    
    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast-message';
        document.body.appendChild(toast);
    }
    
    // Set message and show toast
    toast.textContent = message;
    toast.classList.add('show');
    
    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}