document.addEventListener('DOMContentLoaded', function() {
    // Category filtering functionality
    const categoryTabs = document.querySelectorAll('.category-tab');
    const recordItems = document.querySelectorAll('.record-item');
    
    categoryTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            // Update active tab
            categoryTabs.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            
            const category = this.getAttribute('data-category');
            
            // Filter records
            recordItems.forEach(item => {
                if (category === 'all' || item.getAttribute('data-category') === category) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    });

    
    // View toggle (list/grid/timeline view)
    const viewToggle = document.querySelector('.view-toggle');
    const recordsContainer = document.querySelector('.records-container');
    
    if (viewToggle) {
        const listIcon = viewToggle.querySelector('.bx-list-ul');
        const gridIcon = viewToggle.querySelector('.bx-grid-alt');
        const timelineIcon = viewToggle.querySelector('.bx-time-five');
        
        viewToggle.addEventListener('click', function(e) {
            const clickedIcon = e.target.closest('i');
            
            if (clickedIcon) {
                // Remove active class from all icons
                listIcon.classList.remove('active');
                gridIcon.classList.remove('active');
                if (timelineIcon) timelineIcon.classList.remove('active');
                
                // Add active class to clicked icon
                clickedIcon.classList.add('active');
                
                // Update view
                if (clickedIcon === listIcon) {
                    recordsContainer.classList.remove('grid-view', 'timeline-view');
                } else if (clickedIcon === gridIcon) {
                    recordsContainer.classList.add('grid-view');
                    recordsContainer.classList.remove('timeline-view');
                } else if (clickedIcon === timelineIcon) {
                    recordsContainer.classList.add('timeline-view');
                    recordsContainer.classList.remove('grid-view');
                }
            }
        });
    }
    
    // Search functionality (basic, client-side)
    const searchInput = document.querySelector('.search-bar input');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            recordItems.forEach(item => {
                const recordTitle = item.querySelector('.record-title').textContent.toLowerCase();
                const recordDescription = item.querySelector('.record-description').textContent.toLowerCase();
                
                if (recordTitle.includes(searchTerm) || recordDescription.includes(searchTerm)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        });
    }
    
    // Add Record button - show a toast message instead of modal
    const addRecordBtn = document.querySelector('.primary-btn');
    
    if (addRecordBtn) {
        addRecordBtn.addEventListener('click', function() {
            showToast('This feature would require backend integration');
        });
    }
    
    // Action buttons on records - show toast messages
    const actionButtons = document.querySelectorAll('.record-actions .action-btn');
    
    if (actionButtons.length > 0) {
        actionButtons.forEach(button => {
            button.addEventListener('click', function() {
                const action = this.getAttribute('title');
                showToast(`${action} functionality would require backend integration`);
            });
        });
    }
    
    // Simple toast message function
    function showToast(message) {
        // Create toast element if it doesn't exist
        let toast = document.querySelector('.toast-message');
        
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-message';
            document.body.appendChild(toast);
            
            // Add style for the toast
            const style = document.createElement('style');
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
        
        // Set message and show toast
        toast.textContent = message;
        toast.classList.add('show');
        
        // Hide toast after 3 seconds
        setTimeout(() => {
            toast.classList.remove('show');
        }, 3000);
    }
    
    // Pagination buttons - for demo purposes only
    const paginationButtons = document.querySelectorAll('.pagination-btn');
    
    if (paginationButtons.length > 0) {
        paginationButtons.forEach(button => {
            if (!button.hasAttribute('disabled')) {
                button.addEventListener('click', function() {
                    // Update active button
                    paginationButtons.forEach(b => b.classList.remove('active'));
                    this.classList.add('active');
                    
                    showToast('Pagination would load additional records from the backend');
                });
            }
        });
    }
    
    // Add timeline date elements if in timeline view
    function setupTimelineView() {
        if (recordsContainer.classList.contains('timeline-view')) {
            recordItems.forEach(item => {
                // Check if date element already exists
                if (!item.querySelector('.record-date-marker')) {
                    const dateText = item.querySelector('.record-meta .record-date').textContent.replace('Jun', 'June').replace('Apr', 'April').replace('Jan', 'January').replace('Aug', 'August');
                    const dateSpan = document.createElement('span');
                    dateSpan.className = 'record-date-marker';
                    dateSpan.textContent = dateText.replace(/<[^>]*>/g, '').trim();
                    item.appendChild(dateSpan);
                }
            });
        }
    }
    
    // Call timeline setup when view changes
    viewToggle.addEventListener('click', setupTimelineView);
    
    // Notification button functionality
    const notificationBtn = document.querySelector('.notification-btn');
    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            showToast('Notifications feature would be implemented with backend integration');
        });
    }
    
    // User dropdown functionality
    const userBtn = document.querySelector('.user-btn');
    if (userBtn) {
        userBtn.addEventListener('click', function() {
            showToast('User profile options would be implemented with backend integration');
        });
    }
});