// Utility Functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}


/**
 * Format a date for input fields (YYYY-MM-DD)
 * @param {Date} date - The date to format
 * @returns {string} The formatted date string
 */
function formatDateForInput(date) {
    const d = new Date(date);
    const month = (d.getMonth() + 1).toString().padStart(2, '0');
    const day = d.getDate().toString().padStart(2, '0');
    return `${d.getFullYear()}-${month}-${day}`;
}

/**
 * Format a date for display
 * @param {string} dateString - The date string to format
 * @returns {string} The formatted date string
 */
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    });
}

/**
 * Format a time string for display
 * @param {string} timeString - The time string to format
 * @returns {string} The formatted time string
 */
function formatTime(timeString) {
    return new Date(`1970-01-01T${timeString}`).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

/**
 * Open a modal by its ID
 * @param {string} modalId - The ID of the modal to open
 */
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.display = 'block';
    modal.classList.add('active');

    // Add ESC key listener
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal(modalId);
        }
    });
}

// Add this after your utility functions
/**
 * Show a notification message
 * @param {string} type - The type of notification ('success' or 'error')
 * @param {string} message - The message to display
 */
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class='bx ${type === 'success' ? 'bx-check-circle' : 'bx-x-circle'}'></i>
            <span>${message}</span>
        </div>
    `;

    document.body.appendChild(notification);

    // Remove notification after 3 seconds
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

/**
 * Close a modal by its ID
 * @param {string} modalId - The ID of the modal to close
 */
function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return;

    modal.style.display = 'none';
    modal.classList.remove('active');

    // Clear form if it's an edit modal
    if (modalId === 'appointment-edit-modal') {
        const form = document.getElementById('appointment-form');
        if (form) form.reset();
    }
}

// Make modal functions globally accessible
window.openModal = openModal;
window.closeModal = closeModal;
// Global variables
let activeContextMenu = null;


document.addEventListener('DOMContentLoaded', function() {
    initializeAppointmentPage();
    setupEventListeners();

     // Add filter change listeners
     const filterInputs = [
        'status-filter',
        'service-filter',
        'date-start',
        'date-end'
    ];

    filterInputs.forEach(id => {
        const element = document.getElementById(id);
        if (element) {
            element.addEventListener('change', applyFilters);
        }
    });
});

function initializeAppointmentPage() {
    // Initialize search with debounce
    const searchInput = document.getElementById('search-appointments');
    if (searchInput) {
        searchInput.addEventListener('input', debounce(function(e) {
            handleSearch(e.target.value);
        }, 500));
    }

  

    
    // Load initial appointments
    loadDoctorAppointments();
}

function setupEventListeners() {
    // Modal event listeners
    setupModalEventListeners();
    
    // View toggle buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.getAttribute('data-view');
            toggleView(view);
        });
    });
    
    // Form submission handling
    const appointmentForm = document.getElementById('appointment-form');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', handleAppointmentFormSubmit);
    }
    
    // Cancel button on form
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            closeModal('appointment-edit-modal');
        });
    }
}


function loadDoctorAppointments() {
    const tableBody = document.querySelector('.appointments-table tbody');
    if (!tableBody) return;

    // Show loading state
    tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading appointments...</td></tr>';

    fetch('/SamaCare/actions/get_doctor_appointments.php', {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(async response => {
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return response.json();
        } else {
            const text = await response.text();
            throw new Error(`Server returned invalid response: ${text}`);
        }
    })
    .then(data => {
        if (data.success && data.appointments) {
            if (data.appointments.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="no-data">No appointments found.</td></tr>';
                return;
            }

            // Clear table
            tableBody.innerHTML = '';

            // Add each appointment to the table
            data.appointments.forEach(appointment => {
                const row = createAppointmentRow(appointment);
                tableBody.appendChild(row);
            });
        } else {
            throw new Error(data.message || 'Failed to load appointments');
        }
    })
    .catch(error => {
        console.error('Error loading appointments:', error);
        showNotification('error', 'Failed to load appointments');
        tableBody.innerHTML = '<tr><td colspan="6" class="error">Failed to load appointments. Please try again.</td></tr>';
    });
}

/**
 * Set up modal-related event listeners
 */
function setupModalEventListeners() {
    // Close buttons for modals
    const modalCloseButtons = document.querySelectorAll('.modal-close');
    modalCloseButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Add appointment button
    const addAppointmentBtn = document.getElementById('add-appointment-btn');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function() {
            editAppointment(null)
        });
    }
}

/**
 * Set up context menu for appointment actions
 * @param {HTMLElement} button - The more options button that was clicked
 */
function setupContextMenu(button) {
    const menu = document.getElementById('appointment-actions-menu');
    if (!menu) return;

    // Get button position and dimensions
    const rect = button.getBoundingClientRect();
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const scrollLeft = window.pageXOffset || document.documentElement.scrollLeft;

    // Position menu below the button
    menu.style.top = `${rect.bottom + scrollTop}px`;
    menu.style.left = `${rect.left + scrollLeft}px`;
    menu.style.display = 'block';
    
    // Store appointment ID in menu's dataset
    const appointmentId = button.getAttribute('data-id');
    menu.dataset.appointmentId = appointmentId;

    // Close menu when clicking outside
    const closeMenu = (e) => {
        if (!menu.contains(e.target) && e.target !== button) {
            menu.style.display = 'none';
            document.removeEventListener('click', closeMenu);
        }
    };

    // Add click event listeners to menu items
    menu.querySelectorAll('li').forEach(item => {
        item.onclick = (e) => {
            e.stopPropagation();
            const action = item.getAttribute('data-action');
            handleContextMenuAction(action, appointmentId);
            menu.style.display = 'none';
        };
    });

    // Add click listener to close menu
    setTimeout(() => {
        document.addEventListener('click', closeMenu);
    }, 0);
}

/**
 * Handle context menu actions
 * @param {string} action - The action to perform
 * @param {string} appointmentId - The ID of the appointment
 */
function handleContextMenuAction(action, appointmentId) {
    switch (action) {
        case 'reschedule':
            editAppointment(appointmentId, true);
            break;
        case 'cancel':
            if (confirm('Are you sure you want to cancel this appointment?')) {
                updateAppointmentStatus(appointmentId, 'cancelled');
            }
            break;
        case 'delete':
            if (confirm('Are you sure you want to delete this appointment? This action cannot be undone.')) {
                deleteAppointment(appointmentId);
            }
            break;
    }
}


/**
 * Toggle between list and calendar views
 * @param {string} viewType - The view type to show ('list' or 'calendar')
 */
function toggleView(viewType) {
    // Update active state on view buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        if (btn.getAttribute('data-view') === viewType) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
    
    // Show the selected view and hide others
    const viewSections = document.querySelectorAll('.view-section');
    viewSections.forEach(section => {
        if (section.id === viewType + '-view') {
            section.classList.add('active');
            
            // Initialize calendar if switching to calendar view
            if (viewType === 'calendar') {
                initializeCalendarView();
            }
        } else {
            section.classList.remove('active');
        }
    });
}

/**
 * Show appointment details modal
 * @param {string} appointmentId - The ID of the appointment to view
 */
// Add view appointment details function
function viewAppointmentDetails(appointmentId) {
    fetch(`/SamaCare/actions/get_appointment_data.php?id=${appointmentId}`)
        .then(response => {
            if (!response.ok) throw new Error('Network response was not ok');
            return response.json();
        })
        .then(data => {
            if (data.success && data.appointment) {
                const appointment = data.appointment;
                const modal = document.getElementById('appointment-view-modal');
                
                // Update modal content
                document.getElementById('view-patient-name').textContent = `${appointment.patient_first_name} ${appointment.patient_last_name}`;
                document.getElementById('view-service').textContent = appointment.service_name;
                document.getElementById('view-date').textContent = formatDate(appointment.appointment_date);
                document.getElementById('view-time').textContent = `${formatTime(appointment.start_time)} - ${formatTime(appointment.end_time)}`;
                document.getElementById('view-location').textContent = appointment.location_name;
                document.getElementById('view-status').textContent = appointment.status;
                document.getElementById('view-notes').textContent = appointment.notes || 'No notes';

                // Add status badge class
                const statusSpan = document.getElementById('view-status');
                statusSpan.className = `status-badge ${appointment.status.toLowerCase()}`;

                // Show modal
                openModal('appointment-view-modal');
            } else {
                throw new Error(data.message || 'Failed to load appointment details');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('error', 'Failed to load appointment details');
        });
}


/**
 * Render appointment details in the modal
 * @param {Element} container - The container element
 * @param {Object} appointment - The appointment data
 */
function renderAppointmentDetails(container, appointment) {
    // Format date and time
    const appointmentDate = formatDate(appointment.appointment_date);
    const startTime = formatTime(appointment.start_time);
    const endTime = formatTime(appointment.end_time);
    
    // Create status badge class
    const statusClass = appointment.status.toLowerCase();
    
    // Build HTML content
    const html = `
        <div class="appointment-details">
            <div class="details-header">
                <div class="appointment-info">
                    <h3>${appointment.service_name}</h3>
                    <span class="status-badge ${statusClass}">${appointment.status}</span>
                </div>
                <div class="appointment-datetime">
                    <div class="date"><i class='bx bx-calendar'></i> ${appointmentDate}</div>
                    <div class="time"><i class='bx bx-time'></i> ${startTime} - ${endTime}</div>
                </div>
            </div>
            
            <div class="details-section">
                <h4>Patient Information</h4>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="label">Name:</span>
                        <span class="value">${appointment.patient_first_name} ${appointment.patient_last_name}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value">${appointment.patient_email || 'N/A'}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Phone:</span>
                        <span class="value">${appointment.patient_phone || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <div class="details-section">
                <h4>Appointment Details</h4>
                <div class="info-grid">
                    <div class="info-row">
                        <span class="label">Service:</span>
                        <span class="value">${appointment.service_name}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Doctor:</span>
                        <span class="value">Dr. ${appointment.doctor_first_name} ${appointment.doctor_last_name}</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Location:</span>
                        <span class="value">${appointment.location_name}</span>
                    </div>
                </div>
            </div>
            
            ${appointment.notes ? `
            <div class="details-section">
                <h4>Notes</h4>
                <div class="appointment-notes">
                    ${appointment.notes}
                </div>
            </div>
            ` : ''}
            
            <div class="details-actions">
                <button class="btn secondary-btn" onclick="editAppointment('${appointment.appointment_id}')">
                    <i class='bx bx-edit'></i> Edit
                </button>
                ${appointment.status !== 'completed' ? `
                <button class="btn primary-btn" onclick="updateAppointmentStatus('${appointment.appointment_id}', 'completed')">
                    <i class='bx bx-check-circle'></i> Mark as Completed
                </button>
                ` : ''}
                ${appointment.status !== 'cancelled' ? `
                <button class="btn danger-btn" onclick="confirmCancelAppointment('${appointment.appointment_id}')">
                    <i class='bx bx-x-circle'></i> Cancel Appointment
                </button>
                ` : ''}
            </div>
        </div>
    `;
    
    container.innerHTML = html;
}

/**
 * Show the appointment edit/create modal
 * @param {string|null} appointmentId - The ID of the appointment to edit, or null for a new appointment
 * @param {boolean} focusDateTime - Whether to focus on date/time fields (for rescheduling)
 */
function editAppointment(appointmentId = null, focusDateTime = false) {
    const modal = document.getElementById('appointment-edit-modal');
    const form = document.getElementById('appointment-form');
    const modalTitle = document.getElementById('appointment-modal-title');
    
    // Clear the form
    form.reset();
    
    // Set title based on edit or create
    if (appointmentId) {
        modalTitle.textContent = 'Edit Appointment';
        document.getElementById('appointment_id').value = appointmentId;
        
        // Fetch appointment data via AJAX
        fetch(`/SamaCare/actions/get_appointment_data.php?id=${appointmentId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    populateAppointmentForm(data.appointment);
                    
                    // Focus on date/time fields if rescheduling
                    if (focusDateTime) {
                        setTimeout(() => {
                            document.getElementById('appointment_date').focus();
                        }, 100);
                    }
                } else {
                    showNotification('error', data.message || 'Failed to load appointment data.');
                }
            })
            .catch(error => {
                console.error('Error fetching appointment data:', error);
                showNotification('error', 'Failed to load appointment data. Please try again.');
            });
    } else {
        modalTitle.textContent = 'Schedule Appointment';
        document.getElementById('appointment_id').value = '';
        
        // Set default values for new appointment
        const today = new Date();
        document.getElementById('appointment_date').value = formatDateForInput(today);
        document.getElementById('status').value = 'pending';
    }
    
    openModal('appointment-edit-modal');
}

/**
 * Populate the appointment form with data
 * @param {Object} appointment - The appointment data
 */
function populateAppointmentForm(appointment) {
    document.getElementById('patient_id').value = appointment.patient_id;
    document.getElementById('service_id').value = appointment.service_id;
    document.getElementById('location_id').value = appointment.location_id;
    document.getElementById('appointment_date').value = appointment.appointment_date;
    document.getElementById('start_time').value = appointment.start_time;
    document.getElementById('end_time').value = appointment.end_time;
    document.getElementById('status').value = appointment.status;
    document.getElementById('notes').value = appointment.notes || '';
}


function saveAppointment(formData) {
    return new Promise((resolve, reject) => {
        // Get submit button and save original text
        const submitBtn = document.querySelector('#appointment-form button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Saving...';
        submitBtn.disabled = true;

        // Log form data for debugging
        console.log('Form data being sent:', Object.fromEntries(formData));

        fetch('/SamaCare/actions/save_appointment.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        })
        .then(async response => {
            // Log raw response for debugging
            const responseText = await response.text();
            console.log('Raw server response:', responseText);

            try {
                // Try to parse the response as JSON
                const data = JSON.parse(responseText);
                if (!data.success) {
                    throw new Error(data.message || 'Failed to save appointment');
                }
                return data;
            } catch (e) {
                throw new Error(`Server error: ${responseText}`);
            }
        })
        .then(data => {
            showNotification('success', 'Appointment saved successfully');
            
            if (data.appointment) {
                updateAppointmentsList(data.appointment);
            } else {
                // If no appointment data returned, refresh the page
                window.location.reload();
            }
            
            closeModal('appointment-edit-modal');
            resolve(data);
        })
        .catch(error => {
            console.error('Error saving appointment:', error);
            showNotification('error', error.message);
            reject(error);
        })
        .finally(() => {
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
}

function handleAppointmentFormSubmit(e) {
    e.preventDefault();
    
    try {
        const form = e.target;
        const formData = new FormData(form);

        // Add required fields if missing
        if (!formData.get('appointment_id')) {
            formData.append('appointment_id', '0'); // Use 0 for new appointments
        }
        
        // Validate required fields
        const requiredFields = ['patient_id', 'service_id', 'location_id', 
                              'appointment_date', 'start_time', 'end_time', 'status'];
        
        for (const field of requiredFields) {
            if (!formData.get(field)) {
                throw new Error(`${field.replace('_', ' ')} is required`);
            }
        }

        // Save appointment
        saveAppointment(formData)
            .then(() => {
                console.log('Appointment saved successfully');
            })
            .catch(error => {
                console.error('Form submission error:', error);
                showNotification('error', error.message);
            });

    } catch (error) {
        console.error('Form validation error:', error);
        showNotification('error', error.message);
    }
}

function editAppointment(appointmentId = null, focusDateTime = false) {
    // Get modal elements
    const modal = document.getElementById('appointment-edit-modal');
    const form = document.getElementById('appointment-form');
    
    // First check if modal exists
    if (!modal) {
        console.error('Modal not found');
        return;
    }

    // Find modal title - try different possible selectors
    const modalTitle = modal.querySelector('.modal-title') || 
                      modal.querySelector('#appointment-modal-title') ||
                      modal.querySelector('h3');

    if (!modalTitle) {
        console.error('Modal title element not found');
        return;
    }

    // Reset form if it exists
    if (form) {
        form.reset();
    } else {
        console.error('Form not found');
        return;
    }

    if (appointmentId) {
        // Edit existing appointment
        modalTitle.textContent = 'Edit Appointment';
        
        const appointmentIdInput = document.getElementById('appointment_id');
        if (appointmentIdInput) {
            appointmentIdInput.value = appointmentId;
        }

        // Fetch appointment data
        fetch(`/SamaCare/actions/get_appointment_data.php?id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    populateAppointmentForm(data.appointment);
                    if (focusDateTime) {
                        const dateInput = document.getElementById('appointment_date');
                        if (dateInput) {
                            dateInput.focus();
                        }
                    }
                } else {
                    throw new Error(data.message || 'Failed to load appointment data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('error', error.message);
            });
    } else {
        // New appointment
        modalTitle.textContent = 'Schedule New Appointment';
        
        const appointmentIdInput = document.getElementById('appointment_id');
        if (appointmentIdInput) {
            appointmentIdInput.value = '0';
        }
        
        // Set default values for new appointment
        const today = new Date();
        const dateInput = document.getElementById('appointment_date');
        const statusInput = document.getElementById('status');
        
        if (dateInput) {
            dateInput.value = formatDateForInput(today);
        }
        if (statusInput) {
            statusInput.value = 'pending';
        }
    }

    openModal('appointment-edit-modal');
}

// Add event listener for form submission
document.addEventListener('DOMContentLoaded', function() {
    const appointmentForm = document.getElementById('appointment-form');
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', handleAppointmentFormSubmit);
    }
});

/**
 * Update appointments list with new data
 * @param {Object} appointment - The updated appointment data
 */
function updateAppointmentsList(appointment) {
    try {
        console.log('Updating appointment row with data:', appointment);

        if (!appointment || !appointment.appointment_id) {
            throw new Error('Invalid appointment data');
        }

        const newRow = createAppointmentRow(appointment);

        // Find and replace existing row
        const existingRow = document.querySelector(`tr[data-appointment-id="${appointment.appointment_id}"]`);
        if (existingRow) {
            existingRow.replaceWith(newRow);
            console.log('Row replaced successfully');
        } else {
            // If no existing row, insert at the beginning
            const tableBody = document.querySelector('.appointments-table tbody');
            if (tableBody) {
                tableBody.insertBefore(newRow, tableBody.firstChild);
            }
        }

    } catch (error) {
        console.error('Error in updateAppointmentsList:', error);
        showNotification('error', 'Failed to update appointments list');
    }
}
/**
 * Get initials from first and last name
 * @param {string} firstName
 * @param {string} lastName
 * @returns {string} Initials
 */
function getInitials(firstName, lastName) {
    return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase();
}

/**
 * Format date for display
 * @param {string} dateStr
 * @returns {string} Formatted date
 */
function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { 
        month: 'long', 
        day: 'numeric', 
        year: 'numeric' 
    });
}

/**
 * Format time for display
 * @param {string} timeStr
 * @returns {string} Formatted time
 */
function formatTime(timeStr) {
    const date = new Date(`2000-01-01T${timeStr}`);
    return date.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit',
        hour12: true 
    });
}
/**
 * Show a confirmation dialog for cancelling an appointment
 * @param {string} appointmentId - The ID of the appointment to cancel
 */
function confirmCancelAppointment(appointmentId) {
    if (confirm('Are you sure you want to cancel this appointment? This action cannot be undone.')) {
        updateAppointmentStatus(appointmentId, 'cancelled');
    }
}







function applyFilters() {
    // Get filter values
    const statusFilter = document.getElementById('status-filter')?.value;
    const serviceFilter = document.getElementById('service-filter')?.value;
    const dateStart = document.getElementById('date-start')?.value;
    const dateEnd = document.getElementById('date-end')?.value;

    // Build query parameters
    const params = new URLSearchParams();
    if (statusFilter && statusFilter !== 'all') params.append('status', statusFilter.toLowerCase());
    if (serviceFilter && serviceFilter !== 'all') params.append('service', serviceFilter);
    if (dateStart) params.append('date_start', dateStart);
    if (dateEnd) params.append('date_end', dateEnd);

    // Show loading state
    const tableBody = document.querySelector('.appointments-table tbody');
    if (tableBody) {
        tableBody.innerHTML = '<tr><td colspan="6" class="loading">Loading appointments...</td></tr>';
    }

    // Fetch filtered appointments
    fetch(`/SamaCare/actions/get_doctor_appointments.php?${params.toString()}`, {
        method: 'GET',
        credentials: 'same-origin'
    })
    .then(response => response.json())
    .then(data => {
        console.log('Full response:', data); // Debug log

        if (!tableBody) return;

        if (data.success) {
            if (!data.appointments || data.appointments.length === 0) {
                tableBody.innerHTML = '<tr><td colspan="6" class="no-data">No appointments found for the selected filters.</td></tr>';
                return;
            }

            tableBody.innerHTML = '';
            data.appointments.forEach(appointment => {
                const row = createAppointmentRow(appointment);
                tableBody.appendChild(row);
            });
        } else {
            throw new Error(data.message || 'Failed to load appointments');
        }
    })
    .catch(error => {
        console.error('Filter error:', error);
        showNotification('error', 'Failed to load appointments');
        if (tableBody) {
            tableBody.innerHTML = '<tr><td colspan="6" class="error">Failed to load appointments. Please try again.</td></tr>';
        }
    });
}

/**
 * Search appointments by patient name or service
 */
function handleSearch(searchTerm) {
    searchTerm = searchTerm.toLowerCase();
    const tableRows = document.querySelectorAll('.appointments-table tbody tr');
    
    tableRows.forEach(row => {
        if (row.classList.contains('no-data')) return;
        
        const patientName = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const service = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
        
        if (patientName.includes(searchTerm) || service.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
    
    // Show "no results" row if all rows are hidden
    const visibleRows = Array.from(tableRows).filter(row => row.style.display !== 'none' && !row.classList.contains('no-data'));
    
    const noDataRow = document.querySelector('.appointments-table tbody tr.no-data');
    if (visibleRows.length === 0 && !noDataRow) {
        const tbody = document.querySelector('.appointments-table tbody');
        const newRow = document.createElement('tr');
        newRow.classList.add('no-data', 'search-no-results');
        newRow.innerHTML = `<td colspan="6" class="no-data">No appointments match your search.</td>`;
        tbody.appendChild(newRow);
    } else if (visibleRows.length > 0) {
        const noResultsRow = document.querySelector('.appointments-table tbody tr.search-no-results');
        if (noResultsRow) {
            noResultsRow.remove();
        }
    }
}

/**
 * Navigate to a specific page for pagination
 * @param {number} page - The page number to navigate to
 */
function goToPage(page) {
    if (page < 1) return;
    
    // Get current URL and update the page parameter
    const url = new URL(window.location.href);
    url.searchParams.set('page', page);
    
    // Navigate to the new URL
    window.location.href = url.toString();
}

/**
 * Initialize the calendar view
 */
function initializeCalendarView() {
    const calendarContainer = document.getElementById('calendar-container');
    if (!calendarContainer) return;
    
    // Get current date
    const currentDate = new Date();
    let currentMonth = currentDate.getMonth();
    let currentYear = currentDate.getFullYear();
    
    // Initial calendar render
    renderCalendar(currentMonth, currentYear);
    
    // Set up calendar navigation
    document.getElementById('prev-month').addEventListener('click', function() {
        currentMonth--;
        if (currentMonth < 0) {
            currentMonth = 11;
            currentYear--;
        }
        renderCalendar(currentMonth, currentYear);
    });
    
    document.getElementById('next-month').addEventListener('click', function() {
        currentMonth++;
        if (currentMonth > 11) {
            currentMonth = 0;
            currentYear++;
        }
        renderCalendar(currentMonth, currentYear);
    });
    
    // Set up calendar view type buttons
    const calendarViewButtons = document.querySelectorAll('.calendar-view-btn');
    calendarViewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove active class from all buttons
            calendarViewButtons.forEach(b => b.classList.remove('active'));
            
            // Add active class to clicked button
            this.classList.add('active');
            
            // Get the view type
            const viewType = this.getAttribute('data-view');
            
            // Handle view change
            changeCalendarView(viewType, currentMonth, currentYear);
        });
    });
}

/**
 * Change the calendar view type
 * @param {string} viewType - The view type (month, week, day)
 * @param {number} month - The current month
 * @param {number} year - The current year
 */
function changeCalendarView(viewType, month, year) {
    switch (viewType) {
        case 'month':
            renderCalendar(month, year);
            break;
        case 'week':
            renderWeekView(month, year);
            break;
        case 'day':
            renderDayView(month, year);
            break;
    }
}

/**
 * Render the monthly calendar
 * @param {number} month - The month to render (0-11)
 * @param {number} year - The year to render
 */
function renderCalendar(month, year) {
    const calendarContainer = document.getElementById('calendar-container');
    const monthYearElement = document.getElementById('current-month-year');
    
    // Update the month/year display
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    monthYearElement.textContent = `${monthNames[month]} ${year}`;
    
    // Get the first day of the month
    const firstDay = new Date(year, month, 1).getDay();
    
    // Get the number of days in the month
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Generate calendar HTML
    let calendarHTML = `
        <div class="calendar">
            <div class="calendar-header">
                <div class="calendar-day">Sun</div>
                <div class="calendar-day">Mon</div>
                <div class="calendar-day">Tue</div>
                <div class="calendar-day">Wed</div>
                <div class="calendar-day">Thu</div>
                <div class="calendar-day">Fri</div>
                <div class="calendar-day">Sat</div>
            </div>
            <div class="calendar-body">
    `;
    
    // Fill in the days
    let dayCount = 1;
    const today = new Date();
    
    // Previous month days
    for (let i = 0; i < firstDay; i++) {
        calendarHTML += `<div class="calendar-date other-month"></div>`;
    }
    
    // Current month days
    for (let i = 1; i <= daysInMonth; i++) {
        const dateString = `${year}-${(month + 1).toString().padStart(2, '0')}-${i.toString().padStart(2, '0')}`;
        let classes = 'calendar-date';
        
        // Check if it's today
        if (i === today.getDate() && month === today.getMonth() && year === today.getFullYear()) {
            classes += ' today';
        }
        
        // Add the date
        calendarHTML += `
            <div class="${classes}" data-date="${dateString}" onclick="loadDayAppointments('${dateString}')">
                <div class="date-number">${i}</div>
                <div class="date-appointments" id="date-${dateString}">
                    <!-- Appointments will be loaded here -->
                </div>
            </div>
        `;
        
        dayCount++;
    }
    
    // Next month days to fill out the grid
    const totalCells = Math.ceil((firstDay + daysInMonth) / 7) * 7;
    for (let i = dayCount + firstDay; i < totalCells + 1; i++) {
        calendarHTML += `<div class="calendar-date other-month"></div>`;
    }
    
    calendarHTML += `
            </div>
        </div>
    `;
    
    // Update the calendar container
    calendarContainer.innerHTML = calendarHTML;
    
    // Load appointments for the month
    loadMonthAppointments(month, year);
}

/**
 * Render the weekly calendar view
 * @param {number} month - The month (0-11)
 * @param {number} year - The year
 */
function renderWeekView(month, year) {
    // Get the first day of the current week
    const today = new Date();
    const currentDay = today.getDate();
    const currentMonth = today.getMonth();
    const currentYear = today.getFullYear();
    
    // Get the day of the week (0-6, where 0 is Sunday)
    const dayOfWeek = today.getDay();
    
    // Calculate the first day of the week (Sunday)
    const firstDayOfWeek = new Date(currentYear, currentMonth, currentDay - dayOfWeek);
    
    // Generate dates for the week
    const weekDates = [];
    for (let i = 0; i < 7; i++) {
        const date = new Date(firstDayOfWeek);
        date.setDate(firstDayOfWeek.getDate() + i);
        weekDates.push(date);
    }
    
    const calendarContainer = document.getElementById('calendar-container');
    const monthYearElement = document.getElementById('current-month-year');
    
    // Update the month/year display
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                        'July', 'August', 'September', 'October', 'November', 'December'];
    
    // Format the week range
    const weekStart = formatDate(weekDates[0]);
    const weekEnd = formatDate(weekDates[6]);
    monthYearElement.textContent = `${weekStart} - ${weekEnd}`;
    
    // Generate weekly view HTML
    let weeklyHTML = `
        <div class="week-calendar">
            <div class="week-header">
    `;
    
    // Add day headers
    const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    weekDates.forEach((date, index) => {
        const isToday = date.getDate() === today.getDate() && 
                        date.getMonth() === today.getMonth() && 
                        date.getFullYear() === today.getFullYear();
        
        const dayClass = isToday ? 'today' : '';
        weeklyHTML += `
            <div class="week-day ${dayClass}">
                <div class="day-name">${dayNames[index]}</div>
                <div class="day-number">${date.getDate()}</div>
            </div>
        `;
    });
    
    weeklyHTML += `
            </div>
            <div class="week-body">
    `;
    
    // Add time slots
    const timeSlots = ['08:00', '09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00'];
    
    timeSlots.forEach(time => {
        weeklyHTML += `
            <div class="time-row">
                <div class="time-label">${formatTime(time)}</div>
                <div class="time-slots">
        `;
        
        weekDates.forEach(date => {
            const dateString = formatDateForInput(date);
            weeklyHTML += `
                <div class="time-slot" data-date="${dateString}" data-time="${time}">
                    <!-- Appointments will be loaded here -->
                </div>
            `;
        });
        
        weeklyHTML += `
                </div>
            </div>
        `;
    });
    
    weeklyHTML += `
            </div>
        </div>
    `;
    
    // Update the calendar container
    calendarContainer.innerHTML = weeklyHTML;
    
    // Load appointments for the week
    loadWeekAppointments(weekDates);
}


/**
 * Attach event listeners to appointment row buttons
 * @param {HTMLElement} row - The table row element
 */
function attachRowEventListeners(row) {

    const statusDropdown = row.querySelector('.status-dropdown');
    const statusBadge = row.querySelector('.status-badge');
    const statusOptions = row.querySelectorAll('.status-option');

    if (statusBadge && statusDropdown) {
        // Toggle dropdown on badge click
        statusBadge.addEventListener('click', function(e) {
            e.stopPropagation();
            const allDropdowns = document.querySelectorAll('.status-dropdown.active');
            allDropdowns.forEach(dropdown => {
                if (dropdown !== statusDropdown) {
                    dropdown.classList.remove('active');
                }
            });
            statusDropdown.classList.toggle('active');
        });

        // Handle status option selection
        statusOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const newStatus = this.dataset.status;
                const appointmentId = statusBadge.dataset.id;
                updateAppointmentStatus(appointmentId, newStatus);
                statusDropdown.classList.remove('active');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            statusDropdown.classList.remove('active');
        });
    }


   // View button
    const viewBtn = row.querySelector('.view-appointment');
    if (viewBtn) {
        viewBtn.addEventListener('click', () => {
            viewAppointmentDetails(viewBtn.dataset.id);
        });
    }

    // Edit button
    const editBtn = row.querySelector('.edit-appointment');
    if (editBtn) {
        editBtn.addEventListener('click', () => {
            editAppointment(editBtn.dataset.id);
        });
    }

    // More options button
    const moreBtn = row.querySelector('.more-options');
    if (moreBtn) {
        moreBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            setupContextMenu(this);
        });
    }
}

// Add delete appointment function
function deleteAppointment(appointmentId) {
    fetch('/SamaCare/actions/delete_appointment.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `appointment_id=${appointmentId}`
    })
    .then(response => {
        if (!response.ok) throw new Error('Network response was not ok');
        return response.json();
    })
    .then(data => {
        if (data.success) {
            const row = document.querySelector(`tr[data-appointment-id="${appointmentId}"]`);
            if (row) row.remove();
            showNotification('success', 'Appointment deleted successfully');
        } else {
            throw new Error(data.message || 'Failed to delete appointment');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Failed to delete appointment');
    });
}

function createAppointmentRow(appointment) {
    const row = document.createElement('tr');
    row.dataset.appointmentId = appointment.appointment_id;
    
    const appointmentDate = formatDate(appointment.appointment_date);
    const startTime = formatTime(appointment.start_time);
    const endTime = formatTime(appointment.end_time);
    const timeRange = `${startTime} - ${endTime}`;
    
    row.innerHTML = `
        <td>
            <div class="date-time">
                <div class="date">${appointmentDate}</div>
                <div class="time">${startTime}</div>
            </div>
        </td>
        <td>
            <div class="user-info">
                <div class="user-avatar">${getInitials(appointment.patient_first_name, appointment.patient_last_name)}</div>
                <span>${appointment.patient_first_name} ${appointment.patient_last_name}</span>
            </div>
        </td>
        <td>${appointment.service_name}</td>
        <td>${timeRange}</td>
        <td><span class="status-badge ${appointment.status.toLowerCase()}">${appointment.status}</span></td>
        <td>
            <div class="action-buttons">
                <button class="btn icon-btn sm view-appointment" 
                        title="View Details" 
                        data-id="${appointment.appointment_id}">
                    <i class='bx bx-show'></i>
                </button>
                <button class="btn icon-btn sm edit-appointment" 
                        title="Edit" 
                        data-id="${appointment.appointment_id}">
                    <i class='bx bx-edit'></i>
                </button>
            </div>
        </td>
    `;

    // Attach event listeners
    const viewBtn = row.querySelector('.view-appointment');
    if (viewBtn) {
        viewBtn.addEventListener('click', () => viewAppointmentDetails(appointment.appointment_id));
    }

    const editBtn = row.querySelector('.edit-appointment');
    if (editBtn) {
        editBtn.addEventListener('click', () => editAppointment(appointment.appointment_id));
    }
    
    return row;
}



function attachStatusEventListeners(row) {
    const statusDropdown = row.querySelector('.status-dropdown');
    const statusBadge = row.querySelector('.status-badge');
    const statusOptions = row.querySelectorAll('.status-option');

    if (statusBadge && statusDropdown) {
        // Toggle dropdown on badge click
        statusBadge.addEventListener('click', function(e) {
            e.stopPropagation();
            const allDropdowns = document.querySelectorAll('.status-dropdown.active');
            allDropdowns.forEach(dropdown => {
                if (dropdown !== statusDropdown) {
                    dropdown.classList.remove('active');
                }
            });
            statusDropdown.classList.toggle('active');
        });

        // Handle status option selection
        statusOptions.forEach(option => {
            option.addEventListener('click', function(e) {
                e.stopPropagation();
                const newStatus = this.dataset.status;
                const appointmentId = statusBadge.dataset.id;
                updateAppointmentStatus(appointmentId, newStatus);
                statusDropdown.classList.remove('active');
            });
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            statusDropdown.classList.remove('active');
        });
    }

    // View button
    const viewBtn = row.querySelector('.view-appointment');
    if (viewBtn) {
        viewBtn.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');
            viewAppointmentDetails(appointmentId);
        });
    }

    // Edit button
    const editBtn = row.querySelector('.edit-appointment');
    if (editBtn) {
        editBtn.addEventListener('click', function() {
            const appointmentId = this.getAttribute('data-id');
            editAppointment(appointmentId);
        });
    }

    // More options button
    const moreBtn = row.querySelector('.more-options');
    if (moreBtn) {
        moreBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            setupContextMenu(e, this.getAttribute('data-id'));
        });
    }
}

// Make the function globally accessible
window.applyFilters = applyFilters;