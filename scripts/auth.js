// Authentication JavaScript

document.addEventListener('DOMContentLoaded', () => {
    initRoleToggle();
    initPasswordToggle();
    initPasswordStrength();
    initFormValidation();
    initFileUpload();
    initFormSubmission();
});

// Role Toggle Functionality
function initRoleToggle() {
    const roleButtons = document.querySelectorAll('.role-btn');
    const customerForm = document.querySelector('.customer-form');
    const hostForm = document.querySelector('.host-form');
    
    roleButtons.forEach(button => {
        button.addEventListener('click', () => {
            // Remove active class from all buttons
            roleButtons.forEach(btn => btn.classList.remove('active'));
            
            // Add active class to clicked button
            button.classList.add('active');
            
            // Show/hide forms based on role
            const role = button.dataset.role;
            if (role === 'customer') {
                customerForm.classList.add('active');
                hostForm.classList.remove('active');
            } else {
                hostForm.classList.add('active');
                customerForm.classList.remove('active');
            }
        });
    });
}

// Password Toggle Functionality
function initPasswordToggle() {
    const toggleButtons = document.querySelectorAll('.password-toggle');
    
    toggleButtons.forEach(button => {
        button.addEventListener('click', () => {
            const targetId = button.dataset.target;
            const passwordInput = document.getElementById(targetId);
            const icon = button.querySelector('i');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
}

// Password Strength Indicator
function initPasswordStrength() {
    const passwordInputs = document.querySelectorAll('input[type="password"]');
    
    passwordInputs.forEach(input => {
        if (input.name === 'password') {
            input.addEventListener('input', () => {
                const password = input.value;
                const strengthBar = input.closest('.form-group').querySelector('.strength-fill');
                const strengthText = input.closest('.form-group').querySelector('.strength-text');
                
                if (!strengthBar || !strengthText) return;
                
                const strength = calculatePasswordStrength(password);
                
                strengthBar.style.width = `${strength.percentage}%`;
                strengthText.textContent = strength.text;
                strengthText.style.color = strength.color;
            });
        }
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    let feedback = [];
    
    if (password.length >= 8) score += 25;
    else feedback.push('At least 8 characters');
    
    if (/[a-z]/.test(password)) score += 25;
    else feedback.push('Lowercase letter');
    
    if (/[A-Z]/.test(password)) score += 25;
    else feedback.push('Uppercase letter');
    
    if (/[0-9]/.test(password)) score += 25;
    else feedback.push('Number');
    
    if (/[^A-Za-z0-9]/.test(password)) score += 10;
    
    let text, color;
    if (score < 25) {
        text = 'Weak';
        color = '#ff4444';
    } else if (score < 50) {
        text = 'Fair';
        color = '#ffaa00';
    } else if (score < 75) {
        text = 'Good';
        color = '#00aa00';
    } else {
        text = 'Strong';
        color = '#00aa00';
    }
    
    return { percentage: Math.min(score, 100), text, color };
}

// Form Validation
function initFormValidation() {
    const forms = document.querySelectorAll('.auth-form');
    
    forms.forEach(form => {
        const inputs = form.querySelectorAll('input[required]');
        
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearError(input));
        });
    });
}

function validateField(input) {
    const wrapper = input.closest('.input-wrapper');
    const errorElement = input.closest('.form-group').querySelector('.error-message');
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous states
    wrapper.classList.remove('error', 'valid');
    
    // Required field validation
    if (input.hasAttribute('required') && !input.value.trim()) {
        isValid = false;
        errorMessage = 'This field is required';
    }
    
    // Email validation
    if (input.type === 'email' && input.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid email address';
        }
    }
    
    // Phone validation
    if (input.type === 'tel' && input.value) {
        const phoneRegex = /^[\+]?[0-9\s\-\(\)]{10,}$/;
        if (!phoneRegex.test(input.value)) {
            isValid = false;
            errorMessage = 'Please enter a valid phone number';
        }
    }
    
    // Password confirmation
    if (input.name === 'confirmPassword') {
        const passwordInput = input.form.querySelector('input[name="password"]');
        if (input.value !== passwordInput.value) {
            isValid = false;
            errorMessage = 'Passwords do not match';
        }
    }
    
    // Show error or success state
    if (!isValid) {
        wrapper.classList.add('error');
        showError(errorElement, errorMessage);
    } else if (input.value) {
        wrapper.classList.add('valid');
        hideError(errorElement);
    }
    
    return isValid;
}

function showError(errorElement, message) {
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.classList.add('show');
    }
}

function hideError(errorElement) {
    if (errorElement) {
        errorElement.classList.remove('show');
    }
}

function clearError(input) {
    const wrapper = input.closest('.input-wrapper');
    const errorElement = input.closest('.form-group').querySelector('.error-message');
    
    wrapper.classList.remove('error');
    hideError(errorElement);
}

// File Upload Functionality
function initFileUpload() {
    const fileInput = document.getElementById('hostDocuments');
    const uploadArea = document.querySelector('.file-upload-area');
    const preview = document.getElementById('filePreview');
    
    if (!fileInput || !uploadArea || !preview) return;
    
    // Click to upload
    uploadArea.addEventListener('click', () => {
        fileInput.click();
    });
    
    // Drag and drop
    uploadArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'var(--text-primary)';
        uploadArea.style.background = 'rgba(255, 255, 255, 0.1)';
    });
    
    uploadArea.addEventListener('dragleave', () => {
        uploadArea.style.borderColor = 'rgba(255, 255, 255, 0.3)';
        uploadArea.style.background = 'rgba(255, 255, 255, 0.05)';
    });
    
    uploadArea.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadArea.style.borderColor = 'rgba(255, 255, 255, 0.3)';
        uploadArea.style.background = 'rgba(255, 255, 255, 0.05)';
        
        const files = e.dataTransfer.files;
        handleFiles(files);
    });
    
    // File input change
    fileInput.addEventListener('change', (e) => {
        handleFiles(e.target.files);
    });
    
    function handleFiles(files) {
        preview.innerHTML = '';
        
        Array.from(files).forEach((file, index) => {
            if (file.size > 5 * 1024 * 1024) { // 5MB limit
                alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                return;
            }
            
            const fileItem = document.createElement('div');
            fileItem.className = 'file-item';
            fileItem.innerHTML = `
                <i class="fas fa-file"></i>
                <span>${file.name}</span>
                <button type="button" class="remove-file" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            
            preview.appendChild(fileItem);
        });
        
        // Add remove functionality
        preview.querySelectorAll('.remove-file').forEach(button => {
            button.addEventListener('click', (e) => {
                e.target.closest('.file-item').remove();
            });
        });
    }
}

// Form Submission
function initFormSubmission() {
    const customerForm = document.getElementById('customerForm');
    const hostForm = document.getElementById('hostForm');
    const successModal = document.getElementById('successModal');
    const continueBtn = document.getElementById('continueBtn');
    
    if (customerForm) {
        customerForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleFormSubmission(customerForm, 'customer');
        });
    }
    
    if (hostForm) {
        hostForm.addEventListener('submit', (e) => {
            e.preventDefault();
            handleFormSubmission(hostForm, 'host');
        });
    }
    
    if (continueBtn) {
        continueBtn.addEventListener('click', () => {
            successModal.classList.remove('show');
            
            // Redirect based on user type
            const userType = utils.storage.get('userType');
            if (userType === 'customer') {
                window.location.href = 'customer-dashboard.html';
            } else {
                window.location.href = 'host-dashboard.html';
            }
        });
    }
}

function handleFormSubmission(form, userType) {
    const inputs = form.querySelectorAll('input[required]');
    let isFormValid = true;
    
    // Validate all required fields
    inputs.forEach(input => {
        if (!validateField(input)) {
            isFormValid = false;
        }
    });
    
    if (!isFormValid) {
        // Shake animation for invalid form
        form.style.animation = 'shake 0.5s ease-in-out';
        setTimeout(() => {
            form.style.animation = '';
        }, 500);
        return;
    }
    
    // Simulate form submission
    const submitButton = form.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating Account...';
    submitButton.disabled = true;
    
    setTimeout(() => {
        // Store user data (mock)
        const formData = new FormData(form);
        const userData = {
            name: formData.get('name'),
            email: formData.get('email'),
            phone: formData.get('phone'),
            userType: userType,
            registeredAt: new Date().toISOString()
        };
        
        utils.storage.set('userData', userData);
        utils.storage.set('userType', userType);
        utils.storage.set('isLoggedIn', true);
        
        // Show success modal
        document.getElementById('successModal').classList.add('show');
        
        // Reset button
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    }, 2000);
}

// Add shake animation
const style = document.createElement('style');
style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
`;
document.head.appendChild(style);