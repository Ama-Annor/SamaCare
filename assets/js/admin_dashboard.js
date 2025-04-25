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
    const data = typeof patientGrowthData !== 'undefined' ? patientGrowthData : [
        { month: 'Jan', patients: 1850 },
        { month: 'Feb', patients: 1920 },
        { month: 'Mar', patients: 2050 },
        { month: 'Apr', patients: 2180 },
        { month: 'May', patients: 2320 },
        { month: 'Jun', patients: 2543 }
    ];

    // Chart dimensions with better responsive sizing
    const width = ctx.clientWidth || 600;
    const height = Math.max(300, ctx.clientHeight || 300);
    const padding = { top: 30, right: 30, bottom: 50, left: 60 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;

    // Create SVG for the chart with proper viewBox
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    svg.setAttribute('class', 'patient-growth-chart');

    // Create scales
    const dataPoints = data.length;
    const xScale = dataPoints > 1 ? chartWidth / (dataPoints - 1) : chartWidth;
    const yMax = Math.max(...data.map(d => d.patients)) * 1.2; // Add 20% for better visualization
    const yScale = yMax ? chartHeight / yMax : chartHeight;

    // Add chart title
    const title = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    title.setAttribute('x', width / 2);
    title.setAttribute('y', 15);
    title.setAttribute('text-anchor', 'middle');
    title.setAttribute('font-size', '14');
    title.setAttribute('font-weight', 'bold');
    title.setAttribute('fill', '#374151');
    title.textContent = 'Monthly Patient Growth';
    svg.appendChild(title);

    // Create group for chart elements
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${padding.left}, ${padding.top})`);

    // Add chart background
    const chartBg = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
    chartBg.setAttribute('x', 0);
    chartBg.setAttribute('y', 0);
    chartBg.setAttribute('width', chartWidth);
    chartBg.setAttribute('height', chartHeight);
    chartBg.setAttribute('fill', '#f3faf7');
    chartBg.setAttribute('rx', '5');
    chartGroup.appendChild(chartBg);

    // Add Y-axis
    const yAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    yAxis.setAttribute('x1', 0);
    yAxis.setAttribute('y1', 0);
    yAxis.setAttribute('x2', 0);
    yAxis.setAttribute('y2', chartHeight);
    yAxis.setAttribute('stroke', '#d1d5db');
    yAxis.setAttribute('stroke-width', '2');
    chartGroup.appendChild(yAxis);

    // Add X-axis
    const xAxis = document.createElementNS('http://www.w3.org/2000/svg', 'line');
    xAxis.setAttribute('x1', 0);
    xAxis.setAttribute('y1', chartHeight);
    xAxis.setAttribute('x2', chartWidth);
    xAxis.setAttribute('y2', chartHeight);
    xAxis.setAttribute('stroke', '#d1d5db');
    xAxis.setAttribute('stroke-width', '2');
    chartGroup.appendChild(xAxis);

    // Add Y-axis title
    const yAxisTitle = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    yAxisTitle.setAttribute('transform', `translate(-45, ${chartHeight/2}) rotate(-90)`);
    yAxisTitle.setAttribute('text-anchor', 'middle');
    yAxisTitle.setAttribute('font-size', '12');
    yAxisTitle.setAttribute('fill', '#6b7280');
    yAxisTitle.textContent = 'Number of Patients';
    chartGroup.appendChild(yAxisTitle);

    // Add Y-axis grid lines and labels with better spacing
    const yTickCount = 5;
    for (let i = 0; i <= yTickCount; i++) {
        const yValue = Math.round(yMax * i / yTickCount);
        const y = chartHeight - yValue * yScale;

        // Grid line with better styling
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', 0);
        line.setAttribute('y1', y);
        line.setAttribute('x2', chartWidth);
        line.setAttribute('y2', y);
        line.setAttribute('stroke', 'rgba(209, 213, 219, 0.5)');
        line.setAttribute('stroke-width', '1');
        line.setAttribute('stroke-dasharray', '4 4');
        chartGroup.appendChild(line);

        // Y-axis label with better formatting
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', -10);
        text.setAttribute('y', y + 5);
        text.setAttribute('text-anchor', 'end');
        text.setAttribute('fill', '#6b7280');
        text.setAttribute('font-size', '12');
        text.textContent = yValue.toLocaleString();
        chartGroup.appendChild(text);
    }

    // Add gradient for area fill
    const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
    const gradient = document.createElementNS('http://www.w3.org/2000/svg', 'linearGradient');
    gradient.setAttribute('id', 'patientGrowthGradient');
    gradient.setAttribute('x1', '0');
    gradient.setAttribute('y1', '0');
    gradient.setAttribute('x2', '0');
    gradient.setAttribute('y2', '1');

    const stop1 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop1.setAttribute('offset', '0%');
    stop1.setAttribute('stop-color', 'rgba(74, 222, 128, 0.6)');

    const stop2 = document.createElementNS('http://www.w3.org/2000/svg', 'stop');
    stop2.setAttribute('offset', '100%');
    stop2.setAttribute('stop-color', 'rgba(74, 222, 128, 0.1)');

    gradient.appendChild(stop1);
    gradient.appendChild(stop2);
    defs.appendChild(gradient);
    svg.appendChild(defs);

    // Create path for the area with gradient fill
    const areaPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    // Generate area path data
    let areaPathData = '';
    if (data.length > 0) {
        areaPathData = `M0,${chartHeight - data[0].patients * yScale}`;
        for (let i = 1; i < data.length; i++) {
            areaPathData += ` L${i * xScale},${chartHeight - data[i].patients * yScale}`;
        }
        areaPathData += ` L${(data.length - 1) * xScale},${chartHeight} L0,${chartHeight} Z`;
    }

    areaPath.setAttribute('d', areaPathData);
    areaPath.setAttribute('fill', 'url(#patientGrowthGradient)');
    chartGroup.appendChild(areaPath);

    // Create path for the line with smoother curve
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

    // Generate path data with Bezier curve for smoother line
    let pathData = '';
    if (data.length > 0) {
        pathData = `M0,${chartHeight - data[0].patients * yScale}`;
        for (let i = 1; i < data.length; i++) {
            const x1 = (i - 1) * xScale;
            const y1 = chartHeight - data[i-1].patients * yScale;
            const x2 = i * xScale;
            const y2 = chartHeight - data[i].patients * yScale;

            // Simple curve using control points
            const cpx1 = x1 + (x2 - x1) / 2;
            const cpy1 = y1;
            const cpx2 = x1 + (x2 - x1) / 2;
            const cpy2 = y2;

            pathData += ` C${cpx1},${cpy1} ${cpx2},${cpy2} ${x2},${y2}`;
        }
    }

    path.setAttribute('d', pathData);
    path.setAttribute('stroke', '#10b981');
    path.setAttribute('stroke-width', '3');
    path.setAttribute('fill', 'none');
    path.setAttribute('stroke-linecap', 'round');
    path.setAttribute('stroke-linejoin', 'round');
    chartGroup.appendChild(path);

    // Add X-axis labels and data points
    data.forEach((d, i) => {
        // X-axis label
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', i * xScale);
        text.setAttribute('y', chartHeight + 20);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#4b5563');
        text.setAttribute('font-size', '12');
        text.setAttribute('font-weight', 'bold');
        text.textContent = d.month;
        chartGroup.appendChild(text);

        // Data point with enhanced style
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', i * xScale);
        circle.setAttribute('cy', chartHeight - d.patients * yScale);
        circle.setAttribute('r', '6');
        circle.setAttribute('fill', '#10b981');
        circle.setAttribute('stroke', 'white');
        circle.setAttribute('stroke-width', '2');
        circle.setAttribute('class', 'data-point');

        // Add tooltip event with improved tooltip
        circle.addEventListener('mouseover', function(e) {
            // Remove any existing tooltip
            const oldTooltip = chartGroup.querySelector('.tooltip');
            if (oldTooltip) chartGroup.removeChild(oldTooltip);

            const tooltip = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            tooltip.setAttribute('class', 'tooltip');

            // Better positioned tooltip that stays within chart bounds
            let tooltipX = i * xScale;
            let tooltipY = chartHeight - d.patients * yScale - 15;

            // Create tooltip background with rounded corners
            const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            tooltipRect.setAttribute('x', tooltipX - 70);
            tooltipRect.setAttribute('y', tooltipY - 40);
            tooltipRect.setAttribute('width', '140');
            tooltipRect.setAttribute('height', '35');
            tooltipRect.setAttribute('rx', '5');
            tooltipRect.setAttribute('fill', 'rgba(17, 24, 39, 0.9)');
            tooltipRect.setAttribute('stroke', '#10b981');
            tooltipRect.setAttribute('stroke-width', '1');

            // Add tooltip pointer
            const tooltipPointer = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            tooltipPointer.setAttribute('d', `M${tooltipX},${tooltipY - 5} L${tooltipX - 5},${tooltipY - 10} L${tooltipX + 5},${tooltipY - 10} Z`);
            tooltipPointer.setAttribute('fill', 'rgba(17, 24, 39, 0.9)');

            // Tooltip text with better formatting
            const tooltipText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tooltipText.setAttribute('x', tooltipX);
            tooltipText.setAttribute('y', tooltipY - 20);
            tooltipText.setAttribute('text-anchor', 'middle');
            tooltipText.setAttribute('fill', 'white');
            tooltipText.setAttribute('font-size', '12');
            tooltipText.setAttribute('font-weight', 'bold');
            tooltipText.textContent = `${d.month}: ${d.patients.toLocaleString()} patients`;

            // Animate the tooltip appearance
            tooltipRect.setAttribute('opacity', '0');
            tooltipText.setAttribute('opacity', '0');
            tooltipPointer.setAttribute('opacity', '0');

            tooltip.appendChild(tooltipRect);
            tooltip.appendChild(tooltipPointer);
            tooltip.appendChild(tooltipText);
            chartGroup.appendChild(tooltip);

            // Simple fade-in animation
            setTimeout(() => {
                tooltipRect.setAttribute('opacity', '1');
                tooltipText.setAttribute('opacity', '1');
                tooltipPointer.setAttribute('opacity', '1');
            }, 10);

            // Highlight the current point
            circle.setAttribute('r', '8');
            circle.setAttribute('stroke-width', '3');
        });

        circle.addEventListener('mouseout', function() {
            const tooltip = chartGroup.querySelector('.tooltip');
            if (tooltip) {
                // Fade out animation
                const tooltipElements = tooltip.childNodes;
                tooltipElements.forEach(el => el.setAttribute('opacity', '0'));

                setTimeout(() => {
                    chartGroup.removeChild(tooltip);
                }, 200);
            }

            // Reset point size
            circle.setAttribute('r', '6');
            circle.setAttribute('stroke-width', '2');
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

    // Use data from PHP
    const data = typeof appointmentDistributionData !== 'undefined' ? appointmentDistributionData : [
        { category: 'General Checkup', count: 145, color: '#4ade80' },
        { category: 'Specialist Consultation', count: 98, color: '#60a5fa' },
        { category: 'Dental Care', count: 76, color: '#a78bfa' },
        { category: 'Laboratory Tests', count: 54, color: '#f97316' },
        { category: 'Vaccination', count: 42, color: '#f43f5e' }
    ];

    // Better chart dimensions
    const width = ctx.clientWidth || 600;
    const height = Math.max(350, ctx.clientHeight || 350);
    const radius = Math.min(width, height) / 3; // Smaller radius for better proportions
    const centerX = width / 2;
    const centerY = height / 2;

    // Create SVG with better viewBox
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    svg.setAttribute('viewBox', `0 0 ${width} ${height}`);
    svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    svg.setAttribute('class', 'appointment-chart');

    // Add chart title
    const title = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    title.setAttribute('x', centerX);
    title.setAttribute('y', 20);
    title.setAttribute('text-anchor', 'middle');
    title.setAttribute('font-size', '14');
    title.setAttribute('font-weight', 'bold');
    title.setAttribute('fill', '#374151');
    title.textContent = 'Appointment Types Distribution';
    svg.appendChild(title);

    // Create group for chart elements centered in SVG
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${centerX}, ${centerY})`);

    // Calculate total for percentages
    const total = data.reduce((sum, d) => sum + d.count, 0);

    // Add inner circle for better visual (donut chart style)
    const innerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    innerCircle.setAttribute('cx', 0);
    innerCircle.setAttribute('cy', 0);
    innerCircle.setAttribute('r', radius * 0.6);
    innerCircle.setAttribute('fill', 'white');
    innerCircle.setAttribute('stroke', '#e5e7eb');
    innerCircle.setAttribute('stroke-width', '1');

    // Add total count in center
    const totalText1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    totalText1.setAttribute('x', 0);
    totalText1.setAttribute('y', -10);
    totalText1.setAttribute('text-anchor', 'middle');
    totalText1.setAttribute('font-size', '14');
    totalText1.setAttribute('fill', '#6b7280');
    totalText1.textContent = 'Total';

    const totalText2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
    totalText2.setAttribute('x', 0);
    totalText2.setAttribute('y', 15);
    totalText2.setAttribute('text-anchor', 'middle');
    totalText2.setAttribute('font-size', '18');
    totalText2.setAttribute('font-weight', 'bold');
    totalText2.setAttribute('fill', '#374151');
    totalText2.textContent = total.toLocaleString();

    // Only create pie if we have data
    if (data.length > 0 && total > 0) {
        // Create pie chart with segments
        let startAngle = 0;
        data.forEach((d, i) => {
            const portion = d.count / total;
            const angle = portion * Math.PI * 2;
            const endAngle = startAngle + angle;

            // Calculate path for arc (donut segment)
            const outerRadius = radius;
            const innerRadius = radius * 0.7; // For donut effect

            const x1Outer = outerRadius * Math.cos(startAngle - Math.PI / 2);
            const y1Outer = outerRadius * Math.sin(startAngle - Math.PI / 2);
            const x2Outer = outerRadius * Math.cos(endAngle - Math.PI / 2);
            const y2Outer = outerRadius * Math.sin(endAngle - Math.PI / 2);

            const x1Inner = innerRadius * Math.cos(endAngle - Math.PI / 2);
            const y1Inner = innerRadius * Math.sin(endAngle - Math.PI / 2);
            const x2Inner = innerRadius * Math.cos(startAngle - Math.PI / 2);
            const y2Inner = innerRadius * Math.sin(startAngle - Math.PI / 2);

            // Create path for the slice
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');

            // Generate path data for donut slice
            const largeArcFlagOuter = angle > Math.PI ? 1 : 0;
            const largeArcFlagInner = angle > Math.PI ? 1 : 0;

            const pathData = `
                M ${x1Outer},${y1Outer}
                A ${outerRadius},${outerRadius} 0 ${largeArcFlagOuter},1 ${x2Outer},${y2Outer}
                L ${x1Inner},${y1Inner}
                A ${innerRadius},${innerRadius} 0 ${largeArcFlagInner},0 ${x2Inner},${y2Inner}
                Z
            `;

            path.setAttribute('d', pathData);
            path.setAttribute('fill', d.color);
            path.setAttribute('stroke', 'white');
            path.setAttribute('stroke-width', '1');
            path.setAttribute('data-category', d.category);
            path.setAttribute('data-count', d.count);
            path.setAttribute('data-percent', Math.round(portion * 100));

            // Add hover effect with animation
            path.addEventListener('mouseover', function() {
                // Highlight segment by moving it slightly outward
                const midAngle = startAngle + angle / 2;
                const moveX = Math.cos(midAngle - Math.PI / 2) * 10;
                const moveY = Math.sin(midAngle - Math.PI / 2) * 10;
                path.setAttribute('transform', `translate(${moveX}, ${moveY})`);

                // Remove any existing tooltip
                const oldTooltip = chartGroup.querySelector('.tooltip');
                if (oldTooltip) chartGroup.removeChild(oldTooltip);

                // Show enhanced tooltip
                const tooltipGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
                tooltipGroup.setAttribute('class', 'tooltip');

                // Position tooltip near the slice
                const tooltipX = (radius * 1.2) * Math.cos(midAngle - Math.PI / 2);
                const tooltipY = (radius * 1.2) * Math.sin(midAngle - Math.PI / 2);

                // Create tooltip background
                const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
                tooltipRect.setAttribute('x', tooltipX - 75);
                tooltipRect.setAttribute('y', tooltipY - 30);
                tooltipRect.setAttribute('width', '150');
                tooltipRect.setAttribute('height', '60');
                tooltipRect.setAttribute('rx', '5');
                tooltipRect.setAttribute('fill', 'rgba(17, 24, 39, 0.9)');
                tooltipRect.setAttribute('stroke', d.color);
                tooltipRect.setAttribute('stroke-width', '2');

                // Create tooltip text elements
                const tooltipText1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tooltipText1.setAttribute('x', tooltipX);
                tooltipText1.setAttribute('y', tooltipY - 10);
                tooltipText1.setAttribute('text-anchor', 'middle');
                tooltipText1.setAttribute('fill', 'white');
                tooltipText1.setAttribute('font-size', '12');
                tooltipText1.setAttribute('font-weight', 'bold');
                tooltipText1.textContent = d.category;

                const tooltipText2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tooltipText2.setAttribute('x', tooltipX);
                tooltipText2.setAttribute('y', tooltipY + 10);
                tooltipText2.setAttribute('text-anchor', 'middle');
                tooltipText2.setAttribute('fill', 'white');
                tooltipText2.setAttribute('font-size', '12');
                tooltipText2.textContent = `Count: ${d.count.toLocaleString()}`;

                const tooltipText3 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
                tooltipText3.setAttribute('x', tooltipX);
                tooltipText3.setAttribute('y', tooltipY + 30);
                tooltipText3.setAttribute('text-anchor', 'middle');
                tooltipText3.setAttribute('fill', d.color);
                tooltipText3.setAttribute('font-size', '14');
                tooltipText3.setAttribute('font-weight', 'bold');
                tooltipText3.textContent = `${Math.round(portion * 100)}%`;

                // Add elements to tooltip group with fade-in animation
                tooltipRect.setAttribute('opacity', '0');
                tooltipText1.setAttribute('opacity', '0');
                tooltipText2.setAttribute('opacity', '0');
                tooltipText3.setAttribute('opacity', '0');

                tooltipGroup.appendChild(tooltipRect);
                tooltipGroup.appendChild(tooltipText1);
                tooltipGroup.appendChild(tooltipText2);
                tooltipGroup.appendChild(tooltipText3);
                chartGroup.appendChild(tooltipGroup);

                // Animate tooltip appearance
                setTimeout(() => {
                    tooltipRect.setAttribute('opacity', '1');
                    tooltipText1.setAttribute('opacity', '1');
                    tooltipText2.setAttribute('opacity', '1');
                    tooltipText3.setAttribute('opacity', '1');
                }, 10);
            });

            path.addEventListener('mouseout', function() {
                // Reset segment position
                path.setAttribute('transform', '');

                // Remove tooltip with fade-out effect
                const tooltip = chartGroup.querySelector('.tooltip');
                if (tooltip) {
                    const tooltipElements = tooltip.childNodes;
                    tooltipElements.forEach(el => el.setAttribute('opacity', '0'));

                    setTimeout(() => {
                        chartGroup.removeChild(tooltip);
                    }, 200);
                }
            });

            chartGroup.appendChild(path);
            startAngle = endAngle;
        });

        // Add inner circle and total text after segments
        chartGroup.appendChild(innerCircle);
        chartGroup.appendChild(totalText1);
        chartGroup.appendChild(totalText2);
    } else {
        // Show "No data" message with better styling
        const noDataText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        noDataText.setAttribute('text-anchor', 'middle');
        noDataText.setAttribute('dominant-baseline', 'middle');
        noDataText.setAttribute('fill', '#6b7280');
        noDataText.setAttribute('font-size', '16');
        noDataText.setAttribute('font-weight', 'bold');
        noDataText.textContent = 'No appointment data available';
        chartGroup.appendChild(noDataText);
    }

    // Add legend items
    data.forEach((d, i) => {
        const legendItem = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        // Adjust X and Y to be within the SVG viewport
        const legendX = 20; // Place legend on the right side
        const legendY = i * 25 + 20; // Start from top

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