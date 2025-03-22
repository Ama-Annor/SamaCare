document.addEventListener('DOMContentLoaded', function() {
    // Create charts for the admin dashboard
    createAdminCharts();
    
    // Handle approval actions
    setupApprovalActions();
    
    // Handle dropdown toggles
    setupDropdowns();
    
    // Handle mobile menu toggle
    setupMobileMenu();
});

// Function to create charts for the admin dashboard
function createAdminCharts() {
    // User Growth Chart
    createUserGrowthChart();
    
    // Appointment Distribution Chart
    createAppointmentDistributionChart();
}

// Function to create the user growth chart
function createUserGrowthChart() {
    const ctx = document.getElementById('user-growth-chart');
    
    if (!ctx) return;
    
    // Create SVG for the chart
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    
    // Sample data for user growth chart
    const data = [
        { month: 'Jan', users: 1850 },
        { month: 'Feb', users: 1920 },
        { month: 'Mar', users: 2050 },
        { month: 'Apr', users: 2180 },
        { month: 'May', users: 2320 },
        { month: 'Jun', users: 2543 }
    ];
    
    // Chart dimensions
    const width = ctx.clientWidth;
    const height = ctx.clientHeight;
    const padding = { top: 20, right: 20, bottom: 30, left: 40 };
    const chartWidth = width - padding.left - padding.right;
    const chartHeight = height - padding.top - padding.bottom;
    
    // Create scales
    const xScale = chartWidth / (data.length - 1);
    const yMax = Math.max(...data.map(d => d.users));
    const yScale = chartHeight / yMax;
    
    // Create group for chart elements
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${padding.left}, ${padding.top})`);
    
    // Create path for the line
    const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    
    // Generate path data
    let pathData = `M0,${chartHeight - data[0].users * yScale}`;
    
    for (let i = 1; i < data.length; i++) {
        pathData += ` L${i * xScale},${chartHeight - data[i].users * yScale}`;
    }
    
    path.setAttribute('d', pathData);
    path.setAttribute('stroke', '#4361ee');
    path.setAttribute('stroke-width', '3');
    path.setAttribute('fill', 'none');
    
    // Create path for the area
    const areaPath = document.createElementNS('http://www.w3.org/2000/svg', 'path');
    
    // Generate area path data
    let areaPathData = `M0,${chartHeight - data[0].users * yScale}`;
    
    for (let i = 1; i < data.length; i++) {
        areaPathData += ` L${i * xScale},${chartHeight - data[i].users * yScale}`;
    }
    
    // Close the path
    areaPathData += ` L${(data.length - 1) * xScale},${chartHeight} L0,${chartHeight} Z`;
    
    areaPath.setAttribute('d', areaPathData);
    areaPath.setAttribute('fill', 'rgba(67, 97, 238, 0.1)');
    
    // Add data points
    data.forEach((d, i) => {
        const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
        circle.setAttribute('cx', i * xScale);
        circle.setAttribute('cy', chartHeight - d.users * yScale);
        circle.setAttribute('r', '4');
        circle.setAttribute('fill', '#4361ee');
        circle.setAttribute('stroke', 'white');
        circle.setAttribute('stroke-width', '2');
        
        // Add tooltip on hover
        circle.addEventListener('mouseover', function(e) {
            const tooltip = document.createElementNS('http://www.w3.org/2000/svg', 'g');
            tooltip.setAttribute('class', 'tooltip');
            
            const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            tooltipRect.setAttribute('x', i * xScale - 40);
            tooltipRect.setAttribute('y', chartHeight - d.users * yScale - 40);
            tooltipRect.setAttribute('width', '80');
            tooltipRect.setAttribute('height', '30');
            tooltipRect.setAttribute('rx', '5');
            tooltipRect.setAttribute('fill', 'rgba(0, 0, 0, 0.8)');
            
            const tooltipText = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tooltipText.setAttribute('x', i * xScale);
            tooltipText.setAttribute('y', chartHeight - d.users * yScale - 20);
            tooltipText.setAttribute('text-anchor', 'middle');
            tooltipText.setAttribute('fill', 'white');
            tooltipText.setAttribute('font-size', '12');
            tooltipText.textContent = `${d.month}: ${d.users}`;
            
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
    
    // Add X-axis labels
    data.forEach((d, i) => {
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', i * xScale);
        text.setAttribute('y', chartHeight + 20);
        text.setAttribute('text-anchor', 'middle');
        text.setAttribute('fill', '#6c757d');
        text.setAttribute('font-size', '12');
        text.textContent = d.month;
        
        chartGroup.appendChild(text);
    });
    
    // Add Y-axis labels
    for (let i = 0; i <= 5; i++) {
        const yValue = Math.round(yMax * i / 5);
        const y = chartHeight - yValue * yScale;
        
        const line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', 0);
        line.setAttribute('y1', y);
        line.setAttribute('x2', chartWidth);
        line.setAttribute('y2', y);
        line.setAttribute('stroke', 'rgba(0, 0, 0, 0.1)');
        line.setAttribute('stroke-width', '1');
        
        const text = document.createElementNS('http://www.w3.org/2000/svg', 'text');
        text.setAttribute('x', -10);
        text.setAttribute('y', y + 5);
        text.setAttribute('text-anchor', 'end');
        text.setAttribute('fill', '#6c757d');
        text.setAttribute('font-size', '12');
        text.textContent = yValue;
        
        chartGroup.appendChild(line);
        chartGroup.appendChild(text);
    }
    
    chartGroup.appendChild(areaPath);
    chartGroup.appendChild(path);
    svg.appendChild(chartGroup);
    
    // Add SVG to the chart container
    ctx.innerHTML = '';
    ctx.appendChild(svg);
}

// Function to create the appointment distribution chart
function createAppointmentDistributionChart() {
    const ctx = document.getElementById('appointment-distribution-chart');
    
    if (!ctx) return;
    
    // Create SVG for the chart
    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('width', '100%');
    svg.setAttribute('height', '100%');
    
    // Sample data for appointment distribution chart
    const data = [
        { category: 'General', count: 45, color: '#4361ee' },
        { category: 'Dental', count: 30, color: '#4cc9f0' },
        { category: 'Cardiology', count: 25, color: '#f72585' },
        { category: 'Pediatrics', count: 20, color: '#3f37c9' },
        { category: 'Dermatology', count: 15, color: '#60a5fa' },
        { category: 'Others', count: 52, color: '#a78bfa' }
    ];
    
    // Chart dimensions
    const width = ctx.clientWidth;
    const height = ctx.clientHeight;
    const radius = Math.min(width, height) / 2 - 40;
    
    // Create group for chart elements
    const chartGroup = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    chartGroup.setAttribute('transform', `translate(${width / 2}, ${height / 2})`);
    
    // Calculate total for percentages
    const total = data.reduce((sum, d) => sum + d.count, 0);
    
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
            
            const tooltipRect = document.createElementNS('http://www.w3.org/2000/svg', 'rect');
            const textOffsetX = (x1 + x2) / 3;
            const textOffsetY = (y1 + y2) / 3;
            
            tooltipRect.setAttribute('x', textOffsetX - 60);
            tooltipRect.setAttribute('y', textOffsetY - 30);
            tooltipRect.setAttribute('width', '120');
            tooltipRect.setAttribute('height', '40');
            tooltipRect.setAttribute('rx', '5');
            tooltipRect.setAttribute('fill', 'rgba(0, 0, 0, 0.8)');
            
            const tooltipText1 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tooltipText1.setAttribute('x', textOffsetX);
            tooltipText1.setAttribute('y', textOffsetY - 10);
            tooltipText1.setAttribute('text-anchor', 'middle');
            tooltipText1.setAttribute('fill', 'white');
            tooltipText1.setAttribute('font-size', '12');
            tooltipText1.textContent = d.category;
            
            const tooltipText2 = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            tooltipText2.setAttribute('x', textOffsetX);
            tooltipText2.setAttribute('y', textOffsetY + 10);
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
        
        // Generate legend items
        const legendItem = document.createElementNS('http://www.w3.org/2000/svg', 'g');
        const legendX = -width / 2 + 20;
        const legendY = height / 2 - 150 + i * 25;
        
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
        legendText.setAttribute('fill', '#6c757d');
        legendText.textContent = `${d.category} (${d.count})`;
        
        legendItem.appendChild(legendColor);
        legendItem.appendChild(legendText);
        
        svg.appendChild(legendItem);
        
        startAngle = endAngle;
    });
    
    svg.appendChild(chartGroup);
    
    // Add SVG to the chart container
    ctx.innerHTML = '';
    ctx.appendChild(svg);
}

// Function to setup approval actions
function setupApprovalActions() {
    const approveButtons = document.querySelectorAll('.table-actions .approve');
    const rejectButtons = document.querySelectorAll('.table-actions .reject');
    
    approveButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const statusCell = row.querySelector('.status-badge');
            
            statusCell.textContent = 'Approved';
            statusCell.classList.remove('pending');
            statusCell.classList.add('approved');
            
            showToast('Item approved successfully');
        });
    });
    
    rejectButtons.forEach(button => {
        button.addEventListener('click', function() {
            const row = this.closest('tr');
            const statusCell = row.querySelector('.status-badge');
            
            statusCell.textContent = 'Rejected';
            statusCell.classList.remove('pending');
            statusCell.classList.add('rejected');
            
            showToast('Item rejected');
        });
    });
}

// Function to setup dropdowns
function setupDropdowns() {
    const userBtn = document.querySelector('.user-btn');
    
    if (userBtn) {
        userBtn.addEventListener('click', function() {
            // Toggle user dropdown
            // Implementation would go here
            showToast('User menu would open here');
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
        
        // Add style for the toast if not already added
        if (!document.querySelector('style#toast-style')) {
            const style = document.createElement('style');
            style.id = 'toast-style';
            style.textContent = `
                .toast-message {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    background-color: var(--admin-primary, #4361ee);
                    color: white;
                    padding: 12px 20px;
                    border-radius: 8px;
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