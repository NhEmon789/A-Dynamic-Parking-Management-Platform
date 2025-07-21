// Spot details page functionality
document.addEventListener('DOMContentLoaded', function() {
    initializeSpotDetails();
    initializeCarousel();
    initializeBookingForm();
    initializeSpotMap();
    loadSpotData();
});

let currentSlide = 0;
let totalSlides = 5;
let spotData = null;

function initializeSpotDetails() {
    // Initialize role toggle
    const roleToggle = document.getElementById('roleToggle');
    if (roleToggle) {
        roleToggle.addEventListener('click', function() {
            window.location.href = 'host-dashboard.html';
        });
    }
}

function initializeCarousel() {
    const indicators = document.querySelectorAll('.indicator');
    indicators.forEach((indicator, index) => {
        indicator.addEventListener('click', () => goToSlide(index));
    });
}

function initializeBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    if (bookingForm) {
        bookingForm.addEventListener('submit', handleBookingSubmit);
    }
    
    // Update cost calculation when form changes
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    
    if (startTime && endTime) {
        startTime.addEventListener('change', updateCostCalculation);
        endTime.addEventListener('change', updateCostCalculation);
    }
}

function initializeSpotMap() {
    // Initialize Google Map for spot location
    const mapOptions = {
        zoom: 16,
        center: { lat: 40.7128, lng: -74.0060 }, // Default to NYC
        styles: [
            {
                featureType: "all",
                elementType: "geometry.fill",
                stylers: [{ weight: "2.00" }]
            },
            {
                featureType: "all",
                elementType: "geometry.stroke",
                stylers: [{ color: "#9c9c9c" }]
            }
        ]
    };
    
    const map = new google.maps.Map(document.getElementById('spotMap'), mapOptions);
    
    // Add marker for the spot
    const marker = new google.maps.Marker({
        position: { lat: 40.7128, lng: -74.0060 },
        map: map,
        title: 'Downtown Parking Garage',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="40" height="40" viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="20" cy="20" r="18" fill="#3F3F3F" stroke="#fff" stroke-width="2"/>
                    <text x="20" y="26" text-anchor="middle" fill="white" font-size="12" font-family="Arial">P</text>
                </svg>
            `),
            scaledSize: new google.maps.Size(40, 40)
        }
    });
}

function loadSpotData() {
    // Get spot ID from localStorage (set from search page)
    const spotId = localStorage.getItem('selectedSpotId');
    
    // Mock spot data (in real app, this would come from API)
    spotData = {
        id: 1,
        name: "Downtown Parking Garage",
        address: "123 Main Street, Downtown, NY 10001",
        host: "John Smith",
        price: 8.00,
        rating: 4.5,
        reviews: 127,
        distance: 0.3,
        amenities: ["covered", "security", "ev", "handicap", "24_7"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: { lat: 40.7128, lng: -74.0060 }
    };
    
    // Update page with spot data
    updateSpotInfo();
}

function updateSpotInfo() {
    if (!spotData) return;
    
    // Update basic info
    document.querySelector('.spot-header h1').textContent = spotData.name;
    document.querySelector('.spot-rating .rating-text').textContent = `${spotData.rating} (${spotData.reviews} reviews)`;
    
    // Update details
    const addressElement = document.querySelector('.detail-item .value');
    if (addressElement) {
        addressElement.textContent = spotData.address;
    }
    
    // Update pricing
    const priceElements = document.querySelectorAll('.price-display');
    priceElements.forEach(element => {
        element.innerHTML = `$${spotData.price.toFixed(2)} <span>/hour</span>`;
    });
    
    // Update amenities
    const amenitiesBadges = document.querySelector('.amenity-badges');
    if (amenitiesBadges) {
        amenitiesBadges.innerHTML = spotData.amenities.map(amenity => 
            `<span class="badge">${getAmenityName(amenity)}</span>`
        ).join('');
    }
}

function getAmenityName(amenity) {
    const names = {
        covered: 'Covered',
        security: 'Security Cameras',
        ev: 'EV Charging',
        handicap: 'Handicap Accessible',
        '24_7': '24/7 Access'
    };
    return names[amenity] || amenity;
}

function previousSlide() {
    currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
    updateCarousel();
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % totalSlides;
    updateCarousel();
}

function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
}

function updateCarousel() {
    const track = document.getElementById('carouselTrack');
    const indicators = document.querySelectorAll('.indicator');
    
    if (track) {
        track.style.transform = `translateX(-${currentSlide * 100}%)`;
    }
    
    // Update indicators
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentSlide);
    });
}

function updateCostCalculation() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const hourlyRate = spotData ? spotData.price : 8.00;
    
    if (startTime && endTime) {
        const start = new Date(`2000-01-01T${startTime}`);
        const end = new Date(`2000-01-01T${endTime}`);
        
        let duration = (end - start) / (1000 * 60 * 60); // Convert to hours
        
        // Handle overnight parking
        if (duration <= 0) {
            duration = 24 + duration;
        }
        
        const subtotal = duration * hourlyRate;
        const serviceFee = 2.00;
        const total = subtotal + serviceFee;
        
        // Update display
        document.getElementById('duration').textContent = `${duration} hours`;
        document.getElementById('totalCost').textContent = `$${total.toFixed(2)}`;
    }
}

function handleBookingSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const bookingData = {
        spotId: spotData.id,
        date: formData.get('date'),
        startTime: formData.get('startTime'),
        endTime: formData.get('endTime'),
        vehicleType: formData.get('vehicleType'),
        total: document.getElementById('totalCost').textContent
    };
    
    // Show booking modal
    showBookingModal(bookingData);
}

function showBookingModal(bookingData) {
    const modal = document.getElementById('bookingModal');
    modal.style.display = 'flex';
    
    // Update modal content
    document.getElementById('summaryDate').textContent = formatDate(bookingData.date);
    document.getElementById('summaryTime').textContent = `${formatTime(bookingData.startTime)} - ${formatTime(bookingData.endTime)}`;
    document.getElementById('summaryVehicle').textContent = bookingData.vehicleType;
    document.getElementById('summaryTotal').textContent = bookingData.total;
    
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

function closeBookingModal() {
    const modal = document.getElementById('bookingModal');
    const modalContent = modal.querySelector('.modal-content');
    
    modalContent.style.opacity = '0';
    modalContent.style.transform = 'scale(0.9)';
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
}

function processPayment() {
    // Simulate payment processing
    const confirmButton = document.querySelector('.modal-actions .btn-primary');
    const originalText = confirmButton.textContent;
    
    confirmButton.textContent = 'Processing...';
    confirmButton.disabled = true;
    
    setTimeout(() => {
        closeBookingModal();
        showSuccessModal();
        
        // Reset button
        confirmButton.textContent = originalText;
        confirmButton.disabled = false;
    }, 2000);
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
        // Redirect to bookings page
        window.location.href = 'customer-bookings.html';
    }, 300);
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
}

function formatTime(timeString) {
    const time = new Date(`2000-01-01T${timeString}`);
    return time.toLocaleTimeString('en-US', { 
        hour: 'numeric', 
        minute: '2-digit', 
        hour12: true 
    });
}

function logout() {
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    window.location.href = 'index.html';
}

// Initialize spot map when Google Maps API is loaded
window.initSpotMap = function() {
    initializeSpotMap();
};