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
    
    // Calendar elements
    const calendarContainer = document.getElementById('calendar-container');
    const currentMonthYearEl = document.getElementById('current-month-year');
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const calendarViewBtns = document.querySelectorAll('.calendar-view-btn');
    const dayAppointmentsList = document.getElementById('day-appointments-list');
    const selectedDateHeading = document.getElementById('selected-date-heading');
    
    // Current view state
    let currentDate = new Date();
    let currentView = 'month'; // month, week, day
    let selectedDate = new Date();
    let activeAppointmentId = null;
    
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
        
        // Keep the current page if it exists
        const currentPage = new URLSearchParams(window.location.search).get('page');
        if (currentPage) {
            url.searchParams.set('page', currentPage);
        }
        
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
    
    function viewAppointmentDetails(appointmentId) {
        // Show loading spinner
        document.getElementById('appointment-details-content').innerHTML = '<div class="loading-spinner">Loading...</div>';
        
        // Open modal
        openModal(appointmentDetailsModal);
        
        // Fetch appointment details via AJAX
        fetch(`../api/get_appointment.php?id=${appointmentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('appointment-details-content').innerHTML = `<div class="error-message">${data.error}</div>`;
                    return;
                }
                
                // Format the appointment details
                const appointment = data.appointment;
                const start = new Date(`${appointment.appointment_date}T${appointment.start_time}`);
                const end = new Date(`${appointment.appointment_date}T${appointment.end_time}`);
                const formattedStart = start.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                const formattedEnd = end.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                const formattedDate = start.toLocaleDateString([], {weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'});
                
                let statusClass = '';
                switch(appointment.status) {
                    case 'confirmed': statusClass = 'confirmed'; break;
                    case 'pending': statusClass = 'pending'; break;
                    case 'completed': statusClass = 'completed'; break;
                    case 'cancelled': statusClass = 'cancelled'; break;
                }
                
                // Create HTML for appointment details
                const detailsHTML = `
                    <div class="appointment-detail-card">
                        <div class="appointment-header">
                            <div class="appointment-date-time">
                                <h3>${formattedDate}</h3>
                                <p>${formattedStart} - ${formattedEnd}</p>
                            </div>
                            <span class="status-badge ${statusClass}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span>
                        </div>
                        
                        <div class="appointment-section">
                            <h4><i class='bx bx-user-circle'></i> Patient Information</h4>
                            <div class="patient-details">
                                <div class="patient-name">${appointment.patient_first_name} ${appointment.patient_last_name}</div>
                                <div class="patient-info">
                                    <p><i class='bx bx-phone'></i> ${appointment.patient_phone || 'No phone number'}</p>
                                    <p><i class='bx bx-envelope'></i> ${appointment.patient_email || 'No email'}</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="appointment-section">
                            <h4><i class='bx bx-info-circle'></i> Appointment Details</h4>
                            <div class="appointment-info">
                                <div class="info-item">
                                    <span class="label">Service:</span>
                                    <span class="value">${appointment.service_name}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Location:</span>
                                    <span class="value">${appointment.location_name}</span>
                                </div>
                                <div class="info-item">
                                    <span class="label">Status:</span>
                                    <span class="value status-text ${statusClass}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span>
                                </div>
                            </div>
                        </div>
                        
                        ${appointment.notes ? `
                        <div class="appointment-section">
                            <h4><i class='bx bx-notepad'></i> Notes</h4>
                            <div class="appointment-notes">
                                <p>${appointment.notes}</p>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="appointment-actions">
                            <button class="btn secondary-btn edit-btn" onclick="editAppointment(${appointment.appointment_id})">
                                <i class='bx bx-edit'></i> Edit
                            </button>
                            ${appointment.status !== 'completed' ? `
                            <button class="btn primary-btn complete-btn" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'completed')">
                                <i class='bx bx-check'></i> Mark as Completed
                            </button>
                            ` : ''}
                            ${appointment.status !== 'cancelled' ? `
                            <button class="btn danger-btn cancel-btn" onclick="updateAppointmentStatus(${appointment.appointment_id}, 'cancelled')">
                                <i class='bx bx-x'></i> Cancel Appointment
                            </button>
                            ` : ''}
                        </div>
                    </div>
                `;
                
                document.getElementById('appointment-details-content').innerHTML = detailsHTML;
            })
            .catch(error => {
                console.error('Error fetching appointment details:', error);
                document.getElementById('appointment-details-content').innerHTML = '<div class="error-message">Error loading appointment details. Please try again.</div>';
            });
    }
    
    // Attach event listeners to view buttons
    document.querySelectorAll('.view-appointment').forEach(btn => {
        btn.addEventListener('click', function() {
            const appointmentId = this.dataset.id;
            viewAppointmentDetails(appointmentId);
        });
    });
    
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
        closeModal(appointmentDetailsModal);
        
        // Reset form
        appointmentForm.reset();
        
        // Set form title
        document.getElementById('appointment-modal-title').textContent = appointmentId ? 'Edit Appointment' : 'Schedule Appointment';
        
        if (appointmentId) {
            // Fetch appointment data
            fetch(`../api/get_appointment.php?id=${appointmentId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Error: ' + data.error);
                        return;
                    }
                    
                    const appointment = data.appointment;
                    
                    // Populate form fields
                    document.getElementById('appointment_id').value = appointment.appointment_id;
                    document.getElementById('patient_id').value = appointment.patient_id;
                    document.getElementById('service_id').value = appointment.service_id;
                    document.getElementById('location_id').value = appointment.location_id;
                    document.getElementById('appointment_date').value = appointment.appointment_date;
                    document.getElementById('start_time').value = appointment.start_time;
                    document.getElementById('end_time').value = appointment.end_time;
                    document.getElementById('status').value = appointment.status;
                    document.getElementById('notes').value = appointment.notes || '';
                    
                    // Open modal
                    openModal(appointmentEditModal);
                })
                .catch(error => {
                    console.error('Error fetching appointment data:', error);
                    alert('An error occurred while loading appointment data. Please try again.');
                });
        } else {
            // Clear appointment ID for new appointment
            document.getElementById('appointment_id').value = '';
            
            // Set default date to today
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('appointment_date').value = today;
            
            // Open modal
            openModal(appointmentEditModal);
        }
    }
    
    // Attach event listeners to edit buttons
    document.querySelectorAll('.edit-appointment').forEach(btn => {
        btn.addEventListener('click', function() {
            const appointmentId = this.dataset.id;
            editAppointment(appointmentId);
        });
    });
    
    // Add appointment button
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', () => {
            editAppointment(null); // null for new appointment
        });
    }
    
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
    // Context Menu Functions
    // ------------------
    
    document.querySelectorAll('.more-options').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.stopPropagation();
            
            // Get appointment ID
            const appointmentId = this.dataset.id;
            activeAppointmentId = appointmentId;
            
            // Get appointment status from the row
            const row = this.closest('tr');
            const statusEl = row.querySelector('.status-badge');
            const status = statusEl ? statusEl.textContent.toLowerCase() : '';
            
            // Show/hide menu items based on status
            const completeOption = actionsMenu.querySelector('[data-action="complete"]');
            const cancelOption = actionsMenu.querySelector('[data-action="cancel"]');
            
            if (completeOption) {
                completeOption.style.display = (status === 'completed' || status === 'cancelled') ? 'none' : '';
            }
            
            if (cancelOption) {
                cancelOption.style.display = (status === 'cancelled' || status === 'completed') ? 'none' : '';
            }
            
            // Position and show menu
            const rect = this.getBoundingClientRect();
            actionsMenu.style.top = `${rect.bottom + window.scrollY}px`;
            actionsMenu.style.left = `${rect.left + window.scrollX - actionsMenu.offsetWidth + rect.width}px`;
            actionsMenu.classList.add('active');
            
            // Handle clicking outside to close menu
            const closeContextMenu = function(event) {
                if (!actionsMenu.contains(event.target) && event.target !== btn) {
                    actionsMenu.classList.remove('active');
                    document.removeEventListener('click', closeContextMenu);
                }
            };
            
            setTimeout(() => {
                document.addEventListener('click', closeContextMenu);
            }, 0);
        });
    });
    
    // Handle context menu actions
    actionsMenu.querySelectorAll('li').forEach(item => {
        item.addEventListener('click', function() {
            const action = this.dataset.action;
            
            if (!activeAppointmentId) return;
            
            actionsMenu.classList.remove('active');
            
            switch(action) {
                case 'view':
                    viewAppointmentDetails(activeAppointmentId);
                    break;
                case 'edit':
                    editAppointment(activeAppointmentId);
                    break;
                case 'complete':
                    updateAppointmentStatus(activeAppointmentId, 'completed');
                    break;
                case 'cancel':
                    updateAppointmentStatus(activeAppointmentId, 'cancelled');
                    break;
                case 'reschedule':
                    // First get the appointment data, then open edit modal
                    editAppointment(activeAppointmentId);
                    break;
                case 'reminder':
                    sendAppointmentReminder(activeAppointmentId);
                    break;
            }
        });
    });
    
    // Function to send reminder
    function sendAppointmentReminder(appointmentId) {
        const formData = new FormData();
        formData.append('appointment_id', appointmentId);
        formData.append('action', 'send_reminder');
        
        fetch('../api/send_appointment_reminder.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error: ' + data.error);
            } else {
                alert('Reminder sent successfully!');
            }
        })
        .catch(error => {
            console.error('Error sending reminder:', error);
            alert('An error occurred while sending the reminder. Please try again.');
        });
    }
    
    // ------------------
    // Calendar Functions
    // ------------------
    
    function initCalendar() {
        updateCalendarHeader();
        renderCalendar();
        
        // Load appointments for the selected date
        loadDailyAppointments(formatDate(selectedDate));
    }
    
    function updateCalendarHeader() {
        const monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"];
        
        if (currentMonthYearEl) {
            currentMonthYearEl.textContent = `${monthNames[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
        }
    }
    
    function renderCalendar() {
        if (!calendarContainer) return;
        
        // Clear calendar
        calendarContainer.innerHTML = '';
        
        if (currentView === 'month') {
            renderMonthView();
        } else if (currentView === 'week') {
            renderWeekView();
        } else if (currentView === 'day') {
            renderDayView();
        }
    }
    
    function renderMonthView() {
        // Get first day of the month
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        
        // Create day names header
        const dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
        const daysHeader = document.createElement('div');
        daysHeader.className = 'calendar-days-header';
        
        dayNames.forEach(day => {
            const dayEl = document.createElement('div');
            dayEl.className = 'day-name';
            dayEl.textContent = day;
            daysHeader.appendChild(dayEl);
        });
        
        calendarContainer.appendChild(daysHeader);
        
        // Create calendar grid
        const calendarGrid = document.createElement('div');
        calendarGrid.className = 'calendar-grid';
        
        // Add empty cells for days before the first of the month
        let dayOfWeek = firstDay.getDay();
        for (let i = 0; i < dayOfWeek; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'calendar-day empty';
            calendarGrid.appendChild(emptyCell);
        }
        
        // Add cells for each day of the month
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 1; i <= lastDay.getDate(); i++) {
            const dayDate = new Date(currentDate.getFullYear(), currentDate.getMonth(), i);
            const dayCell = document.createElement('div');
            dayCell.className = 'calendar-day';
            dayCell.dataset.date = formatDate(dayDate);
            
            // Mark today
            if (dayDate.getTime() === today.getTime()) {
                dayCell.classList.add('today');
            }
            
            // Mark selected day
            if (selectedDate && dayDate.getTime() === selectedDate.getTime()) {
                dayCell.classList.add('selected');
            }
            
            // Add day number
            const dayNumber = document.createElement('div');
            dayNumber.className = 'day-number';
            dayNumber.textContent = i;
            dayCell.appendChild(dayNumber);
            
            // Add appointment indicators container
            const appointmentIndicators = document.createElement('div');
            appointmentIndicators.className = 'appointment-indicators';
            dayCell.appendChild(appointmentIndicators);
            
            // Add click event to select date
            dayCell.addEventListener('click', () => {
                // Remove selected class from previous selection
                document.querySelectorAll('.calendar-day.selected').forEach(el => {
                    el.classList.remove('selected');
                });
                
                // Add selected class to clicked day
                dayCell.classList.add('selected');
                
                // Update selected date
                selectedDate = dayDate;
                
                // Load appointments for selected date
                loadDailyAppointments(formatDate(selectedDate));
            });
            
            calendarGrid.appendChild(dayCell);
        }
        
        calendarContainer.appendChild(calendarGrid);
        
        // Load appointment indicators for the current month
        loadMonthAppointments();
    }
    
    function renderWeekView() {
        // Determine the week start (Sunday) and end (Saturday)
        const current = new Date(currentDate);
        const dayOfWeek = current.getDay();
        const weekStart = new Date(current);
        weekStart.setDate(current.getDate() - dayOfWeek);
        
        const weekContainer = document.createElement('div');
        weekContainer.className = 'week-view';
        
        // Create day headers
        const dayNames = ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"];
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let i = 0; i < 7; i++) {
            const dayDate = new Date(weekStart);
            dayDate.setDate(weekStart.getDate() + i);
            
            const dayCol = document.createElement('div');
            dayCol.className = 'week-day-column';
            dayCol.dataset.date = formatDate(dayDate);
            
            // Day header
            const dayHeader = document.createElement('div');
            dayHeader.className = 'week-day-header';
            
            // Mark today
            if (dayDate.getTime() === today.getTime()) {
                dayHeader.classList.add('today');
            }
            
            // Mark selected day
            if (selectedDate && dayDate.getTime() === selectedDate.getTime()) {
                dayHeader.classList.add('selected');
            }
            
            const dayNameEl = document.createElement('div');
            dayNameEl.className = 'day-name';
            dayNameEl.textContent = dayNames[i];
            
            const dayNumberEl = document.createElement('div');
            dayNumberEl.className = 'day-number';
            dayNumberEl.textContent = dayDate.getDate();
            
            dayHeader.appendChild(dayNameEl);
            dayHeader.appendChild(dayNumberEl);
            dayCol.appendChild(dayHeader);
            
            // Day content for appointments
            const dayContent = document.createElement('div');
            dayContent.className = 'week-day-content';
            dayContent.id = `day-content-${formatDate(dayDate)}`;
            dayCol.appendChild(dayContent);
            
            // Add click event to select date
            dayHeader.addEventListener('click', () => {
                // Remove selected class from previous selection
                document.querySelectorAll('.week-day-header.selected').forEach(el => {
                    el.classList.remove('selected');
                });
                
                // Add selected class to clicked day
                dayHeader.classList.add('selected');
                
                // Update selected date
                selectedDate = dayDate;
                
                // Load appointments for selected date
                loadDailyAppointments(formatDate(selectedDate));
            });
            
            weekContainer.appendChild(dayCol);
        }
        
        calendarContainer.appendChild(weekContainer);
        
        // Load appointments for the week
        loadWeekAppointments(formatDate(weekStart), formatDate(new Date(weekStart.getTime() + 6 * 24 * 60 * 60 * 1000)));
    }
    
    function renderDayView() {
        const dayContainer = document.createElement('div');
        dayContainer.className = 'day-view';
        
        // Create time slots from 8 AM to 5 PM
        const startHour = 8;
        const endHour = 17;
        
        for (let hour = startHour; hour <= endHour; hour++) {
            const timeSlot = document.createElement('div');
            timeSlot.className = 'day-time-slot';
            
            const timeLabel = document.createElement('div');
            timeLabel.className = 'time-label';
            timeLabel.textContent = hour > 12 ? `${hour - 12}:00 PM` : hour === 12 ? '12:00 PM' : `${hour}:00 AM`;
            timeSlot.appendChild(timeLabel);
            
            const slotContent = document.createElement('div');
            slotContent.className = 'slot-content';
            slotContent.dataset.hour = hour;
            timeSlot.appendChild(slotContent);
            
            dayContainer.appendChild(timeSlot);
        }
        
        calendarContainer.appendChild(dayContainer);
        
        // Load appointments for the day
        loadDayViewAppointments(formatDate(selectedDate));
    }
    
    function loadMonthAppointments() {
        // Get first and last day of the month
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const lastDay = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
        
        fetch(`../api/get_calendar_appointments.php?start_date=${formatDate(firstDay)}&end_date=${formatDate(lastDay)}`)
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error('Error loading appointments:', data.error);
                    return;
                }
                
                // Group appointments by date
                const appointmentsByDate = {};
                
                data.appointments.forEach(appointment => {
                    const date = appointment.appointment_date;
                    if (!appointmentsByDate[date]) {
                        appointmentsByDate[date] = [];
                    }
                    appointmentsByDate[date].push(appointment);
                });
                
                // Add indicators to calendar cells
                Object.keys(appointmentsByDate).forEach(date => {
                    const dayCell = document.querySelector(`.calendar-day[data-date="${date}"]`);
                    if (dayCell) {
                        const indicators = dayCell.querySelector('.appointment-indicators');
                        const count = appointmentsByDate[date].length;
                        
                        // Add count indicator
                        const countIndicator = document.createElement('div');
                        countIndicator.className = 'appointment-count';
                        countIndicator.textContent = count > 0 ? `${count} appts` : 'No appts';
                        indicators.appendChild(countIndicator);
                        
                       // Add status indicators (max 3)
                       const statuses = {};
                       appointmentsByDate[date].forEach(appointment => {
                           if (!statuses[appointment.status]) {
                               statuses[appointment.status] = 0;
                           }
                           statuses[appointment.status]++;
                       });
                       
                       const statusContainer = document.createElement('div');
                       statusContainer.className = 'status-indicators';
                       
                       Object.keys(statuses).slice(0, 3).forEach(status => {
                           const statusDot = document.createElement('div');
                           statusDot.className = `status-dot ${status}`;
                           statusDot.title = `${statuses[status]} ${status}`;
                           statusContainer.appendChild(statusDot);
                       });
                       
                       indicators.appendChild(statusContainer);
                   }
               });
           })
           .catch(error => {
               console.error('Error loading month appointments:', error);
           });
   }
   
   function loadWeekAppointments(startDate, endDate) {
       fetch(`../api/get_calendar_appointments.php?start_date=${startDate}&end_date=${endDate}`)
           .then(response => response.json())
           .then(data => {
               if (data.error) {
                   console.error('Error loading appointments:', data.error);
                   return;
               }
               
               // Group appointments by date
               const appointmentsByDate = {};
               
               data.appointments.forEach(appointment => {
                   const date = appointment.appointment_date;
                   if (!appointmentsByDate[date]) {
                       appointmentsByDate[date] = [];
                   }
                   appointmentsByDate[date].push(appointment);
               });
               
               // Add appointment blocks to week columns
               Object.keys(appointmentsByDate).forEach(date => {
                   const dayColumn = document.querySelector(`.week-day-column[data-date="${date}"] .week-day-content`);
                   if (dayColumn) {
                       appointmentsByDate[date].forEach(appointment => {
                           // Create appointment block
                           const apptBlock = document.createElement('div');
                           apptBlock.className = `appointment-block ${appointment.status}`;
                           apptBlock.dataset.id = appointment.appointment_id;
                           
                           const startTime = appointment.start_time.substring(0, 5);
                           const endTime = appointment.end_time.substring(0, 5);
                           
                           apptBlock.innerHTML = `
                               <div class="appointment-time">${startTime} - ${endTime}</div>
                               <div class="appointment-title">${appointment.service_name}</div>
                               <div class="appointment-patient">${appointment.patient_first_name} ${appointment.patient_last_name}</div>
                           `;
                           
                           // Add click handler to view details
                           apptBlock.addEventListener('click', () => {
                               viewAppointmentDetails(appointment.appointment_id);
                           });
                           
                           dayColumn.appendChild(apptBlock);
                       });
                   }
               });
           })
           .catch(error => {
               console.error('Error loading week appointments:', error);
               
           });
   }
   
   function loadDayViewAppointments(date) {
       fetch(`../api/get_calendar_appointments.php?start_date=${date}&end_date=${date}`)
       
           .then(response => response.json())
           .then(data => {
               if (data.error) {
                   console.error('Error loading appointments:', data.error);
                   return;
               }
            
               
               // Clear any existing appointments
               document.querySelectorAll('.appointment-block').forEach(el => el.remove());
               
               // Add appointment blocks to time slots
               data.appointments.forEach(appointment => {
                   // Parse times
                   const startHour = parseInt(appointment.start_time.split(':')[0]);
                   const endHour = parseInt(appointment.end_time.split(':')[0]);
                   const startMinute = parseInt(appointment.start_time.split(':')[1]);
                   const endMinute = parseInt(appointment.end_time.split(':')[1]);
                   
                   // Find appropriate time slot
                   const timeSlot = document.querySelector(`.slot-content[data-hour="${startHour}"]`);
                   if (timeSlot) {
                       // Create appointment block
                       const apptBlock = document.createElement('div');
                       apptBlock.className = `appointment-block ${appointment.status}`;
                       apptBlock.dataset.id = appointment.appointment_id;
                       
                       // Calculate duration in minutes
                       const durationMinutes = (endHour * 60 + endMinute) - (startHour * 60 + startMinute);
                       // Set height based on duration (assume 1 hour = 60px)
                       apptBlock.style.height = `${durationMinutes}px`;
                       // Set top offset based on start minute (assume 1 minute = 1px)
                       apptBlock.style.top = `${startMinute}px`;
                       
                       const startTimeStr = appointment.start_time.substring(0, 5);
                       const endTimeStr = appointment.end_time.substring(0, 5);
                       
                       apptBlock.innerHTML = `
                           <div class="appointment-time">${startTimeStr} - ${endTimeStr}</div>
                           <div class="appointment-title">${appointment.service_name}</div>
                           <div class="appointment-patient">${appointment.patient_first_name} ${appointment.patient_last_name}</div>
                       `;
                       
                       // Add click handler to view details
                       apptBlock.addEventListener('click', () => {
                           viewAppointmentDetails(appointment.appointment_id);
                       });
                       
                       timeSlot.appendChild(apptBlock);
                   }
               });
           })
           .catch(error => {
               console.error('Error loading day appointments:', error);
           });
   }
   
   function loadDailyAppointments(date) {
       if (!dayAppointmentsList || !selectedDateHeading) return;
       
       // Update heading
       const formattedDate = new Date(date).toLocaleDateString(undefined, { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
       selectedDateHeading.textContent = `Appointments for ${formattedDate}`;
       
       // Show loading indicator
       dayAppointmentsList.innerHTML = '<div class="loading-spinner">Loading appointments...</div>';
       
       
       // Fetch appointments for the selected date
       fetch(`../api/get_calendar_appointments.php?start_date=${date}&end_date=${date}`)
           .then(response => response.json())
           .then(data => {
               if (data.error) {
                   dayAppointmentsList.innerHTML = `<div class="error-message">${data.error}</div>`;
                   return;
               }
               
               const appointments = data.appointments;
               
               if (appointments.length === 0) {
                   dayAppointmentsList.innerHTML = '<div class="no-data">No appointments scheduled for this day.</div>';
                   return;
               }
               
               // Sort appointments by time
               appointments.sort((a, b) => {
                   return a.start_time.localeCompare(b.start_time);
               });
               
               // Generate HTML for each appointment
               let html = '';
               appointments.forEach(appointment => {
                   const startTime = appointment.start_time.substring(0, 5);
                   const endTime = appointment.end_time.substring(0, 5);
                   
                   html += `
                       <div class="day-appointment-item ${appointment.status}" data-id="${appointment.appointment_id}">
                           <div class="appointment-time">${startTime} - ${endTime}</div>
                           <div class="appointment-details">
                               <div class="appointment-title">${appointment.service_name}</div>
                               <div class="appointment-patient">
                                   <div class="user-avatar small">${getInitials(appointment.patient_first_name, appointment.patient_last_name)}</div>
                                   ${appointment.patient_first_name} ${appointment.patient_last_name}
                               </div>
                           </div>
                           <div class="appointment-status">
                               <span class="status-badge ${appointment.status}">${appointment.status.charAt(0).toUpperCase() + appointment.status.slice(1)}</span>
                           </div>
                           <div class="appointment-actions">
                               <button class="btn icon-btn sm" title="View Details" onclick="viewAppointmentDetails(${appointment.appointment_id})">
                                   <i class='bx bx-show'></i>
                               </button>
                               <button class="btn icon-btn sm" title="Edit" onclick="editAppointment(${appointment.appointment_id})">
                                   <i class='bx bx-edit'></i>
                               </button>
                           </div>
                       </div>
                   `;
               });
               
               dayAppointmentsList.innerHTML = html;
           })
           
           .catch(error => {
               console.error('Error loading daily appointments:', error);
               dayAppointmentsList.innerHTML = '<div class="error-message">Error loading appointments. Please try again🤣.</div>';
            
           });
   }
   
   // Helper function to get initials
   function getInitials(firstName, lastName) {
       return (firstName.charAt(0) + lastName.charAt(0)).toUpperCase();
   }
   
   // Helper function to format date as YYYY-MM-DD
   function formatDate(date) {
       const d = new Date(date);
       let month = '' + (d.getMonth() + 1);
       let day = '' + d.getDate();
       const year = d.getFullYear();
       
       if (month.length < 2) month = '0' + month;
       if (day.length < 2) day = '0' + day;
       
       return [year, month, day].join('-');
   }
   
   // Calendar navigation buttons
   if (prevMonthBtn) {
       prevMonthBtn.addEventListener('click', () => {
           if (currentView === 'month') {
               currentDate.setMonth(currentDate.getMonth() - 1);
           } else if (currentView === 'week') {
               currentDate.setDate(currentDate.getDate() - 7);
           } else if (currentView === 'day') {
               currentDate.setDate(currentDate.getDate() - 1);
               selectedDate = new Date(currentDate);
           }
           
           updateCalendarHeader();
           renderCalendar();
       });
   }
   
   if (nextMonthBtn) {
       nextMonthBtn.addEventListener('click', () => {
           if (currentView === 'month') {
               currentDate.setMonth(currentDate.getMonth() + 1);
           } else if (currentView === 'week') {
               currentDate.setDate(currentDate.getDate() + 7);
           } else if (currentView === 'day') {
               currentDate.setDate(currentDate.getDate() + 1);
               selectedDate = new Date(currentDate);
           }
           
           updateCalendarHeader();
           renderCalendar();
       });
   }
   
   // Calendar view toggles
   calendarViewBtns.forEach(btn => {
       btn.addEventListener('click', () => {
           // Remove active class from all buttons
           calendarViewBtns.forEach(b => b.classList.remove('active'));
           // Add active class to clicked button
           btn.classList.add('active');
           
           // Update current view
           currentView = btn.dataset.view;
           
           // If switching to day view, set currentDate to selectedDate
           if (currentView === 'day') {
               currentDate = new Date(selectedDate);
           }
           
           // Re-render calendar
           updateCalendarHeader();
           renderCalendar();
       });
   });
   
   // ------------------
   // Export Function
   // ------------------
   
   function exportAppointments() {
       // Get current filter parameters
       const status = document.getElementById('status-filter').value;
       const service = document.getElementById('service-filter').value;
       const dateStart = document.getElementById('date-start').value;
       const dateEnd = document.getElementById('date-end').value;
       
       // Create URL with parameters
       let url = `../api/export_appointments.php?status=${status}&service=${service}&date_start=${dateStart}&date_end=${dateEnd}`;
       
       // Open download in new tab/window
       window.open(url, '_blank');
   }
   
   // Make exportAppointments globally accessible
   window.exportAppointments = exportAppointments;
   
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
});