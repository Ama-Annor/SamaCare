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

    // Pagination
    setupPagination();

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

    // Export button
    setupExportButton();
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
            // Ignore if it's from other month
            if (this.classList.contains('other-month')) {
                return;
            }

            // Clear previously selected day
            calendarDays.forEach(d => d.classList.remove('selected'));

            // Select clicked day
            this.classList.add('selected');

            // Get the date from the data attribute
            const selectedDate = this.getAttribute('data-date');

            // Update the appointments shown below
            if (selectedDate) {
                // Format date for display
                const dateObj = new Date(selectedDate);
                const formattedDate = dateObj.toLocaleDateString('en-US', {
                    month: 'long',
                    day: 'numeric',
                    year: 'numeric'
                });

                const dateHeader = document.querySelector('.selected-date-appointments h4');
                if (dateHeader) {
                    dateHeader.textContent = `${formattedDate} Appointments`;
                }

                // Load appointments for the selected date via AJAX
                fetchAppointmentsForDate(selectedDate);
            }
        });
    });
}

// Function to fetch appointments for a specific date
function fetchAppointmentsForDate(date) {
    const appointmentsContainer = document.getElementById('selected-day-appointments');

    if (appointmentsContainer) {
        // Show loading indicator
        appointmentsContainer.innerHTML = '<div class="loading-spinner"><i class="bx bx-loader-alt bx-spin"></i><p>Loading appointments...</p></div>';

        // Fetch appointments via AJAX
        fetch(`../actions/get_appointments_by_date.php?date=${date}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    // Render appointments
                    let html = '';
                    data.forEach(appointment => {
                        html += `
                            <div class="day-appointment-item" data-id="${appointment.appointment_id}">
                                <div class="appointment-time">${formatTime(appointment.start_time)}</div>
                                <div class="appointment-info">
                                    <div class="appointment-main">
                                        <h5>${appointment.service_name}</h5>
                                        <span class="status-badge ${appointment.status}">${capitalizeFirstLetter(appointment.status)}</span>
                                    </div>
                                    <div class="appointment-people">
                                        <p><i class='bx bx-user'></i> ${appointment.patient_first_name} ${appointment.patient_last_name}</p>
                                        <p><i class='bx bx-user-circle'></i> Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</p>
                                    </div>
                                </div>
                                <div class="appointment-actions">
                                    <button class="btn icon-btn sm view-appointment" title="View Details" data-id="${appointment.appointment_id}">
                                        <i class='bx bx-show'></i>
                                    </button>
                                    ${appointment.status !== 'completed' && appointment.status !== 'cancelled' ?
                            `<button class="btn icon-btn sm edit-appointment" title="Edit" data-id="${appointment.appointment_id}">
                                            <i class='bx bx-edit'></i>
                                        </button>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    appointmentsContainer.innerHTML = html;

                    // Reattach event listeners
                    setupActionButtons();
                } else {
                    appointmentsContainer.innerHTML = '<div class="no-appointments">No appointments scheduled for this date</div>';
                }
            })
            .catch(error => {
                console.error('Error fetching appointments:', error);
                appointmentsContainer.innerHTML = '<div class="error-message">Failed to load appointments. Please try again.</div>';
            });
    }
}

// Function to setup calendar navigation
function setupCalendarNavigation() {
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const monthDisplay = document.querySelector('.calendar-navigation h3');

    if (prevMonthBtn && nextMonthBtn && monthDisplay) {
        // Get current month and year
        let currentMonth = new Date().getMonth(); // 0-11
        let currentYear = new Date().getFullYear();

        // Extract month and year from display text if already set
        if (monthDisplay.textContent) {
            const parts = monthDisplay.textContent.split(' ');
            if (parts.length === 2) {
                const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
                currentMonth = monthNames.indexOf(parts[0]);
                currentYear = parseInt(parts[1]);
            }
        }

        prevMonthBtn.addEventListener('click', function() {
            // Go to previous month
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendar(currentMonth, currentYear);
        });

        nextMonthBtn.addEventListener('click', function() {
            // Go to next month
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendar(currentMonth, currentYear);
        });
    }
}

// Function to update calendar
function updateCalendar(month, year) {
    // Show loading indicator
    const calendarDays = document.querySelector('.calendar-days');
    if (calendarDays) {
        calendarDays.innerHTML = '<div class="loading-spinner center-spinner"><i class="bx bx-loader-alt bx-spin"></i></div>';
    }

    // Update month display
    const monthDisplay = document.querySelector('.calendar-navigation h3');
    if (monthDisplay) {
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        monthDisplay.textContent = `${monthNames[month]} ${year}`;
    }

    // Fetch calendar data for the selected month
    fetch(`../actions/get_calendar_data.php?month=${month + 1}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (calendarDays) {
                calendarDays.innerHTML = data.html;

                // Reattach event listeners
                setupCalendarDaySelection();
            }
        })
        .catch(error => {
            console.error('Error updating calendar:', error);
            if (calendarDays) {
                calendarDays.innerHTML = '<div class="error-message">Failed to load calendar. Please try again.</div>';
            }
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

            // Update view-specific URL parameters
            const urlParams = new URLSearchParams(window.location.search);
            urlParams.set('calendar_view', viewType);

            // Fetch appropriate view
            fetch(`../actions/get_calendar_view.php?view=${viewType}&${urlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    if (calendarContainer) {
                        calendarContainer.innerHTML = data.html;

                        // Reattach event listeners based on view type
                        if (viewType === 'month') {
                            setupCalendarDaySelection();
                        } else if (viewType === 'week') {
                            setupWeekView();
                        } else if (viewType === 'day') {
                            setupDayView();
                        }
                    }
                })
                .catch(error => {
                    console.error(`Error loading ${viewType} view:`, error);
                    showToast(`Failed to load ${viewType} view. Please try again.`);
                });
        });
    });
}

// Function to setup week view specifics
function setupWeekView() {
    // Add week view specific event handlers
    console.log('Week view loaded');
    // This would be implemented based on your week view HTML structure
}

// Function to setup day view specifics
function setupDayView() {
    // Add day view specific event handlers
    console.log('Day view loaded');
    // This would be implemented based on your day view HTML structure
}

// Function to setup pagination
function setupPagination() {
    // Already handled by PHP for initial load
    // This is for dynamic pagination changes
}

// Helper function to change page
function changePage(page) {
    // Get current URL parameters
    const urlParams = new URLSearchParams(window.location.search);

    // Update page parameter
    urlParams.set('page', page);

    // Redirect to new page
    window.location.href = `admin_appointments.php?${urlParams.toString()}`;
}

// Function to setup search filter
function setupSearchFilter() {
    const searchInput = document.getElementById('appointment-search');
    const appointmentRows = document.querySelectorAll('.appointments-table tbody tr');

    if (searchInput && appointmentRows.length > 0) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();

            appointmentRows.forEach(row => {
                // Get all text content from the row
                const rowText = row.textContent.toLowerCase();

                // Show/hide based on search term
                if (rowText.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Function to setup action buttons
function setupActionButtons() {
    // View details buttons
    const viewButtons = document.querySelectorAll('.view-appointment');

    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');

            // Show appointment details modal
            const detailsModal = document.getElementById('appointment-details-modal');
            const contentArea = document.getElementById('appointment-details-content');

            if (detailsModal && contentArea) {
                // Show loading indicator
                contentArea.innerHTML = '<div class="loading-spinner"><i class="bx bx-loader-alt bx-spin"></i><p>Loading appointment details...</p></div>';

                // Show modal
                detailsModal.classList.add('show');

                // Fetch appointment details
                fetch(`../actions/get_appointment_details.php?id=${appointmentId}`)
                    .then(response => response.text())
                    .then(html => {
                        contentArea.innerHTML = html;

                        // Setup status action buttons
                        setupStatusActionButtons();
                    })
                    .catch(error => {
                        console.error('Error fetching appointment details:', error);
                        contentArea.innerHTML = '<div class="error-message">Failed to load appointment details. Please try again.</div>';
                    });
            }
        });
    });

    // Edit buttons
    const editButtons = document.querySelectorAll('.edit-appointment');

    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');

            // Show appointment edit modal
            const editModal = document.getElementById('appointment-edit-modal');
            if (editModal) {
                // Update modal title
                document.getElementById('appointment-modal-title').textContent = 'Edit Appointment';

                // Set appointment ID in the form
                document.getElementById('appointment_id').value = appointmentId;

                // Fetch appointment data and populate form
                fetch(`../actions/get_appointment_data.php?id=${appointmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        // Populate form fields
                        document.getElementById('patient_id').value = data.patient_id;
                        document.getElementById('doctor_id').value = data.doctor_id;
                        document.getElementById('service_id').value = data.service_id;
                        document.getElementById('location_id').value = data.location_id;
                        document.getElementById('appointment_date').value = data.appointment_date;
                        document.getElementById('appointment_time').value = data.start_time;
                        document.getElementById('duration').value = data.duration;
                        document.getElementById('status').value = data.status;
                        document.getElementById('notes').value = data.notes;

                        // Show modal
                        editModal.classList.add('show');
                    })
                    .catch(error => {
                        console.error('Error fetching appointment data:', error);
                        showToast('Failed to load appointment data. Please try again.');
                    });
            }
        });
    });

    // More options buttons
    const moreButtons = document.querySelectorAll('.more-options');

    moreButtons.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.stopPropagation();

            const appointmentId = this.getAttribute('data-id');

            // Show context menu
            const contextMenu = document.getElementById('appointment-actions-menu');
            if (contextMenu) {
                const rect = this.getBoundingClientRect();
                contextMenu.style.top = rect.bottom + window.scrollY + 5 + 'px';
                contextMenu.style.left = rect.left - 170 + 'px';

                contextMenu.classList.add('show');

                // Store the appointment ID
                contextMenu.dataset.appointmentId = appointmentId;

                // Update available actions based on appointment status
                const row = this.closest('tr');
                if (row) {
                    const status = row.querySelector('.status-badge').textContent.toLowerCase();
                    const isPast = row.classList.contains('past-appointment');

                    // Get menu items
                    const editItem = contextMenu.querySelector('[data-action="edit"]');
                    const completeItem = contextMenu.querySelector('[data-action="complete"]');
                    const rescheduleItem = contextMenu.querySelector('[data-action="reschedule"]');
                    const reminderItem = contextMenu.querySelector('[data-action="reminder"]');
                    const cancelItem = contextMenu.querySelector('[data-action="cancel"]');

                    // Adjust available actions based on status
                    if (editItem) editItem.style.display = (isPast || status === 'completed' || status === 'cancelled') ? 'none' : '';
                    if (completeItem) completeItem.style.display = (status === 'completed' || status === 'cancelled') ? 'none' : '';
                    if (rescheduleItem) rescheduleItem.style.display = (status === 'cancelled') ? 'none' : '';
                    if (reminderItem) reminderItem.style.display = (isPast || status === 'completed' || status === 'cancelled') ? 'none' : '';
                    if (cancelItem) cancelItem.style.display = (status === 'completed' || status === 'cancelled') ? 'none' : '';
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
                document.getElementById('appointment_id').value = '';

                // Set default date to today
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('appointment_date').value = today;

                // Show modal
                editModal.classList.add('show');
            }
        });
    }
}

// Function to setup status action buttons inside appointment details
function setupStatusActionButtons() {
    const statusActionBtns = document.querySelectorAll('.status-actions .btn');

    statusActionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('data-action');
            const appointmentId = this.closest('[data-appointment-id]').getAttribute('data-appointment-id');

            // Process status change
            fetch('../actions/update_appointment_status.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `appointment_id=${appointmentId}&action=${action}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Appointment status updated successfully');

                        // Close modal
                        const detailsModal = document.getElementById('appointment-details-modal');
                        if (detailsModal) {
                            detailsModal.classList.remove('show');
                        }

                        // Refresh page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message || 'Failed to update appointment status');
                    }
                })
                .catch(error => {
                    console.error('Error updating appointment status:', error);
                    showToast('An error occurred. Please try again.');
                });
        });
    });
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

            // Get form data
            const formData = new FormData(this);

            // Convert to URL encoded string
            const urlEncoded = new URLSearchParams(formData).toString();

            // Send data to server
            fetch('../actions/process_appointment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: urlEncoded
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message || 'Appointment saved successfully');

                        // Close modal
                        editModal.classList.remove('show');

                        // Refresh page after short delay
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        showToast(data.message || 'Failed to save appointment');
                    }
                })
                .catch(error => {
                    console.error('Error saving appointment:', error);
                    showToast('An error occurred. Please try again.');
                });
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
            const appointmentId = contextMenu.dataset.appointmentId || '';

            // Handle different actions
            switch (action) {
                case 'view':
                    // Trigger the view button click for this appointment
                    const viewBtn = document.querySelector(`.view-appointment[data-id="${appointmentId}"]`);
                    if (viewBtn) {
                        viewBtn.click();
                    }
                    break;

                case 'edit':
                    // Trigger the edit button click for this appointment
                    const editBtn = document.querySelector(`.edit-appointment[data-id="${appointmentId}"]`);
                    if (editBtn) {
                        editBtn.click();
                    }
                    break;

                case 'complete':
                case 'cancel':
                    // Update appointment status
                    updateAppointmentStatus(appointmentId, action);
                    break;

                case 'reschedule':
                    // Open edit modal in reschedule mode
                    openRescheduleModal(appointmentId);
                    break;

                case 'reminder':
                    // Send reminder to patient
                    sendAppointmentReminder(appointmentId);
                    break;
            }

            // Close context menu
            contextMenu.classList.remove('show');
        });
    });
}

// Function to update appointment status
function updateAppointmentStatus(appointmentId, action) {
    // Map action to status
    const statusMap = {
        'complete': 'completed',
        'cancel': 'cancelled'
    };

    const status = statusMap[action] || action;

    // Send update request
    fetch('../actions/update_appointment_status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `appointment_id=${appointmentId}&status=${status}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || `Appointment ${action}ed successfully`);

                // Refresh page after short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(data.message || `Failed to ${action} appointment`);
            }
        })
        .catch(error => {
            console.error(`Error ${action}ing appointment:`, error);
            showToast('An error occurred. Please try again.');
        });
}

// Function to open reschedule modal
function openRescheduleModal(appointmentId) {
    // Show appointment edit modal
    const editModal = document.getElementById('appointment-edit-modal');
    if (editModal) {
        // Update modal title
        document.getElementById('appointment-modal-title').textContent = 'Reschedule Appointment';

        // Set appointment ID in the form
        document.getElementById('appointment_id').value = appointmentId;

        // Fetch appointment data and populate form
        fetch(`../actions/get_appointment_data.php?id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                // Populate form fields
                document.getElementById('patient_id').value = data.patient_id;
                document.getElementById('doctor_id').value = data.doctor_id;
                document.getElementById('service_id').value = data.service_id;
                document.getElementById('location_id').value = data.location_id;
                document.getElementById('appointment_date').value = data.appointment_date;
                document.getElementById('appointment_time').value = data.start_time;
                document.getElementById('duration').value = data.duration;
                document.getElementById('status').value = data.status;
                document.getElementById('notes').value = data.notes;

                // Add a day to the date for rescheduling suggestion
                const date = new Date(data.appointment_date);
                date.setDate(date.getDate() + 1);
                const newDate = date.toISOString().split('T')[0];
                document.getElementById('appointment_date').value = newDate;

                // Show modal
                editModal.classList.add('show');
            })
            .catch(error => {
                console.error('Error fetching appointment data:', error);
                showToast('Failed to load appointment data. Please try again.');
            });
    }
}

// Function to send appointment reminder
function sendAppointmentReminder(appointmentId) {
    fetch('../actions/send_appointment_reminder.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `appointment_id=${appointmentId}`
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(data.message || 'Reminder sent successfully');
            } else {
                showToast(data.message || 'Failed to send reminder');
            }
        })
        .catch(error => {
            console.error('Error sending reminder:', error);
            showToast('An error occurred. Please try again.');
        });
}

// Function to setup export button
function setupExportButton() {
    const exportBtn = document.getElementById('export-appointments');

    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            // Get current filter parameters
            const urlParams = new URLSearchParams(window.location.search);

            // Redirect to export script with filters
            window.location.href = `../actions/export_appointments.php?${urlParams.toString()}`;
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
function showToast(message, type = 'info') {
    // Check if toast container exists, if not create it
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }

    // Create toast
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class='bx ${type === 'success' ? 'bx-check-circle' : type === 'error' ? 'bx-x-circle' : 'bx-info-circle'}'></i>
            <span>${message}</span>
        </div>
        <i class='bx bx-x toast-close'></i>
    `;

    // Add toast to container
    toastContainer.appendChild(toast);

    // Show toast
    setTimeout(() => {
        toast.classList.add('show');
    }, 10);

    // Auto hide after 5 seconds
    const hideTimeout = setTimeout(() => {
        hideToast(toast);
    }, 5000);

    // Close button functionality
    const closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            clearTimeout(hideTimeout);
            hideToast(toast);
        });
    }
}

function hideToast(toast) {
    toast.classList.remove('show');
    // Remove from DOM after animation
    setTimeout(() => {
        if (toast.parentElement) {
            toast.parentElement.removeChild(toast);
        }
    }, 300);
}