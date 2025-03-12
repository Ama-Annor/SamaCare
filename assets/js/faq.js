document.addEventListener('DOMContentLoaded', function() {
    // FAQ Accordion functionality
    const faqItems = document.querySelectorAll('.faq-item');
    
    faqItems.forEach(item => {
        const question = item.querySelector('.faq-question');
        
        question.addEventListener('click', () => {
            // Toggle the current item
            item.classList.toggle('active');
            
            // Update the icon
            const icon = item.querySelector('.faq-toggle i');
            if (item.classList.contains('active')) {
                icon.classList.remove('bx-plus');
                icon.classList.add('bx-minus');
            } else {
                icon.classList.remove('bx-minus');
                icon.classList.add('bx-plus');
            }
        });
    });
    
    // FAQ Categories navigation
    const categoryLinks = document.querySelectorAll('.category-card');
    const faqSections = document.querySelectorAll('.faq-section');
    
    categoryLinks.forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault(); // Prevent default anchor behavior
            
            // Remove active class from all links
            categoryLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            link.classList.add('active');
            
            // Get the target section id
            const targetId = link.getAttribute('href').substring(1);
            
            // Hide all sections
            faqSections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show the target section
            document.getElementById(targetId).classList.add('active');
            
            // Scroll to section
            document.getElementById(targetId).scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
            
            // Update URL hash (for bookmarking/sharing)
            history.pushState(null, null, `#${targetId}`);
        });
    });
    
    // Handle direct URL access with hash
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1);
        const targetSection = document.getElementById(targetId);
        const targetLink = document.querySelector(`a[href="#${targetId}"]`);
        
        if (targetSection && targetLink) {
            // Hide all sections
            faqSections.forEach(section => {
                section.classList.remove('active');
            });
            
            // Show target section
            targetSection.classList.add('active');
            
            // Update active link
            categoryLinks.forEach(l => l.classList.remove('active'));
            targetLink.classList.add('active');
            
            // Scroll to section (with slight delay to ensure page is loaded)
            setTimeout(() => {
                targetSection.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 300);
        }
    }
    
    // Your existing Community Questions Tab functionality
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
    
    // Add Back to Top buttons to each section
    faqSections.forEach(section => {
        // Create back to top button
        const backToTopBtn = document.createElement('a');
        backToTopBtn.className = 'back-to-top-btn';
        backToTopBtn.innerHTML = '<i class="bx bx-up-arrow-alt"></i> Back to Categories';
        backToTopBtn.href = '#';
        
        // Add click event
        backToTopBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Scroll to categories section
            document.querySelector('.faq-categories').scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        });
        
        // Append to section
        section.appendChild(backToTopBtn);
    });
});