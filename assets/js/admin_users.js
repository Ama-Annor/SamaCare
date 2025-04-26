document.addEventListener('DOMContentLoaded', function() {
    // Initialize user management features
    initUserManagement();

    // Handle mobile menu toggle
    setupMobileMenu();

    // Handle dropdown toggles
    setupDropdowns();

    // Hide toast messages after 3 seconds
    const toastMessages = document.querySelectorAll('.toast-message');
    toastMessages.forEach(toast => {
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    });
});

// Function to initialize user management features
function initUserManagement() {
    // Handle modal functionality
    setupModal();

    // Handle context menu
    setupContextMenu();

    // Handle select all checkbox
    setupSelectAll();

    // Handle role-specific fields in form
    setupRoleFields();

    // Handle password toggle
    setupPasswordToggle();

    // Setup search filtering
    setupSearch();

    // Setup filters
    setupFilters();
}

// Function to setup modal
function setupModal() {
    const modal = document.getElementById('user-modal');
    const addUserBtn = document.getElementById('add-user-btn');
    const closeBtn = document.querySelector('.modal-close');
    const cancelBtn = document.getElementById('cancel-btn');
    const editBtns = document.querySelectorAll('.edit-btn');
    const userForm = document.getElementById('user-form');

    // Show modal when Add User button is clicked
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            // Reset form
            userForm.reset();

            // Set form action for adding a user
            document.getElementById('form_action').value = 'add_user';
            userForm.setAttribute('action', '');

            // Set modal title
            document.getElementById('modal-title').textContent = 'Add New User';

            // Hide the password help text for new users
            document.getElementById('password-help').style.display = 'none';

            // Hide doctor fields by default
            document.querySelector('.doctor-fields').style.display = 'none';

            // Enable all form fields
            setFormReadOnly(false);

            // Show save button
            document.getElementById('save-btn').style.display = 'block';

            // Show modal
            modal.classList.add('show');
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
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            modal.classList.remove('show');
        });
    }

    // Close modal when clicking outside
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.classList.remove('show');
        }
    });

    // Show modal with user data when Edit button is clicked
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            openUserModal(userId, 'edit');
        });
    });

    // Handle role selection to show/hide doctor fields
    const roleSelect = document.getElementById('role_id');
    if (roleSelect) {
        roleSelect.addEventListener('change', function() {
            const doctorFields = document.querySelector('.doctor-fields');
            if (this.options[this.selectedIndex].text === 'Doctor') {
                doctorFields.style.display = 'block';
            } else {
                doctorFields.style.display = 'none';
            }
        });
    }

    // Fix form submission
    if (userForm) {
        userForm.addEventListener('submit', function(e) {
            const formAction = document.getElementById('form_action').value;

            // Set the correct form action based on form_action value
            if (formAction === 'add_user') {
                this.setAttribute('action', '?action=add_user');
                this.querySelector('button[type="submit"]').name = 'add_user';
            } else if (formAction === 'edit_user') {
                this.setAttribute('action', '?action=update_user');
                this.querySelector('button[type="submit"]').name = 'edit_user';
            }
        });
    }
}

// Helper function to set all form fields to read-only or editable
function setFormReadOnly(readOnly) {
    const formInputs = document.querySelectorAll('#user-form input, #user-form select');
    formInputs.forEach(input => {
        input.readOnly = readOnly;
        if (input.tagName === 'SELECT') {
            input.disabled = readOnly;
        }
    });

    // Hide/show the password toggle button
    const passwordToggle = document.querySelector('.password-toggle');
    if (passwordToggle) {
        passwordToggle.style.display = readOnly ? 'none' : 'block';
    }
}

// Function to open user modal in different modes (view/edit)
function openUserModal(userId, mode) {
    const modal = document.getElementById('user-modal');
    const userForm = document.getElementById('user-form');

    // Set form action and title based on mode
    if (mode === 'view') {
        document.getElementById('modal-title').textContent = 'User Profile';
        document.getElementById('form_action').value = 'view_user';
        // Hide save button for view mode
        document.getElementById('save-btn').style.display = 'none';
    } else {
        document.getElementById('modal-title').textContent = 'Edit User';
        document.getElementById('form_action').value = 'edit_user';
        document.getElementById('save-btn').style.display = 'block';
    }

    document.getElementById('user_id').value = userId;

    // Show the password help text for existing users
    document.getElementById('password-help').style.display = mode === 'edit' ? 'block' : 'none';

    // Fetch user data via AJAX
    fetch(`admin_users.php?action=get_user_details&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                showToast(data.error, true);
                return;
            }

            // Fill form with user data
            document.getElementById('first_name').value = data.first_name;
            document.getElementById('last_name').value = data.last_name;
            document.getElementById('email').value = data.email;
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('role_id').value = data.role_id;
            document.getElementById('status').value = data.status;
            document.getElementById('password').value = ''; // Clear password field

            // Show or hide doctor fields based on user role
            if (data.role_name === 'Doctor') {
                document.querySelector('.doctor-fields').style.display = 'block';

                // Fill doctor-specific fields
                if (data.specialty_id) {
                    document.getElementById('specialty').value = data.specialty_id;
                }
                if (data.license_number) {
                    document.getElementById('license').value = data.license_number;
                }
            } else {
                document.querySelector('.doctor-fields').style.display = 'none';
            }

            // Set form fields to read-only or editable based on mode
            setFormReadOnly(mode === 'view');

            // Show modal
            modal.classList.add('show');
        })
        .catch(error => {
            console.error('Error fetching user data:', error);
            showToast('Error loading user data. Please try again.', true);
        });
}

// Function to setup role fields
function setupRoleFields() {
    // This is a placeholder for the function referenced in initUserManagement
    // If needed, add specific role field handling code here
}

// Function to setup context menu
function setupContextMenu() {
    const contextMenu = document.getElementById('user-actions-menu');
    const moreBtns = document.querySelectorAll('.more-btn');

    // Show context menu when More button is clicked
    moreBtns.forEach(btn => {
        btn.addEventListener('click', function(event) {
            event.stopPropagation();

            const userId = this.dataset.id;

            // Position menu next to the button
            const rect = this.getBoundingClientRect();
            contextMenu.style.top = rect.bottom + 5 + 'px';
            contextMenu.style.left = rect.left - 170 + 'px'; // 170 = menu width - button width

            // Show menu
            contextMenu.classList.add('show');

            // Store the user ID
            contextMenu.dataset.userId = userId;
        });
    });

    // Close context menu when clicking outside
    document.addEventListener('click', function() {
        contextMenu.classList.remove('show');
    });

    // Handle context menu actions
    const menuItems = contextMenu.querySelectorAll('li');
    menuItems.forEach(item => {
        item.addEventListener('click', function() {
            const action = this.dataset.action;
            const userId = contextMenu.dataset.userId;

            // Handle different actions
            switch (action) {
                case 'view':
                    // Open modal in view mode
                    openUserModal(userId, 'view');
                    break;
                case 'edit':
                    // Open modal in edit mode
                    openUserModal(userId, 'edit');
                    break;
                case 'reset-password':
                    if (confirm('Send password reset link to this user?')) {
                        // Placeholder for password reset functionality
                        showToast('Password reset link sent successfully!');
                    }
                    break;
                case 'disable':
                    // Handle disable/enable toggle
                    const row = document.querySelector(`.edit-btn[data-id="${userId}"]`).closest('tr');
                    const statusBadge = row.querySelector('.status-badge');
                    const newStatus = statusBadge.classList.contains('active') ? 'inactive' : 'active';

                    // Create and submit form to change status
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.style.display = 'none';

                    const userIdInput = document.createElement('input');
                    userIdInput.name = 'user_id';
                    userIdInput.value = userId;

                    const statusInput = document.createElement('input');
                    statusInput.name = 'status';
                    statusInput.value = newStatus;

                    const actionInput = document.createElement('input');
                    actionInput.name = 'change_status';
                    actionInput.value = '1';

                    form.appendChild(userIdInput);
                    form.appendChild(statusInput);
                    form.appendChild(actionInput);

                    document.body.appendChild(form);
                    form.submit();
                    break;
                case 'delete':
                    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        // Create and submit form to delete user
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.style.display = 'none';

                        const userIdInput = document.createElement('input');
                        userIdInput.name = 'user_id';
                        userIdInput.value = userId;

                        const actionInput = document.createElement('input');
                        actionInput.name = 'delete_user';
                        actionInput.value = '1';

                        form.appendChild(userIdInput);
                        form.appendChild(actionInput);

                        document.body.appendChild(form);
                        form.submit();
                    }
                    break;
            }

            // Close context menu
            contextMenu.classList.remove('show');
        });
    });
}

// Function to setup select all checkbox
function setupSelectAll() {
    const selectAll = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-select');

    if (selectAll && userCheckboxes.length > 0) {
        selectAll.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = this.checked;
            });
        });

        // Update select all state when individual checkboxes change
        userCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const allChecked = Array.from(userCheckboxes).every(cb => cb.checked);
                const someChecked = Array.from(userCheckboxes).some(cb => cb.checked);

                selectAll.checked = allChecked;
                selectAll.indeterminate = someChecked && !allChecked;
            });
        });
    }
}

// Function to setup password toggle
function setupPasswordToggle() {
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('password');

    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.getAttribute('type');

            if (type === 'password') {
                passwordInput.setAttribute('type', 'text');
                this.innerHTML = '<i class="bx bx-show"></i>';
            } else {
                passwordInput.setAttribute('type', 'password');
                this.innerHTML = '<i class="bx bx-hide"></i>';
            }
        });
    }
}

// Function to setup search filtering
function setupSearch() {
    const searchInput = document.getElementById('search-input');
    const tableRows = document.querySelectorAll('.users-table tbody tr');

    if (searchInput && tableRows.length > 0) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase().trim();

            // When search is cleared, reapply only the dropdown filters
            if (searchTerm === '') {
                // Get current filter values
                const selectedRole = document.getElementById('role-filter').value;
                const selectedStatus = document.getElementById('status-filter').value;

                tableRows.forEach(row => {
                    const rowRole = row.getAttribute('data-role');
                    const rowStatus = row.getAttribute('data-status');

                    // Only apply role and status filters when search is empty
                    const roleMatch = selectedRole === 'all' || rowRole === selectedRole;
                    const statusMatch = selectedStatus === 'all' || rowStatus === selectedStatus;

                    if (roleMatch && statusMatch) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
                return;
            }

            // If there's a search term, do the regular search logic
            tableRows.forEach(row => {
                // Check if row is already hidden by role/status filters
                const roleFilter = document.getElementById('role-filter').value;
                const statusFilter = document.getElementById('status-filter').value;
                const rowRole = row.getAttribute('data-role');
                const rowStatus = row.getAttribute('data-status');

                const roleMatch = roleFilter === 'all' || rowRole === roleFilter;
                const statusMatch = statusFilter === 'all' || rowStatus === statusFilter;

                // Skip filtering if row should be hidden by dropdown filters
                if (!roleMatch || !statusMatch) {
                    row.style.display = 'none';
                    return;
                }

                // Get text content to search
                const nameCell = row.querySelector('.user-info span');
                const emailCell = row.cells[2];
                const roleCell = row.cells[3];

                if (!nameCell || !emailCell || !roleCell) return;

                const name = nameCell.textContent.toLowerCase();
                const email = emailCell.textContent.toLowerCase();
                const role = roleCell.textContent.toLowerCase();

                // Show/hide based on search term
                if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Handle search clear button if present
        const clearButton = document.querySelector('.search-clear');
        if (clearButton) {
            clearButton.addEventListener('click', function() {
                searchInput.value = '';
                // Trigger the keyup event to apply filters properly
                searchInput.dispatchEvent(new Event('keyup'));
            });
        }
    }
}

// Function to setup filters
function setupFilters() {
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const tableRows = document.querySelectorAll('.users-table tbody tr');

    if (roleFilter && statusFilter && tableRows.length > 0) {
        // Function to apply filters
        const applyFilters = () => {
            const selectedRole = roleFilter.value;
            const selectedStatus = statusFilter.value;
            const searchTerm = document.getElementById('search-input')?.value.toLowerCase().trim() || '';

            tableRows.forEach(row => {
                // Get row data attributes
                const rowRole = row.getAttribute('data-role');
                const rowStatus = row.getAttribute('data-status');

                // Match conditions
                const roleMatch = selectedRole === 'all' || rowRole === selectedRole;
                const statusMatch = selectedStatus === 'all' || rowStatus === selectedStatus;

                // Get text content for search
                const nameCell = row.querySelector('.user-info span');
                const emailCell = row.cells[2];
                const roleCell = row.cells[3];

                if (!nameCell || !emailCell || !roleCell) return;

                const name = nameCell.textContent.toLowerCase();
                const email = emailCell.textContent.toLowerCase();
                const role = roleCell.textContent.toLowerCase();

                const searchMatch = searchTerm === '' ||
                    name.includes(searchTerm) ||
                    email.includes(searchTerm) ||
                    role.includes(searchTerm);

                // Show or hide based on all conditions
                if (roleMatch && statusMatch && searchMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };

        // Add event listeners
        roleFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);

        // Initial filter application
        applyFilters();
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
            // Toggle user dropdown (placeholder)
            console.log('User dropdown clicked');
        });
    }
}

// Toast notification function
function showToast(message, isError = false) {
    // Create toast element if it doesn't exist
    let toast = document.querySelector('.toast-message');

    if (!toast) {
        toast = document.createElement('div');
        toast.className = 'toast-message';
        document.body.appendChild(toast);
    }

    // Clear existing classes
    toast.classList.remove('show', 'error');

    // Set message and show toast
    toast.textContent = message;
    toast.classList.add('show');

    if (isError) {
        toast.classList.add('error');
    }

    // Hide toast after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
    }, 3000);
}