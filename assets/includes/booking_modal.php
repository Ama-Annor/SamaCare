<?php
// File: includes/booking_modal.php
?>
<div class="booking-modal" id="booking-modal">
    <div class="form-card">
        <div class="form-header">
            <h3>Book an Appointment</h3>
            <button class="close-form" id="close-booking-modal">
                <i class='bx bx-x'></i>
            </button>
        </div>
        <div class="form-body">
            <!-- Step 1: Service Selection -->
            <div class="booking-step" id="step-1">
                <h4 class="step-title">1. Select Service</h4>
                <div class="form-group">
                    <label for="service-type">Service Type</label>
                    <select id="service-type" class="form-control">
                        <option value="">Select a service</option>
                        <option value="general-checkup">General Checkup</option>
                        <option value="specialist-consultation">Specialist Consultation</option>
                        <option value="dental-care">Dental Care</option>
                        <option value="laboratory-tests">Laboratory Tests</option>
                        <option value="vaccination">Vaccination</option>
                    </select>
                </div>
                <div class="form-navigation">
                    <div></div> <!-- Empty div for spacing -->
                    <button class="btn primary-btn next-step" data-step="1">Continue</button>
                </div>
            </div>

            <!-- Step 2: Doctor Selection -->
            <div class="booking-step" id="step-2" style="display: none;">
                <h4 class="step-title">2. Select Doctor</h4>
                <div class="form-group">
                    <label for="doctor">Select Doctor</label>
                    <select id="doctor" class="form-control">
                        <option value="">Select a doctor</option>
                        <option value="dr-ama-mensah">Dr. Ama Mensah - General Physician</option>
                        <option value="dr-mcnobert-amoah">Dr. McNobert Amoah - Cardiologist</option>
                        <option value="dr-sarah-johnson">Dr. Sarah Johnson - Dentist</option>
                        <option value="dr-michael-ofori">Dr. Michael Ofori - Pediatrician</option>
                    </select>
                </div>
                <div class="doctor-info" id="doctor-info" style="display: none;">
                    <div class="doctor-card">
                        <div class="doctor-avatar">
                            <i class='bx bx-user-circle'></i>
                        </div>
                        <div class="doctor-details">
                            <h5 id="doctor-name">Dr. Ama Mensah</h5>
                            <p id="doctor-specialty">General Physician</p>
                            <div class="doctor-rating">
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star'></i>
                                <i class='bx bxs-star-half'></i>
                                <span>4.5</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="form-navigation">
                    <button class="btn secondary-btn prev-step" data-step="2">Back</button>
                    <button class="btn primary-btn next-step" data-step="2">Continue</button>
                </div>
            </div>

            <!-- Step 3: Date & Time Selection -->
            <div class="booking-step" id="step-3" style="display: none;">
                <h4 class="step-title">3. Select Date & Time</h4>
                <div class="form-group">
                    <label for="appointment-date">Date</label>
                    <input type="date" id="appointment-date" class="form-control">
                </div>
                <div class="form-group">
                    <label>Available Time Slots</label>
                    <div class="time-slots">
                        <?php
                        $timeSlots = [
                            ['id' => 'slot-1', 'value' => '09:00', 'label' => '9:00 AM'],
                            ['id' => 'slot-2', 'value' => '09:30', 'label' => '9:30 AM'],
                            ['id' => 'slot-3', 'value' => '10:00', 'label' => '10:00 AM'],
                            ['id' => 'slot-4', 'value' => '10:30', 'label' => '10:30 AM'],
                            ['id' => 'slot-5', 'value' => '11:00', 'label' => '11:00 AM'],
                            ['id' => 'slot-6', 'value' => '11:30', 'label' => '11:30 AM'],
                            ['id' => 'slot-7', 'value' => '13:00', 'label' => '1:00 PM'],
                            ['id' => 'slot-8', 'value' => '13:30', 'label' => '1:30 PM'],
                            ['id' => 'slot-9', 'value' => '14:00', 'label' => '2:00 PM']
                        ];
                        
                        $slotCount = 0;
                        foreach ($timeSlots as $slot) {
                            if ($slotCount % 3 == 0) {
                                if ($slotCount > 0) echo '</div>';
                                echo '<div class="time-slot-row">';
                            }
                            ?>
                            <div class="time-slot">
                                <input type="radio" name="time-slot" id="<?php echo $slot['id']; ?>" value="<?php echo $slot['value']; ?>">
                                <label for="<?php echo $slot['id']; ?>"><?php echo $slot['label']; ?></label>
                            </div>
                            <?php
                            $slotCount++;
                        }
                        if ($slotCount > 0) echo '</div>';
                        ?>
                    </div>
                </div>
                <div class="form-navigation">
                    <button class="btn secondary-btn prev-step" data-step="3">Back</button>
                    <button class="btn primary-btn next-step" data-step="3">Continue</button>
                </div>
            </div>

            <!-- Step 4: Additional Information -->
            <div class="booking-step" id="step-4" style="display: none;">
                <h4 class="step-title">4. Additional Information</h4>
                <div class="form-group">
                    <label for="appointment-reason">Reason for Visit</label>
                    <textarea id="appointment-reason" class="form-control" rows="3" placeholder="Please describe your symptoms or reason for this appointment"></textarea>
                </div>
                <div class="form-group">
                    <label for="appointment-location">Clinic Location</label>
                    <select id="appointment-location" class="form-control">
                        <option value="main-clinic">SamaCare Main Clinic</option>
                        <option value="west-branch">SamaCare West Branch</option>
                        <option value="east-branch">SamaCare East Branch</option>
                    </select>
                </div>
                <div class="form-navigation">
                    <button class="btn secondary-btn prev-step" data-step="4">Back</button>
                    <button class="btn primary-btn next-step" data-step="4">Review</button>
                </div>
            </div>

            <!-- Step 5: Review and Confirm -->
            <div class="booking-step" id="step-5" style="display: none;">
                <h4 class="step-title">5. Review and Confirm</h4>
                <div class="booking-summary">
                    <div class="summary-item">
                        <span class="summary-label">Service:</span>
                        <span class="summary-value" id="summary-service">General Checkup</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Doctor:</span>
                        <span class="summary-value" id="summary-doctor">Dr. Ama Mensah</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Date:</span>
                        <span class="summary-value" id="summary-date">June 25, 2024</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Time:</span>
                        <span class="summary-value" id="summary-time">10:30 AM</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Location:</span>
                        <span class="summary-value" id="summary-location">SamaCare Main Clinic</span>
                    </div>
                    <div class="summary-item">
                        <span class="summary-label">Reason:</span>
                        <span class="summary-value" id="summary-reason">Regular checkup and consultation</span>
                    </div>
                </div>
                <div class="form-navigation">
                    <button class="btn secondary-btn prev-step" data-step="5">Back</button>
                    <button class="btn primary-btn" id="confirm-booking">Confirm Booking</button>
                </div>
            </div>
        </div>
    </div>
</div>
