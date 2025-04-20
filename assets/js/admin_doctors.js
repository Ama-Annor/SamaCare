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
    const doctorForm = document.getElementById('doctor-form');

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
            openEditDoctorModal(this.dataset.id, false); // false = not view only
        });
    });

    // Handle form submission
    doctorForm.addEventListener('submit', function(event) {
        event.preventDefault();

        // Check if form is in view-only mode
        if (document.getElementById('is-view-only').value === '1') {
            modal.classList.remove('show');
            return;
        }

        // Create FormData object
        const formData = new FormData(this);

        // Send AJAX request
        fetch('admin_doctors.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message);
                    modal.classList.remove('show');

                    // Reload the page to show updated data
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    showToast('Error: ' + data.message);
                }
            })
            .catch(error => {
                showToast('Error: ' + error.message);
            });
    });
}

// Function to open Add Doctor modal
function openAddDoctorModal() {
    const modal = document.getElementById('doctor-modal');

    // Reset form
    document.getElementById('doctor-form').reset();
    document.getElementById('doctor-id').value = '0';
    document.getElementById('is-view-only').value = '0';

    // Enable all form fields
    const formElements = document.querySelectorAll('#doctor-form input, #doctor-form select, #doctor-form textarea');
    formElements.forEach(element => {
        element.disabled = false;
    });

    // Show save button
    document.getElementById('save-btn').style.display = 'block';

    // Set modal title
    document.getElementById('modal-title').textContent = 'Add New Doctor';

    // Show modal
    modal.classList.add('show');
}

// Function to open Edit/View Doctor modal
function openEditDoctorModal(doctorId, viewOnly = false) {
    const modal = document.getElementById('doctor-modal');

    // Set modal title based on mode
    document.getElementById('modal-title').textContent = viewOnly ? 'Doctor Details' : 'Edit Doctor';

    // Set the doctor ID in the form
    document.getElementById('doctor-id').value = doctorId;

    // Set view-only flag
    document.getElementById('is-view-only').value = viewOnly ? '1' : '0';

    // Find the doctor card
    const doctorCard = document.querySelector(`.doctor-card .edit-btn[data-id="${doctorId}"]`).closest('.doctor-card');

    // Fill form with doctor data
    const doctorName = doctorCard.getAttribute('data-name');
    const nameParts = doctorName.replace('Dr. ', '').split(' ');
    const firstName = nameParts[0];
    const lastName = nameParts.slice(1).join(' ');

    const doctorSpecialty = doctorCard.getAttribute('data-specialty');
    const doctorEmail = doctorCard.getAttribute('data-email');
    const doctorPhone = doctorCard.querySelector('.doctor-contact p:last-child').textContent.trim().replace('📞 ', '');
    const doctorStatus = doctorCard.getAttribute('data-status');

    document.getElementById('doctor-first-name').value = firstName;
    document.getElementById('doctor-last-name').value = lastName;
    document.getElementById('doctor-email').value = doctorEmail;
    document.getElementById('doctor-phone').value = doctorPhone;
    document.getElementById('doctor-status').value = doctorStatus;

    // Set specialty
    const specialtySelect = document.getElementById('doctor-specialty');
    for (let i = 0; i < specialtySelect.options.length; i++) {
        if (specialtySelect.options[i].text === doctorSpecialty) {
            specialtySelect.selectedIndex = i;
            break;
        }
    }

    // Sample license number and bio for demo purposes
    document.getElementById('doctor-license').value = `LIC${doctorId}${Math.floor(Math.random() * 10000)}`;
    document.getElementById('doctor-bio').value = `Dr. ${nameParts[1]} is a dedicated ${doctorSpecialty} with over 10 years of experience in the field. Specializing in comprehensive patient care, they are committed to providing the highest quality medical services.`;

    // If view-only mode, disable all form fields
    if (viewOnly) {
        const formElements = document.querySelectorAll('#doctor-form input, #doctor-form select, #doctor-form textarea');
        formElements.forEach(element => {
            element.disabled = true;
        });

        // Hide save button in view-only mode
        document.getElementById('save-btn').style.display = 'none';
    } else {
        // Enable all form fields if not view-only
        const formElements = document.querySelectorAll('#doctor-form input, #doctor-form select, #doctor-form textarea');
        formElements.forEach(element => {
            element.disabled = false;
        });

        // Show save button
        document.getElementById('save-btn').style.display = 'block';
    }

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
                const specialty = card.getAttribute('data-specialty').toLowerCase();
                const status = card.getAttribute('data-status');

                let specialtyMatch = selectedSpecialty === 'all';
                if (!specialtyMatch && specialty) {
                    // This will match any specialty that contains the selected value
                    specialtyMatch = specialty.toLowerCase().includes(selectedSpecialty.toLowerCase());
                }

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
                const doctorName = card.getAttribute('data-name').toLowerCase();
                const doctorSpecialty = card.getAttribute('data-specialty').toLowerCase();
                const doctorEmail = card.getAttribute('data-email').toLowerCase();

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
            openEditDoctorModal(doctorId, true); // true = view only mode
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