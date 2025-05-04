// doctors_appointments.js - Script for the doctors_appointments.php page
document.addEventListener('DOMContentLoaded', function() {
    // Element references
    const sidebar = document.querySelector('.sidebar');
    const menuToggle = document.querySelector('.menu-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const searchInput = document.getElementById('search-appointments');
    const appointmentTable = document.querySelector('.appointments-table');
    const addAppointmentBtn = document.getElementById('add-appointment-btn');
    const viewBtns = document.querySelectorAll('.view-btn');
    const viewSections = document.querySelectorAll('.view-section');
    const appointmentDetailsModal = document.getElementById('appointment-details-modal');
    const appointmentEditModal = document.getElementById('appointment-edit-modal');
    const appointmentForm = document.getElementById('appointment-form');
    const actionsMenu = document.getElementById('appointment-actions-menu');

    // Status and service filter elements
    const statusFilter = document.getElementById('status-filter');
    const serviceFilter = document.getElementById('service-filter');
    const dateStart = document.getElementById('date-start');
    const dateEnd = document.getElementById('date-end');

    // ----------------
    // Sidebar Functions
    // ----------------

    function toggleSidebar() {
        sidebar.classList.toggle('collapsed');
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', toggleSidebar);
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', toggleSidebar);
    }

    // -------------------
    // Filtering Functions
    // -------------------

    // Add change event listeners to filters to apply them immediately
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            applyFilters();
        });
    }

    if (serviceFilter) {
        serviceFilter.addEventListener('change', function() {
            applyFilters();
        });
    }

    function applyFilters() {
        const status = document.getElementById('status-filter').value;
        const service = document.getElementById('service-filter').value;
        const dateStart = document.getElementById('date-start').value;
        const dateEnd = document.getElementById('date-end').value;

        // Build URL with query parameters
        let url = new URL(window.location.href);
        url.searchParams.set('status', status);
        url.searchParams.set('service', service);
        url.searchParams.set('date_start', dateStart);
        url.searchParams.set('date_end', dateEnd);

        // Reset to page 1 when filters change
        url.searchParams.set('page', 1);

        // Navigate to filtered URL
        window.location.href = url.toString();
    }

    // Make the function globally accessible
    window.applyFilters = applyFilters;

    // Pagination function
    function goToPage(page) {
        let url = new URL(window.location.href);
        url.searchParams.set('page', page);
        window.location.href = url.toString();
    }

    // Make the function globally accessible
    window.goToPage = goToPage;

    // ------------------
    // Search Functionality
    // ------------------

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = appointmentTable.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });

            // Show "no results" message if needed
            const visibleRows = Array.from(rows).filter(row => row.style.display !== 'none');
            const noDataRow = appointmentTable.querySelector('.no-data');

            if (visibleRows.length === 0 && !noDataRow) {
                const tbody = appointmentTable.querySelector('tbody');
                const tr = document.createElement('tr');
                tr.className = 'no-data temp';
                const td = document.createElement('td');
                td.colSpan = 6;
                td.textContent = `No appointments matching "${searchTerm}" found.`;
                tr.appendChild(td);
                tbody.appendChild(tr);
            } else if (visibleRows.length > 0) {
                const tempNoData = appointmentTable.querySelector('.no-data.temp');
                if (tempNoData) {
                    tempNoData.remove();
                }
            }
        });
    }

    // ------------------
    // View Toggle Functions
    // ------------------

    function toggleView(viewType) {
        viewBtns.forEach(btn => {
            btn.classList.toggle('active', btn.dataset.view === viewType);
        });

        viewSections.forEach(section => {
            section.classList.toggle('active', section.id === `${viewType}-view`);
        });

        // If switching to calendar view, initialize or refresh the calendar
        if (viewType === 'calendar') {
            initCalendar();
        }
    }

    viewBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            toggleView(btn.dataset.view);
        });
    });

    // ------------------
    // Modal Functions
    // ------------------

    function openModal(modal) {
        modal.classList.add('active');
        document.body.classList.add('modal-open');
    }

    function closeModal(modal) {
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    // Close modal when clicking on close button or outside
    document.querySelectorAll('.modal-close').forEach(closeBtn => {
        closeBtn.addEventListener('click', function() {
            const modal = this.closest('.modal');
            closeModal(modal);
        });
    });

    // Close modal when clicking outside content
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(event) {
            if (event.target === this) {
                closeModal(this);
            }
        });
    });

    // ------------------
    // Appointment Details Modal
    // ------------------

    // Function to handle viewing appointment details
    function viewAppointmentDetails(appointmentId) {
        // Fetch appointment data
        fetch(`../api/get_appointment_data.php?id=${appointmentId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                    return;
                }

                const appointment = data.appointment;

                // Populate form fields
                document.getElementById('view_appointment_id').value = appointment.appointment_id;

                // Set select element values
                const patientSelect = document.getElementById('view_patient_id');
                for (let i = 0; i < patientSelect.options.length; i++) {
                    if (patientSelect.options[i].value == appointment.patient_id) {
                        patientSelect.options[i].selected = true;
                        break;
                    }
                }

                const serviceSelect = document.getElementById('view_service_id');
                for (let i = 0; i < serviceSelect.options.length; i++) {
                    if (serviceSelect.options[i].value == appointment.service_id) {
                        serviceSelect.options[i].selected = true;
                        break;
                    }
                }

                const locationSelect = document.getElementById('view_location_id');
                for (let i = 0; i < locationSelect.options.length; i++) {
                    if (locationSelect.options[i].value == appointment.location_id) {
                        locationSelect.options[i].selected = true;
                        break;
                    }
                }

                const statusSelect = document.getElementById('view_status');
                for (let i = 0; i < statusSelect.options.length; i++) {
                    if (statusSelect.options[i].value == appointment.status) {
                        statusSelect.options[i].selected = true;
                        break;
                    }
                }

                // Set input field values
                document.getElementById('view_appointment_date').value = appointment.appointment_date;
                document.getElementById('view_start_time').value = appointment.start_time;
                document.getElementById('view_end_time').value = appointment.end_time;
                document.getElementById('view_notes').value = appointment.notes || '';

                // Open modal
                const detailsModal = document.getElementById('appointment-details-modal');
                if (detailsModal) {
                    detailsModal.classList.add('active');
                    document.body.classList.add('modal-open');
                }
            })
            .catch(error => {
                console.error('Error fetching appointment data:', error);
                alert('Error loading appointment details. Please try again.');
            });
    }

    // Make the function globally accessible
    window.viewAppointmentDetails = viewAppointmentDetails;

    // ------------------
    // Appointment Edit Modal
    // ------------------

    // Function to handle form submission
    if (appointmentForm) {
        appointmentForm.addEventListener('submit', function(event) {
            event.preventDefault();

            // Get form data
            const formData = new FormData(this);

            // Submit via fetch API
            fetch('../api/save_appointment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        // Show error message
                        alert('Error: ' + data.error);
                    } else {
                        // Close modal and reload page
                        closeModal(appointmentEditModal);
                        window.location.reload();
                    }
                })
                .catch(error => {
                    console.error('Error saving appointment:', error);
                    alert('An error occurred while saving the appointment. Please try again.');
                });
        });
    }

    // Function to open edit modal with existing appointment data
    function editAppointment(appointmentId) {
        // Close details modal if open
        const detailsModal = document.getElementById('appointment-details-modal');
        if (detailsModal) {
            closeModal(detailsModal);
        }

        // Reset form
        const appointmentForm = document.getElementById('appointment-form');
        if (appointmentForm) {
            appointmentForm.reset();
        }

        // Set form title
        const modalTitle = document.getElementById('appointment-modal-title');
        if (modalTitle) {
            modalTitle.textContent = appointmentId ? 'Edit Appointment' : 'Schedule Appointment';
        }

        if (appointmentId) {
            // Show loading state
            const form = document.getElementById('appointment-form');
            if (form) {
                form.classList.add('loading');
            }

            // Fetch appointment data with correct path
            fetch(`../api/get_appointment_data.php?id=${appointmentId}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.text().then(text => {
                        try {
                            return JSON.parse(text);
                        } catch (err) {
                            console.error('Raw response:', text);
                            throw new Error('Invalid JSON response');
                        }
                    });
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }

                    const appointment = data.appointment;

                    // Populate form fields with null checks
                    const fields = {
                        'appointment_id': appointment.appointment_id,
                        'patient_id': appointment.patient_id,
                        'service_id': appointment.service_id,
                        'location_id': appointment.location_id,
                        'appointment_date': appointment.appointment_date,
                        'start_time': appointment.start_time,
                        'end_time': appointment.end_time,
                        'status': appointment.status,
                        'notes': appointment.notes || ''
                    };

                    Object.entries(fields).forEach(([id, value]) => {
                        const element = document.getElementById(id);
                        if (element) {
                            element.value = value;
                        }
                    });

                    // Open modal
                    const editModal = document.getElementById('appointment-edit-modal');
                    if (editModal) {
                        openModal(editModal);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading appointment: ' + error.message);
                })
                .finally(() => {
                    const form = document.getElementById('appointment-form');
                    if (form) {
                        form.classList.remove('loading');
                    }
                });
        } else {
            // Set default values for new appointment
            const appointmentIdField = document.getElementById('appointment_id');
            if (appointmentIdField) {
                appointmentIdField.value = '';
            }

            const dateField = document.getElementById('appointment_date');
            if (dateField) {
                dateField.value = new Date().toISOString().split('T')[0];
            }

            // Open modal
            const editModal = document.getElementById('appointment-edit-modal');
            if (editModal) {
                openModal(editModal);
            }
        }
    }

    // No need for event listeners since we're using onclick in HTML
    // Make editAppointment globally accessible
    window.editAppointment = editAppointment;

    // ------------------
    // Status Update Function
    // ------------------

    function updateAppointmentStatus(appointmentId, status) {
        if (!confirm(`Are you sure you want to mark this appointment as ${status}?`)) {
            return;
        }

        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('status', status);
        formData.append('action', 'update_status');

        fetch('../api/update_appointment_status.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    alert('Error: ' + data.error);
                } else {
                    // Close modal and reload page
                    closeModal(appointmentDetailsModal);
                    window.location.reload();
                }
            })
            .catch(error => {
                console.error('Error updating appointment status:', error);
                alert('An error occurred while updating the appointment status. Please try again.');
            });
    }

    // Make updateAppointmentStatus globally accessible
    window.updateAppointmentStatus = updateAppointmentStatus;

    // ------------------
    // Initialize on load
    // ------------------

    // Initialize default view
    if (document.querySelector('.view-btn[data-view="list"]').classList.contains('active')) {
        // List view is active by default, nothing to do
    } else if (document.querySelector('.view-btn[data-view="calendar"]').classList.contains('active')) {
        // Initialize calendar if calendar view is active
        initCalendar();
    }

    // Set min/max dates for date inputs
    const today = new Date().toISOString().split('T')[0];
    const dateStartInput = document.getElementById('date-start');
    const dateEndInput = document.getElementById('date-end');

    if (dateStartInput && dateEndInput) {
        // Ensure date end is at least equal to date start
        dateStartInput.addEventListener('change', function() {
            if (dateEndInput.value < this.value) {
                dateEndInput.value = this.value;
            }
        });

        dateEndInput.addEventListener('change', function() {
            if (dateStartInput.value > this.value) {
                dateStartInput.value = this.value;
            }
        });
    }

    // Add event listener for add appointment button
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function() {
            editAppointment(); // Call without ID to create new appointment
        });
    }

    // Add event listener for cancel button
    const cancelBtn = document.getElementById('cancel-btn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            closeModal(appointmentEditModal);
        });
    }
});