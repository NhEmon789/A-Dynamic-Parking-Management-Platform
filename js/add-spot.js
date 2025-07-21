// Add spot functionality with Leaflet map integration
document.addEventListener('DOMContentLoaded', function() {
    initializeAddSpotPage();
    initializePhotoUpload();
    initializeFormValidation();
    
    // Initialize map after Leaflet is loaded
    if (typeof L !== 'undefined') {
        initializeMap();
    } else {
        // Wait for Leaflet to load
        const checkLeaflet = setInterval(() => {
            if (typeof L !== 'undefined') {
                clearInterval(checkLeaflet);
                initializeMap();
            }
        }, 100);
    }
});

let map;
let marker;
let selectedLocation = null;

function initializeAddSpotPage() {
    // Initialize role toggle
    const roleToggle = document.getElementById('roleToggle');
    if (roleToggle) {
        roleToggle.addEventListener('click', function() {
            window.location.href = 'customer-dashboard.html';
        });
    }
    
    // Initialize form submission
    const addSpotForm = document.getElementById('addSpotForm');
    if (addSpotForm) {
        addSpotForm.addEventListener('submit', handleFormSubmit);
    }
    
    // Initialize address geocoding
    const addressInput = document.getElementById('address');
    if (addressInput) {
        addressInput.addEventListener('blur', geocodeAddress);
    }
}

function initializePhotoUpload() {
    const photoUpload = document.getElementById('photoUpload');
    const uploadArea = document.getElementById('uploadArea');
    const photoPreview = document.getElementById('photoPreview');
    
    if (photoUpload && uploadArea) {
        // Handle file selection
        photoUpload.addEventListener('change', handlePhotoSelection);
        
        // Handle drag and drop
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            uploadArea.classList.add('drag-over');
        });
        
        uploadArea.addEventListener('dragleave', function() {
            uploadArea.classList.remove('drag-over');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            uploadArea.classList.remove('drag-over');
            
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                photoUpload.files = files;
                handlePhotoSelection({ target: { files: files } });
            }
        });
    }
}

function handlePhotoSelection(e) {
    const files = e.target.files;
    const photoPreview = document.getElementById('photoPreview');
    
    if (files.length > 5) {
        showErrorMessage('Maximum 5 photos allowed');
        return;
    }
    
    // Clear previous previews
    photoPreview.innerHTML = '';
    
    // Show previews
    Array.from(files).forEach((file, index) => {
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const previewItem = document.createElement('div');
                previewItem.className = 'photo-preview-item';
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}">
                    <button type="button" class="remove-photo" onclick="removePhoto(${index})">×</button>
                `;
                photoPreview.appendChild(previewItem);
                
                // Add animation
                previewItem.style.opacity = '0';
                previewItem.style.transform = 'scale(0.8)';
                previewItem.style.transition = 'all 0.3s ease';
                
                setTimeout(() => {
                    previewItem.style.opacity = '1';
                    previewItem.style.transform = 'scale(1)';
                }, 100);
            };
            reader.readAsDataURL(file);
        }
    });
}

function removePhoto(index) {
    const photoPreview = document.getElementById('photoPreview');
    const previewItems = photoPreview.querySelectorAll('.photo-preview-item');
    
    if (previewItems[index]) {
        previewItems[index].style.opacity = '0';
        previewItems[index].style.transform = 'scale(0.8)';
        
        setTimeout(() => {
            previewItems[index].remove();
        }, 300);
    }
}

function initializeMap() {
    // Initialize Leaflet map
    map = L.map('addSpotMap').setView([40.7128, -74.0060], 13);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add click listener to map
    map.on('click', function(e) {
        setMapMarker(e.latlng);
    });
}

function setMapMarker(latlng) {
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }
    
    // Create custom icon
    const customIcon = L.divIcon({
        className: 'custom-marker',
        html: `<div style="background: #3F3F3F; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">P</div>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20]
    });
    
    // Add new marker
    marker = L.marker(latlng, { 
        icon: customIcon,
        draggable: true 
    }).addTo(map);
    
    // Store selected location
    selectedLocation = {
        lat: latlng.lat,
        lng: latlng.lng
    };
    
    // Add drag listener
    marker.on('dragend', function() {
        const position = marker.getLatLng();
        selectedLocation = {
            lat: position.lat,
            lng: position.lng
        };
        reverseGeocode(position);
    });
    
    // Reverse geocode to get address
    reverseGeocode(latlng);
}

function geocodeAddress() {
    const address = document.getElementById('address').value;
    if (!address) return;
    
    // Simple geocoding using Nominatim (OpenStreetMap)
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const result = data[0];
                const latlng = L.latLng(parseFloat(result.lat), parseFloat(result.lon));
                map.setView(latlng, 15);
                setMapMarker(latlng);
            }
        })
        .catch(error => {
            console.error('Geocoding error:', error);
        });
}

function reverseGeocode(latlng) {
    // Simple reverse geocoding using Nominatim
    fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latlng.lat}&lon=${latlng.lng}`)
        .then(response => response.json())
        .then(data => {
            if (data && data.display_name) {
                const addressInput = document.getElementById('address');
                if (addressInput && !addressInput.value) {
                    addressInput.value = data.display_name;
                }
            }
        })
        .catch(error => {
            console.error('Reverse geocoding error:', error);
        });
}

function initializeFormValidation() {
    const requiredFields = document.querySelectorAll('input[required], textarea[required]');
    
    requiredFields.forEach(field => {
        field.addEventListener('blur', function() {
            validateField(this);
        });
        
        field.addEventListener('input', function() {
            if (this.classList.contains('error')) {
                validateField(this);
            }
        });
    });
}

function validateField(field) {
    const value = field.value.trim();
    let isValid = true;
    
    // Clear previous error
    field.classList.remove('error');
    
    // Required field validation
    if (field.required && !value) {
        isValid = false;
        field.classList.add('error');
    }
    
    // Specific field validations
    if (field.type === 'number' && value) {
        const num = parseFloat(value);
        if (isNaN(num) || num <= 0) {
            isValid = false;
            field.classList.add('error');
        }
    }
    
    return isValid;
}

function handleFormSubmit(e) {
    e.preventDefault();
    
    // Validate form
    if (!validateForm()) {
        showErrorMessage('Please fill in all required fields');
        return;
    }
    
    // Check if location is selected
    if (!selectedLocation) {
        showErrorMessage('Please select a location on the map');
        return;
    }
    
    // Collect form data
    const formData = new FormData(e.target);
    const spotData = {
        id: Date.now(),
        name: formData.get('spotName'),
        description: formData.get('description'),
        address: formData.get('address'),
        location: selectedLocation,
        hourlyRate: parseFloat(formData.get('hourlyRate')),
        currency: formData.get('currency'),
        vehicleTypes: formData.getAll('vehicleTypes'),
        amenities: formData.getAll('amenities'),
        availability: collectAvailabilityData(formData),
        available: true,
        createdAt: new Date().toISOString(),
        status: 'active'
    };
    
    // Show loading state
    const submitButton = e.target.querySelector('button[type="submit"]');
    const originalText = submitButton.textContent;
    submitButton.textContent = 'Publishing...';
    submitButton.disabled = true;
    
    // Simulate submission
    setTimeout(() => {
        // Store spot data
        saveSpotData(spotData);
        
        // Reset button
        submitButton.textContent = originalText;
        submitButton.disabled = false;
        
        // Show success modal
        showSuccessModal();
    }, 2000);
}

function validateForm() {
    const requiredFields = document.querySelectorAll('input[required], textarea[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!validateField(field)) {
            isValid = false;
        }
    });
    
    return isValid;
}

function collectAvailabilityData(formData) {
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const availability = {};
    
    days.forEach(day => {
        const isAvailable = formData.getAll('days').includes(day);
        if (isAvailable) {
            availability[day] = {
                start: formData.get(`${day}_start`) || '08:00',
                end: formData.get(`${day}_end`) || '18:00'
            };
        }
    });
    
    return availability;
}

function saveSpotData(spotData) {
    // Get existing spots from localStorage
    const existingSpots = JSON.parse(localStorage.getItem('host_spots') || '[]');
    
    // Add new spot
    existingSpots.push(spotData);
    
    // Save back to localStorage
    localStorage.setItem('host_spots', JSON.stringify(existingSpots));
}

function saveDraft() {
    // Collect current form data
    const formData = new FormData(document.getElementById('addSpotForm'));
    const draftData = {};
    
    for (let [key, value] of formData.entries()) {
        draftData[key] = value;
    }
    
    // Save draft
    localStorage.setItem('spot_draft', JSON.stringify(draftData));
    
    showSuccessMessage('Draft saved successfully!');
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
        // Reset form
        document.getElementById('addSpotForm').reset();
        document.getElementById('photoPreview').innerHTML = '';
        
        // Clear map marker
        if (marker) {
            map.removeLayer(marker);
            marker = null;
        }
        selectedLocation = null;
    }, 300);
}

function goToListings() {
    window.location.href = 'host-listings.html';
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

function showErrorMessage(message) {
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

function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}

// Add Leaflet CSS
const leafletCSS = document.createElement('link');
leafletCSS.rel = 'stylesheet';
leafletCSS.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
document.head.appendChild(leafletCSS);

// Add Leaflet JS
const leafletJS = document.createElement('script');
leafletJS.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
document.head.appendChild(leafletJS);