// Login functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeLoginForm();
    initializeRoleToggle();
    initializeFormValidation();
    initializeFormAnimations();
});

let currentRole = 'customer';
let loginAttempts = 0;
const maxLoginAttempts = 5;

function initializeLoginForm() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', handleLoginSubmit);
    }
    
    // Add input event listeners for real-time validation
    const inputs = loginForm.querySelectorAll('input');
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
}

function initializeRoleToggle() {
    const toggleBtns = document.querySelectorAll('.toggle-btn');
    
    toggleBtns.forEach(btn => {
        btn.addEventListener('click', function() {
            const role = this.dataset.role;
            
            // Update active button
            toggleBtns.forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            
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
    const form = document.getElementById('loginForm');
    const inputs = form.querySelectorAll('input[required]');
    
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
    });
}

function initializeFormAnimations() {
    const loginCard = document.querySelector('.login-card');
    const formGroups = document.querySelectorAll('.form-group');
    
    // Animate login card entrance
    if (loginCard) {
        loginCard.style.opacity = '0';
        loginCard.style.transform = 'translateY(50px) scale(0.95)';
        loginCard.style.transition = 'all 0.8s ease';
        
        setTimeout(() => {
            loginCard.style.opacity = '1';
            loginCard.style.transform = 'translateY(0) scale(1)';
        }, 100);
    }
    
    // Animate form groups
    formGroups.forEach((group, index) => {
        group.style.opacity = '0';
        group.style.transform = 'translateY(30px)';
        group.style.transition = 'all 0.6s ease';
        group.style.transitionDelay = `${0.3 + index * 0.1}s`;
        
        setTimeout(() => {
            group.style.opacity = '1';
            group.style.transform = 'translateY(0)';
        }, 300 + index * 100);
    });
}

function handleLoginSubmit(e) {
    e.preventDefault();
    
    if (loginAttempts >= maxLoginAttempts) {
        showError('Too many login attempts. Please try again later.');
        return;
    }
    
    const formData = new FormData(e.target);
    const email = formData.get('email');
    const password = formData.get('password');
    const remember = formData.get('remember');
    
    // Validate form
    if (!validateForm()) {
        return;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Logging in...';
    submitButton.disabled = true;
    
    // Simulate login process
    setTimeout(() => {
        if (simulateLogin(email, password)) {
            // Successful login
            showSuccess('Login successful! Redirecting...');
            
            // Store user data
            const userData = {
                email: email,
                role: currentRole,
                loginTime: new Date().toISOString(),
                remember: remember === 'on'
            };
            
            if (remember === 'on') {
                saveToLocalStorage('user_data', userData);
            } else {
                sessionStorage.setItem('user_data', JSON.stringify(userData));
            }
            
            // Redirect based on role
            setTimeout(() => {
                if (currentRole === 'customer') {
                    window.location.href = 'customer-dashboard.html';
                } else {
                    window.location.href = 'host-dashboard.html';
                }
            }, 1500);
        } else {
            // Failed login
            loginAttempts++;
            showError('Invalid email or password. Please try again.');
            
            // Add shake animation to form
            const loginCard = document.querySelector('.login-card');
            loginCard.style.animation = 'shake 0.5s ease-out';
            
            setTimeout(() => {
                loginCard.style.animation = '';
            }, 500);
        }
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }, 1500);
}

function validateForm() {
    const form = document.getElementById('loginForm');
    const inputs = form.querySelectorAll('input[required]');
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
    
    // Password validation
    if (fieldType === 'password' && value) {
        if (value.length < 6) {
            isValid = false;
            errorMessage = 'Password must be at least 6 characters long';
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

function simulateLogin(email, password) {
    // Mock login validation
    // In a real application, this would make an API call
    const validCredentials = [
        { email: 'customer@google.com', password: 'Password123' },
        { email: 'host@google.com', password: 'Password123' },
        { email: 'john@google.com', password: 'Password123' },
        { email: 'demo@google.com', password: 'Password123' }
    ];
    
    return validCredentials.some(cred => 
        cred.email === email && cred.password === password
    );
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

function showSuccess(message) {
    const successDiv = document.createElement('div');
    successDiv.className = 'success-toast';
    successDiv.textContent = message;
    successDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: #2ed573;
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        z-index: 10000;
        animation: slideInFromRight 0.5s ease-out;
        box-shadow: 0 4px 12px rgba(46, 213, 115, 0.3);
    `;
    
    document.body.appendChild(successDiv);
    
    setTimeout(() => {
        successDiv.style.animation = 'slideInFromRight 0.5s ease-out reverse';
        setTimeout(() => {
            successDiv.remove();
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

// Password visibility toggle
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
    input.setAttribute('type', type);
}

// Add password visibility toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        const toggleBtn = document.createElement('button');
        toggleBtn.type = 'button';
        toggleBtn.className = 'password-toggle';
        toggleBtn.innerHTML = '👁️';
        toggleBtn.style.cssText = `
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            color: #666;
        `;
        
        input.parentElement.style.position = 'relative';
        input.parentElement.appendChild(toggleBtn);
        
        toggleBtn.addEventListener('click', function() {
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '👁️' : '🙈';
        });
    });
});