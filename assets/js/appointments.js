document.addEventListener('DOMContentLoaded', function() {
    //View toggle between list and calendar
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
    
    //Calendar view options
    const calendarViewBtns = document.querySelectorAll('.calendar-view-btn');
    const calendarContainer = document.querySelector('.calendar-container');
    
    calendarViewBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            //update active button
            calendarViewBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            //get the selected view type
            const viewType = this.getAttribute('data-view');
            
            //update calendar display based on view type
            updateCalendarView(viewType);
        });
    });
    
    //function to update calendar display based on view type
    function updateCalendarView(viewType) {
        //clear current calendar content
        calendarContainer.innerHTML = '';
        
        if (viewType === 'month') {
            //month view (default) - recreate the original month view
            const monthView = `
                <div class="calendar-weekdays">
                    <div>Sun</div>
                    <div>Mon</div>
                    <div>Tue</div>
                    <div>Wed</div>
                    <div>Thu</div>
                    <div>Fri</div>
                    <div>Sat</div>
                </div>
                <div class="calendar-days">
                    <!-- Previous month days -->
                    <div class="calendar-day other-month">26</div>
                    <div class="calendar-day other-month">27</div>
                    <div class="calendar-day other-month">28</div>
                    <div class="calendar-day other-month">29</div>
                    <div class="calendar-day other-month">30</div>
                    <div class="calendar-day other-month">31</div>
                    
                    <!-- Current month days -->
                    <div class="calendar-day">1</div>
                    <div class="calendar-day">2</div>
                    <div class="calendar-day">3</div>
                    <div class="calendar-day">4</div>
                    <div class="calendar-day">5</div>
                    <div class="calendar-day">6</div>
                    <div class="calendar-day">7</div>
                    <div class="calendar-day">8</div>
                    <div class="calendar-day">9</div>
                    <div class="calendar-day">10</div>
                    <div class="calendar-day">11</div>
                    <div class="calendar-day">12</div>
                    <div class="calendar-day">13</div>
                    <div class="calendar-day">14</div>
                    <div class="calendar-day">15</div>
                    <div class="calendar-day">16</div>
                    <div class="calendar-day">17</div>
                    <div class="calendar-day">18</div>
                    <div class="calendar-day current-date has-appointment">19</div>
                    <div class="calendar-day">20</div>
                    <div class="calendar-day">21</div>
                    <div class="calendar-day">22</div>
                    <div class="calendar-day">23</div>
                    <div class="calendar-day">24</div>
                    <div class="calendar-day">25</div>
                    <div class="calendar-day">26</div>
                    <div class="calendar-day">27</div>
                    <div class="calendar-day">28</div>
                    <div class="calendar-day">29</div>
                    <div class="calendar-day">30</div>
                    
                    <!-- Next month days -->
                    <div class="calendar-day other-month">1</div>
                    <div class="calendar-day other-month">2</div>
                    <div class="calendar-day other-month">3</div>
                    <div class="calendar-day other-month">4</div>
                    <div class="calendar-day other-month">5</div>
                    <div class="calendar-day other-month">6</div>
                </div>
            `;
            calendarContainer.innerHTML = monthView;
            
            //Attach event listeners to calendar days
            attachCalendarDayListeners();
        } 
        else if (viewType === 'week') {
            //Week view - Create a weekly calendar view
            const weekView = `
                <div class="week-header">
                    <div class="week-day-names">
                        <div>Sun</div>
                        <div>Mon</div>
                        <div>Tue</div>
                        <div>Wed</div>
                        <div>Thu</div>
                        <div>Fri</div>
                        <div>Sat</div>
                    </div>
                    <div class="week-dates">
                        <div>16</div>
                        <div>17</div>
                        <div>18</div>
                        <div class="current-date has-appointment">19</div>
                        <div>20</div>
                        <div>21</div>
                        <div>22</div>
                    </div>
                </div>
                <div class="week-hours-container">
                    <div class="week-hours-col time-labels">
                        <div class="hour-slot">8:00 AM</div>
                        <div class="hour-slot">9:00 AM</div>
                        <div class="hour-slot">10:00 AM</div>
                        <div class="hour-slot">11:00 AM</div>
                        <div class="hour-slot">12:00 PM</div>
                        <div class="hour-slot">1:00 PM</div>
                        <div class="hour-slot">2:00 PM</div>
                        <div class="hour-slot">3:00 PM</div>
                        <div class="hour-slot">4:00 PM</div>
                        <div class="hour-slot">5:00 PM</div>
                    </div>
                    <div class="week-grid">
                        <div class="day-col"></div>
                        <div class="day-col"></div>
                        <div class="day-col"></div>
                        <div class="day-col">
                            <div class="appointment-event" style="top: 25%; height: 10%;">
                                <div class="appointment-event-content">
                                    <span class="appointment-time">10:30 AM</span>
                                    <span class="appointment-title">General Checkup</span>
                                </div>
                            </div>
                        </div>
                        <div class="day-col"></div>
                        <div class="day-col"></div>
                        <div class="day-col"></div>
                    </div>
                </div>
            `;
            calendarContainer.innerHTML = weekView;
            
            //update selected date details for the week view
            document.querySelector('.selected-date-appointments h4').textContent = 'June 19, 2024';
        } 
        else if (viewType === 'day') {
            //Day view - Create a daily calendar view
            const dayView = `
                <div class="day-header">
                    <h4 class="day-title">Wednesday, June 19</h4>
                </div>
                <div class="day-hours-container">
                    <div class="hour-slots">
                        <div class="hour-slot">8:00 AM</div>
                        <div class="hour-slot">9:00 AM</div>
                        <div class="hour-slot">10:00 AM</div>
                        <div class="hour-slot">
                            <div class="appointment-event">
                                <div class="appointment-time">10:30 AM</div>
                                <div class="appointment-details">
                                    <h5>General Checkup</h5>
                                    <p>Dr. Ama Mensah - SamaCare Main Clinic</p>
                                </div>
                            </div>
                        </div>
                        <div class="hour-slot">11:00 AM</div>
                        <div class="hour-slot">12:00 PM</div>
                        <div class="hour-slot">1:00 PM</div>
                        <div class="hour-slot">2:00 PM</div>
                        <div class="hour-slot">3:00 PM</div>
                        <div class="hour-slot">4:00 PM</div>
                        <div class="hour-slot">5:00 PM</div>
                    </div>
                </div>
            `;
            calendarContainer.innerHTML = dayView;
            
            //update selected date details for the day view
            document.querySelector('.selected-date-appointments h4').textContent = 'June 19, 2024';
        }
        
        //CSS added for the specific view
        addViewSpecificStyles(viewType);
    }
    
    //function to attach event listeners to calendar days in month view
    function attachCalendarDayListeners() {
        const calendarDays = document.querySelectorAll('.calendar-day');
        
        calendarDays.forEach(day => {
            day.addEventListener('click', function() {
                //clear previously selected day
                calendarDays.forEach(d => d.classList.remove('selected'));
                
                //select clicked day
                this.classList.add('selected');
                
                //update the appointment details based on the day
                if (!this.classList.contains('other-month')) {
                    const date = this.textContent;
                    document.querySelector('.selected-date-appointments h4').textContent = 
                        `June ${date}, 2024`;
                    
                    //show or hide the appointment based on the date
                    const appointmentsList = document.querySelector('.selected-date-appointments');
                    if (date === '19') {
                        /show the appointment for the 19th
                        appointmentsList.innerHTML = `
                            <h4>June ${date}, 2024</h4>
                            <div class="day-appointment-item">
                                <div class="appointment-time">10:30 AM</div>
                                <div class="appointment-info">
                                    <h5>General Checkup</h5>
                                    <p>Dr. Ama Mensah - SamaCare Main Clinic</p>
                                </div>
                            </div>
                        `;
                    } else {
                        //No appointments for other days
                        appointmentsList.innerHTML = `<h4>June ${date}, 2024</h4><p>No appointments scheduled for this day.</p>`;
                    }
                }
            });
        });
    }
    
    //Function to add view-specific CSS
    function addViewSpecificStyles(viewType) {
        //remove any existing view-specific style tag
        const existingStyle = document.getElementById('calendar-view-styles');
        if (existingStyle) {
            existingStyle.remove();
        }
        
        //create new style element
        const styleElement = document.createElement('style');
        styleElement.id = 'calendar-view-styles';
        
        //Add CSS based on view type
        if (viewType === 'week') {
            styleElement.textContent = `
                .week-header {
                    display: flex;
                    flex-direction: column;
                    margin-bottom: 10px;
                }
                .week-day-names, .week-dates {
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                    text-align: center;
                }
                .week-day-names div {
                    padding: 10px;
                    font-weight: 600;
                    color: var(--muted-text);
                }
                .week-dates div {
                    padding: 10px;
                    font-weight: 500;
                }
                .week-dates .current-date {
                    background-color: var(--light-jade);
                    color: var(--primary-color);
                    border-radius: 50%;
                    width: 35px;
                    height: 35px;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    margin: 0 auto;
                }
                .week-dates .has-appointment {
                    position: relative;
                }
                .week-dates .has-appointment::after {
                    content: '';
                    position: absolute;
                    bottom: 2px;
                    left: 50%;
                    transform: translateX(-50%);
                    width: 5px;
                    height: 5px;
                    border-radius: 50%;
                    background-color: var(--primary-color);
                }
                .week-hours-container {
                    display: flex;
                    height: 500px;
                    overflow-y: auto;
                    border: 1px solid var(--border-color);
                    border-radius: var(--border-radius);
                }
                .week-hours-col {
                    width: 80px;
                    flex-shrink: 0;
                    border-right: 1px solid var(--border-color);
                }
                .week-grid {
                    flex: 1;
                    display: grid;
                    grid-template-columns: repeat(7, 1fr);
                }
                .hour-slot {
                    height: 50px;
                    border-bottom: 1px solid var(--border-color);
                    padding: 5px;
                    position: relative;
                }
                .day-col {
                    border-right: 1px solid var(--border-color);
                    position: relative;
                }
                .day-col:last-child {
                    border-right: none;
                }
                .appointment-event {
                    position: absolute;
                    left: 2px;
                    right: 2px;
                    background-color: var(--light-jade);
                    border-left: 3px solid var(--primary-color);
                    border-radius: 3px;
                    padding: 5px;
                    overflow: hidden;
                    z-index: 1;
                }
                .appointment-event-content {
                    font-size: 12px;
                    white-space: nowrap;
                    overflow: hidden;
                    text-overflow: ellipsis;
                }
                .appointment-time {
                    font-weight: 600;
                    color: var(--primary-color);
                }
                .appointment-title {
                    margin-left: 5px;
                }
            `;
        } 
        else if (viewType === 'day') {
            styleElement.textContent = `
                .day-header {
                    margin-bottom: 15px;
                    text-align: center;
                }
                .day-title {
                    font-size: 18px;
                    color: var(--dark-color);
                }
                .day-hours-container {
                    border: 1px solid var(--border-color);
                    border-radius: var(--border-radius);
                    height: 550px;
                    overflow-y: auto;
                }
                .hour-slots {
                    display: flex;
                    flex-direction: column;
                }
                .hour-slot {
                    height: 60px;
                    border-bottom: 1px solid var(--border-color);
                    padding: 10px;
                    position: relative;
                }
                .hour-slot:last-child {
                    border-bottom: none;
                }
                .appointment-event {
                    display: flex;
                    background-color: var(--light-jade);
                    border-left: 3px solid var(--primary-color);
                    padding: 10px;
                    border-radius: 5px;
                    margin-top: 5px;
                }
                .appointment-time {
                    font-weight: 600;
                    color: var(--primary-color);
                    min-width: 80px;
                }
                .appointment-details h5 {
                    margin-bottom: 5px;
                }
                .appointment-details p {
                    font-size: 12px;
                    color: var(--muted-text);
                }
            `;
        }
        
        //style element to the document head
        document.head.appendChild(styleElement);
    }
    
    //Calendar navigation
    const calendarNavBtns = document.querySelectorAll('.calendar-nav-btn');
    
    calendarNavBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            // For demo purposes only
            showToast('Calendar would navigate to previous/next month');
        });
    });
    
    //Calendar day selection
    const calendarDays = document.querySelectorAll('.calendar-day');
    
    calendarDays.forEach(day => {
        day.addEventListener('click', function() {
            //clear previously selected day
            calendarDays.forEach(d => d.classList.remove('selected'));
            
            //Select clicked day
            this.classList.add('selected');
            
            //this would update the appointments shown below - not yet a real implementation, please link it to the backend
            if (!this.classList.contains('other-month')) {
                const date = this.textContent;
                document.querySelector('.selected-date-appointments h4').textContent = 
                    `June ${date}, 2024`;
                
                //Show or hide the appointment based on the date
                const appointmentsList = document.querySelector('.selected-date-appointments');
                if (date === '19') {
                    //Show the appointment for the chosen day
                    appointmentsList.style.display = 'block';
                } else {
                    //No appointments for other days
                    appointmentsList.innerHTML = `<h4>June ${date}, 2024</h4><p>No appointments scheduled for this day.</p>`;
                }
            } else {
                //Different month
                showToast('This would show appointments for the selected day in the previous/next month');
            }
        });
    });
    
    //appointment actions
    const appointmentActions = document.querySelectorAll('.appointment-actions .action-btn');
    
    appointmentActions.forEach(btn => {
        btn.addEventListener('click', function() {
            const action = this.getAttribute('title');
            
            // Display toast message for the action
            if (action === 'Reschedule') {
                showToast('Reschedule functionality would be implemented with backend integration');
            } else if (action === 'Cancel') {
                showToast('Cancel functionality would be implemented with backend integration');
            } else if (action === 'View Details') {
                showToast('View details functionality would be implemented with backend integration');
            }
        });
    });
    
    //New appointment button
    const scheduleBtn = document.querySelector('.schedule-btn');
    
    if (scheduleBtn) {
        scheduleBtn.addEventListener('click', function() {
            showToast('New appointment scheduling would be implemented with backend integration');
        });
    }
    
    //Filter dropdown for status
    const filterDropdown = document.querySelector('.filter-dropdown .secondary-btn');
    
    if (filterDropdown) {
        filterDropdown.addEventListener('click', function() {
            showToast('Filter functionality would be implemented with backend integration');
        });
    }
    
    //Toast message function
    function showToast(message) {
        //Create toast element if it doesn't exist
        let toast = document.querySelector('.toast-message');
        
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-message';
            document.body.appendChild(toast);
            
            // Add style for the toast if not already in CSS
            if (!document.querySelector('style.toast-style')) {
                const style = document.createElement('style');
                style.className = 'toast-style';
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
        }
        
        // Set message and show toast
        toast.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
});