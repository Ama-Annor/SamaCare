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

     // get the password confirmation details from html
    const confirmPasswordInput = document.getElementById('confirm-password');
    const passwordMatchMessage =  confirmPasswordInput ? document.getElementById('password-match-message') : null;
    const matchText = passwordMatchMessage ? passwordMatchMessage.querySelector('.match-text') : null;
   
    if(confirmPasswordInput && passwordMatchMessage && matchText){
        //function to check if passwords match
        function checkPasswordsMatch(){
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;

            if(confirmPassword === ''){
                //if confirmaton is empty, show default message
                matchText.textContent = 'Passwords must match';
                matchText.className = 'match-text';
                passwordMatchMessage.className = 'password-match';

            }else if(password === confirmPassword){
                //if passwords match 
                matchText.textContent = 'Passwords match';
                matchText.className = 'match-text match-success';
                passwordMatchMessage.className = 'password-match success';
            }else {
                // if passwords don't match 
                matchText.textContent = 'Passwords do not match';
                matchText.className = 'match-text match-error';
                passwordMatchMessage.className = 'password-match error';

            }
        }

        // Add event listners to both password fields 
        confirmPasswordInput.addEventListener('input',checkPasswordsMatch);
        passwordInput.addEventListener('input', function(){
            if(confirmPasswordInput.value !== ''){
                checkPasswordsMatch();
            }

        });
    

    }

 
    // Update the authentication file and to handle php errors 
     // Function to get URL parameters
     function getUrlParams() {
        let params = {};
        const queryString = window.location.search;
        const urlParams = new URLSearchParams(queryString);
        for (const [key, value] of urlParams.entries()) {
            params[key] = value;
        }
        return params;
    }

    // Check for success message in URL (from login redirect)
    const urlParams = getUrlParams();
    if (urlParams.registered === 'success') {
        // Show success message
        const successContainer = document.createElement('div');
        successContainer.className = 'success-message';
        successContainer.textContent = 'Account created successfully! Please log in.';
        document.querySelector('.auth-header').appendChild(successContainer);
    }

    // Check for PHP session errors
    if (typeof phpErrors !== 'undefined' && phpErrors.length > 0) {
        const errorContainer = document.getElementById('error-container');
        const errorList = document.getElementById('error-list');
        
        // Clear any existing errors
        errorList.innerHTML = '';
        
        // Add each error as a list item
        phpErrors.forEach(function(error) {
            const li = document.createElement('li');
            li.textContent = error;
            errorList.appendChild(li);
        });
        
        // Show the error container
        errorContainer.style.display = 'block';
        
        // Scroll to the error container
        errorContainer.scrollIntoView({ behavior: 'smooth' });
    }
    
    // Re-populate form fields if there was form data in the session
    if (typeof formData !== 'undefined') {
        if (formData.firstName) document.getElementById('firstName').value = formData.firstName;
        if (formData.lastName) document.getElementById('lastName').value = formData.lastName;
        if (formData.email) document.getElementById('email').value = formData.email;
        if (formData.phone) document.getElementById('phone').value = formData.phone;
    }

    //-----------------end of change for php


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
                //submit the form to the server here
                authForm.submit();

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