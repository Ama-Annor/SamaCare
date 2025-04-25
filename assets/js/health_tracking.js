// Update this in health_tracking.js
document.addEventListener('DOMContentLoaded', function() {
    // Create simple charts for the metric cards
    createSimpleCharts();

    // ====== Add New Measurement Modal ======
    const addNewMeasurementBtn = document.querySelector('.action-left .primary-btn') ||
        document.getElementById('addNewMeasurementBtn');
    const addReadingBtns = document.querySelectorAll('.add-reading-btn');
    const addReadingForm = document.getElementById('add-reading-form');
    const closeFormBtn = document.querySelector('.close-form');
    const cancelBtn = document.querySelector('.cancel-btn');
    const addFirstReadingBtn = document.getElementById('addFirstReadingBtn');

    // Function to show the add reading form
    function showAddReadingForm(metricType) {
        if (addReadingForm) {
            // Set default to 1 (blood pressure) if not specified
            const metricTypeId = parseInt(metricType) || 1;

            // Update form title based on metric type
            const formTitle = addReadingForm.querySelector('.form-header h3');
            if (formTitle) {
                formTitle.textContent = metricTypeId === 1 ?
                    'Add Blood Pressure Reading' : 'Add Weight Reading';
            }

            // Update the hidden input field value
            const formMetricType = document.getElementById('form-metric-type');
            if (formMetricType) {
                formMetricType.value = metricTypeId;
            }

            // Show/hide fields based on metric type
            const bpFields = document.getElementById('blood-pressure-fields');
            const weightFields = document.getElementById('weight-fields');

            if (bpFields) bpFields.style.display = metricTypeId === 1 ? 'block' : 'none';
            if (weightFields) weightFields.style.display = metricTypeId === 2 ? 'block' : 'none';

            // Reset form fields
            addReadingForm.querySelectorAll('input[type="number"]').forEach(input => {
                input.value = '';
            });

            const notesField = addReadingForm.querySelector('#notes');
            if (notesField) notesField.value = '';

            // Show the form
            addReadingForm.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
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
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const metricType = this.getAttribute('data-metric');
                showAddReadingForm(metricType);
            });
        });
    }

    // Show form when clicking the main "Add New Measurement" button
    if (addNewMeasurementBtn) {
        addNewMeasurementBtn.addEventListener('click', function(e) {
            e.preventDefault();
            // Default to blood pressure
            showAddReadingForm('1');
        });
    }

    // Add first reading button for empty state
    if (addFirstReadingBtn) {
        addFirstReadingBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showAddReadingForm('1');
        });
    }

    // Close form when clicking the X button
    if (closeFormBtn) {
        closeFormBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideAddReadingForm();
        });
    }

    // Close form when clicking the Cancel button
    if (cancelBtn) {
        cancelBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideAddReadingForm();
        });
    }

    // Ensure form submission works correctly
    const readingForm = document.querySelector('#add-reading-form form');
    if (readingForm) {
        readingForm.addEventListener('submit', function(e) {
            // Don't prevent default - we want the form to submit
            console.log('Form submitted');
            // Hide form after submission
            setTimeout(hideAddReadingForm, 100);
        });
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
        dateFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showDateFilterModal();
        });
    }

    // Close modal when clicking the X button
    if (closeDateFilterBtn) {
        closeDateFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideDateFilterModal();
        });
    }

    // Close modal when clicking the Cancel button
    if (cancelDateFilterBtn) {
        cancelDateFilterBtn.addEventListener('click', function(e) {
            e.preventDefault();
            hideDateFilterModal();
        });
    }

    // Apply filter button - let the form submit normally
    if (applyDateFilterBtn) {
        applyDateFilterBtn.addEventListener('click', function() {
            // Don't prevent default - we want the form to submit
            console.log('Filter form submitted');
        });
    }

    // Handle preset date buttons
    if (presetBtns.length > 0) {
        presetBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                // Remove active class from all buttons
                presetBtns.forEach(b => b.classList.remove('active'));

                // Add active class to the clicked button
                this.classList.add('active');

                // Set date range based on the preset
                const days = this.getAttribute('data-days');
                document.getElementById('date_range').value = days;

                if (days !== 'custom') {
                    const endDate = new Date();
                    const startDate = new Date();
                    startDate.setDate(startDate.getDate() - parseInt(days));

                    // Format dates for input fields: YYYY-MM-DD
                    const formatDate = (date) => {
                        const year = date.getFullYear();
                        const month = String(date.getMonth() + 1).padStart(2, '0');
                        const day = String(date.getDate()).padStart(2, '0');
                        return `${year}-${month}-${day}`;
                    };

                    document.getElementById('end_date').value = formatDate(endDate);
                    document.getElementById('start_date').value = formatDate(startDate);
                }
            });
        });
    }

    // View History functionality
    const viewHistoryBtns = document.querySelectorAll('.view-history-btn');
    const metricHistorySection = document.getElementById('metric-history');
    const closeHistoryBtn = document.querySelector('.close-history');

    if (viewHistoryBtns.length > 0 && metricHistorySection) {
        viewHistoryBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const metricType = this.getAttribute('data-metric');

                // Update the history section title based on metric type
                const historyTitle = metricHistorySection.querySelector('.card-header h3');
                if (historyTitle) {
                    historyTitle.textContent = metricType === '1' ?
                        'Blood Pressure History' : 'Weight History';
                }

                // Filter history table rows based on metric type
                document.querySelectorAll('#history-table-body tr').forEach(row => {
                    if (row.dataset.metricType === metricType || !metricType) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });

                // Show the history section
                metricHistorySection.classList.add('active');

                // Update chart if needed
                if (typeof initHistoryChart === 'function') {
                    initHistoryChart(metricType);
                }

                // Scroll to the history section
                metricHistorySection.scrollIntoView({ behavior: 'smooth' });
            });
        });
    }

    // Close history section
    if (closeHistoryBtn && metricHistorySection) {
        closeHistoryBtn.addEventListener('click', function(e) {
            e.preventDefault();
            metricHistorySection.classList.remove('active');
        });
    }

    // Add Escape key functionality to close modals
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            hideAddReadingForm();
            hideDateFilterModal();
            if (metricHistorySection) {
                metricHistorySection.classList.remove('active');
            }
        }
    });

    // Toggle mobile sidebar
    const menuToggle = document.querySelector('.menu-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle && sidebar) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
        });
    }

    if (closeSidebar && sidebar) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
        });
    }

    // Auto-hide toast messages after 3 seconds
    setTimeout(() => {
        document.querySelectorAll('.toast-message').forEach(toast => {
            toast.classList.add('fade-out');
            setTimeout(() => toast.remove(), 500);
        });
    }, 3000);
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
    svg.setAttribute('viewBox', '0 0 100 100');
    svg.setAttribute('preserveAspectRatio', 'none');

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

// Simple toast message function
function showToast(message) {
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