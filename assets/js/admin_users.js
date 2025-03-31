document.addEventListener('DOMContentLoaded', function() {
    // Initialize user management features
    initUserManagement();
    
    // Handle mobile menu toggle
    setupMobileMenu();
    
    // Handle dropdown toggles
    setupDropdowns();
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
    
    // Show modal when Add User button is clicked
    if (addUserBtn) {
        addUserBtn.addEventListener('click', function() {
            // Reset form
            document.getElementById('user-form').reset();
            
            // Set modal title
            document.getElementById('modal-title').textContent = 'Add New User';
            
            // Hide doctor fields
            document.querySelector('.doctor-fields').style.display = 'none';
            
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
    
    // Show modal with user data when Edit button is clicked
    editBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const userId = this.dataset.id;
            
            // Set modal title
            document.getElementById('modal-title').textContent = 'Edit User';
            
            // Show or hide doctor fields based on user role
            const row = this.closest('tr');
            const role = row.children[3].textContent.trim().toLowerCase();
            
            if (role === 'doctor') {
                document.querySelector('.doctor-fields').style.display = 'block';
                document.getElementById('user-role').value = 'doctor';
            } else {
                document.querySelector('.doctor-fields').style.display = 'none';
                document.getElementById('user-role').value = role;
            }
            
            // Fill form with user data
            const userName = row.querySelector('.user-info span').textContent;
            const userEmail = row.children[2].textContent;
            const userStatus = row.querySelector('.status-badge').classList.contains('active') ? 'active' : 'inactive';
            
            document.getElementById('user-name').value = userName;
            document.getElementById('user-email').value = userEmail;
            document.getElementById('user-status').value = userStatus;
            
            // Show password field as empty
            document.getElementById('user-password').value = '';
            
            // Show modal
            modal.classList.add('show');
        });
    });
    
    // Handle form submission
    const form = document.getElementById('user-form');
    form.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Get form data
        const formData = {
            name: document.getElementById('user-name').value,
            email: document.getElementById('user-email').value,
            phone: document.getElementById('user-phone').value,
            role: document.getElementById('user-role').value,
            password: document.getElementById('user-password').value,
            status: document.getElementById('user-status').value
        };
        
        // Get doctor-specific data if role is doctor
        if (formData.role === 'doctor') {
            formData.specialty = document.getElementById('doctor-specialty').value;
            formData.license = document.getElementById('doctor-license').value;
        }
        
        // Show success message
        showToast('User saved successfully');
        
        // Close modal
        modal.classList.remove('show');
    });
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
                    showToast(`View profile for user ${userId}`);
                    break;
                case 'edit':
                    // Find and trigger click on edit button
                    const editBtn = document.querySelector(`.edit-btn[data-id="${userId}"]`);
                    if (editBtn) {
                        editBtn.click();
                    }
                    break;
                case 'reset-password':
                    showToast(`Password reset link sent for user ${userId}`);
                    break;
                case 'disable':
                    showToast(`User ${userId} has been disabled`);
                    break;
                case 'delete':
                    if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                        showToast(`User ${userId} has been deleted`);
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
    
    if (selectAll) {
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

// Function to setup role-specific fields in form
function setupRoleFields() {
    const roleSelect = document.getElementById('user-role');
    const doctorFields = document.querySelector('.doctor-fields');
    
    if (roleSelect && doctorFields) {
        roleSelect.addEventListener('change', function() {
            if (this.value === 'doctor') {
                doctorFields.style.display = 'block';
            } else {
                doctorFields.style.display = 'none';
            }
        });
    }
}

// Function to setup password toggle
function setupPasswordToggle() {
    const passwordToggle = document.querySelector('.password-toggle');
    const passwordInput = document.getElementById('user-password');
    
    if (passwordToggle && passwordInput) {
        passwordToggle.addEventListener('click', function() {
            const type = passwordInput.type;
            
            if (type === 'password') {
                passwordInput.type = 'text';
                this.innerHTML = '<i class="bx bx-show"></i>';
            } else {
                passwordInput.type = 'password';
                this.innerHTML = '<i class="bx bx-hide"></i>';
            }
        });
    }
}

// Function to setup search filtering
function setupSearch() {
    const searchInput = document.querySelector('.search-bar input');
    const rows = document.querySelectorAll('.users-table tbody tr');
    
    if (searchInput && rows.length > 0) {
        searchInput.addEventListener('keyup', function() {
            const searchTerm = this.value.toLowerCase();
            
            rows.forEach(row => {
                const name = row.querySelector('.user-info span').textContent.toLowerCase();
                const email = row.children[2].textContent.toLowerCase();
                const role = row.children[3].textContent.toLowerCase();
                
                if (name.includes(searchTerm) || email.includes(searchTerm) || role.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
}

// Function to setup filters
function setupFilters() {
    const roleFilter = document.getElementById('role-filter');
    const statusFilter = document.getElementById('status-filter');
    const rows = document.querySelectorAll('.users-table tbody tr');
    
    if (roleFilter && statusFilter && rows.length > 0) {
        // Function to apply filters
        const applyFilters = () => {
            const selectedRole = roleFilter.value;
            const selectedStatus = statusFilter.value;
            
            rows.forEach(row => {
                const role = row.children[3].textContent.trim().toLowerCase();
                const status = row.querySelector('.status-badge').classList.contains('active') ? 'active' : 'inactive';
                
                const roleMatch = selectedRole === 'all' || role === selectedRole;
                const statusMatch = selectedStatus === 'all' || status === selectedStatus;
                
                if (roleMatch && statusMatch) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        };
        
        // Add event listeners
        roleFilter.addEventListener('change', applyFilters);
        statusFilter.addEventListener('change', applyFilters);
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