document.addEventListener('DOMContentLoaded', function() {
    // Create charts for the admin dashboard after a small delay to ensure containers are rendered
    setTimeout(() => {
        createAdminCharts();
    }, 100);

    // Handle dropdown toggles
    setupDropdowns();

    // Handle mobile menu toggle
    setupMobileMenu();
});

// Function to create charts for the admin dashboard
function createAdminCharts() {
    // Patient Growth Chart
    createPatientGrowthChart();

    // Appointment Distribution Chart
    createAppointmentDistributionChart();
}

// Function to create the patient growth chart
function createPatientGrowthChart() {
    const ctx = document.getElementById('user-growth-chart');

    if (!ctx) return;

    // Use data from PHP instead of hardcoded data
    // If patientGrowthData is not defined, fall back to sample data
    const data = typeof patientGrowthData !== 'undefined' ? patientGrowthData : [
        { month: 'Jan', patients: 1850 },
        { month: 'Feb', patients: 1920 },
        { month: 'Mar', patients: 2050 },
        { month: 'Apr', patients: 2180 },
        { month: 'May', patients: 2320 },
        { month: 'Jun', patients: 2543 }
    ];

    // Chart dimensions - ensure we have minimum dimensions even if container is not fully rendered
    const width = ctx.clientWidth || 600;
    const height = ctx.clientHeight || 300;
    const padding = { top: 20, right: 20, bottom: 40, left: 50 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    // Create SVG for the chart with proper viewBox
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    // Create scales - ensure we handle potential division by zero
    const dataPoints = data.length;
    const xScale = dataPoints > 1 ? chartWidth / (dataPoints - 1) : chartWidth;
    const yMax = Math.max(...data.map(d => d.patients));
    // Add a small buffer to prevent divide by zero and to give some space at top
    const yScale = yMax ? chartHeight / (yMax * 1.1) : chartHeight;

    // Create group for chart elements
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${padding.left}, ${padding.top})`);

    // Add Y-axis
    const yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    yAxis.setAttribute('x1', 0);
    yAxis.setAttribute('y1', 0);
    yAxis.setAttribute('x2', 0);
    yAxis.setAttribute('y2', chartHeight);
    yAxis.setAttribute('stroke', '#d1d5db');
    yAxis.setAttribute('stroke-width', '1');
    chartGroup.appendChild(yAxis);

    // Add X-axis
    const xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    xAxis.setAttribute('x1', 0);
    xAxis.setAttribute('y1', chartHeight);
    xAxis.setAttribute('x2', chartWidth);
    xAxis.setAttribute('y2', chartHeight);
    xAxis.setAttribute('stroke', '#d1d5db');
    xAxis.setAttribute('stroke-width', '1');
    chartGroup.appendChild(xAxis);

    // Add Y-axis grid lines and labels
    for (let i = 0; i <= 5; i++) {
        const yValue = Math.round(yMax * i / 5);
        const y = chartHeight - yValue * yScale;

        // Grid line
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', 0);
        line.setAttribute('y1', y);
        line.setAttribute('x2', chartWidth);
        line.setAttribute('y2', y);
        line.setAttribute('stroke', 'rgba(209, 213, 219, 0.5)');
        line.setAttribute('stroke-width', '1');
        chartGroup.appendChild(line);

        // Y-axis label
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', -10);
        text.setAttribute('y', y + 5);
        text.setAttribute('text-anchor', 'end');
        text.setAttribute('fill', '#6b7280');
        text.setAttribute('font-size', '12');
        text.textContent = yValue.toLocaleString();
        chartGroup.appendChild(text);
    }

    // Create path for the area
    const areaPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    // Generate area path data
    let areaPathData = '';

    if (data.length > 0) {
        areaPathData = `M0,${chartHeight - data[0].patients * yScale}`;

        for (let i = 1; i < data.length; i++) {
            areaPathData += ` L${i * xScale},${chartHeight - data[i].patients * yScale}`;
        }

        // Close the path
        areaPathData += ` L${(data.length - 1) * xScale},${chartHeight} L0,${chartHeight} Z`;
    }

    areaPath.setAttribute('d', areaPathData);
    areaPath.setAttribute('fill', 'rgba(74, 222, 128, 0.1)');
    chartGroup.appendChild(areaPath);

    // Create path for the line
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    // Generate path data
    let pathData = '';

    if (data.length > 0) {
        pathData = `M0,${chartHeight - data[0].patients * yScale}`;

        for (let i = 1; i < data.length; i++) {
            pathData += ` L${i * xScale},${chartHeight - data[i].patients * yScale}`;
        }
    }

    path.setAttribute('d', pathData);
    path.setAttribute('stroke', '#4ade80');
    path.setAttribute('stroke-width', '3');
    path.setAttribute('fill', 'none');
    chartGroup.appendChild(path);

    // Add X-axis labels and data points
    data.forEach((d, i) => {
        // X-axis label
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', i * xScale);
        text.setAttribute('y', chartHeight + 20);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#6b7280');
        text.setAttribute('font-size', '12');
        text.textContent = d.month;
        chartGroup.appendChild(text);

        // Data point
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', i * xScale);
        circle.setAttribute('cy', chartHeight - d.patients * yScale);
        circle.setAttribute('r', '4');
        circle.setAttribute('fill', '#4ade80');
        circle.setAttribute('stroke', 'white');
        circle.setAttribute('stroke-width', '2');

        // Add tooltip event
        circle.addEventListener('mouseover', function() {
            const tooltip = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            tooltip.setAttribute('class', 'tooltip');

            const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            tooltipRect.setAttribute('x', i * xScale - 50);
            tooltipRect.setAttribute('y', chartHeight - d.patients * yScale - 40);
            tooltipRect.setAttribute('width', '100');
            tooltipRect.setAttribute('height', '30');
            tooltipRect.setAttribute('rx', '5');
            tooltipRect.setAttribute('fill', 'rgba(0, 0, 0, 0.8)');

            const tooltipText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tooltipText.setAttribute('x', i * xScale);
            tooltipText.setAttribute('y', chartHeight - d.patients * yScale - 20);
            tooltipText.setAttribute('text-anchor', 'middle');
            tooltipText.setAttribute('fill', 'white');
            tooltipText.setAttribute('font-size', '12');
            tooltipText.textContent = `${d.month}: ${d.patients.toLocaleString()}`;

            tooltip.appendChild(tooltipRect);
            tooltip.appendChild(tooltipText);
            chartGroup.appendChild(tooltip);
        });

        circle.addEventListener('mouseout', function() {
            const tooltip = chartGroup.querySelector('.tooltip');
            if (tooltip) {
                chartGroup.removeChild(tooltip);
            }
        });

        chartGroup.appendChild(circle);
    });

    svg.appendChild(chartGroup);

    // Add SVG to the chart container
    ctx.innerHTML = '';
    ctx.appendChild(svg);
}

// Function to create the appointment distribution chart
function createAppointmentDistributionChart() {
    const ctx = document.getElementById('appointment-distribution-chart');

    if (!ctx) return;

    // Use data from PHP instead of hardcoded data
    // If appointmentDistributionData is not defined, fall back to sample data
    const data = typeof appointmentDistributionData !== 'undefined' ? appointmentDistributionData : [
        { category: 'General Checkup', count: 45, color: '#4ade80' },
        { category: 'Specialist Consultation', count: 25, color: '#60a5fa' },
        { category: 'Dental Care', count: 30, color: '#a78bfa' },
        { category: 'Laboratory Tests', count: 20, color: '#f97316' },
        { category: 'Vaccination', count: 15, color: '#f43f5e' }
    ];

    // Chart dimensions
    const width = ctx.clientWidth || 600;
    const height = ctx.clientHeight || 300;
    const radius = Math.min(width, height) / 2 - 60;

    // Create SVG for the chart with proper viewBox
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');

    // Create group for chart elements
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${width / 2}, ${height / 2})`);

    // Calculate total for percentages
    const total = data.reduce((sum, d) => sum + d.count, 0);

    // Only create pie if we have data and total > 0
    if (data.length > 0 && total > 0) {
        // Create pie chart
        let startAngle = 0;
        data.forEach((d, i) => {
            const portion = d.count / total;
            const angle = portion * Math.PI * 2;
            const endAngle = startAngle + angle;

            // Calculate path points
            const x1 = radius * Math.cos(startAngle - Math.PI / 2);
            const y1 = radius * Math.sin(startAngle - Math.PI / 2);
            const x2 = radius * Math.cos(endAngle - Math.PI / 2);
            const y2 = radius * Math.sin(endAngle - Math.PI / 2);

            // Create path for the slice
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            // Generate path data for slice
            const largeArcFlag = angle > Math.PI ? 1 : 0;
            const pathData = `M0,0 L${x1},${y1} A${radius},${radius} 0 ${largeArcFlag},1 ${x2},${y2} Z`;

            path.setAttribute('d', pathData);
            path.setAttribute('fill', d.color);

            // Add hover effect
            path.addEventListener('mouseover', function() {
                path.setAttribute('stroke', 'white');
                path.setAttribute('stroke-width', '2');

                // Show tooltip
                const tooltipGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                tooltipGroup.setAttribute('class', 'tooltip');

                // Position tooltip near the slice
                const midAngle = startAngle + angle / 2;
                const tooltipX = (radius / 2) * Math.cos(midAngle - Math.PI / 2);
                const tooltipY = (radius / 2) * Math.sin(midAngle - Math.PI / 2);

                const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                tooltipRect.setAttribute('x', tooltipX - 60);
                tooltipRect.setAttribute('y', tooltipY - 30);
                tooltipRect.setAttribute('width', '120');
                tooltipRect.setAttribute('height', '40');
                tooltipRect.setAttribute('rx', '5');
                tooltipRect.setAttribute('fill', 'rgba(0, 0, 0, 0.8)');

                const tooltipText1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tooltipText1.setAttribute('x', tooltipX);
                tooltipText1.setAttribute('y', tooltipY - 10);
                tooltipText1.setAttribute('text-anchor', 'middle');
                tooltipText1.setAttribute('fill', 'white');
                tooltipText1.setAttribute('font-size', '12');
                tooltipText1.textContent = d.category;

                const tooltipText2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tooltipText2.setAttribute('x', tooltipX);
                tooltipText2.setAttribute('y', tooltipY + 10);
                tooltipText2.setAttribute('text-anchor', 'middle');
                tooltipText2.setAttribute('fill', 'white');
                tooltipText2.setAttribute('font-size', '12');
                tooltipText2.textContent = `${d.count} (${Math.round(portion * 100)}%)`;

                tooltipGroup.appendChild(tooltipRect);
                tooltipGroup.appendChild(tooltipText1);
                tooltipGroup.appendChild(tooltipText2);

                chartGroup.appendChild(tooltipGroup);
            });

            path.addEventListener('mouseout', function() {
                path.setAttribute('stroke', 'none');

                // Remove tooltip
                const tooltip = chartGroup.querySelector('.tooltip');
                if (tooltip) {
                    chartGroup.removeChild(tooltip);
                }
            });

            chartGroup.appendChild(path);
            startAngle = endAngle;
        });
    } else {
        // Show "No data" message if we don't have data
        const noDataText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        noDataText.setAttribute('text-anchor', 'middle');
        noDataText.setAttribute('dominant-baseline', 'middle');
        noDataText.setAttribute('fill', '#6b7280');
        noDataText.setAttribute('font-size', '16');
        noDataText.textContent = 'No appointment data available';
        chartGroup.appendChild(noDataText);
    }

    // Add legend items
    data.forEach((d, i) => {
        const legendItem = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        const legendX = -width / 2 + 30;
        const legendY = height / 2 - 120 + i * 25;

        legendItem.setAttribute('transform', `translate(${legendX}, ${legendY})`);

        const legendColor = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
        legendColor.setAttribute('width', '12');
        legendColor.setAttribute('height', '12');
        legendColor.setAttribute('fill', d.color);
        legendColor.setAttribute('rx', '2');

        const legendText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        legendText.setAttribute('x', '20');
        legendText.setAttribute('y', '10');
        legendText.setAttribute('font-size', '12');
        legendText.setAttribute('fill', '#6b7280');
        legendText.textContent = `${d.category} (${d.count})`;

        legendItem.appendChild(legendColor);
        legendItem.appendChild(legendText);

        svg.appendChild(legendItem);
    });

    svg.appendChild(chartGroup);

    // Add SVG to the chart container
    ctx.innerHTML = '';
    ctx.appendChild(svg);
}

// Function to setup dropdowns
function setupDropdowns() {
    const userBtn = document.querySelector('.user-btn');

    if (userBtn) {
        userBtn.addEventListener('click', function() {
            // Toggle user dropdown
            showToast('User menu opened');
        });
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