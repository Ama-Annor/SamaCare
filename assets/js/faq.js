// Community Questions Tab functionality
const tabButtons = document.querySelectorAll('.tab-btn');
const tabContents = document.querySelectorAll('.tab-content');

tabButtons.forEach(button => {
    button.addEventListener('click', () => {
        // Remove active class from all buttons
        tabButtons.forEach(btn => btn.classList.remove('active'));
        
        // Add active class to clicked button
        button.classList.add('active');
        
        // Get the target tab content
        const target = button.getAttribute('data-target');
        
        // Hide all tab contents
        tabContents.forEach(content => {
            content.classList.remove('active');
        });
        
        // Show the target tab content
        document.getElementById(target).classList.add('active');
    });
});