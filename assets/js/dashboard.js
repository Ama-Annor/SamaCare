document.addEventListener('DOMContentLoaded', function() {
    // Mobile sidebar toggle
    const menuToggle = document.querySelector('.menu-toggle');
    const closeSidebar = document.querySelector('.close-sidebar');
    const sidebar = document.querySelector('.sidebar');

    if (menuToggle) {
        menuToggle.addEventListener('click', function() {
            sidebar.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent scrolling when sidebar is open
        });
    }

    if (closeSidebar) {
        closeSidebar.addEventListener('click', function() {
            sidebar.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        });
    }

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (!sidebar || !menuToggle) return;

        const isSidebarActive = sidebar.classList.contains('active');
        const clickedInsideSidebar = sidebar.contains(event.target);
        const clickedOnMenuToggle = menuToggle.contains(event.target);

        if (isSidebarActive && !clickedInsideSidebar && !clickedOnMenuToggle) {
            sidebar.classList.remove('active');
            document.body.style.overflow = '';
        }
    });

    // User dropdown and notification button - show toast messages
    const userBtn = document.querySelector('.user-btn');
    const notificationBtn = document.querySelector('.notification-btn');

    if (userBtn) {
        userBtn.addEventListener('click', function() {
            showToast('User profile options would be implemented with backend integration');
        });
    }

    if (notificationBtn) {
        notificationBtn.addEventListener('click', function() {
            showToast('Notifications feature would be implemented with backend integration');
        });
    }

    // Add Appointment interaction
    const addAppointmentBtn = document.querySelector('.add-appointment');
    if (addAppointmentBtn) {
        addAppointmentBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Appointment scheduling would redirect to appointments page');
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

    // Initialize health metric charts - this is our main function to render charts
    initializeHealthMetricCharts();
});

// Function to initialize health metric charts based on real data from PHP
function initializeHealthMetricCharts() {
    // Find all chart data elements injected by PHP
    const chartDataElements = document.querySelectorAll('script[id^="chart-data-"]');

    chartDataElements.forEach(dataElement => {
        // Extract metric ID from the element ID
        const metricId = dataElement.id.replace('chart-data-', '');
        const chartCanvas = document.getElementById('chart-' + metricId);

        if (!chartCanvas) return;

        try {
            // Parse the JSON data injected by PHP
            const chartData = JSON.parse(dataElement.textContent);

            // Configure chart colors based on the trend
            let primaryColor, secondaryColor, backgroundColor, secondaryBgColor;

            switch(chartData.trend) {
                case 'positive':
                    primaryColor = '#2ecc71'; // Green for positive trends
                    backgroundColor = 'rgba(46, 204, 113, 0.1)';
                    secondaryColor = '#27ae60';
                    secondaryBgColor = 'rgba(39, 174, 96, 0.1)';
                    break;
                case 'negative':
                    primaryColor = '#e74c3c'; // Red for negative trends
                    backgroundColor = 'rgba(231, 76, 60, 0.1)';
                    secondaryColor = '#c0392b';
                    secondaryBgColor = 'rgba(192, 57, 43, 0.1)';
                    break;
                default:
                    primaryColor = '#4c84ff'; // Blue for neutral trends
                    backgroundColor = 'rgba(76, 132, 255, 0.1)';
                    secondaryColor = '#3498db';
                    secondaryBgColor = 'rgba(52, 152, 219, 0.1)';
            }

            // Create datasets based on metric type
            const datasets = [];

            // Primary dataset (always present)
            datasets.push({
                label: chartData.name,
                data: chartData.data,
                borderColor: primaryColor,
                backgroundColor: backgroundColor,
                tension: 0.3,
                fill: true,
                borderWidth: 2,
                pointRadius: 0,
                pointHoverRadius: 5,
                pointHoverBackgroundColor: primaryColor,
                pointHoverBorderColor: '#fff',
                pointHoverBorderWidth: 2
            });

            // Blood pressure has dual values (systolic/diastolic)
            if (chartData.hasDualValues && chartData.data2 && chartData.data2.length > 0) {
                datasets.push({
                    label: 'Diastolic',
                    data: chartData.data2,
                    borderColor: secondaryColor,
                    backgroundColor: secondaryBgColor,
                    tension: 0.3,
                    fill: true,
                    borderWidth: 2,
                    pointRadius: 0,
                    pointHoverRadius: 5,
                    pointHoverBackgroundColor: secondaryColor,
                    pointHoverBorderColor: '#fff',
                    pointHoverBorderWidth: 2
                });
            }

            // Initialize Chart.js
            new Chart(chartCanvas, {
                type: 'line',
                data: {
                    labels: chartData.labels,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: chartData.hasDualValues,
                            position: 'top',
                            labels: {
                                boxWidth: 12,
                                usePointStyle: true,
                                padding: 20,
                                font: {
                                    size: 10
                                }
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            backgroundColor: '#333',
                            titleColor: '#fff',
                            bodyColor: '#fff',
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            },
                            padding: 10,
                            cornerRadius: 4,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += context.parsed.y + (chartData.unit ? ' ' + chartData.unit : '');
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            display: false,
                            beginAtZero: false,
                            suggestedMin: Math.min(...chartData.data) * 0.9,
                            suggestedMax: Math.max(...chartData.data) * 1.1
                        },
                        x: {
                            display: false
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    elements: {
                        line: {
                            borderJoinStyle: 'round'
                        }
                    }
                }
            });

            // Remove placeholder class
            const chartContainer = document.getElementById('chart-container-' + metricId);
            if (chartContainer) {
                chartContainer.classList.remove('chart-placeholder');
            }

        } catch (error) {
            console.error('Error initializing chart for metric ID ' + metricId + ':', error);
        }
    });

    // Update the metrics grid layout since we only have two metrics
    const metricsGrid = document.querySelector('.metrics-grid');
    if (metricsGrid && metricsGrid.children.length === 2) {
        metricsGrid.style.gridTemplateColumns = 'repeat(2, 1fr)';

        // Make each metric card wider
        const metricCards = metricsGrid.querySelectorAll('.metric-card');
        metricCards.forEach(card => {
            card.style.maxWidth = '100%';

            // Make charts taller for better visibility
            const chartContainer = card.querySelector('[id^="chart-container-"]');
            if (chartContainer) {
                chartContainer.style.height = '120px';
            }
        });
    }
}

// Function to format time ago (e.g., "Today", "3 days ago")
function formatTimeAgo(dateString) {
    const date = new Date(dateString);
    const now = new Date();

    // Check if same day
    if (date.toDateString() === now.toDateString()) {
        return 'Today';
    }

    // Calculate days difference
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays === 1) {
        return 'Yesterday';
    } else {
        return `${diffDays} days ago`;
    }
}