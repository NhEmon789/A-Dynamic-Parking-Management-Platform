// Customer profile page functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeProfilePage();
    loadProfileData();
    initializeFormValidation();
});

function initializeProfilePage() {
    // Initialize role toggle
    const roleToggle = document.getElementById('roleToggle');
    if (roleToggle) {
        roleToggle.addEventListener('click', function() {
            window.location.href = 'host-dashboard.html';
        });
    }
    
    // Initialize profile form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileSubmit);
    }
    
    // Initialize photo upload
    const photoUpload = document.getElementById('photoUpload');
    if (photoUpload) {
        photoUpload.addEventListener('change', handlePhotoUpload);
    }
    
    // Initialize change password form
    const changePasswordForm = document.getElementById('changePasswordForm');
    if (changePasswordForm) {
        changePasswordForm.addEventListener('submit', handlePasswordChange);
    }
    
    // Add password strength indicator
    const newPasswordInput = document.getElementById('newPassword');
    if (newPasswordInput) {
        newPasswordInput.addEventListener('input', updatePasswordStrength);
    }
}

function loadProfileData() {
    // Load user data from localStorage
    const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
    
    // Populate form fields
    if (userData.name) {
        const nameParts = userData.name.split(' ');
        document.getElementById('firstName').value = nameParts[0] || '';
        document.getElementById('lastName').value = nameParts.slice(1).join(' ') || '';
    }
    
    if (userData.email) {
        document.getElementById('email').value = userData.email;
        document.getElementById('displayEmail').textContent = userData.email;
    }
    
    if (userData.phone) {
        document.getElementById('phone').value = userData.phone;
    }
    
    // Update display name
    const displayName = document.getElementById('displayName');
    if (displayName && userData.name) {
        displayName.textContent = userData.name;
    }
    
    // Load additional profile data (mock data)
    loadMockProfileData();
}

function loadMockProfileData() {
    // Mock additional profile data
    const mockData = {
        vehicleType: 'sedan',
        licensePlate: 'ABC123',
        emailNotifications: true,
        smsNotifications: true,
        marketingEmails: false
    };
    
    // Populate additional fields
    document.getElementById('vehicleType').value = mockData.vehicleType;
    document.getElementById('licensePlate').value = mockData.licensePlate;
    document.getElementById('emailNotifications').checked = mockData.emailNotifications;
    document.getElementById('smsNotifications').checked = mockData.smsNotifications;
    document.getElementById('marketingEmails').checked = mockData.marketingEmails;
}

function initializeFormValidation() {
    const inputs = document.querySelectorAll('#profileForm input[required]');
    inputs.forEach(input => {
        input.addEventListener('blur', function() {
            validateField(this);
        });
        
        input.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    const fieldType = field.type;
    let isValid = true;
    let errorMessage = '';
    
    // Clear previous error
    clearFieldError(field);
    
    // Required field validation
    if (field.required && !value) {
        isValid = false;
        errorMessage = `${field.name.charAt(0).toUpperCase() + field.name.slice(1)} is required`;
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
    
    // Show error or success
    if (!isValid) {
        showFieldError(field, errorMessage);
    } else {
        showFieldSuccess(field);
    }
    
    return isValid;
}

function showFieldError(field, message) {
    field.classList.add('error');
    field.classList.remove('success');
    
    // Add shake animation
    field.style.animation = 'shake 0.3s ease-out';
    setTimeout(() => {
        field.style.animation = '';
    }, 300);
}

function showFieldSuccess(field) {
    field.classList.add('success');
    field.classList.remove('error');
}

function clearFieldError(field) {
    field.classList.remove('error');
}

function handleProfileSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const profileData = {
        firstName: formData.get('firstName'),
        lastName: formData.get('lastName'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        vehicleType: formData.get('vehicleType'),
        licensePlate: formData.get('licensePlate'),
        emailNotifications: formData.get('emailNotifications') === 'on',
        smsNotifications: formData.get('smsNotifications') === 'on',
        marketingEmails: formData.get('marketingEmails') === 'on'
    };
    
    // Validate form
    if (!validateProfileForm()) {
        return;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Saving...';
    submitButton.disabled = true;
    
    // Simulate save process
    setTimeout(() => {
        // Update localStorage
        const userData = JSON.parse(localStorage.getItem('user_data') || '{}');
        userData.name = `${profileData.firstName} ${profileData.lastName}`;
        userData.email = profileData.email;
        userData.phone = profileData.phone;
        userData.profile = profileData;
        
        localStorage.setItem('user_data', JSON.stringify(userData));
        
        // Update display
        document.getElementById('displayName').textContent = userData.name;
        document.getElementById('displayEmail').textContent = userData.email;
        
        // Show success message
        showSuccessMessage('Profile updated successfully!');
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }, 1500);
}

function validateProfileForm() {
    const requiredFields = document.querySelectorAll('#profileForm input[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function handlePhotoUpload(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const profileImage = document.getElementById('profileImage');
            profileImage.src = e.target.result;
            
            // Add upload animation
            profileImage.style.opacity = '0';
            profileImage.style.transform = 'scale(0.9)';
            profileImage.style.transition = 'all 0.3s ease';
            
            setTimeout(() => {
                profileImage.style.opacity = '1';
                profileImage.style.transform = 'scale(1)';
            }, 100);
            
            showSuccessMessage('Profile photo updated!');
        };
        reader.readAsDataURL(file);
    }
}

function resetForm() {
    // Reset form to original values
    loadProfileData();
    
    // Clear any error states
    document.querySelectorAll('.error').forEach(element => {
        element.classList.remove('error');
    });
    
    showSuccessMessage('Form reset to original values');
}

function changePassword() {
    const modal = document.getElementById('changePasswordModal');
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
}

function closeChangePasswordModal() {
    const modal = document.getElementById('changePasswordModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
        
        // Reset form
        document.getElementById('changePasswordForm').reset();
        document.getElementById('passwordStrength').className = 'password-strength';
    }, 300);
}

function handlePasswordChange(e) {
    e.preventDefault();
    
    const currentPassword = document.getElementById('currentPassword').value;
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    // Validate passwords
    if (newPassword !== confirmPassword) {
        showFieldError(document.getElementById('confirmPassword'), 'Passwords do not match');
        return;
    }
    
    if (newPassword.length < 8) {
        showFieldError(document.getElementById('newPassword'), 'Password must be at least 8 characters long');
        return;
    }
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Changing...';
    submitButton.disabled = true;
    
    // Simulate password change
    setTimeout(() => {
        closeChangePasswordModal();
        showSuccessMessage('Password changed successfully!');
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
    }, 2000);
}

function updatePasswordStrength() {
    const password = document.getElementById('newPassword').value;
    const strengthIndicator = document.getElementById('passwordStrength');
    
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

function enable2FA() {
    showSuccessMessage('2FA setup is not implemented in this demo');
}

function deleteAccount() {
    const modal = document.getElementById('deleteAccountModal');
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
}

function closeDeleteAccountModal() {
    const modal = document.getElementById('deleteAccountModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
        
        // Reset confirmation input
        document.getElementById('deleteConfirmation').value = '';
    }, 300);
}

function confirmDeleteAccount() {
    const password = document.getElementById('deletePassword').value;
    const confirmation = document.getElementById('deleteConfirmation').value;
    
    // Check password
    const userData = JSON.parse(localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || '{}');
    if (!password) {
        showFieldError(document.getElementById('deletePassword'), 'Password is required');
        return;
    }
    
    // In a real app, this would verify against the actual password
    // For demo purposes, we'll accept any non-empty password
    
    if (confirmation !== 'DELETE') {
        showFieldError(document.getElementById('deleteConfirmation'), 'Please type "DELETE" to confirm');
        return;
    }
    
    // Show loading state
    const deleteButton = document.querySelector('.danger-content + .modal-actions .btn-danger');
    const originalText = deleteButton.textContent;
    deleteButton.textContent = 'Deleting...';
    deleteButton.disabled = true;
    
    // Simulate account deletion
    setTimeout(() => {
        // Clear all user data
        localStorage.removeItem('user_data');
        sessionStorage.removeItem('user_data');
        
        // Show final message and redirect
        alert('Account deleted successfully. You will be redirected to the home page.');
        window.location.href = 'index.html';
    }, 3000);
}

function showSuccessMessage(message) {
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

function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}