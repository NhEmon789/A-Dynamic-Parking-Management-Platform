// Registration functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeRegisterForm();
    initializeRoleToggle();
    initializeFormValidation();
    initializeFormAnimations();
});

let currentRole = 'customer';

function initializeRegisterForm() {
    const customerForm = document.getElementById('customerForm');
    const hostForm = document.getElementById('hostForm');
    
    if (customerForm) {
        customerForm.addEventListener('submit', handleCustomerSubmit);
    }
    
    if (hostForm) {
        hostForm.addEventListener('submit', handleHostSubmit);
    }
    
    // Add input event listeners for real-time validation
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            validateField(this);
        });
        
        input.addEventListener('focus', function() {
            this.parentElement.classList.add('focused');
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.classList.remove('focused');
            if (this.value.trim() === '') {
                this.parentElement.classList.remove('filled');
            } else {
                this.parentElement.classList.add('filled');
            }
        });
    });
    
    // Password strength indicators
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.name === 'password') {
            input.addEventListener('input', function() {
                updatePasswordStrength(this);
            });
        }
    });
}

function initializeRoleToggle() {
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const customerForm = document.querySelector('.customer-form');
    const hostForm = document.querySelector('.host-form');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const role = this.dataset.role;
            
            // Update active button
            toggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
            // Update active form
            if (role === 'customer') {
                customerForm.classList.add('active');
                hostForm.classList.remove('active');
            } else {
                hostForm.classList.add('active');
                customerForm.classList.remove('active');
            }
            
            currentRole = role;
            
            // Add animation effect
            this.style.transform = 'scale(0.95)';
            setTimeout(() => {
                this.style.transform = 'scale(1)';
            }, 100);
        });
    });
}

function initializeFormValidation() {
    const forms = document.querySelectorAll('.register-form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required], textarea[required]');
        inputs.forEach(input => {
            input.addEventListener('blur', function() {
                validateField(this);
            });
        });
    });
}

function initializeFormAnimations() {
    const registerCard = document.querySelector('.register-card');
    const formGroups = document.querySelectorAll('.form-group');
    
    // Animate register card entrance
    if (registerCard) {
        registerCard.style.opacity = '0';
        registerCard.style.transform = 'translateY(50px) scale(0.95)';
        registerCard.style.transition = 'all 0.8s ease';
        
        setTimeout(() => {
            registerCard.style.opacity = '1';
            registerCard.style.transform = 'translateY(0) scale(1)';
        }, 100);
    }
    
    // Animate form groups
    formGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(30px)';
        group.style.transition = 'all 0.6s ease';
        group.style.transitionDelay = `${0.3 + index * 0.05}s`;
        
        setTimeout(() => {
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, 300 + index * 50);
    });
}

function handleCustomerSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        password: formData.get('password'),
        confirmPassword: formData.get('confirmPassword'),
        role: 'customer'
    };
    
    // Validate form
    if (!validateForm(e.target)) {
        return;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Creating Account...';
    submitButton.disabled = true;
    
    // Simulate registration process
    setTimeout(() => {
        if (simulateRegistration(userData)) {
            // Store user data
            saveToLocalStorage('user_data', userData);
            
            // Show success modal
            showSuccessModal();
        } else {
            showError('Registration failed. Please try again.');
        }
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }, 2000);
}

function handleHostSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const userData = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        password: formData.get('password'),
        confirmPassword: formData.get('confirmPassword'),
        role: 'host'
    };
    
    // Validate form
    if (!validateForm(e.target)) {
        return;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Creating Account...';
    submitButton.disabled = true;
    
    // Simulate registration process
    setTimeout(() => {
        if (simulateRegistration(userData)) {
            // Store user data
            saveToLocalStorage('user_data', userData);
            
            // Show success modal
            showSuccessModal();
        } else {
            showError('Registration failed. Please try again.');
        }
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }, 2000);
}

function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    const fieldName = field.name;
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error
    clearFieldError(field);
    
    // Required field validation
    if (field.required && !value) {
        isValid = false;
        errorMessage = `${fieldName.charAt(0).toUpperCase() + fieldName.slice(1)} is required`;
    }
    
    // Email validation
    if (fieldType === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (fieldType === 'tel' && value) {
        const phoneRegex = /^\+?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }
    
    // Password validation
    if (fieldType === 'password' && fieldName === 'password' && value) {
        if (value.length < 8) {
            isValid = false;
            errorMessage = 'Password must be at least 8 characters long';
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
            isValid = false;
            errorMessage = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
        }
    }
    
    // Confirm password validation
    if (fieldName === 'confirmPassword' && value) {
        const passwordField = field.closest('form').querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }
    
    // Show error or success
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        showFieldSuccess(field);
    }
    
    return isValid;
}

function updatePasswordStrength(passwordField) {
    const password = passwordField.value;
    const strengthIndicator = passwordField.parentElement.querySelector('.password-strength');
    
    if (!strengthIndicator) return;
    
    let strength = 0;
    
    // Check length
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    
    // Check for different character types
    if (/[a-z]/.test(password)) strength++;
    if (/[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
    
    // Update indicator
    strengthIndicator.className = 'password-strength';
    if (strength < 3) {
        strengthIndicator.classList.add('weak');
    } else if (strength < 5) {
        strengthIndicator.classList.add('medium');
    } else {
        strengthIndicator.classList.add('strong');
    }
}

function showFieldError(field, message) {
    const formGroup = field.closest('.form-group');
    const errorElement = formGroup.querySelector('.error-message');
    
    formGroup.classList.add('error');
    formGroup.classList.remove('success');
    
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Add shake animation
    field.style.animation = 'shake 0.3s ease-out';
    setTimeout(() => {
        field.style.animation = '';
    }, 300);
}

function showFieldSuccess(field) {
    const formGroup = field.closest('.form-group');
    
    formGroup.classList.add('success');
    formGroup.classList.remove('error');
}

function clearFieldError(field) {
    const formGroup = field.closest('.form-group');
    const errorElement = formGroup.querySelector('.error-message');
    
    formGroup.classList.remove('error');
    
    if (errorElement) {
        errorElement.style.display = 'none';
    }
}

function simulateRegistration(userData) {
    // Mock registration validation
    // In a real application, this would make an API call
    return userData.email && userData.password && userData.name;
}

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    modal.style.display = 'flex';
    
    // Add entrance animation
    const modalContent = modal.querySelector('.modal-content');
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    modalContent.style.transition = 'all 0.3s ease';
    
    setTimeout(() => {
        modalContent.style.opacity = '1';
        modalContent.style.transform = 'scale(1)';
    }, 100);
    
    // Animate success icon
    const successIcon = modal.querySelector('.success-icon svg');
    successIcon.style.animation = 'checkmark 0.6s ease-in-out';
}

function closeSuccessModal() {
    const modal = document.getElementById('successModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
        // Redirect based on role
        if (currentRole === 'customer') {
            window.location.href = 'customer-dashboard.html';
        } else {
            window.location.href = 'host-dashboard.html';
        }
    }, 300);
}

function showError(message) {
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-toast';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #ff4757;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        animation: slideInFromRight 0.5s ease-out;
        box-shadow: 0 4px 12px rgba(255, 71, 87, 0.3);
    `;
    
    document.body.appendChild(errorDiv);
    
    setTimeout(() => {
        errorDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            errorDiv.remove();
        }, 500);
    }, 3000);
}

function saveToLocalStorage(key, data) {
    try {
        localStorage.setItem(key, JSON.stringify(data));
    } catch (error) {
        console.error('Error saving to localStorage:', error);
    }
}

// File upload preview
document.addEventListener('DOMContentLoaded', function() {
    const fileInputs = document.querySelectorAll('input[type="file"]');
    
    fileInputs.forEach(input => {
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Show preview or confirmation
                    const label = input.closest('.form-group').querySelector('label');
                    const originalText = label.textContent;
                    label.textContent = `File selected: ${file.name}`;
                    
                    setTimeout(() => {
                        label.textContent = originalText;
                    }, 3000);
                };
                reader.readAsDataURL(file);
            }
        });
    });
});