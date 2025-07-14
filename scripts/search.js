// Search Page JavaScript with Google Maps Integration

let map;
let markers = [];
let infoWindow;
let currentLocation = { lat: 23.8103, lng: 90.4125 }; // Dhaka coordinates
let spots = [];

// Initialize Google Maps
function initMap() {
    // Create map
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 13,
        center: currentLocation,
        styles: [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ],
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
    });

    // Create info window
    infoWindow = new google.maps.InfoWindow();

    // Initialize places service
    const placesService = new google.maps.places.PlacesService(map);

    // Load initial data
    loadParkingSpots();
    
    // Initialize search functionality
    initializeSearch();
    
    // Set up map event listeners
    map.addListener('idle', () => {
        updateVisibleSpots();
    });
}

// Initialize search functionality
function initializeSearch() {
    initFilters();
    initViewToggle();
    initMapControls();
    
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('startDate').value = today;
    
    // Load spots based on current filters
    filterAndDisplaySpots();
}

// Load parking spots data
function loadParkingSpots() {
    // Mock parking spots data
    spots = [
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
            available: true,
            image: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&h=200&fit=crop"
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
            available: true,
            image: "https://images.unsplash.com/photo-1486325212027-8081e485255e?w=300&h=200&fit=crop"
        },
        {
            id: 3,
            name: "Banani Office Complex",
            address: "Kemal Ataturk Avenue, Banani, Dhaka",
            lat: 23.7936,
            lng: 90.4066,
            price: 100,
            carTypes: ['sedan', 'hatchback'],
            amenities: ['security', 'handicap'],
            rating: 4.2,
            distance: 3.1,
            available: true,
            image: "https://images.unsplash.com/photo-1480714378408-67cf0d13bc1f?w=300&h=200&fit=crop"
        },
        {
            id: 4,
            name: "Uttara Residential Parking",
            address: "Sector 7, Uttara, Dhaka",
            lat: 23.8759,
            lng: 90.3795,
            price: 60,
            carTypes: ['sedan', 'hatchback', 'suv', 'truck'],
            amenities: ['covered'],
            rating: 4.0,
            distance: 5.2,
            available: true,
            image: "https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=300&h=200&fit=crop"
        },
        {
            id: 5,
            name: "Motijheel Business District",
            address: "Motijheel Commercial Area, Dhaka",
            lat: 23.7337,
            lng: 90.4172,
            price: 90,
            carTypes: ['sedan', 'hatchback', 'minivan'],
            amenities: ['security', 'ev-charger'],
            rating: 4.3,
            distance: 4.5,
            available: true,
            image: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&h=200&fit=crop"
        },
        {
            id: 6,
            name: "Wari Heritage Parking",
            address: "Wari, Old Dhaka",
            lat: 23.7104,
            lng: 90.4074,
            price: 50,
            carTypes: ['sedan', 'hatchback'],
            amenities: ['handicap'],
            rating: 3.8,
            distance: 6.1,
            available: true,
            image: "https://images.unsplash.com/photo-1486325212027-8081e485255e?w=300&h=200&fit=crop"
        }
    ];

    // Add more spots for demonstration
    for (let i = 7; i <= 24; i++) {
        spots.push({
            id: i,
            name: `Parking Spot ${i}`,
            address: `Location ${i}, Dhaka`,
            lat: 23.8103 + (Math.random() - 0.5) * 0.2,
            lng: 90.4125 + (Math.random() - 0.5) * 0.2,
            price: Math.floor(Math.random() * 100) + 40,
            carTypes: ['sedan', 'hatchback', 'suv'].slice(0, Math.floor(Math.random() * 3) + 1),
            amenities: ['covered', 'security', 'ev-charger', 'handicap'].slice(0, Math.floor(Math.random() * 3) + 1),
            rating: Math.round((Math.random() * 2 + 3) * 10) / 10,
            distance: Math.round((Math.random() * 8 + 1) * 10) / 10,
            available: Math.random() > 0.1,
            image: "https://images.unsplash.com/photo-1506905925346-21bda4d32df4?w=300&h=200&fit=crop"
        });
    }

    createMapMarkers();
}

// Create map markers
function createMapMarkers() {
    // Clear existing markers
    markers.forEach(marker => marker.setMap(null));
    markers = [];

    spots.forEach(spot => {
        const marker = new google.maps.Marker({
            position: { lat: spot.lat, lng: spot.lng },
            map: map,
            title: spot.name,
            icon: {
                url: createMarkerIcon(spot.price, spot.available),
                scaledSize: new google.maps.Size(60, 40),
                anchor: new google.maps.Point(30, 40)
            }
        });

        // Add click listener
        marker.addListener('click', () => {
            showSpotInfoWindow(marker, spot);
            highlightSpotCard(spot.id);
        });

        markers.push({ marker, spot });
    });
}

// Create custom marker icon
function createMarkerIcon(price, available) {
    const canvas = document.createElement('canvas');
    canvas.width = 60;
    canvas.height = 40;
    const ctx = canvas.getContext('2d');

    // Background
    ctx.fillStyle = available ? '#262626' : '#999999';
    ctx.fillRect(0, 0, 60, 30);
    ctx.beginPath();
    ctx.moveTo(30, 30);
    ctx.lineTo(25, 40);
    ctx.lineTo(35, 40);
    ctx.closePath();
    ctx.fill();

    // Text
    ctx.fillStyle = 'white';
    ctx.font = 'bold 10px Inter';
    ctx.textAlign = 'center';
    ctx.fillText(`৳${price}`, 30, 20);

    return canvas.toDataURL();
}

// Show spot info window
function showSpotInfoWindow(marker, spot) {
    const content = `
        <div style="padding: 10px; max-width: 250px;">
            <h3 style="margin: 0 0 8px 0; color: #262626;">${spot.name}</h3>
            <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">
                <i class="fas fa-map-marker-alt"></i> ${spot.address}
            </p>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                <span style="font-size: 18px; font-weight: bold; color: #262626;">৳${spot.price}/hr</span>
                <span style="color: #666; font-size: 14px;">${spot.distance} km away</span>
            </div>
            <div style="margin-bottom: 10px;">
                ${spot.amenities.map(amenity => `
                    <span style="display: inline-block; background: #f0f0f0; padding: 2px 6px; border-radius: 12px; font-size: 12px; margin-right: 4px;">
                        ${amenity}
                    </span>
                `).join('')}
            </div>
            <button onclick="viewSpotDetails(${spot.id})" style="width: 100%; padding: 8px; background: #262626; color: white; border: none; border-radius: 6px; cursor: pointer;">
                View Details
            </button>
        </div>
    `;

    infoWindow.setContent(content);
    infoWindow.open(map, marker);
}

// Initialize filters
function initFilters() {
    // Price range slider
    const priceRange = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    const minPrice = document.getElementById('minPrice');
    const maxPrice = document.getElementById('maxPrice');

    priceRange.addEventListener('input', (e) => {
        priceValue.textContent = e.target.value;
        maxPrice.value = e.target.value;
        filterAndDisplaySpots();
    });

    minPrice.addEventListener('input', filterAndDisplaySpots);
    maxPrice.addEventListener('input', filterAndDisplaySpots);

    // Car type buttons
    document.querySelectorAll('.car-type-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            btn.classList.toggle('active');
            filterAndDisplaySpots();
        });
    });

    // Amenities checkboxes
    document.querySelectorAll('input[name="amenities"]').forEach(checkbox => {
        checkbox.addEventListener('change', filterAndDisplaySpots);
    });

    // Sort dropdown
    document.getElementById('sortBy').addEventListener('change', filterAndDisplaySpots);

    // Date and time inputs
    document.getElementById('startDate').addEventListener('change', filterAndDisplaySpots);
    document.getElementById('startTime').addEventListener('change', filterAndDisplaySpots);
    document.getElementById('endTime').addEventListener('change', filterAndDisplaySpots);

    // Location search
    document.getElementById('searchBtn').addEventListener('click', searchLocation);
    document.getElementById('locationSearch').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') {
            searchLocation();
        }
    });

    // Reset filters
    document.getElementById('resetFilters').addEventListener('click', resetFilters);
}

// Filter and display spots
function filterAndDisplaySpots() {
    showLoading();

    setTimeout(() => {
        const filteredSpots = applyFilters();
        displaySpots(filteredSpots);
        updateMapMarkers(filteredSpots);
        updateResultsCount(filteredSpots.length);
        hideLoading();
    }, 500);
}

// Apply filters to spots
function applyFilters() {
    let filtered = [...spots];

    // Price filter
    const minPrice = parseInt(document.getElementById('minPrice').value) || 0;
    const maxPrice = parseInt(document.getElementById('maxPrice').value) || 1000;
    filtered = filtered.filter(spot => spot.price >= minPrice && spot.price <= maxPrice);

    // Car type filter
    const selectedCarTypes = Array.from(document.querySelectorAll('.car-type-btn.active'))
        .map(btn => btn.dataset.type);
    if (selectedCarTypes.length > 0) {
        filtered = filtered.filter(spot => 
            selectedCarTypes.some(type => spot.carTypes.includes(type))
        );
    }

    // Amenities filter
    const selectedAmenities = Array.from(document.querySelectorAll('input[name="amenities"]:checked'))
        .map(checkbox => checkbox.value);
    if (selectedAmenities.length > 0) {
        filtered = filtered.filter(spot =>
            selectedAmenities.every(amenity => spot.amenities.includes(amenity))
        );
    }

    // Availability filter
    filtered = filtered.filter(spot => spot.available);

    // Sort results
    const sortBy = document.getElementById('sortBy').value;
    switch (sortBy) {
        case 'price-low':
            filtered.sort((a, b) => a.price - b.price);
            break;
        case 'price-high':
            filtered.sort((a, b) => b.price - a.price);
            break;
        case 'distance':
            filtered.sort((a, b) => a.distance - b.distance);
            break;
        case 'rating':
            filtered.sort((a, b) => b.rating - a.rating);
            break;
    }

    return filtered;
}

// Display spots in results
// Place this at the top of your JavaScript or before loading the JS file
const utils = window.utils || {};
utils.storage = {
    get: (key) => {
        try {
            return JSON.parse(localStorage.getItem(key));
        } catch (e) {
            return null;
        }
    },
    set: (key, value) => {
        localStorage.setItem(key, JSON.stringify(value));
    },
    remove: (key) => {
        localStorage.removeItem(key);
    }
};

function displaySpots(spotsToShow) {
    const resultsGrid = document.getElementById('resultsGrid');
    const noResults = document.getElementById('noResults');

    if (spotsToShow.length === 0) {
        resultsGrid.style.display = 'none';
        noResults.style.display = 'flex';
        return;
    }

    resultsGrid.style.display = 'flex';
    noResults.style.display = 'none';

    resultsGrid.innerHTML = spotsToShow.map(spot => `
        <div class="spot-card" data-spot-id="${spot.id}" onclick="viewSpotDetails(${spot.id})">
            <div class="spot-header">
                <div class="spot-info">
                    <h3>${spot.name}</h3>
                    <div class="spot-address">
                        <i class="fas fa-map-marker-alt"></i>
                        ${spot.address}
                    </div>
                </div>
                <div class="spot-price">
                    <div class="price-amount">৳${spot.price}</div>
                    <div class="price-unit">per hour</div>
                </div>
            </div>
            
            <div class="spot-details">
                <div class="car-types">
                    ${['sedan', 'suv', 'hatchback', 'minivan', 'truck'].map(type => `
                        <div class="car-type-icon ${spot.carTypes.includes(type) ? 'supported' : ''}">
                            <i class="fas fa-${getCarIcon(type)}"></i>
                        </div>
                    `).join('')}
                </div>
                <div class="spot-distance">
                    <i class="fas fa-route"></i>
                    ${spot.distance} km away
                </div>
            </div>
            
            <div class="spot-amenities">
                ${spot.amenities.map(amenity => `
                    <div class="amenity-badge">
                        <i class="fas fa-${getAmenityIcon(amenity)}"></i>
                        ${formatAmenity(amenity)}
                    </div>
                `).join('')}
            </div>
            
            <div class="spot-actions">
                <button class="view-details-btn" onclick="event.stopPropagation(); viewSpotDetails(${spot.id})">
                    View Details
                    
                </button>
                <button class="favorite-btn" onclick="event.stopPropagation(); toggleFavorite(${spot.id})">
                    <i class="fas fa-heart"></i>
                </button>
            </div>
        </div>
    `).join('');
}

// Update map markers based on filtered spots
function updateMapMarkers(filteredSpots) {
    markers.forEach(({ marker, spot }) => {
        const isVisible = filteredSpots.some(s => s.id === spot.id);
        marker.setVisible(isVisible);
    });
}

// Helper functions
function getCarIcon(type) {
    const icons = {
        sedan: 'car',
        suv: 'truck',
        hatchback: 'car-side',
        minivan: 'shuttle-van',
        truck: 'truck-pickup'
    };
    return icons[type] || 'car';
}

function getAmenityIcon(amenity) {
    const icons = {
        covered: 'home',
        security: 'shield-alt',
        'ev-charger': 'charging-station',
        handicap: 'wheelchair'
    };
    return icons[amenity] || 'check';
}

function formatAmenity(amenity) {
    const names = {
        covered: 'Covered',
        security: 'Security',
        'ev-charger': 'EV Charger',
        handicap: 'Handicap Access'
    };
    return names[amenity] || amenity;
}

// View toggle functionality
function initViewToggle() {
    document.querySelectorAll('.view-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            
            const view = btn.dataset.view;
            const resultsGrid = document.getElementById('resultsGrid');
            
            if (view === 'grid') {
                resultsGrid.classList.add('grid-view');
            } else {
                resultsGrid.classList.remove('grid-view');
            }
        });
    });
}

// Map controls
function initMapControls() {
    document.getElementById('centerMap').addEventListener('click', () => {
        map.setCenter(currentLocation);
        map.setZoom(13);
    });

    document.getElementById('toggleMapView').addEventListener('click', () => {
        const currentType = map.getMapTypeId();
        map.setMapTypeId(currentType === 'roadmap' ? 'satellite' : 'roadmap');
    });
}

// Mobile filters toggle
document.addEventListener('DOMContentLoaded', () => {
    const filtersToggle = document.getElementById('filtersToggle');
    const filtersSidebar = document.getElementById('filtersSidebar');
    const closeFilters = document.getElementById('closeFilters');
    const searchContent = document.querySelector('.search-content');

    if (filtersToggle) {
        filtersToggle.addEventListener('click', () => {
            filtersSidebar.classList.add('show');
        });
    }

    if (closeFilters) {
        closeFilters.addEventListener('click', () => {
            filtersSidebar.classList.remove('show');
        });
    }

    // Close filters when clicking outside on mobile
    document.addEventListener('click', (e) => {
        if (window.innerWidth <= 768 && 
            !filtersSidebar.contains(e.target) && 
            !filtersToggle.contains(e.target)) {
            filtersSidebar.classList.remove('show');
        }
    });
});

// Search location functionality
function searchLocation() {
    const location = document.getElementById('locationSearch').value;
    if (!location) return;

    showLoading();

    const geocoder = new google.maps.Geocoder();
    geocoder.geocode({ address: location + ', Bangladesh' }, (results, status) => {
        if (status === 'OK') {
            const newLocation = results[0].geometry.location;
            currentLocation = { lat: newLocation.lat(), lng: newLocation.lng() };
            map.setCenter(currentLocation);
            map.setZoom(13);
            
            // Update distances and re-filter
            updateDistances();
            filterAndDisplaySpots();
        } else {
            hideLoading();
            alert('Location not found. Please try a different search term.');
        }
    });
}

// Update distances from current location
function updateDistances() {
    spots.forEach(spot => {
        const distance = calculateDistance(
            currentLocation.lat, currentLocation.lng,
            spot.lat, spot.lng
        );
        spot.distance = Math.round(distance * 10) / 10;
    });
}

// Calculate distance between two points
function calculateDistance(lat1, lon1, lat2, lon2) {
    const R = 6371; // Radius of the Earth in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return R * c;
}

// Reset filters
function resetFilters() {
    document.getElementById('locationSearch').value = 'Dhaka';
    document.getElementById('minPrice').value = '0';
    document.getElementById('maxPrice').value = '500';
    document.getElementById('priceRange').value = '250';
    document.getElementById('priceValue').textContent = '250';
    document.getElementById('sortBy').value = 'distance';
    
    document.querySelectorAll('.car-type-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector('.car-type-btn[data-type="sedan"]').classList.add('active');
    
    document.querySelectorAll('input[name="amenities"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    filterAndDisplaySpots();
}

// Utility functions
function updateResultsCount(count) {
    document.getElementById('resultsCount').textContent = count;
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.add('show');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.remove('show');
}

function highlightSpotCard(spotId) {
    document.querySelectorAll('.spot-card').forEach(card => {
        card.classList.remove('highlighted');
    });
    
    const card = document.querySelector(`[data-spot-id="${spotId}"]`);
    if (card) {
        card.classList.add('highlighted');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function updateVisibleSpots() {
    // Update spots based on map bounds if needed
}

// Global functions
window.viewSpotDetails = function(spotId) {
    utils.storage.set('selectedSpotId', spotId);
    window.location.href = `spot-details.html?id=${spotId}`;
};

window.toggleFavorite = function(spotId) {
    const btn = event.target.closest('.favorite-btn');
    btn.classList.toggle('active');
    
    // Store in localStorage
    let favorites = utils.storage.get('favorites') || [];
    if (btn.classList.contains('active')) {
        favorites.push(spotId);
    } else {
        favorites = favorites.filter(id => id !== spotId);
    }
    utils.storage.set('favorites', favorites);
};

window.switchToHost = function() {
    utils.storage.set('userType', 'host');
    window.location.href = 'host-dashboard.html';
};

window.logout = function() {
    utils.storage.remove('isLoggedIn');
    utils.storage.remove('userData');
    utils.storage.remove('userType');
    window.location.href = 'index.html';
};