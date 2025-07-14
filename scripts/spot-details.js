// Spot Details Page JavaScript

let currentSpot = null;
let currentImageIndex = 0;
let spotMap = null;

document.addEventListener('DOMContentLoaded', () => {
    loadSpotDetails();
    initializeCarousel();
    initializeBookingForm();
    initializeModals();
});

// Initialize Google Maps for spot location
function initSpotMap() {
    if (currentSpot) {
        spotMap = new google.maps.Map(document.getElementById('spotMap'), {
            zoom: 15,
            center: { lat: currentSpot.lat, lng: currentSpot.lng },
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        // Add marker for the spot
        const marker = new google.maps.Marker({
            position: { lat: currentSpot.lat, lng: currentSpot.lng },
            map: spotMap,
            title: currentSpot.name,
            icon: {
                url: createSpotMarkerIcon(),
                scaledSize: new google.maps.Size(40, 40),
                anchor: new google.maps.Point(20, 40)
            }
        });

        // Add info window
        const infoWindow = new google.maps.InfoWindow({
            content: `
                <div style="padding: 10px;">
                    <h3 style="margin: 0 0 5px 0; color: #262626;">${currentSpot.name}</h3>
                    <p style="margin: 0; color: #666; font-size: 14px;">${currentSpot.address}</p>
                </div>
            `
        });

        marker.addListener('click', () => {
            infoWindow.open(spotMap, marker);
        });
    }
}

// Create custom marker icon for spot
function createSpotMarkerIcon() {
    const canvas = document.createElement('canvas');
    canvas.width = 40;
    canvas.height = 40;
    const ctx = canvas.getContext('2d');

    // Draw marker
    ctx.fillStyle = '#262626';
    ctx.beginPath();
    ctx.arc(20, 15, 12, 0, 2 * Math.PI);
    ctx.fill();

    // Draw pointer
    ctx.beginPath();
    ctx.moveTo(20, 27);
    ctx.lineTo(15, 35);
    ctx.lineTo(25, 35);
    ctx.closePath();
    ctx.fill();

    // Draw inner circle
    ctx.fillStyle = 'white';
    ctx.beginPath();
    ctx.arc(20, 15, 6, 0, 2 * Math.PI);
    ctx.fill();

    return canvas.toDataURL();
}

// Load spot details
function loadSpotDetails() {
    // Get spot ID from URL or localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const spotId = urlParams.get('id') || utils.storage.get('selectedSpotId');

    if (!spotId) {
        window.location.href = 'search.html';
        return;
    }

    // Mock spot data (in real app, this would be fetched from API)
    const spots = [
        {
            id: 1,
            name: "Dhanmondi Parking Plaza",
            address: "Road 27, Dhanmondi, Dhaka",
            lat: 23.7461,
            lng: 90.3742,
            price: 80,
            carTypes: ['sedan', 'hatchback', 'suv'],
            amenities: ['covered', 'security'],
            rating: 4.5,
            distance: 2.3,
            hostName: "Ahmed Hassan",
            images: [
                "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=400&fit=crop",
                "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=400&fit=crop",
                "https://images.unsplash.com/photo-1486325212027-8081e485255e?w=800&h=400&fit=crop"
            ],
            schedule: {
                monday: { available: true, hours: "6:00 AM - 10:00 PM" },
                tuesday: { available: true, hours: "6:00 AM - 10:00 PM" },
                wednesday: { available: true, hours: "6:00 AM - 10:00 PM" },
                thursday: { available: true, hours: "6:00 AM - 10:00 PM" },
                friday: { available: true, hours: "6:00 AM - 10:00 PM" },
                saturday: { available: true, hours: "8:00 AM - 8:00 PM" },
                sunday: { available: false, hours: "Closed" }
            },
            reviews: [
                {
                    id: 1,
                    userName: "Sarah Ahmed",
                    rating: 5,
                    date: "2024-01-15",
                    comment: "Excellent parking spot! Very convenient location and the host was very helpful."
                },
                {
                    id: 2,
                    userName: "Mohammad Rahman",
                    rating: 4,
                    date: "2024-01-10",
                    comment: "Good spot, easy to find. Security was good. Will book again."
                }
            ]
        },
        {
            id: 2,
            name: "Gulshan Commercial Parking",
            address: "Gulshan Avenue, Gulshan-1, Dhaka",
            lat: 23.7925,
            lng: 90.4078,
            price: 120,
            carTypes: ['sedan', 'suv', 'minivan'],
            amenities: ['covered', 'security', 'ev-charger'],
            rating: 4.8,
            distance: 1.8,
            hostName: "Fatima Khan",
            images: [
                "https://images.unsplash.com/photo-1486325212027-8081e485255e?w=800&h=400&fit=crop",
                "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=400&fit=crop"
            ],
            schedule: {
                monday: { available: true, hours: "24 Hours" },
                tuesday: { available: true, hours: "24 Hours" },
                wednesday: { available: true, hours: "24 Hours" },
                thursday: { available: true, hours: "24 Hours" },
                friday: { available: true, hours: "24 Hours" },
                saturday: { available: true, hours: "24 Hours" },
                sunday: { available: true, hours: "24 Hours" }
            },
            reviews: [
                {
                    id: 1,
                    userName: "John Smith",
                    rating: 5,
                    date: "2024-01-20",
                    comment: "Perfect for business meetings in Gulshan. EV charging was a bonus!"
                }
            ]
        }
    ];

    currentSpot = spots.find(spot => spot.id == spotId);
    
    if (!currentSpot) {
        // Generate mock data for other IDs
        currentSpot = {
            id: parseInt(spotId),
            name: `Parking Spot ${spotId}`,
            address: `Location ${spotId}, Dhaka`,
            lat: 23.8103 + (Math.random() - 0.5) * 0.2,
            lng: 90.4125 + (Math.random() - 0.5) * 0.2,
            price: Math.floor(Math.random() * 100) + 40,
            carTypes: ['sedan', 'hatchback', 'suv'].slice(0, Math.floor(Math.random() * 3) + 1),
            amenities: ['covered', 'security', 'ev-charger', 'handicap'].slice(0, Math.floor(Math.random() * 3) + 1),
            rating: Math.round((Math.random() * 2 + 3) * 10) / 10,
            distance: Math.round((Math.random() * 8 + 1) * 10) / 10,
            hostName: "Host Name",
            images: [
                "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=800&h=400&fit=crop",
                "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=800&h=400&fit=crop"
            ],
            schedule: {
                monday: { available: true, hours: "6:00 AM - 10:00 PM" },
                tuesday: { available: true, hours: "6:00 AM - 10:00 PM" },
                wednesday: { available: true, hours: "6:00 AM - 10:00 PM" },
                thursday: { available: true, hours: "6:00 AM - 10:00 PM" },
                friday: { available: true, hours: "6:00 AM - 10:00 PM" },
                saturday: { available: true, hours: "8:00 AM - 8:00 PM" },
                sunday: { available: false, hours: "Closed" }
            },
            reviews: []
        };
    }

    populateSpotDetails();
}

// Populate spot details in the UI
function populateSpotDetails() {
    // Basic info
    document.getElementById('spotTitle').textContent = currentSpot.name;
    document.getElementById('spotAddress').textContent = currentSpot.address;
    document.getElementById('hostName').textContent = currentSpot.hostName;
    document.getElementById('spotDistance').textContent = `${currentSpot.distance} km away`;
    document.getElementById('spotPrice').textContent = `৳${currentSpot.price}/hr`;
    document.getElementById('bookingPrice').textContent = `৳${currentSpot.price}`;

    // Rating
    populateRating();
    
    // Images
    populateImages();
    
    // Car types
    populateCarTypes();
    
    // Amenities
    populateAmenities();
    
    // Schedule
    populateSchedule();
    
    // Reviews
    populateReviews();
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bookingDate').value = today;
    
    // Check if spot is favorited
    const favorites = utils.storage.get('favorites') || [];
    if (favorites.includes(currentSpot.id)) {
        document.getElementById('favoriteBtn').classList.add('active');
    }
}

// Populate rating stars
function populateRating() {
    const ratingContainer = document.getElementById('spotRating');
    const ratingText = document.getElementById('ratingText');
    
    ratingContainer.innerHTML = '';
    
    for (let i = 1; i <= 5; i++) {
        const star = document.createElement('i');
        star.className = i <= currentSpot.rating ? 'fas fa-star' : 'fas fa-star empty';
        ratingContainer.appendChild(star);
    }
    
    ratingText.textContent = `${currentSpot.rating} (${currentSpot.reviews.length} reviews)`;
}

// Populate images carousel
function populateImages() {
    const carouselWrapper = document.getElementById('carouselWrapper');
    const indicatorsContainer = document.getElementById('carouselIndicators');
    
    carouselWrapper.innerHTML = '';
    indicatorsContainer.innerHTML = '';
    
    currentSpot.images.forEach((image, index) => {
        // Create slide
        const slide = document.createElement('div');
        slide.className = 'carousel-slide';
        slide.innerHTML = `<img src="${image}" alt="${currentSpot.name} - Image ${index + 1}">`;
        carouselWrapper.appendChild(slide);
        
        // Create indicator
        const indicator = document.createElement('div');
        indicator.className = `indicator ${index === 0 ? 'active' : ''}`;
        indicator.addEventListener('click', () => goToSlide(index));
        indicatorsContainer.appendChild(indicator);
    });
}

// Populate car types
function populateCarTypes() {
    const carTypesGrid = document.getElementById('carTypesGrid');
    const carTypeSelect = document.getElementById('carType');
    
    const allCarTypes = [
        { type: 'sedan', icon: 'car', name: 'Sedan' },
        { type: 'suv', icon: 'truck', name: 'SUV' },
        { type: 'hatchback', icon: 'car-side', name: 'Hatchback' },
        { type: 'minivan', icon: 'shuttle-van', name: 'Minivan' },
        { type: 'truck', icon: 'truck-pickup', name: 'Truck' }
    ];
    
    carTypesGrid.innerHTML = '';
    carTypeSelect.innerHTML = '<option value="">Select your vehicle type</option>';
    
    allCarTypes.forEach(carType => {
        const isSupported = currentSpot.carTypes.includes(carType.type);
        
        // Grid item
        const item = document.createElement('div');
        item.className = `car-type-item ${isSupported ? 'supported' : 'not-supported'}`;
        item.innerHTML = `
            <i class="fas fa-${carType.icon}"></i>
            <span>${carType.name}</span>
        `;
        carTypesGrid.appendChild(item);
        
        // Select option (only for supported types)
        if (isSupported) {
            const option = document.createElement('option');
            option.value = carType.type;
            option.textContent = carType.name;
            carTypeSelect.appendChild(option);
        }
    });
}

// Populate amenities
function populateAmenities() {
    const amenitiesGrid = document.getElementById('amenitiesGrid');
    
    const amenityDetails = {
        covered: {
            icon: 'home',
            name: 'Covered Parking',
            description: 'Protected from weather'
        },
        security: {
            icon: 'shield-alt',
            name: 'Security',
            description: '24/7 security monitoring'
        },
        'ev-charger': {
            icon: 'charging-station',
            name: 'EV Charger',
            description: 'Electric vehicle charging'
        },
        handicap: {
            icon: 'wheelchair',
            name: 'Handicap Access',
            description: 'Wheelchair accessible'
        }
    };
    
    amenitiesGrid.innerHTML = '';
    
    currentSpot.amenities.forEach(amenity => {
        const details = amenityDetails[amenity];
        if (details) {
            const item = document.createElement('div');
            item.className = 'amenity-item';
            item.innerHTML = `
                <div class="amenity-icon">
                    <i class="fas fa-${details.icon}"></i>
                </div>
                <div class="amenity-info">
                    <h4>${details.name}</h4>
                    <p>${details.description}</p>
                </div>
            `;
            amenitiesGrid.appendChild(item);
        }
    });
}

// Populate schedule
function populateSchedule() {
    const scheduleGrid = document.getElementById('scheduleGrid');
    
    const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
    const dayNames = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    
    scheduleGrid.innerHTML = '';
    
    days.forEach((day, index) => {
        const schedule = currentSpot.schedule[day];
        const item = document.createElement('div');
        item.className = `schedule-day ${schedule.available ? 'available' : 'unavailable'}`;
        item.innerHTML = `
            <div class="day-name">${dayNames[index]}</div>
            <div class="day-hours">${schedule.hours}</div>
        `;
        scheduleGrid.appendChild(item);
    });
}

// Populate reviews
function populateReviews() {
    const reviewsContainer = document.getElementById('reviewsContainer');
    
    if (currentSpot.reviews.length === 0) {
        reviewsContainer.innerHTML = '<p style="color: var(--text-lighter); text-align: center;">No reviews yet.</p>';
        return;
    }
    
    reviewsContainer.innerHTML = '';
    
    currentSpot.reviews.forEach(review => {
        const item = document.createElement('div');
        item.className = 'review-item';
        item.innerHTML = `
            <div class="review-header">
                <div class="reviewer-info">
                    <div class="reviewer-avatar">${review.userName.charAt(0)}</div>
                    <div>
                        <div class="reviewer-name">${review.userName}</div>
                        <div class="review-date">${utils.formatDate(review.date)}</div>
                    </div>
                </div>
                <div class="review-rating">
                    ${Array.from({length: 5}, (_, i) => 
                        `<i class="fas fa-star ${i < review.rating ? '' : 'empty'}"></i>`
                    ).join('')}
                </div>
            </div>
            <div class="review-text">${review.comment}</div>
        `;
        reviewsContainer.appendChild(item);
    });
}

// Initialize carousel
function initializeCarousel() {
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    
    prevBtn.addEventListener('click', () => {
        currentImageIndex = currentImageIndex > 0 ? currentImageIndex - 1 : currentSpot.images.length - 1;
        updateCarousel();
    });
    
    nextBtn.addEventListener('click', () => {
        currentImageIndex = currentImageIndex < currentSpot.images.length - 1 ? currentImageIndex + 1 : 0;
        updateCarousel();
    });
    
    // Auto-advance carousel
    setInterval(() => {
        if (currentSpot && currentSpot.images.length > 1) {
            currentImageIndex = currentImageIndex < currentSpot.images.length - 1 ? currentImageIndex + 1 : 0;
            updateCarousel();
        }
    }, 5000);
}

// Update carousel position
function updateCarousel() {
    const carouselWrapper = document.getElementById('carouselWrapper');
    const indicators = document.querySelectorAll('.indicator');
    
    carouselWrapper.style.transform = `translateX(-${currentImageIndex * 100}%)`;
    
    indicators.forEach((indicator, index) => {
        indicator.classList.toggle('active', index === currentImageIndex);
    });
}

// Go to specific slide
function goToSlide(index) {
    currentImageIndex = index;
    updateCarousel();
}

// Initialize booking form
function initializeBookingForm() {
    const bookingForm = document.getElementById('bookingForm');
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    
    // Update duration and cost when time changes
    startTime.addEventListener('change', updateBookingCalculation);
    endTime.addEventListener('change', updateBookingCalculation);
    
    // Form submission
    bookingForm.addEventListener('submit', (e) => {
        e.preventDefault();
        showBookingModal();
    });
    
    // Initial calculation
    updateBookingCalculation();
}

// Update booking duration and cost calculation
function updateBookingCalculation() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    
    if (!startTime || !endTime) return;
    
    const start = new Date(`2000-01-01T${startTime}`);
    const end = new Date(`2000-01-01T${endTime}`);
    
    if (end <= start) {
        end.setDate(end.getDate() + 1); // Next day
    }
    
    const durationMs = end - start;
    const durationHours = durationMs / (1000 * 60 * 60);
    const totalCost = Math.round(durationHours * currentSpot.price);
    
    document.getElementById('durationValue').textContent = `${durationHours} hours`;
    document.getElementById('totalCost').textContent = `৳${totalCost}`;
}

// Initialize modals
function initializeModals() {
    // Payment method toggle
    document.querySelectorAll('input[name="payment"]').forEach(radio => {
        radio.addEventListener('change', (e) => {
            const cardDetails = document.getElementById('cardDetails');
            cardDetails.style.display = e.target.value === 'card' ? 'block' : 'none';
        });
    });
    
    // Card number formatting
    const cardNumber = document.getElementById('cardNumber');
    if (cardNumber) {
        cardNumber.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\s/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            e.target.value = formattedValue;
        });
    }
    
    // Expiry date formatting
    const expiryDate = document.getElementById('expiryDate');
    if (expiryDate) {
        expiryDate.addEventListener('input', (e) => {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 2) {
                value = value.substring(0, 2) + '/' + value.substring(2, 4);
            }
            e.target.value = value;
        });
    }
}

// Show booking modal
function showBookingModal() {
    const modal = document.getElementById('bookingModal');
    
    // Populate booking summary
    const formData = new FormData(document.getElementById('bookingForm'));
    const date = formData.get('date');
    const startTime = formData.get('startTime');
    const endTime = formData.get('endTime');
    const carType = formData.get('carType');
    
    if (!date || !startTime || !endTime || !carType) {
        alert('Please fill in all required fields');
        return;
    }
    
    const start = new Date(`2000-01-01T${startTime}`);
    const end = new Date(`2000-01-01T${endTime}`);
    if (end <= start) end.setDate(end.getDate() + 1);
    
    const durationHours = (end - start) / (1000 * 60 * 60);
    const totalCost = Math.round(durationHours * currentSpot.price);
    
    document.getElementById('summarySpotName').textContent = currentSpot.name;
    document.getElementById('summaryDate').textContent = utils.formatDate(date);
    document.getElementById('summaryTime').textContent = `${utils.formatTime(startTime)} - ${utils.formatTime(endTime)}`;
    document.getElementById('summaryDuration').textContent = `${durationHours} hours`;
    document.getElementById('summaryCarType').textContent = carType.charAt(0).toUpperCase() + carType.slice(1);
    document.getElementById('summaryTotal').textContent = `৳${totalCost}`;
    
    modal.classList.add('show');
}

// Close booking modal
function closeBookingModal() {
    document.getElementById('bookingModal').classList.remove('show');
}

// Confirm booking
function confirmBooking() {
    const confirmBtn = event.target;
    const originalText = confirmBtn.innerHTML;
    
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
    confirmBtn.disabled = true;
    
    setTimeout(() => {
        // Generate booking ID
        const bookingId = `FMS-${Date.now().toString().slice(-5)}`;
        
        // Store booking (mock)
        const bookingData = {
            id: bookingId,
            spotId: currentSpot.id,
            spotName: currentSpot.name,
            date: document.getElementById('bookingDate').value,
            startTime: document.getElementById('startTime').value,
            endTime: document.getElementById('endTime').value,
            carType: document.getElementById('carType').value,
            totalCost: document.getElementById('totalCost').textContent,
            status: 'confirmed',
            bookedAt: new Date().toISOString()
        };
        
        // Store in localStorage
        let bookings = utils.storage.get('customerBookings') || [];
        bookings.unshift(bookingData);
        utils.storage.set('customerBookings', bookings);
        
        // Close booking modal and show success
        closeBookingModal();
        showSuccessModal(bookingId);
        
        // Reset button
        confirmBtn.innerHTML = originalText;
        confirmBtn.disabled = false;
    }, 2000);
}

// Show success modal
function showSuccessModal(bookingId) {
    document.getElementById('bookingId').textContent = `#${bookingId}`;
    document.getElementById('successModal').classList.add('show');
}

// Close success modal
function closeSuccessModal() {
    document.getElementById('successModal').classList.remove('show');
}

// View bookings
function viewBookings() {
    window.location.href = 'customer-bookings.html';
}

// Toggle favorite
function toggleFavorite() {
    const btn = document.getElementById('favoriteBtn');
    btn.classList.toggle('active');
    
    let favorites = utils.storage.get('favorites') || [];
    if (btn.classList.contains('active')) {
        favorites.push(currentSpot.id);
    } else {
        favorites = favorites.filter(id => id !== currentSpot.id);
    }
    utils.storage.set('favorites', favorites);
}

// Share spot
function shareSpot() {
    if (navigator.share) {
        navigator.share({
            title: currentSpot.name,
            text: `Check out this parking spot: ${currentSpot.name}`,
            url: window.location.href
        });
    } else {
        // Fallback: copy to clipboard
        navigator.clipboard.writeText(window.location.href).then(() => {
            alert('Link copied to clipboard!');
        });
    }
}

// Go back to search
function goBack() {
    window.history.back();
}

// Logout
function logout() {
    utils.storage.remove('isLoggedIn');
    utils.storage.remove('userData');
    utils.storage.remove('userType');
    window.location.href = 'index.html';
}