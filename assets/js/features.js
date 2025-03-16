document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu toggle
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const nav = document.querySelector('nav');
    
    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', () => {
            nav.classList.toggle('active');
            
            // Toggle icon
            const icon = mobileMenuBtn.querySelector('i');
            if (nav.classList.contains('active')) {
                icon.classList.remove('bx-menu');
                icon.classList.add('bx-x');
            } else {
                icon.classList.remove('bx-x');
                icon.classList.add('bx-menu');
            }
        });
    }
    
    // Testimonial slider functionality
    const prevBtn = document.querySelector('.prev-btn');
    const nextBtn = document.querySelector('.next-btn');
    const dots = document.querySelectorAll('.dot');
    const testimonialSlider = document.querySelector('.testimonial-slider');
    const testimonials = document.querySelectorAll('.testimonial-card');
    let currentIndex = 0;
    
    // Function to update testimonial display
    function updateTestimonials() {
        // For mobile and tablets
        if (window.innerWidth <= 992) {
            testimonials.forEach((testimonial, index) => {
                if (index === currentIndex) {
                    testimonial.style.display = 'flex';
                } else {
                    testimonial.style.display = 'none';
                }
            });
        } else {
            // For desktop view, reset all testimonials to display flex
            testimonials.forEach(testimonial => {
                testimonial.style.display = 'flex';
            });
        }
        
        // Update active dot
        dots.forEach((dot, index) => {
            if (index === currentIndex) {
                dot.classList.add('active');
            } else {
                dot.classList.remove('active');
            }
        });
    }
    
    // Initialize testimonials
    updateTestimonials();
    
    // Window resize listener
    window.addEventListener('resize', updateTestimonials);
    
    // Previous button click
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentIndex = (currentIndex - 1 + testimonials.length) % testimonials.length;
            updateTestimonials();
        });
    }
    
    // Next button click
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentIndex = (currentIndex + 1) % testimonials.length;
            updateTestimonials();
        });
    }
    
    // Dot click
    dots.forEach((dot, index) => {
        dot.addEventListener('click', () => {
            currentIndex = index;
            updateTestimonials();
        });
    });
    
    // Animate on scroll functionality
    const animatedElements = document.querySelectorAll('.feature-card, .feature-row, .device, .testimonial-card');
    
    function checkIfInView() {
        const windowHeight = window.innerHeight;
        const windowTopPosition = window.scrollY;
        const windowBottomPosition = windowTopPosition + windowHeight;
        
        animatedElements.forEach(element => {
            const elementHeight = element.offsetHeight;
            const elementTopPosition = element.offsetTop;
            const elementBottomPosition = elementTopPosition + elementHeight;
            
            // Check if element is in viewport
            if ((elementBottomPosition >= windowTopPosition) && 
                (elementTopPosition <= windowBottomPosition)) {
                element.classList.add('animated');
            }
        });
    }
    
    // Add animated class to elements in view on page load
    window.addEventListener('load', checkIfInView);
    
    // Add animated class to elements in view on scroll
    window.addEventListener('scroll', checkIfInView);
    
    // Add CSS for animations
    const style = document.createElement('style');
    style.textContent = `
        .feature-card, .feature-row, .device, .testimonial-card {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease, transform 0.6s ease;
        }
        
        .feature-card.animated, .feature-row.animated, .device.animated, .testimonial-card.animated {
            opacity: 1;
            transform: translateY(0);
        }
        
        .feature-card:nth-child(2) {
            transition-delay: 0.2s;
        }
        
        .feature-card:nth-child(3) {
            transition-delay: 0.4s;
        }
        
        .feature-card:nth-child(4) {
            transition-delay: 0.6s;
        }
        
        .feature-card:nth-child(5) {
            transition-delay: 0.8s;
        }
        
        .feature-card:nth-child(6) {
            transition-delay: 1s;
        }
        
        .device:nth-child(2) {
            transition-delay: 0.3s;
        }
        
        .device:nth-child(3) {
            transition-delay: 0.6s;
        }
    `;
    document.head.appendChild(style);
});