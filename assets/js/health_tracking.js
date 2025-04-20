document.addEventListener('DOMContentLoaded', function() {
    // Create simple charts for the metric cards
    createSimpleCharts();
    
    // ====== Add New Measurement Modal ======
    // Fix to make sure the Add New Measurement button is correctly connected
    const addNewMeasurementBtn = document.querySelector('.action-left .primary-btn');
    // Add ID to the button if not present
    if (addNewMeasurementBtn && !addNewMeasurementBtn.id) {
        addNewMeasurementBtn.id = 'addNewMeasurementBtn';
    }
    
    const addReadingBtns = document.querySelectorAll('.add-reading-btn');
    const addReadingForm = document.getElementById('add-reading-form');
    const closeFormBtn = document.querySelector('.close-form');
    const cancelBtn = document.querySelector('.cancel-btn');
    const saveBtn = document.querySelector('.primary-btn[name="add_reading"]');

    // Function to show the add reading form
    function showAddReadingForm(metricType) {
        if (addReadingForm) {
            // Convert string to number if it's not already
            const metricTypeId = parseInt(metricType);

            // Update form title based on metric type
            const formTitle = addReadingForm.querySelector('.form-header h3');
            if (formTitle) {
                formTitle.textContent = metricTypeId === 1 ?
                    'Add Blood Pressure Reading' : 'Add Weight Reading';
            }

            // Update the hidden input field value
            document.getElementById('form-metric-type').value = metricTypeId;

            // Show/hide fields based on metric type
            document.getElementById('blood-pressure-fields').style.display =
                metricTypeId === 1 ? 'block' : 'none';
            document.getElementById('weight-fields').style.display =
                metricTypeId === 2 ? 'block' : 'none';

            // Show the form
            addReadingForm.classList.add('active');
        }
    }
    
    // Function to hide the add reading form
    function hideAddReadingForm() {
        if (addReadingForm) {
            addReadingForm.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
    }
    
    // Show form when clicking the "+" buttons in metric cards
    if (addReadingBtns && addReadingBtns.length > 0) {
        addReadingBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const metricType = this.getAttribute('data-metric');
                showAddReadingForm(metricType);
            });
        });
    }
    
    // Show form when clicking the main "Add New Measurement" button
    if (addNewMeasurementBtn) {
        addNewMeasurementBtn.addEventListener('click', function() {
            // Default to blood pressure
            showAddReadingForm('blood-pressure');
        });
    }
    
    // Close form when clicking the X button
    if (closeFormBtn) {
        closeFormBtn.addEventListener('click', hideAddReadingForm);
    }
    
    // Close form when clicking the Cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', hideAddReadingForm);
    }

    
    // ====== Date Filter Modal ======
    const dateFilterBtn = document.querySelector('.date-filter .secondary-btn');
    const dateFilterModal = document.getElementById('date-filter-modal');
    const closeDateFilterBtn = document.getElementById('close-date-filter');
    const cancelDateFilterBtn = document.getElementById('cancel-date-filter');
    const applyDateFilterBtn = document.getElementById('apply-date-filter');
    const presetBtns = document.querySelectorAll('.preset-btn');
    
    // Function to show the date filter modal
    function showDateFilterModal() {
        if (dateFilterModal) {
            dateFilterModal.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            
            // Set default dates (last 30 days)
            const endDate = new Date();
            const startDate = new Date();
            startDate.setDate(startDate.getDate() - 30);
            
            document.getElementById('end-date').valueAsDate = endDate;
            document.getElementById('start-date').valueAsDate = startDate;
        }
    }
    
    // Function to hide the date filter modal
    function hideDateFilterModal() {
        if (dateFilterModal) {
            dateFilterModal.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
    }
    
    // Show date filter modal when clicking the date filter button
    if (dateFilterBtn) {
        dateFilterBtn.addEventListener('click', function() {
            showDateFilterModal();
        });
    }
    
    // Close modal when clicking the X button
    if (closeDateFilterBtn) {
        closeDateFilterBtn.addEventListener('click', hideDateFilterModal);
    }
    
    // Close modal when clicking the Cancel button
    if (cancelDateFilterBtn) {
        cancelDateFilterBtn.addEventListener('click', hideDateFilterModal);
    }
    
    // Apply filter and close modal
    if (applyDateFilterBtn) {
        applyDateFilterBtn.addEventListener('click', function() {
            // Get selected date range
            const startDate = document.getElementById('start-date').value;
            const endDate = document.getElementById('end-date').value;
            
            // Update the button text to show the selected range
            if (startDate && endDate) {
                const formattedStartDate = new Date(startDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                const formattedEndDate = new Date(endDate).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                dateFilterBtn.querySelector('span').textContent = `${formattedStartDate} - ${formattedEndDate}`;
            }
            
            showToast('Date filter applied successfully');
            hideDateFilterModal();
            
            // Refresh chart data (in a real application, this would fetch new data)
            // This is just for demonstration purposes
            createSimpleCharts();
        });
    }
    
    // Handle preset date buttons
    if (presetBtns.length > 0) {
        presetBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                presetBtns.forEach(b => b.classList.remove('active'));
                
                // Add active class to the clicked button
                this.classList.add('active');
                
                // Set date range based on the preset
                const days = parseInt(this.getAttribute('data-days'));
                const endDate = new Date();
                const startDate = new Date();
                startDate.setDate(startDate.getDate() - days);
                
                document.getElementById('end-date').valueAsDate = endDate;
                document.getElementById('start-date').valueAsDate = startDate;
            });
        });
    }
    
    // View History functionality
    const viewHistoryBtns = document.querySelectorAll('.view-history-btn');
    const metricHistorySection = document.getElementById('metric-history');
    const closeHistoryBtn = document.querySelector('.close-history');
    
    if (viewHistoryBtns.length > 0 && metricHistorySection) {
        viewHistoryBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const metricType = this.getAttribute('data-metric');
                
                // Update the history section title based on metric type
                const historyTitle = metricHistorySection.querySelector('.card-header h3');
                if (historyTitle) {
                    historyTitle.textContent = metricType === 'blood-pressure' ? 
                        'Blood Pressure History' : 'Weight History';
                }
                
                // Show the history section
                metricHistorySection.classList.add('active');
                
                // Scroll to the history section
                metricHistorySection.scrollIntoView({ behavior: 'smooth' });
            });
        });
    }
    
    // Close history section
    if (closeHistoryBtn && metricHistorySection) {
        closeHistoryBtn.addEventListener('click', function() {
            metricHistorySection.classList.remove('active');
        });
    }
    
    // Simple toast message function
    window.showToast = function(message) {
        // Create toast element if it doesn't exist
        let toast = document.querySelector('.toast-message');
        
        if (!toast) {
            toast = document.createElement('div');
            toast.className = 'toast-message';
            document.body.appendChild(toast);
            
            // Add style for the toast if not already added
            if (!document.querySelector('style#toast-style')) {
                const style = document.createElement('style');
                style.id = 'toast-style';
                style.textContent = `
                    .toast-message {
                        position: fixed;
                        bottom: 20px;
                        right: 20px;
                        background-color: var(--dark-color, #333);
                        color: white;
                        padding: 12px 20px;
                        border-radius: var(--border-radius, 8px);
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
    };
    
    // Add Escape key functionality to close modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideAddReadingForm();
            hideDateFilterModal();
        }
    });
});

// Function to create simple SVG charts for metric cards
function createSimpleCharts() {
    // Blood Pressure Chart
    createSVGChart('bp-chart', [
        { x: 0, y: 60 },
        { x: 20, y: 55 },
        { x: 40, y: 50 },
        { x: 60, y: 45 },
        { x: 80, y: 40 }
    ], '#2a9d8f');
    
    // Weight Chart
    createSVGChart('weight-chart', [
        { x: 0, y: 40 },
        { x: 20, y: 42 },
        { x: 40, y: 41 },
        { x: 60, y: 43 },
        { x: 80, y: 45 }
    ], '#2a9d8f');
}

// Function to create a simple SVG line chart
function createSVGChart(elementId, dataPoints, lineColor) {
    const chartElement = document.getElementById(elementId);
    
    if (!chartElement) return;
    
    // Create SVG element
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    
    // Create path for the line
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    
    // Generate path data from points
    let pathData = `M${dataPoints[0].x},${dataPoints[0].y}`;
    
    for (let i = 1; i < dataPoints.length; i++) {
        pathData += ` L${dataPoints[i].x},${dataPoints[i].y}`;
    }
    
    path.setAttribute('d', pathData);
    path.setAttribute('stroke', lineColor);
    path.setAttribute('stroke-width', '2');
    path.setAttribute('fill', 'none');
    
    svg.appendChild(path);
    
    // Add dots at each data point
    dataPoints.forEach(point => {
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', point.x);
        circle.setAttribute('cy', point.y);
        circle.setAttribute('r', '3');
        circle.setAttribute('fill', lineColor);
        
        svg.appendChild(circle);
    });
    
    // Add SVG to the chart container
    chartElement.innerHTML = '';
    chartElement.appendChild(svg);
}

// Show/hide forms
document.getElementById('addNewMeasurementBtn').addEventListener('click', () => {
    document.getElementById('add-reading-form').classList.add('active');
});

document.querySelectorAll('.close-form, .cancel-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.add-reading-form, .date-filter-modal').forEach(el => {
            el.classList.remove('active');
        });
    });
});

// Toast messages
function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `toast-message ${type}`;
    toast.textContent = message;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Auto-hide toast messages
setTimeout(() => {
    document.querySelectorAll('.toast-message').forEach(toast => {
        toast.remove();
    });
}, 3000);

// View History functionality
document.querySelectorAll('.view-history-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        // Show history section
        document.getElementById('metric-history').classList.add('active');

        // Refresh chart with historical data
        createHistoryChart();
    });
});

// Close history section
document.querySelector('.close-history').addEventListener('click', () => {
    document.getElementById('metric-history').classList.remove('active');
});

// Chart functions
function createHistoryChart() {
    // Implement your chart logic here using Chart.js or similar
    console.log('History chart created');
}

function createMetricCharts() {
    // Create charts for metric cards
    document.querySelectorAll('.metric-chart .chart-placeholder').forEach(chart => {
        const data = [
            { x: 0, y: Math.random() * 100 },
            { x: 20, y: Math.random() * 100 },
            { x: 40, y: Math.random() * 100 },
            { x: 60, y: Math.random() * 100 },
            { x: 80, y: Math.random() * 100 }
        ];
        createSVGChart(chart.id, data, '#2a9d8f');
    });
}