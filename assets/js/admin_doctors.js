document.addEventListener('DOMContentLoaded', function() {
    // Initialize doctor management features
    initDoctorManagement();
    
    // Handle mobile menu toggle
    setupMobileMenu();
    
    // Handle dropdown toggles
    setupDropdowns();
});

// Function to initialize doctor management features
function initDoctorManagement() {
    // Setup modal
    setupModal();
    
    // Setup filters
    setupFilters();
    
    // Setup search
    setupSearch();
    
    // Setup Add Doctor card
    setupAddDoctorCard();
    
    // Setup View buttons
    setupViewButtons();
}

// Function to setup modal
function setupModal() {
    const modal = document.getElementById('doctor-modal');
    const addDoctorBtn = document.getElementById('add-doctor-btn');
    const addDoctorCard = document.getElementById('add-doctor-card');
    const closeBtn = document.querySelector('.modal-close');
    const cancelBtn = document.getElementById('cancel-btn');
    const editBtns = document.querySelectorAll('.edit-btn');
    
    // Show modal when Add Doctor button is clicked
    if (addDoctorBtn) {
        addDoctorBtn.addEventListener('click', function() {
            openAddDoctorModal();
        });
    }
    
    // Show modal when Add Doctor card is clicked
    if (addDoctorCard) {
        addDoctorCard.addEventListener('click', function() {
            openAddDoctorModal();
        });
    }
    
    // Close modal when X button is clicked
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
    }
    
    // Close modal when Cancel button is clicked
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function() {
            modal.classList.remove('show');
        });
    }
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });
    
    // Show modal with doctor data when Edit button is clicked
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            openEditDoctorModal(this.dataset.id);
        });
    });
    
    // Handle form submission
    const form = document.getElementById('doctor-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Get form data
        const formData = {
            name: document.getElementById('doctor-name').value,
            email: document.getElementById('doctor-email').value,
            phone: document.getElementById('doctor-phone').value,
            specialty: document.getElementById('doctor-specialty-select').value,
            license: document.getElementById('doctor-license').value,
            status: document.getElementById('doctor-status').value,
            bio: document.getElementById('doctor-bio').value
        };
        
        // Get schedule data
        const scheduleDays = document.querySelectorAll('.schedule-day');
        const schedule = {};
        
        scheduleDays.forEach(day => {
            const dayName = day.querySelector('input[type="checkbox"]').value;
            const isWorking = day.querySelector('input[type="checkbox"]').checked;
            
            if (isWorking) {
                const startTime = day.querySelector('input[type="time"]:first-of-type').value;
                const endTime = day.querySelector('input[type="time"]:last-of-type').value;
                
                schedule[dayName] = {
                    start: startTime,
                    end: endTime
                };
            }
        });
        
        formData.schedule = schedule;
        
        // Show success message
        showToast('Doctor saved successfully');
        
        // Close modal
        modal.classList.remove('show');
    });
}

// Function to open Add Doctor modal
function openAddDoctorModal() {
    const modal = document.getElementById('doctor-modal');
    
    // Reset form
    document.getElementById('doctor-form').reset();
    
    // Set modal title
    document.getElementById('modal-title').textContent = 'Add New Doctor';
    
    // Show modal
    modal.classList.add('show');
}

// Function to open Edit Doctor modal
function openEditDoctorModal(doctorId) {
    const modal = document.getElementById('doctor-modal');
    
    // Set modal title
    document.getElementById('modal-title').textContent = 'Edit Doctor';
    
    // Find the doctor card
    const doctorCard = document.querySelector(`.doctor-card .edit-btn[data-id="${doctorId}"]`).closest('.doctor-card');
    
    // Fill form with doctor data
    const doctorName = doctorCard.querySelector('.doctor-name').textContent;
    const doctorSpecialty = doctorCard.querySelector('.doctor-specialty').textContent;
    const doctorEmail = doctorCard.querySelector('.doctor-contact p:first-child').textContent.trim().replace('✉️ ', '');
    const doctorPhone = doctorCard.querySelector('.doctor-contact p:last-child').textContent.trim().replace('📞 ', '');
    const doctorStatus = doctorCard.querySelector('.doctor-status').classList.contains('active') ? 'active' : 'inactive';
    
    document.getElementById('doctor-name').value = doctorName;
    document.getElementById('doctor-email').value = doctorEmail;
    document.getElementById('doctor-phone').value = doctorPhone;
    document.getElementById('doctor-status').value = doctorStatus;
    
    // Set specialty
    const specialtySelect = document.getElementById('doctor-specialty-select');
    for (let i = 0; i < specialtySelect.options.length; i++) {
        if (specialtySelect.options[i].text === doctorSpecialty) {
            specialtySelect.selectedIndex = i;
            break;
        }
    }
    
    // Sample bio for demo purposes
    document.getElementById('doctor-bio').value = `Dr. ${doctorName.split(' ')[1]} is a dedicated ${doctorSpecialty} with over 10 years of experience in the field. Specializing in comprehensive patient care, they are committed to providing the highest quality medical services.`;
    
    // Show modal
    modal.classList.add('show');
}

// Function to setup filters
function setupFilters() {
    const specialtyFilter = document.getElementById('specialty-filter');
    const statusFilter = document.getElementById('status-filter');
    const doctorCards = document.querySelectorAll('.doctor-card:not(.add-card)');
    
    if (specialtyFilter && statusFilter && doctorCards.length > 0) {
        // Function to apply filters
        const applyFilters = () => {
            const selectedSpecialty = specialtyFilter.value;
            const selectedStatus = statusFilter.value;
            
            doctorCards.forEach(card => {
                const specialty = card.querySelector('.doctor-specialty').textContent.toLowerCase();
                const isActive = card.querySelector('.doctor-status').classList.contains('active');
                const status = isActive ? 'active' : 'inactive';
                
                let specialtyMatch = selectedSpecialty === 'all';
                if (selectedSpecialty === 'general' && specialty.includes('general')) specialtyMatch = true;
                if (selectedSpecialty === 'cardiology' && specialty.includes('cardiologist')) specialtyMatch = true;
                if (selectedSpecialty === 'dental' && specialty.includes('dentist')) specialtyMatch = true;
                if (selectedSpecialty === 'pediatrics' && specialty.includes('pediatrician')) specialtyMatch = true;
                
                const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                
                if (specialtyMatch && statusMatch) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
            
            showToast('Filters applied');
        };
        
        // Add event listeners
        specialtyFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
    }
}

// Function to setup search
function setupSearch() {
    const searchInput = document.querySelector('.search-bar input');
    const doctorCards = document.querySelectorAll('.doctor-card:not(.add-card)');
    
    if (searchInput && doctorCards.length > 0) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            doctorCards.forEach(card => {
                const doctorName = card.querySelector('.doctor-name').textContent.toLowerCase();
                const doctorSpecialty = card.querySelector('.doctor-specialty').textContent.toLowerCase();
                const doctorEmail = card.querySelector('.doctor-contact p:first-child').textContent.toLowerCase();
                
                if (doctorName.includes(searchTerm) || doctorSpecialty.includes(searchTerm) || doctorEmail.includes(searchTerm)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    }
}

// Function to setup Add Doctor card
function setupAddDoctorCard() {
    const addDoctorCard = document.getElementById('add-doctor-card');
    
    if (addDoctorCard) {
        addDoctorCard.addEventListener('click', function() {
            openAddDoctorModal();
        });
    }
}

// Function to setup View buttons
function setupViewButtons() {
    const viewButtons = document.querySelectorAll('.view-btn');
    
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const doctorId = this.dataset.id;
            
            // Find the doctor card
            const doctorCard = this.closest('.doctor-card');
            const doctorName = doctorCard.querySelector('.doctor-name').textContent;
            
            showToast(`Viewing ${doctorName}'s details`);
            
            // In a real application, this would navigate to a doctor detail page
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