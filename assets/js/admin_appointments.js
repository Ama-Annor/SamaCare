document.addEventListener('DOMContentLoaded', function() {
    // Initialize appointments management features
    initAppointmentsManagement();
    
    // Handle mobile menu toggle
    setupMobileMenu();
    
    // Handle dropdown toggles
    setupDropdowns();
});

// Function to initialize appointments management features
function initAppointmentsManagement() {
    // View toggle (list/calendar)
    setupViewToggle();
    
    // Calendar day selection
    setupCalendarDaySelection();
    
    // Calendar navigation
    setupCalendarNavigation();
    
    // Calendar view options (month/week/day)
    setupCalendarViewOptions();
    
    // Status filter
    setupStatusFilter();
    
    // Doctor filter
    setupDoctorFilter();
    
    // Service filter
    setupServiceFilter();
    
    // Date range filter
    setupDateRangeFilter();
    
    // Search functionality
    setupSearchFilter();
    
    // Action buttons
    setupActionButtons();
    
    // Details modal
    setupDetailsModal();
    
    // Edit/Create modal
    setupEditModal();
    
    // Context menu
    setupContextMenu();
}

// Function to setup view toggle (list/calendar)
function setupViewToggle() {
    const viewBtns = document.querySelectorAll('.view-btn');
    const viewSections = document.querySelectorAll('.view-section');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Show the corresponding view
            const view = this.getAttribute('data-view');
            viewSections.forEach(section => {
                if (section.id === view + '-view') {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });
        });
    });
}

// Function to setup calendar day selection
function setupCalendarDaySelection() {
    const calendarDays = document.querySelectorAll('.calendar-day');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            // Clear previously selected day
            calendarDays.forEach(d => d.classList.remove('selected'));
            
            // Select clicked day
            this.classList.add('selected');
            
            // Update the appointments shown below
            if (!this.classList.contains('other-month')) {
                const date = this.textContent.trim().replace(/\d+/, '');
                const dateHeader = document.querySelector('.selected-date-appointments h4');
                if (dateHeader) {
                    dateHeader.textContent = `June ${date}, 2024 Appointments`;
                }
                
                // In a real application, this would load appointments for the selected date
                // For now, we'll just leave the existing appointments
            }
        });
    });
}

// Function to setup calendar navigation
function setupCalendarNavigation() {
    const navBtns = document.querySelectorAll('.calendar-nav-btn');
    
    navBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            showToast('Calendar navigation would be implemented with backend integration');
        });
    });
}

// Function to setup calendar view options (month/week/day)
function setupCalendarViewOptions() {
    const viewBtns = document.querySelectorAll('.calendar-view-btn');
    const calendarContainer = document.querySelector('.calendar-container');
    
    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // Update active button
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Get the selected view type
            const viewType = this.getAttribute('data-view');
            
            // Here you would update the calendar view based on the selected type
            showToast(`Calendar ${viewType} view would be implemented with backend integration`);
        });
    });
}

// Function to setup status filter
function setupStatusFilter() {
    const statusFilter = document.getElementById('status-filter');
    
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            const selectedStatus = this.value;
            
            // For demo purposes, we'll just show a toast message
            showToast(`Filtering by status: ${selectedStatus}`);
            
            // In a real application, this would filter the appointments
            filterAppointments();
        });
    }
}

// Function to setup doctor filter
function setupDoctorFilter() {
    const doctorFilter = document.getElementById('doctor-filter');
    
    if (doctorFilter) {
        doctorFilter.addEventListener('change', function() {
            const selectedDoctor = this.value;
            
            // For demo purposes, we'll just show a toast message
            showToast(`Filtering by doctor: ${selectedDoctor}`);
            
            // In a real application, this would filter the appointments
            filterAppointments();
        });
    }
}

// Function to setup service filter
function setupServiceFilter() {
    const serviceFilter = document.getElementById('service-filter');
    
    if (serviceFilter) {
        serviceFilter.addEventListener('change', function() {
            const selectedService = this.value;
            
            // For demo purposes, we'll just show a toast message
            showToast(`Filtering by service: ${selectedService}`);
            
            // In a real application, this would filter the appointments
            filterAppointments();
        });
    }
}

// Function to setup date range filter
function setupDateRangeFilter() {
    const startDateInput = document.getElementById('date-start');
    const endDateInput = document.getElementById('date-end');
    const applyDateBtn = document.querySelector('.apply-date-btn');
    
    // Set default dates (current week)
    const today = new Date();
    const oneWeekAgo = new Date();
    oneWeekAgo.setDate(today.getDate() - 7);
    
    if (startDateInput && endDateInput) {
        startDateInput.valueAsDate = oneWeekAgo;
        endDateInput.valueAsDate = today;
    }
    
    if (applyDateBtn) {
        applyDateBtn.addEventListener('click', function() {
            const startDate = startDateInput.value;
            const endDate = endDateInput.value;
            
            if (startDate && endDate) {
                // For demo purposes, we'll just show a toast message
                showToast(`Filtering by date range: ${startDate} to ${endDate}`);
                
                // In a real application, this would filter the appointments
                filterAppointments();
            } else {
                showToast('Please select valid start and end dates');
            }
        });
    }
}

// Function to setup search filter
function setupSearchFilter() {
    const searchInput = document.querySelector('.search-bar input');
    const appointmentRows = document.querySelectorAll('.appointments-table tbody tr');
    
    if (searchInput && appointmentRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            appointmentRows.forEach(row => {
                // Get patient and doctor names
                const patientName = row.querySelector('.user-info span').textContent.toLowerCase();
                const doctorName = row.querySelectorAll('.user-info span')[1].textContent.toLowerCase();
                const service = row.querySelector('td:nth-child(4)').textContent.toLowerCase();
                
                if (patientName.includes(searchTerm) || doctorName.includes(searchTerm) || service.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Function to filter appointments (dummy implementation)
function filterAppointments() {
    // In a real application, this would send a request to the server
    // or filter the appointments on the client side
    
    // For demo purposes, we'll just show a loading indicator
    const tableContainer = document.querySelector('.table-container');
    
    if (tableContainer) {
        tableContainer.style.opacity = '0.5';
        
        // Simulate loading
        setTimeout(() => {
            tableContainer.style.opacity = '1';
        }, 500);
    }
}

// Function to setup action buttons
function setupActionButtons() {
    // View details buttons
    const viewButtons = document.querySelectorAll('.btn[title="View Details"]');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Show appointment details modal
            const detailsModal = document.getElementById('appointment-details-modal');
            if (detailsModal) {
                detailsModal.classList.add('show');
            }
        });
    });
    
    // Edit buttons
    const editButtons = document.querySelectorAll('.btn[title="Edit"]');
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Show appointment edit modal
            const editModal = document.getElementById('appointment-edit-modal');
            if (editModal) {
                // Update modal title
                document.getElementById('appointment-modal-title').textContent = 'Edit Appointment';
                
                // In a real application, you would populate the form with appointment data
                
                editModal.classList.add('show');
            }
        });
    });
    
    // More options buttons
    const moreButtons = document.querySelectorAll('.btn[title="More Options"]');
    
    moreButtons.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.stopPropagation();
            
            // Show context menu
            const contextMenu = document.getElementById('appointment-actions-menu');
            if (contextMenu) {
                const rect = this.getBoundingClientRect();
                contextMenu.style.top = rect.bottom + 5 + 'px';
                contextMenu.style.left = rect.left - 170 + 'px';
                
                contextMenu.classList.add('show');
                
                // Store the row for context
                const row = this.closest('tr');
                if (row) {
                    contextMenu.dataset.appointmentId = row.dataset.appointmentId || '1';
                }
            }
        });
    });
    
    // Add appointment button
    const addAppointmentBtn = document.getElementById('add-appointment-btn');
    
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function() {
            // Show appointment edit modal
            const editModal = document.getElementById('appointment-edit-modal');
            if (editModal) {
                // Update modal title
                document.getElementById('appointment-modal-title').textContent = 'Schedule Appointment';
                
                // Reset form
                document.getElementById('appointment-form').reset();
                
                editModal.classList.add('show');
            }
        });
    }
}

// Function to setup details modal
function setupDetailsModal() {
    const detailsModal = document.getElementById('appointment-details-modal');
    const closeModalBtn = detailsModal ? detailsModal.querySelector('.modal-close') : null;
    
    // Close modal when clicking the X button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            detailsModal.classList.remove('show');
        });
    }
    
    // Close modal when clicking outside
    if (detailsModal) {
        detailsModal.addEventListener('click', function(event) {
            if (event.target === detailsModal) {
                detailsModal.classList.remove('show');
            }
        });
        
        // Status action buttons
        const statusActionBtns = detailsModal.querySelectorAll('.status-actions .btn');
        
        statusActionBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const action = this.textContent.trim().toLowerCase();
                
                showToast(`${action} action would be implemented with backend integration`);
                
                // Close modal after short delay
                setTimeout(() => {
                    detailsModal.classList.remove('show');
                }, 500);
            });
        });
    }
}

// Function to setup edit modal
function setupEditModal() {
    const editModal = document.getElementById('appointment-edit-modal');
    const closeModalBtn = editModal ? editModal.querySelector('.modal-close') : null;
    const cancelBtn = editModal ? editModal.querySelector('#cancel-btn') : null;
    const form = editModal ? editModal.querySelector('#appointment-form') : null;
    
    // Close modal when clicking the X button
    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', function() {
            editModal.classList.remove('show');
        });
    }
    
    // Close modal when clicking the Cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            editModal.classList.remove('show');
        });
    }
    
    // Close modal when clicking outside
    if (editModal) {
        editModal.addEventListener('click', function(event) {
            if (event.target === editModal) {
                editModal.classList.remove('show');
            }
        });
    }
    
    // Handle form submission
    if (form) {
        form.addEventListener('submit', function(event) {
            event.preventDefault();
            
            // In a real application, this would save the appointment data
            showToast('Appointment saved successfully');
            
            // Close modal
            editModal.classList.remove('show');
        });
    }
}

// Function to setup context menu
function setupContextMenu() {
    const contextMenu = document.getElementById('appointment-actions-menu');
    const menuItems = contextMenu ? contextMenu.querySelectorAll('li') : [];
    
    // Close context menu when clicking outside
    document.addEventListener('click', function() {
        if (contextMenu) {
            contextMenu.classList.remove('show');
        }
    });
    
    // Handle context menu actions
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const appointmentId = contextMenu.dataset.appointmentId || '1';
            
            // Handle different actions
            switch (action) {
                case 'view':
                    // Show appointment details modal
                    const detailsModal = document.getElementById('appointment-details-modal');
                    if (detailsModal) {
                        detailsModal.classList.add('show');
                    }
                    break;
                case 'edit':
                    // Show appointment edit modal
                    const editModal = document.getElementById('appointment-edit-modal');
                    if (editModal) {
                        // Update modal title
                        document.getElementById('appointment-modal-title').textContent = 'Edit Appointment';
                        
                        // In a real application, you would populate the form with appointment data
                        
                        editModal.classList.add('show');
                    }
                    break;
                default:
                    // For other actions, just show a toast message
                    showToast(`${action} action would be implemented with backend integration`);
            }
            
            // Close context menu
            contextMenu.classList.remove('show');
        });
    });
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