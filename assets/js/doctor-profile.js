document.addEventListener('DOMContentLoaded', function() {
    // Toggle sidebar functionality
    const menuToggle = document.querySelector('.menu-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');
    
    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
    }
    
    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }
    
    // Edit section buttons functionality
    const editSectionBtns = document.querySelectorAll('.edit-section-btn');
    
    editSectionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const section = this.closest('.content-card');
            const inputs = section.querySelectorAll('.form-control');
            
            inputs.forEach(input => {
                input.disabled = !input.disabled;
            });
            
            if (this.innerHTML.includes('Edit')) {
                this.innerHTML = '<i class="bx bx-check"></i><span>Done</span>';
                this.style.backgroundColor = 'var(--primary-color)';
                this.style.color = 'white';
            } else {
                this.innerHTML = '<i class="bx bx-edit"></i><span>Edit</span>';
                this.style.backgroundColor = 'transparent';
                this.style.color = 'var(--primary-color)';
            }
        });
    });
    
    // Save profile button
    const saveProfileBtn = document.getElementById('save-profile');
    if (saveProfileBtn) {
        saveProfileBtn.addEventListener('click', function() {
            // Reset all edit section buttons
            editSectionBtns.forEach(btn => {
                btn.innerHTML = '<i class="bx bx-edit"></i><span>Edit</span>';
                btn.style.backgroundColor = 'transparent';
                btn.style.color = 'var(--primary-color)';
            });
            
            // Disable all inputs
            document.querySelectorAll('.form-control').forEach(input => {
                input.disabled = true;
            });
            
            // Show success message
            showNotification('Profile updated successfully!', 'success');
        });
    }
    
    // Cancel edit button
    const cancelEditBtn = document.getElementById('cancel-edit');
    if (cancelEditBtn) {
        cancelEditBtn.addEventListener('click', function() {
            // Reset all edit section buttons
            editSectionBtns.forEach(btn => {
                btn.innerHTML = '<i class="bx bx-edit"></i><span>Edit</span>';
                btn.style.backgroundColor = 'transparent';
                btn.style.color = 'var(--primary-color)';
            });
            
            // Disable all inputs and reset to original values
            document.querySelectorAll('.form-control').forEach(input => {
                input.disabled = true;
                // In a real app, we would reset to the original values from the server here
            });
        });
    }
    
    // Schedule day checkbox toggle functionality
    const dayCheckboxes = document.querySelectorAll('.day-label input[type="checkbox"]');
    
    dayCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const timeSlots = this.closest('.schedule-day').querySelector('.time-slots');
            const inputs = timeSlots.querySelectorAll('input');
            
            if (this.checked) {
                timeSlots.classList.remove('disabled');
                inputs.forEach(input => {
                    input.disabled = false;
                });
            } else {
                timeSlots.classList.add('disabled');
                inputs.forEach(input => {
                    input.disabled = true;
                });
            }
        });
    });
    
    // Status change functionality
    const statusText = document.querySelector('.status-text');
    const statusBadge = document.querySelector('.status-badge');
    
    if (statusText && statusBadge) {
        statusText.addEventListener('click', function() {
            const currentStatus = statusBadge.textContent;
            const availabilitySelect = document.getElementById('availability');
            
            // Open the availability dropdown
            if (availabilitySelect) {
                availabilitySelect.focus();
                availabilitySelect.click();
            }
        });
    }
    
    // Availability select change event
    const availabilitySelect = document.getElementById('availability');
    
    if (availabilitySelect && statusBadge) {
        availabilitySelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            // Update status badge
            statusBadge.className = 'status-badge';
            statusBadge.classList.add(selectedValue);
            
            // Update badge text
            switch (selectedValue) {
                case 'available':
                    statusBadge.textContent = 'Available';
                    break;
                case 'busy':
                    statusBadge.textContent = 'Busy';
                    break;
                case 'away':
                    statusBadge.textContent = 'Away';
                    break;
                case 'off-duty':
                    statusBadge.textContent = 'Off Duty';
                    break;
            }
        });
    }
    
    // Function to show notification
    function showNotification(message, type) {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <div class="notification-content">
                <i class='bx bx-check-circle'></i>
                <span>${message}</span>
            </div>
            <button class="close-notification">
                <i class='bx bx-x'></i>
            </button>
        `;
        
        // Add notification to the DOM
        document.body.appendChild(notification);
        
        // Show notification
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);
        
        // Auto hide after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 5000);
        
        // Close button functionality
        const closeButton = notification.querySelector('.close-notification');
        closeButton.addEventListener('click', function() {
            notification.classList.remove('show');
            setTimeout(() => {
                notification.remove();
            }, 300);
        });
    }
    
    // Initialize by disabling all form controls initially
    document.querySelectorAll('.form-control').forEach(input => {
        input.disabled = true;
    });
    
    // Allow enabled time slots on checked days
    dayCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            const timeSlots = checkbox.closest('.schedule-day').querySelector('.time-slots');
            const inputs = timeSlots.querySelectorAll('input');
            
            timeSlots.classList.remove('disabled');
            inputs.forEach(input => {
                input.disabled = false;
            });
        }
    });
    
    // Profile avatar upload functionality
    const editAvatar = document.querySelector('.edit-avatar');
    if (editAvatar) {
        editAvatar.addEventListener('click', function() {
            // Create a file input
            const fileInput = document.createElement('input');
            fileInput.type = 'file';
            fileInput.accept = 'image/*';
            
            // Trigger click on file input
            fileInput.click();
            
            // Handle file selection
            fileInput.addEventListener('change', function() {
                if (this.files && this.files[0]) {
                    // In a real app, we would upload the file to server here
                    // For demo, just show notification
                    showNotification('Profile picture updated!', 'success');
                }
            });
        });
    }
});