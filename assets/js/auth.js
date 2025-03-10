document.addEventListener('DOMContentLoaded', function() {
    // Password toggle visibility
    const togglePasswordButtons = document.querySelectorAll('.toggle-password');
    
    togglePasswordButtons.forEach(button => {
        button.addEventListener('click', function() {
            const passwordInput = this.previousElementSibling;
            const icon = this.querySelector('i');
            
            // Toggle the password visibility
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('bx-hide');
                icon.classList.add('bx-show');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('bx-show');
                icon.classList.add('bx-hide');
            }
        });
    });
    
    // Password strength meter
    const passwordInput = document.getElementById('password');
    if (passwordInput) {
        passwordInput.addEventListener('input', function() {
            updatePasswordStrength(this.value);
        });
    }
    
    function updatePasswordStrength(password) {
        const strengthMeter = document.querySelector('.strength-meter');
        const strengthText = document.querySelector('.strength-text');
        
        if (!strengthMeter || !strengthText) return;
        
        const meterSections = strengthMeter.querySelectorAll('.meter-section');
        
        // Remove all classes
        meterSections.forEach(section => {
            section.className = 'meter-section';
        });
        
        // Calculate password strength
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        
        // Uppercase check
        if (/[A-Z]/.test(password)) strength += 1;
        
        // Lowercase check
        if (/[a-z]/.test(password)) strength += 1;
        
        // Number check
        if (/[0-9]/.test(password)) strength += 1;
        
        // Special character check
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        // Update the strength meter
        let strengthClass = '';
        let strengthLabel = '';
        
        if (password.length === 0) {
            strengthLabel = 'Password strength';
        } else if (strength < 2) {
            strengthClass = 'weak';
            strengthLabel = 'Weak - Try harder!';
            
            meterSections[0].classList.add(strengthClass);
        } else if (strength < 3) {
            strengthClass = 'medium';
            strengthLabel = 'Medium - Getting better';
            
            meterSections[0].classList.add(strengthClass);
            meterSections[1].classList.add(strengthClass);
        } else if (strength < 5) {
            strengthClass = 'strong';
            strengthLabel = 'Strong - Well done!';
            
            meterSections[0].classList.add(strengthClass);
            meterSections[1].classList.add(strengthClass);
            meterSections[2].classList.add(strengthClass);
        } else {
            strengthClass = 'very-strong';
            strengthLabel = 'Very Strong - Excellent!';
            
            meterSections.forEach(section => {
                section.classList.add(strengthClass);
            });
        }
        
        strengthText.textContent = strengthLabel;
    }
    
    // Form validation
    const authForm = document.querySelector('.auth-form');
    
    if (authForm) {
        authForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Simple validation check
            let isValid = true;
            const inputs = authForm.querySelectorAll('input[required]');
            
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    isValid = false;
                    input.classList.add('error');
                    
                    // Add error message if it doesn't exist
                    let errorMessage = input.parentElement.querySelector('.error-message');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'This field is required';
                        input.parentElement.appendChild(errorMessage);
                    }
                } else {
                    input.classList.remove('error');
                    
                    // Remove error message if it exists
                    const errorMessage = input.parentElement.querySelector('.error-message');
                    if (errorMessage) {
                        errorMessage.remove();
                    }
                }
            });
            
            // Email validation
            const emailInput = authForm.querySelector('input[type="email"]');
            if (emailInput && emailInput.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(emailInput.value.trim())) {
                    isValid = false;
                    emailInput.classList.add('error');
                    
                    // Add error message if it doesn't exist
                    let errorMessage = emailInput.parentElement.querySelector('.error-message');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'Please enter a valid email address';
                        emailInput.parentElement.appendChild(errorMessage);
                    } else {
                        errorMessage.textContent = 'Please enter a valid email address';
                    }
                }
            }
            
            // Password strength validation for signup
            if (passwordInput && document.querySelector('.strength-meter') && passwordInput.value.trim()) {
                const strength = calculatePasswordStrength(passwordInput.value);
                
                if (strength < 3) {
                    isValid = false;
                    passwordInput.classList.add('error');
                    
                    // Add error message if it doesn't exist
                    let errorMessage = passwordInput.parentElement.querySelector('.error-message');
                    if (!errorMessage) {
                        errorMessage = document.createElement('div');
                        errorMessage.className = 'error-message';
                        errorMessage.textContent = 'Password is too weak';
                        passwordInput.parentElement.appendChild(errorMessage);
                    } else {
                        errorMessage.textContent = 'Password is too weak';
                    }
                }
            }
            
            if (isValid) {
                //submit the form to the server here - MCNOBERT AND SAMUELLLLL
                
                // For demo purposes, show a success message
                const submitBtn = authForm.querySelector('.submit-btn');
                const originalText = submitBtn.innerHTML;
                
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Processing...';
                
            
                setTimeout(() => {
                    //actual form submission and redirection
                    
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                    
                    // Show success message
                    const successMessage = document.createElement('div');
                    successMessage.className = 'success-message';
                    successMessage.innerHTML = '<i class="bx bx-check-circle"></i> Success! Redirecting...';
                    
                    authForm.appendChild(successMessage);
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = document.querySelector('.auth-footer a').href;
                    }, 2000);
                }, 1500);
            }
        });
        
        // Remove error styling on input
        const inputs = authForm.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('error');
                
                // Remove error message if it exists
                const errorMessage = this.parentElement.querySelector('.error-message');
                if (errorMessage) {
                    errorMessage.remove();
                }
            });
        });
    }
    
    function calculatePasswordStrength(password) {
        let strength = 0;
        
        // Length check
        if (password.length >= 8) strength += 1;
        
        // Uppercase check
        if (/[A-Z]/.test(password)) strength += 1;
        
        // Lowercase check
        if (/[a-z]/.test(password)) strength += 1;
        
        // Number check
        if (/[0-9]/.test(password)) strength += 1;
        
        // Special character check
        if (/[^A-Za-z0-9]/.test(password)) strength += 1;
        
        return strength;
    }
    
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
    
    // Biometric login buttons animation
    const biometricBtns = document.querySelectorAll('.biometric-btn');
    
    biometricBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            this.classList.add('processing');
            const icon = this.querySelector('i');
            const originalClass = icon.className;
            
            icon.className = 'bx bx-loader-alt bx-spin';
            
            setTimeout(() => {
                icon.className = 'bx bx-check';
                this.classList.remove('processing');
                this.classList.add('success');
                
                setTimeout(() => {
                    icon.className = originalClass;
                    this.classList.remove('success');
                    
                    // Display success message
                    const quickLogin = document.querySelector('.quick-login');
                    const successMessage = document.createElement('div');
                    successMessage.className = 'biometric-success';
                    successMessage.innerHTML = '<i class="bx bx-check-circle"></i> Authentication successful! Redirecting...';
                    
                    quickLogin.appendChild(successMessage);
                    
                    // Redirect after a delay
                    setTimeout(() => {
                        window.location.href = '../index.html';
                    }, 2000);
                }, 1000);
            }, 2000);
        });
    });
    
    // Add CSS to support the animations
    const style = document.createElement('style');
    style.textContent = `
        .error {
            border-color: #dc3545 !important;
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
        }
        
        .error-message {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: var(--border-radius);
            margin-top: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .success-message i {
            font-size: 20px;
        }
        
    `;
    document.head.appendChild(style);
});