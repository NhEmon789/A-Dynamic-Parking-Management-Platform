document.addEventListener('DOMContentLoaded', function () {
    var passwordInput = document.getElementById('customerPassword');
    var criteriaDiv = document.getElementById('passwordCriteria');
    if (passwordInput && criteriaDiv) {
        passwordInput.addEventListener('input', function () {
            var pwd = passwordInput.value;
            var missing = [];
            if (pwd.length < 8) missing.push('at least 8 characters');
            if (!/[A-Z]/.test(pwd)) missing.push('an uppercase letter');
            if (!/[a-z]/.test(pwd)) missing.push('a lowercase letter');
            if (!/[0-9]/.test(pwd)) missing.push('a number');
            if (pwd && missing.length) {
                criteriaDiv.textContent = 'Password must contain ' + missing.filter(m => m !== 'a special character').join(', ') + '.';
            } else {
                criteriaDiv.textContent = '';
            }
        });
    }
});

// Registration functionality
document.addEventListener('DOMContentLoaded', function () {
    initializeRegisterForm();
    initializeRoleToggle();
    initializeFormValidation();
    initializeFormAnimations();
});

let currentRole = 'customer';

function initializeRegisterForm() {
    const customerForm = document.getElementById('customerForm');
    const hostForm = document.getElementById('hostForm');

    // Only perform client-side validation, do not block form submission
    if (customerForm) {
        customerForm.addEventListener('submit', function (e) {
            if (!validateForm(customerForm)) {
                e.preventDefault();
                // Only show per-field error messages, no generic message
                const firstError = customerForm.querySelector('.form-group.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }
        });
    }

    if (hostForm) {
        hostForm.addEventListener('submit', handleHostSubmit);
    }

    // Add input event listeners for real-time validation and input animations
    const inputs = document.querySelectorAll('input, textarea');
    inputs.forEach(input => {
        input.addEventListener('input', function () {
            validateField(this);
        });

        input.addEventListener('focus', function () {
            this.parentElement.classList.add('focused');
        });

        input.addEventListener('blur', function () {
            this.parentElement.classList.remove('focused');
            if (this.value.trim() === '') {
                this.parentElement.classList.remove('filled');
            } else {
                this.parentElement.classList.add('filled');
            }
        });
    });

    // Password strength indicator
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    passwordInputs.forEach(input => {
        if (input.name === 'password') {
            input.addEventListener('input', function () {
                updatePasswordStrength(this);
            });
        }
    });
}

function initializeRoleToggle() {
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    const customerForm = document.getElementById('customerForm');
    const hostForm = document.getElementById('hostForm');

    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            const role = this.dataset.role;

            // Update active button style
            toggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // Show/hide the appropriate form
            if (role === 'customer') {
                customerForm.style.display = 'block';
                hostForm.style.display = 'none';
            } else {
                hostForm.style.display = 'block';
                customerForm.style.display = 'none';
            }

            currentRole = role;

            // Animate toggle button briefly
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
            input.addEventListener('blur', function () {
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

    // Animate form groups sequentially
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
    // This function is no longer used for customer form submission
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

    if (!validateForm(e.target)) return;

    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Creating Account...';
    submitButton.disabled = true;

    // Simulate registration delay
    setTimeout(() => {
        if (simulateRegistration(userData)) {
            saveToLocalStorage('user_data', userData);
            showSuccessModal();
        } else {
            showError('Registration failed. Please try again.');
        }
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

    clearFieldError(field);

    if (field.required && !value) {
        isValid = false;
        errorMessage = `${capitalize(fieldName)} is required`;
    }

    if (fieldType === 'email' && value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }

    if (fieldType === 'tel' && value) {
        const phoneRegex = /^\+?[\d\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }

    if (fieldType === 'password' && fieldName === 'password' && value) {
        if (value.length < 8) {
            isValid = false;
            errorMessage = 'Password must be at least 8 characters long';
        } else if (!/(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(value)) {
            isValid = false;
            errorMessage = 'Password must contain uppercase, lowercase letters, and a number';
        }
    }

    if (fieldName === 'confirmPassword' && value) {
        const passwordField = field.closest('form').querySelector('input[name="password"]');
        if (passwordField && value !== passwordField.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }

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
    let missing = [];
    if (password.length < 8) missing.push('at least 8 characters');
    if (!/[A-Z]/.test(password)) missing.push('an uppercase letter');
    if (!/[a-z]/.test(password)) missing.push('a lowercase letter');
    if (!/[0-9]/.test(password)) missing.push('a number');
    if (!/[^A-Za-z0-9]/.test(password)) missing.push('a special character');
    strengthIndicator.className = 'password-strength';
    if (password && missing.length) {
        strengthIndicator.textContent = 'Password must contain ' + missing.join(', ') + '.';
        strengthIndicator.style.color = '#ff4757';
        strengthIndicator.style.display = 'block';
        strengthIndicator.style.background = 'none';
        strengthIndicator.style.minHeight = '18px';
    } else {
        strengthIndicator.textContent = '';
        strengthIndicator.style.display = 'block';
        strengthIndicator.style.background = 'none';
        strengthIndicator.style.minHeight = '18px';
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
        errorElement.textContent = '';
        errorElement.style.display = 'none';
    }
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function simulateRegistration(userData) {
    // Simulate registration logic, replace with real API call as needed
    return true;
}

function saveToLocalStorage(key, data) {
    localStorage.setItem(key, JSON.stringify(data));
}

function showSuccessModal() {
    const modal = document.getElementById('successModal');
    if (modal) {
        modal.style.display = 'block';
    }
}

function showError(message) {
    alert(message);
}

// Modal close button handler
const closeModalBtn = document.getElementById('closeModalBtn');
if (closeModalBtn) {
    closeModalBtn.addEventListener('click', () => {
        const modal = document.getElementById('successModal');
        if (modal) {
            modal.style.display = 'none';
        }
    });
}

// Ensure success modal continue button works
const continueBtn = document.querySelector('#successModal .btn');
if (continueBtn) {
    continueBtn.addEventListener('click', function () {
        window.location.href = 'login.php';
    });
}
