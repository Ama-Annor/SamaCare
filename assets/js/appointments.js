// Global variables
let bookingData = {
    service_id: null,
    doctor_id: null,
    appointment_date: null,
    time_slot: null,
    location_id: null,
    notes: null
};

let currentMonth = new Date().getMonth();
let currentYear = new Date().getFullYear();

// DOM ready event
document.addEventListener('DOMContentLoaded', function() {
    // Initialize action buttons
    initActionButtons();

    // Initialize appointment booking flow
    initBookingFlow();

    // Initialize status filter functionality
    initStatusFilter();

    // Initialize view toggle (list/calendar)
    initViewToggle();

    // Initialize calendar functionality
    initCalendar();
});

// Initialize action buttons
function initActionButtons() {
    // New appointment button
    const scheduleBtn = document.querySelector('.schedule-btn');
    if (scheduleBtn) {
        scheduleBtn.addEventListener('click', function() {
            // Reset booking form
            resetBookingForm();
            // Show booking modal
            document.getElementById('booking-modal').style.display = 'flex';
        });
    }

    // Close booking modal button
    const closeBookingBtn = document.getElementById('close-booking-modal');
    if (closeBookingBtn) {
        closeBookingBtn.addEventListener('click', function() {
            closeBookingModal();
        });
    }

    // Close confirmation modal button
    const closeConfirmationBtn = document.getElementById('close-confirmation');
    if (closeConfirmationBtn) {
        closeConfirmationBtn.addEventListener('click', function() {
            document.getElementById('confirmation-modal').style.display = 'none';
            window.location.reload(); // Reload page to show new appointment
        });
    }

    // Reschedule and cancel appointment buttons
    const actionBtns = document.querySelectorAll('.appointment-actions .action-btn');
    actionBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const appointmentItem = this.closest('.appointment-item');
            const appointmentId = appointmentItem.dataset.id;

            if (this.title === 'Reschedule') {
                // TODO: Implement reschedule functionality
                alert('Reschedule functionality will be implemented soon.');
            } else if (this.title === 'Cancel') {
                if (confirm('Are you sure you want to cancel this appointment?')) {
                    cancelAppointment(appointmentId);
                }
            } else if (this.title === 'View Details') {
                // TODO: Implement view details functionality
                alert('View details functionality will be implemented soon.');
            }
        });
    });
}

// Initialize booking flow
function initBookingFlow() {
    // Service selection change
    const serviceSelect = document.getElementById('service-type');
    if (serviceSelect) {
        serviceSelect.addEventListener('change', function() {
            bookingData.service_id = this.value;
            if (this.value) {
                // Get the selected specialty ID
                const specialtyId = this.options[this.selectedIndex].dataset.specialty;
                // Fetch doctors for this specialty
                fetchDoctorsBySpecialty(specialtyId);
            }
        });
    }

    // Doctor selection change
    const doctorSelect = document.getElementById('doctor');
    if (doctorSelect) {
        doctorSelect.addEventListener('change', function() {
            bookingData.doctor_id = this.value;
            if (this.value) {
                // Display doctor info
                const doctorName = this.options[this.selectedIndex].text;
                const doctorSpecialty = this.options[this.selectedIndex].dataset.specialty;
                const doctorRating = this.options[this.selectedIndex].dataset.rating;

                const doctorInfo = document.getElementById('doctor-info');
                doctorInfo.innerHTML = `
                    <div class="doctor-card">
                        <div class="doctor-avatar">
                            <span>${doctorName.split(' ').map(n => n[0]).join('')}</span>
                        </div>
                        <div class="doctor-details">
                            <h5 id="doctor-name">${doctorName}</h5>
                            <p id="doctor-specialty">${doctorSpecialty}</p>
                            <div class="doctor-rating">
                                ${generateStarRating(doctorRating)}
                                <span>${doctorRating}</span>
                            </div>
                        </div>
                    </div>
                `;
                doctorInfo.style.display = 'block';
            } else {
                document.getElementById('doctor-info').style.display = 'none';
            }
        });
    }

    // Date selection change
    const dateInput = document.getElementById('appointment-date');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            bookingData.appointment_date = this.value;
            if (this.value && bookingData.doctor_id) {
                // Fetch available time slots
                fetchAvailableTimeSlots(bookingData.doctor_id, this.value);
            }
        });

        // Set min date to today
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        dateInput.min = `${yyyy}-${mm}-${dd}`;
    }

    // Location selection change
    const locationSelect = document.getElementById('appointment-location');
    if (locationSelect) {
        locationSelect.addEventListener('change', function() {
            bookingData.location_id = this.value;
        });
    }

    // Notes input change
    const notesInput = document.getElementById('appointment-reason');
    if (notesInput) {
        notesInput.addEventListener('input', function() {
            bookingData.notes = this.value;
        });
    }

    // Booking step navigation
    const nextButtons = document.querySelectorAll('.next-step');
    nextButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStep = parseInt(this.dataset.step);

            if (validateStep(currentStep)) {
                // Hide current step
                document.getElementById(`step-${currentStep}`).style.display = 'none';
                // Show next step
                document.getElementById(`step-${currentStep + 1}`).style.display = 'block';

                // If it's the review step, populate the summary
                if (currentStep + 1 === 5) {
                    populateBookingSummary();
                }
            }
        });
    });

    const prevButtons = document.querySelectorAll('.prev-step');
    prevButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const currentStep = parseInt(this.dataset.step);

            // Hide current step
            document.getElementById(`step-${currentStep}`).style.display = 'none';
            // Show previous step
            document.getElementById(`step-${currentStep - 1}`).style.display = 'block';
        });
    });

    // Confirm booking button
    const confirmButton = document.getElementById('confirm-booking');
    if (confirmButton) {
        confirmButton.addEventListener('click', function() {
            // Submit booking data
            submitBooking();
        });
    }
}

// Initialize status filter
function initStatusFilter() {
    const filterLinks = document.querySelectorAll('.filter-dropdown-content a');
    const filterText = document.querySelector('.status-filter-btn span');

    filterLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const status = this.dataset.status;

            // Update filter button text
            filterText.textContent = `Status: ${status.charAt(0).toUpperCase() + status.slice(1)}`;

            // Filter appointments
            filterAppointmentsByStatus(status);
        });
    });
}

// Filter appointments by status
function filterAppointmentsByStatus(status) {
    const appointmentItems = document.querySelectorAll('.appointment-item');

    appointmentItems.forEach(item => {
        if (status === 'all' || item.dataset.status === status) {
            item.style.display = 'flex';
        } else {
            item.style.display = 'none';
        }
    });
}

// Initialize view toggle (list/calendar)
function initViewToggle() {
    const viewBtns = document.querySelectorAll('.view-btn');
    const viewSections = document.querySelectorAll('.view-section');

    viewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const view = this.dataset.view;

            // Activate clicked button
            viewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Show corresponding view section
            viewSections.forEach(section => {
                if (section.id === `${view}-view`) {
                    section.classList.add('active');
                } else {
                    section.classList.remove('active');
                }
            });

            // If calendar view is selected, refresh calendar
            if (view === 'calendar') {
                renderCalendar(currentMonth, currentYear);
                fetchCalendarAppointments(currentMonth + 1, currentYear);
            }
        });
    });
}

// Initialize calendar functionality
function initCalendar() {
    const prevMonthBtn = document.getElementById('prev-month');
    const nextMonthBtn = document.getElementById('next-month');
    const calendarMonthYear = document.getElementById('calendar-month-year');

    if (prevMonthBtn && nextMonthBtn && calendarMonthYear) {
        // Set initial month/year
        updateCalendarHeader();

        // Render initial calendar
        renderCalendar(currentMonth, currentYear);
        fetchCalendarAppointments(currentMonth + 1, currentYear);

        // Previous month button
        prevMonthBtn.addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 0) {
                currentMonth = 11;
                currentYear--;
            }
            updateCalendarHeader();
            renderCalendar(currentMonth, currentYear);
            fetchCalendarAppointments(currentMonth + 1, currentYear);
        });

        // Next month button
        nextMonthBtn.addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 11) {
                currentMonth = 0;
                currentYear++;
            }
            updateCalendarHeader();
            renderCalendar(currentMonth, currentYear);
            fetchCalendarAppointments(currentMonth + 1, currentYear);
        });
    }
}

// Update calendar header with current month and year
function updateCalendarHeader() {
    const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    document.getElementById('calendar-month-year').textContent = `${monthNames[currentMonth]} ${currentYear}`;
}

// Render calendar for a specific month and year
function renderCalendar(month, year) {
    const calendarDays = document.getElementById('calendar-days');
    if (!calendarDays) return;

    // Clear calendar
    calendarDays.innerHTML = '';

    // Get first day of month and number of days
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    // Get days from previous month to display
    const prevMonthDays = new Date(year, month, 0).getDate();

    // Add days from previous month
    for (let i = firstDay - 1; i >= 0; i--) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day other-month';
        dayDiv.textContent = prevMonthDays - i;
        calendarDays.appendChild(dayDiv);
    }

    // Add days from current month
    const today = new Date();
    const currentDate = today.getDate();
    const currentMonthReal = today.getMonth();
    const currentYearReal = today.getFullYear();

    for (let i = 1; i <= daysInMonth; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day';
        dayDiv.textContent = i;
        dayDiv.dataset.date = `${year}-${String(month + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

        // Add 'current-date' class if it's today
        if (i === currentDate && month === currentMonthReal && year === currentYearReal) {
            dayDiv.classList.add('current-date');
        }

        // Add click event
        dayDiv.addEventListener('click', function() {
            // Remove selected class from all days
            document.querySelectorAll('.calendar-day').forEach(day => {
                day.classList.remove('selected');
            });

            // Add selected class to clicked day
            this.classList.add('selected');

            // Show appointments for selected date
            showAppointmentsForDate(this.dataset.date);
        });

        calendarDays.appendChild(dayDiv);
    }

    // Calculate days to add from next month
    const totalDaysAdded = firstDay + daysInMonth;
    const remainingDays = 42 - totalDaysAdded; // 6 rows * 7 days = 42

    // Add days from next month
    for (let i = 1; i <= remainingDays; i++) {
        const dayDiv = document.createElement('div');
        dayDiv.className = 'calendar-day other-month';
        dayDiv.textContent = i;
        calendarDays.appendChild(dayDiv);
    }
}

// Fetch appointments for calendar view
function fetchCalendarAppointments(month, year) {
    fetch(`appointments.php?action=get_calendar&month=${month}&year=${year}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error('Error fetching calendar data:', data.error);
                return;
            }

            // Mark days with appointments
            data.forEach(appointment => {
                const day = parseInt(appointment.day);
                const dateString = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

                const dayElement = document.querySelector(`.calendar-day[data-date="${dateString}"]`);
                if (dayElement) {
                    dayElement.classList.add('has-appointment');
                    dayElement.dataset.appointmentId = appointment.appointment_id;
                }
            });
        })
        .catch(error => {
            console.error('Error:', error);
        });
}

// Show appointments for a selected date
function showAppointmentsForDate(dateString) {
    const appointmentsContainer = document.getElementById('selected-date-appointments');
    if (!appointmentsContainer) return;

    // Format date for display
    const date = new Date(dateString);
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    const formattedDate = date.toLocaleDateString('en-US', options);

    // Update container with selected date
    appointmentsContainer.innerHTML = `<h4>${formattedDate}</h4>`;

    // Find appointments for this date
    const upcomingAppointments = Array.from(document.querySelectorAll('#upcoming-appointments .appointment-item'));
    const pastAppointments = Array.from(document.querySelectorAll('#past-appointments .appointment-item'));
    const allAppointments = [...upcomingAppointments, ...pastAppointments];

    const dateAppointments = allAppointments.filter(appointment => {
        const day = appointment.querySelector('.appointment-date .day').textContent;
        const month = appointment.querySelector('.appointment-date .month').textContent;

        // Convert month abbreviation to month number
        const monthNames = {
            'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04', 'May': '05', 'Jun': '06',
            'Jul': '07', 'Aug': '08', 'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
        };

        const appointmentMonth = monthNames[month];
        const appointmentYear = new Date().getFullYear(); // Assuming current year

        // Extract year from dateString (format: YYYY-MM-DD)
        const selectedYear = dateString.split('-')[0];
        const selectedMonth = dateString.split('-')[1];
        const selectedDay = dateString.split('-')[2];

        return day === selectedDay && appointmentMonth === selectedMonth;
    });

    if (dateAppointments.length === 0) {
        appointmentsContainer.innerHTML += `<p>No appointments scheduled for this date.</p>`;
    } else {
        dateAppointments.forEach(appointment => {
            const serviceName = appointment.querySelector('.appointment-details h4').textContent;
            const time = appointment.querySelector('.appointment-details p:nth-child(2)').textContent.replace('', '').trim();
            const doctor = appointment.querySelector('.appointment-details p:nth-child(3)').textContent.replace('', '').trim();
            const location = appointment.querySelector('.appointment-details p:nth-child(4)').textContent.replace('', '').trim();

            appointmentsContainer.innerHTML += `
                <div class="day-appointment-item">
                    <div class="appointment-time">${time}</div>
                    <div class="appointment-info">
                        <h5>${serviceName}</h5>
                        <p>${doctor} - ${location}</p>
                    </div>
                </div>
            `;
        });
    }
}

// Fetch doctors by specialty
function fetchDoctorsBySpecialty(specialtyId) {
    if (!specialtyId) {
        console.error("No specialty ID provided");
        return;
    }

    // Clear current doctors
    const doctorSelect = document.getElementById('doctor');
    doctorSelect.innerHTML = '<option value="">Select a doctor</option>';
    document.getElementById('doctor-info').style.display = 'none';

    // Show loading indicator
    doctorSelect.disabled = true;

    // Make AJAX request
    fetch(`appointments.php?action=get_doctors&specialty_id=${specialtyId}`)
        .then(response => response.json())
        .then(doctors => {
            if (doctors.error) {
                console.error("Error fetching doctors:", doctors.error);
                return;
            }

            // Populate doctors dropdown
            doctors.forEach(doctor => {
                const option = document.createElement('option');
                option.value = doctor.doctor_id;
                option.textContent = `Dr. ${doctor.full_name}`;
                option.dataset.specialty = doctor.specialty;
                option.dataset.rating = doctor.rating || "N/A";
                doctorSelect.appendChild(option);
            });
        })
        .catch(error => console.error("Error:", error))
        .finally(() => {
            doctorSelect.disabled = false;
        });
}

// Fetch available time slots for a doctor on a specific date
function fetchAvailableTimeSlots(doctorId, date) {
    // Add loading state
    const timeSlotsContainer = document.getElementById('time-slots');
    timeSlotsContainer.innerHTML = '<p>Loading available slots...</p>';

    fetch(`appointments.php?action=get_slots&doctor_id=${doctorId}&date=${date}`)
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json();
        })
        .then(data => {
            timeSlotsContainer.innerHTML = '';

            if (data.error) {
                timeSlotsContainer.innerHTML = `<p class="error">${data.error}</p>`;
                return;
            }

            // Check if there are morning slots
            if (data[0] && data[0].times && data[0].times.length > 0) {
                timeSlotsContainer.innerHTML += '<h5>Morning</h5>';
                let morningRow = document.createElement('div');
                morningRow.className = 'time-slot-row';

                data[0].times.forEach((slot, index) => {
                    if (index > 0 && index % 3 === 0) {
                        timeSlotsContainer.appendChild(morningRow);
                        morningRow = document.createElement('div');
                        morningRow.className = 'time-slot-row';
                    }

                    morningRow.innerHTML += `
                        <div class="time-slot ${slot.booked ? 'booked' : ''}">
                            <input type="radio" name="time-slot" id="slot-${index}" 
                                value="${slot.start}-${slot.end}" ${slot.booked ? 'disabled' : ''}>
                            <label for="slot-${index}">${formatTime(slot.start)}</label>
                        </div>
                    `;
                });

                if (morningRow.children.length > 0) {
                    timeSlotsContainer.appendChild(morningRow);
                }
            }

            // Check if there are afternoon slots
            if (data[1] && data[1].times && data[1].times.length > 0) {
                timeSlotsContainer.innerHTML += '<h5>Afternoon</h5>';
                let afternoonRow = document.createElement('div');
                afternoonRow.className = 'time-slot-row';

                data[1].times.forEach((slot, index) => {
                    if (index > 0 && index % 3 === 0) {
                        timeSlotsContainer.appendChild(afternoonRow);
                        afternoonRow = document.createElement('div');
                        afternoonRow.className = 'time-slot-row';
                    }

                    afternoonRow.innerHTML += `
                        <div class="time-slot ${slot.booked ? 'booked' : ''}">
                            <input type="radio" name="time-slot" id="slot-${index + 100}" 
                                value="${slot.start}-${slot.end}" ${slot.booked ? 'disabled' : ''}>
                            <label for="slot-${index + 100}">${formatTime(slot.start)}</label>
                        </div>
                    `;
                });

                if (afternoonRow.children.length > 0) {
                    timeSlotsContainer.appendChild(afternoonRow);
                }
            }

            // Add event listeners to time slot radios
            const timeSlotRadios = document.querySelectorAll('input[name="time-slot"]');
            timeSlotRadios.forEach(radio => {
                radio.addEventListener('change', function() {
                    if (this.checked) {
                        bookingData.time_slot = this.value;
                    }
                });
            });

            // If no slots found at all
            if (timeSlotsContainer.children.length === 0) {
                timeSlotsContainer.innerHTML = '<p>No available time slots for this date</p>';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            timeSlotsContainer.innerHTML = `<p class="error">Error loading time slots: Check console for details</p>`;
        });
}

// Helper function to format time from 24h to 12h format
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const hour = parseInt(hours, 10);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const displayHour = hour % 12 || 12;
    return `${displayHour}:${minutes} ${ampm}`;
}


// Submit booking
// Submit booking
function submitBooking() {
    // Show loading state
    const confirmButton = document.getElementById('confirm-booking');
    const originalText = confirmButton.textContent;
    confirmButton.textContent = 'Processing...';
    confirmButton.disabled = true;

    // Ensure date is in YYYY-MM-DD format
    let appointmentDate = bookingData.date;
    if (appointmentDate instanceof Date) {
        // Format the date as YYYY-MM-DD
        appointmentDate = appointmentDate.toISOString().split('T')[0];
    }

    // Create form data from booking data
    const formData = new FormData();
    formData.append('doctor_id', bookingData.doctor_id);
    formData.append('service_id', bookingData.service_id);
    formData.append('appointment_date', bookingData.appointment_date);
    formData.append('time_slot', bookingData.time_slot);
    formData.append('notes', bookingData.notes);
    formData.append('location_id', bookingData.location_id);

    // For debugging
    console.log('Submitting appointment date:', appointmentDate);

    // Submit booking via fetch API
    fetch('appointments.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            // Reset button state
            confirmButton.textContent = originalText;
            confirmButton.disabled = false;

            if (data.success) {
                // Show success message
                showNotification('Success', 'Appointment booked successfully', 'success');

                // Close the modal and refresh the page after a short delay
                setTimeout(() => {
                    closeBookingModal();
                    window.location.reload();
                }, 2000);
            } else {
                // Show error message
                showNotification('Error', data.error || 'Failed to book appointment', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            // Reset button state
            confirmButton.textContent = originalText;
            confirmButton.disabled = false;
            // Show error message
            showNotification('Error', 'Failed to book appointment: ' + error.message, 'error');
        });
}

// Close booking modal function
function closeBookingModal() {
    document.getElementById('booking-modal').style.display = 'none';
}

function showNotification(title, message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.innerHTML = `
        <div class="notification-header">
            <h4>${title}</h4>
            <button class="close-notification"><i class='bx bx-x'></i></button>
        </div>
        <p>${message}</p>
    `;

    // Add to the DOM
    document.body.appendChild(notification);

    // Show with animation
    setTimeout(() => {
        notification.classList.add('show');
    }, 10);

    // Add event listener to close button
    notification.querySelector('.close-notification').addEventListener('click', () => {
        closeNotification(notification);
    });

    // Auto-close after 5 seconds
    setTimeout(() => {
        closeNotification(notification);
    }, 5000);
}

function closeNotification(notification) {
    notification.classList.remove('show');
    setTimeout(() => {
        notification.remove();
    }, 300); // match the CSS transition time
}

// Cancel appointment
function cancelAppointment(appointmentId) {
    // Send request
    fetch('appointments.php?action=cancel_appointment&appointment_id=' + appointmentId)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to update appointment list
                window.location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
        });
}

// Populate booking summary
function populateBookingSummary() {
    document.getElementById('summary-service').textContent = document.querySelector('#service-type option:checked').text;
    document.getElementById('summary-doctor').textContent = document.querySelector('#doctor option:checked').text;
    document.getElementById('summary-date').textContent = formatDate(bookingData.appointment_date);

    // Parse time slot
    const timeSlotParts = bookingData.time_slot.split('-');
    document.getElementById('summary-time').textContent = formatTime(timeSlotParts[0]);

    document.getElementById('summary-location').textContent = document.querySelector('#appointment-location option:checked').text;
    document.getElementById('summary-reason').textContent = bookingData.notes || 'No reason provided';
}

// Validate step
function validateStep(step) {
    switch (step) {
        case 1:
            if (!bookingData.service_id) {
                alert('Please select a service.');
                return false;
            }
            return true;
        case 2:
            if (!bookingData.doctor_id) {
                alert('Please select a doctor.');
                return false;
            }
            return true;
        case 3:
            if (!bookingData.appointment_date) {
                alert('Please select a date.');
                return false;
            }
            if (!bookingData.time_slot) {
                alert('Please select a time slot.');
                return false;
            }
            return true;
        case 4:
            if (!bookingData.location_id) {
                alert('Please select a location.');
                return false;
            }
            return true;
        default:
            return true;
    }
}

// Reset booking form
function resetBookingForm() {
    // Reset booking data
    bookingData = {
        service_id: null,
        doctor_id: null,
        appointment_date: null,
        time_slot: null,
        location_id: null,
        notes: null
    };

    // Reset form fields
    document.getElementById('service-type').value = '';
    document.getElementById('doctor').innerHTML = '<option value="">Select a doctor</option>';
    document.getElementById('doctor-info').style.display = 'none';
    document.getElementById('appointment-date').value = '';
    document.getElementById('time-slots').innerHTML = '<p>Please select a date to view available time slots.</p>';
    document.getElementById('appointment-reason').value = '';

    // Reset to first step
    document.querySelectorAll('.booking-step').forEach((step, index) => {
        step.style.display = index === 0 ? 'block' : 'none';
    });
}

// Helper function: Generate star rating display
function generateStarRating(rating) {
    const fullStars = Math.floor(rating);
    const halfStar = rating % 1 >= 0.5;
    const emptyStars = 5 - fullStars - (halfStar ? 1 : 0);

    let starsHTML = '';

    // Add full stars
    for (let i = 0; i < fullStars; i++) {
        starsHTML += '<i class="bx bxs-star"></i>';
    }

    // Add half star if needed
    if (halfStar) {
        starsHTML += '<i class="bx bxs-star-half"></i>';
    }

    // Add empty stars
    for (let i = 0; i < emptyStars; i++) {
        starsHTML += '<i class="bx bx-star"></i>';
    }

    return starsHTML;
}

// Helper function: Format time (24h to 12h)
function formatTime(time) {
    const [hours, minutes] = time.split(':');
    const period = hours >= 12 ? 'PM' : 'AM';
    const hours12 = hours % 12 || 12;
    return `${hours12}:${minutes} ${period}`;
}

// Helper function: Format date (YYYY-MM-DD to Month DD, YYYY)
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('en-US', options);
}