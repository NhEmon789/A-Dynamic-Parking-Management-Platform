// Search page functionality with Leaflet map integration
document.addEventListener('DOMContentLoaded', function() {
    initializeSearchPage();
    initializeFilters();
    loadParkingSpots();
    initializePriceSlider();
    
    // Initialize map after a short delay to ensure DOM is ready
    setTimeout(() => {
        initializeMap();
    }, 100);
});

let map;
let spots = [];
let markers = [];
let filteredSpots = [];

// Expanded mock parking spots data with 30+ spots across different cities in Bangladesh
const mockSpots = [
    // Dhaka spots
    {
        id: 1,
        name: "Gulshan Premium Garage",
        address: "Road 11, Gulshan 1, Dhaka",
        city: "Dhaka",
        price: 120,
        rating: 4.5,
        reviews: 127,
        distance: 0.3,
        image: "https://images.unsplash.com/photo-1729123366016-54ab44df1063?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "ev"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [23.7808, 90.4142],
        available: true
    },
    {
        id: 2,
        name: "Dhanmondi Shopping Complex",
        address: "Road 27, Dhanmondi, Dhaka",
        city: "Dhaka",
        price: 80,
        rating: 4.2,
        reviews: 89,
        distance: 0.5,
        image: "https://images.unsplash.com/photo-1593105632614-370657b81ab4?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "handicap"],
        vehicleTypes: ["sedan", "hatchback"],
        coordinates: [23.7461, 90.3742],
        available: true
    },
    {
        id: 3,
        name: "Bashundhara City Mall",
        address: "Panthapath, Dhaka",
        city: "Dhaka",
        price: 100,
        rating: 4.8,
        reviews: 203,
        distance: 0.8,
        image: "https://images.unsplash.com/photo-1667293647774-40a031101971?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "ev", "security"],
        vehicleTypes: ["sedan", "suv", "minivan"],
        coordinates: [23.7501, 90.3872],
        available: true
    },
    {
        id: 4,
        name: "Hazrat Shahjalal Airport",
        address: "Airport Road, Dhaka",
        city: "Dhaka",
        price: 150,
        rating: 4.6,
        reviews: 156,
        distance: 1.2,
        image: "https://images.unsplash.com/photo-1676971021989-e9bde0dd8e83?q=80&w=2128&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["handicap", "security"],
        vehicleTypes: ["sedan", "hatchback", "suv"],
        coordinates: [23.8431, 90.3979],
        available: true
    },
    {
        id: 5,
        name: "Uttara Sector 7 Parking",
        address: "Sector 7, Uttara, Dhaka",
        city: "Dhaka",
        price: 60,
        rating: 4.3,
        reviews: 134,
        distance: 2.1,
        image: "https://images.unsplash.com/photo-1565793979206-10951493332d?q=80&w=1964&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [23.8759, 90.3795],
        available: true
    },
    {
        id: 6,
        name: "Old Dhaka Heritage Area",
        address: "Lalbagh, Old Dhaka",
        city: "Dhaka",
        price: 40,
        rating: 4.1,
        reviews: 98,
        distance: 1.8,
        image: "https://images.unsplash.com/photo-1750509009716-1b12aea09501?q=80&w=1181&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [23.7197, 90.3875],
        available: true
    },
    // Chittagong spots
    {
        id: 7,
        name: "Chittagong Port Authority",
        address: "Port Area, Chittagong",
        city: "Chittagong",
        price: 90,
        rating: 4.4,
        reviews: 445,
        distance: 3.1,
        image: "https://images.unsplash.com/photo-1626744032344-9d2f75d0d644?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan", "truck"],
        coordinates: [22.3569, 91.7832],
        available: true
    },
    {
        id: 8,
        name: "GEC Circle Shopping",
        address: "GEC Circle, Chittagong",
        city: "Chittagong",
        price: 70,
        rating: 4.7,
        reviews: 178,
        distance: 2.8,
        image: "https://images.unsplash.com/photo-1595951280326-8ffa79bdfbc0?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "ev", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [22.3569, 91.8325],
        available: true
    },
    // Dinajpur spots
    {
        id: 9,
        name: "Dinajpur City",
        address: "Highway Road, Dinajpur",
        city: "Dinajpur",
        price: 110,
        rating: 4.3,
        reviews: 267,
        distance: 1.5,
        image: "https://images.unsplash.com/photo-1582734972359-a627445b4936?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security"],
        vehicleTypes: ["sedan", "hatchback"],
        coordinates: [24.9633, 91.8687],
        available: true
    },
    {
        id: 10,
        name: "Dinajpur City Center",
        address: "Manikbazar, Dinajpur",
        city: "Dinajpur",
        price: 85,
        rating: 4.5,
        reviews: 198,
        distance: 3.7,
        image: "https://images.unsplash.com/photo-1657217674164-9cbf85acfc6d?q=80&w=1932&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "ev", "security"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [24.8949, 91.8687],
        available: true
    },
    // Rajshahi spots
    {
        id: 11,
        name: "Rajshahi University Area",
        address: "University Campus, Rajshahi",
        city: "Rajshahi",
        price: 50,
        rating: 4.2,
        reviews: 134,
        distance: 4.2,
        image: "https://images.unsplash.com/photo-1617719132740-4b18c12cc90b?q=80&w=1989&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["handicap", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan", "truck"],
        coordinates: [24.3745, 88.6042],
        available: true
    },
    {
        id: 12,
        name: "Rajshahi Medical College",
        address: "Medical College Road, Rajshahi",
        city: "Rajshahi",
        price: 65,
        rating: 4.6,
        reviews: 312,
        distance: 2.3,
        image: "https://images.unsplash.com/photo-1616620419419-95cb4a218445?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [24.3636, 88.6241],
        available: true
    },
    // Khulna spots
    {
        id: 13,
        name: "Khulna Shipyard Area",
        address: "Shipyard Road, Khulna",
        city: "Khulna",
        price: 75,
        rating: 4.4,
        reviews: 223,
        distance: 6.1,
        image: "https://images.unsplash.com/photo-1715024950038-802fd3f4934c?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [22.8456, 89.5403],
        available: true
    },
    {
        id: 14,
        name: "Khulna University Campus",
        address: "University Road, Khulna",
        city: "Khulna",
        price: 55,
        rating: 4.3,
        reviews: 356,
        distance: 0.7,
        image: "https://images.unsplash.com/photo-1744223786000-4f92d4285bf6?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "ev"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [22.8022, 89.5264],
        available: true
    },
    // Barisal spots
    {
        id: 15,
        name: "Barisal Launch Terminal",
        address: "Launch Ghat, Barisal",
        city: "Barisal",
        price: 45,
        rating: 4.8,
        reviews: 445,
        distance: 1.9,
        image: "https://images.unsplash.com/photo-1720070227525-4deedc433d80?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "handicap", "security"],
        vehicleTypes: ["sedan", "hatchback"],
        coordinates: [22.7010, 90.3535],
        available: true
    },
    {
        id: 16,
        name: "Barisal Medical College",
        address: "Medical College Road, Barisal",
        city: "Barisal",
        price: 60,
        rating: 4.0,
        reviews: 234,
        distance: 1.1,
        image: "https://images.unsplash.com/photo-1676218208053-5d2491c7857d?q=80&w=1331&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [22.7596, 90.3708],
        available: true
    },
    // Rangpur spots
    {
        id: 17,
        name: "Rangpur Cantonment",
        address: "Cantonment Area, Rangpur",
        city: "Rangpur",
        price: 80,
        rating: 4.1,
        reviews: 112,
        distance: 2.7,
        image: "https://images.unsplash.com/photo-1734244814344-a5e3092376b4?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["handicap", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan", "truck"],
        coordinates: [25.7439, 89.2752],
        available: true
    },
    {
        id: 18,
        name: "Rangpur City Center",
        address: "Station Road, Rangpur",
        city: "Rangpur",
        price: 55,
        rating: 4.2,
        reviews: 187,
        distance: 1.3,
        image: "https://images.unsplash.com/photo-1621687576093-913154675b05?q=80&w=1158&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [25.7558, 89.2444],
        available: true
    },
    // Mymensingh spots
    {
        id: 19,
        name: "Bangladesh Agricultural University",
        address: "BAU Campus, Mymensingh",
        city: "Mymensingh",
        price: 40,
        rating: 4.5,
        reviews: 298,
        distance: 0.9,
        image: "https://images.unsplash.com/photo-1687993320698-456243eb9a3d?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "ev", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [24.7471, 90.4203],
        available: true
    },
    {
        id: 20,
        name: "Mymensingh Medical College",
        address: "Medical College Road, Mymensingh",
        city: "Mymensingh",
        price: 50,
        rating: 3.7,
        reviews: 76,
        distance: 3.4,
        image: "https://images.unsplash.com/photo-1611740801331-d8b5d6962822?q=80&w=2071&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["handicap"],
        vehicleTypes: ["sedan", "hatchback"],
        coordinates: [24.7636, 90.4203],
        available: true
    },

    // Jessore spots
    {
        id: 23,
        name: "Jessore Airport",
        address: "Airport Road, Jessore",
        city: "Jessore",
        price: 90,
        rating: 4.6,
        reviews: 412,
        distance: 1.6,
        image: "https://images.unsplash.com/photo-1691729316466-765a6e708736?q=80&w=2127&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "ev", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [23.1838, 89.1608],
        available: true
    },
    {
        id: 24,
        name: "Jessore Science & Technology University",
        address: "University Road, Jessore",
        city: "Jessore",
        price: 35,
        rating: 4.3,
        reviews: 245,
        distance: 0.8,
        image: "https://images.unsplash.com/photo-1672197340787-744f24a6edad?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [23.1697, 89.2072],
        available: true
    },
    // Bogra spots
    {
        id: 25,
        name: "Bogra Cantonment",
        address: "Cantonment Area, Bogra",
        city: "Bogra",
        price: 65,
        rating: 4.1,
        reviews: 189,
        distance: 2.3,
        image: "https://images.unsplash.com/photo-1621195217314-2472aa861fdb?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [24.8465, 89.3776],
        available: true
    },
    // Narayanganj spots
    {
        id: 26,
        name: "Narayanganj Industrial Area",
        address: "Industrial Zone, Narayanganj",
        city: "Narayanganj",
        price: 85,
        rating: 3.9,
        reviews: 98,
        distance: 5.2,
        image: "https://images.unsplash.com/photo-1737786414716-06028920f780?q=80&w=1206&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "handicap"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan", "truck"],
        coordinates: [23.6238, 90.4990],
        available: true
    },
    // Gazipur spots
    {
        id: 27,
        name: "Gazipur Industrial Zone",
        address: "Export Processing Zone, Gazipur",
        city: "Gazipur",
        price: 75,
        reviews: 156,
        rating: 4.4,
        distance: 1.8,
        image: "https://images.unsplash.com/photo-1743003116620-048b9b430be0?q=80&w=880&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["security", "handicap"],
        vehicleTypes: ["sedan", "hatchback"],
        coordinates: [23.9999, 90.4203],
        available: true
    },
    // Tangail spots
    {
        id: 28,
        name: "Tangail Sadar Hospital",
        address: "Hospital Road, Tangail",
        city: "Tangail",
        price: 40,
        rating: 4.0,
        reviews: 134,
        distance: 3.1,
        image: "https://images.unsplash.com/photo-1692632061047-83b215e63d0d?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan"],
        coordinates: [24.2513, 89.9167],
        available: true
    },
    // Pabna spots
    {
        id: 29,
        name: "Pabna Science & Technology University",
        address: "University Campus, Pabna",
        city: "Pabna",
        price: 45,
        rating: 4.3,
        reviews: 201,
        distance: 2.7,
        image: "https://images.unsplash.com/photo-1691019807758-3647f75a3154?q=80&w=764&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["handicap", "covered"],
        vehicleTypes: ["sedan", "suv", "hatchback"],
        coordinates: [24.0064, 89.2372],
        available: true
    },
    // Cox's Bazar spots
    {
        id: 30,
        name: "Cox's Bazar Beach Resort",
        address: "Beach Road, Cox's Bazar",
        city: "Cox's Bazar",
        price: 120,
        rating: 4.7,
        reviews: 567,
        distance: 8.2,
        image: "https://images.unsplash.com/photo-1738796906434-04571bfb91de?q=80&w=1170&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D",
        amenities: ["covered", "security", "handicap", "ev"],
        vehicleTypes: ["sedan", "suv", "hatchback", "minivan", "truck"],
        coordinates: [21.4272, 92.0058],
        available: true
    }
];

function initializeSearchPage() {
    // Check if user is logged in
    const userData = JSON.parse(localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || '{}');
    
    // Initialize search functionality
    const heroSearchBtn = document.querySelector('.hero-search-btn');
    if (heroSearchBtn) {
        heroSearchBtn.addEventListener('click', searchParkingSpots);
    }
    
    const locationSearch = document.getElementById('heroLocationSearch');
    if (locationSearch) {
        locationSearch.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchParkingSpots();
            }
        });
    }
}

function toggleSidebar() {
    const sidebar = document.getElementById('searchSidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    sidebar.classList.toggle('active');
    
    if (sidebar.classList.contains('active')) {
        toggleBtn.style.display = 'none';
    } else {
        setTimeout(() => {
            toggleBtn.style.display = 'flex';
        }, 300);
    }
}

function initializeFilters() {
    // Date filter
    const dateFilter = document.getElementById('dateFilter');
    if (dateFilter) {
        const today = new Date();
        dateFilter.value = today.toISOString().split('T')[0];
    }
    
    // Filter event listeners
    const filterElements = document.querySelectorAll('#startTime, #endTime, #vehicleType, #sortBy, #dateFilter');
    filterElements.forEach(element => {
        element.addEventListener('change', applyFilters);
    });
    
    // Amenities checkboxes
    const amenityCheckboxes = document.querySelectorAll('input[name="amenities"]');
    amenityCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', applyFilters);
    });
}

function initializePriceSlider() {
    const priceSlider = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    
    if (priceSlider && priceValue) {
        priceSlider.addEventListener('input', function() {
            priceValue.textContent = this.value;
            updateSliderBackground(this);
            applyFilters();
        });
        
        // Initialize slider background
        updateSliderBackground(priceSlider);
    }
}

function updatePriceRange() {
    const priceSlider = document.getElementById('priceRange');
    const priceValue = document.getElementById('priceValue');
    
    priceValue.textContent = priceSlider.value;
    updateSliderBackground(priceSlider);
    applyFilters();
}

function updateSliderBackground(slider) {
    const value = ((slider.value - slider.min) / (slider.max - slider.min)) * 100;
    slider.style.background = `linear-gradient(to right, #3F3F3F 0%, #3F3F3F ${value}%, #E7E7E7 ${value}%, #E7E7E7 100%)`;
}

function initializeMap() {
    if (typeof L === 'undefined') {
        console.error('Leaflet is not loaded');
        return;
    }
    
    // Initialize Leaflet map centered on Bangladesh
    map = L.map('map').setView([23.6850, 90.3563], 7);
    
    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);
    
    // Add markers for each spot
    addMarkersToMap();
}

function addMarkersToMap() {
    if (!map) return;
    
    // Clear existing markers
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];
    
    // Track which popup is open and mouse state
    let popupOpenMarker = null;
    let popupMouseOver = false;
    function closeAllPopups(exceptMarker) {
        markers.forEach(m => {
            if (!exceptMarker || m !== exceptMarker) m.closePopup();
        });
    }
    filteredSpots.forEach(spot => {
        if (!spot.available) return;
        const customIcon = L.divIcon({
            className: 'custom-marker',
            html: `<div style="background: #3F3F3F; color: white; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; border: 2px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3);">৳${spot.price}</div>`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });
        const marker = L.marker(spot.coordinates, { icon: customIcon }).addTo(map);
        const popupContent = `
            <div class="custom-popup-content" data-spot-id="${spot.id}" style="padding: 15px; min-width: 250px; font-family: 'Poppins', sans-serif;">
                <h3 style="margin: 0 0 10px 0; font-size: 16px; color: #262626; font-weight: 600;">${spot.name}</h3>
                <p style="margin: 0 0 8px 0; color: #666; font-size: 14px;">${spot.address}</p>
                <p style="margin: 0 0 10px 0; font-weight: bold; color: #3F3F3F; font-size: 16px;">৳${spot.price}/hour</p>
                <div style="display: flex; align-items: center; gap: 5px; margin-bottom: 15px;">
                    <span style="color: #FFD700;">★★★★☆</span>
                    <span style="font-size: 14px; color: #666;">${spot.rating} (${spot.reviews})</span>
                </div>
                <button class="view-more-details-btn" data-spot-id="${spot.id}" style="background: #3F3F3F; color: white; border: none; padding: 10px 20px; border-radius: 25px; cursor: pointer; font-size: 14px; font-weight: 500; width: 100%; transition: all 0.3s ease;" onmouseover="this.style.background='#4D4D4D'" onmouseout="this.style.background='#3F3F3F'">View More Details</button>
            </div>
        `;
        marker.bindPopup(popupContent, {autoClose: false, closeOnClick: false, closeButton: true});

        // Mouse state for this marker
        marker._mouseOver = false;

        // Show popup on click
        marker.on('click', function(e) {
            closeAllPopups(marker);
            marker.openPopup();
            popupOpenMarker = marker;
        });

        // Optional: close popup if user clicks elsewhere on the map
        // (Leaflet default behavior may already do this, but we ensure only one popup is open)

        // Listen for popupopen to manage mouse events
        marker.on('popupopen', function(e) {
            setTimeout(() => {
                const popupEl = document.querySelector('.leaflet-popup .custom-popup-content[data-spot-id="' + spot.id + '"]');
                if (popupEl) {
                    // Remove any previous listeners by cloning
                    const newPopupEl = popupEl.cloneNode(true);
                    popupEl.parentNode.replaceChild(newPopupEl, popupEl);
                    // Attach click handler for 'View More Details' button (cloned node)
                    const btn = newPopupEl.querySelector('.view-more-details-btn');
                    if (btn) {
                        btn.onclick = function(e) {
                            e.stopPropagation();
                            const spotId = this.getAttribute('data-spot-id');
                            if (spotId) viewSpotDetails(Number(spotId));
                        };
                    }
                }
                // Also attach handler to original popup DOM (in case Leaflet reuses DOM and doesn't clone)
                if (popupEl) {
                    const btnOrig = popupEl.querySelector('.view-more-details-btn');
                    if (btnOrig) {
                        btnOrig.onclick = function(e) {
                            e.stopPropagation();
                            const spotId = this.getAttribute('data-spot-id');
                            if (spotId) viewSpotDetails(Number(spotId));
                        };
                    }
                }
            }, 0);
        });

        // Listen for popupclose to always reset mouse state
        marker.on('popupclose', function(e) {
            popupOpenMarker = null;
        });

        markers.push(marker);
    });

    // ...existing code...
}

function loadParkingSpots() {
    // Load spots from localStorage (for host availability toggles)
    const hostSpots = JSON.parse(localStorage.getItem('host_spots') || '[]');
    
    // Update availability based on host settings
    spots = mockSpots.map(spot => {
        const hostSpot = hostSpots.find(h => h.name === spot.name);
        return {
            ...spot,
            available: hostSpot ? hostSpot.available !== false : true
        };
    });
    
    filteredSpots = spots.filter(spot => spot.available);
    updateSpotsList();
    
    // Initialize map after spots are loaded
    if (map) {
        addMarkersToMap();
    }
    
    updateResultsCount();
}

function updateSpotsList() {
    const spotsList = document.getElementById('spotsList');
    if (!spotsList) return;
    
    if (filteredSpots.length === 0) {
        spotsList.innerHTML = `
            <div class="no-results" style="text-align: center; padding: 3rem; color: #666;">
                <h3>No spots found</h3>
                <p>Try adjusting your filters or search in a different area.</p>
            </div>
        `;
        return;
    }
    
    spotsList.innerHTML = filteredSpots.map(spot => `
        <div class="spot-card glass-card" data-spot-id="${spot.id}" onclick="viewSpotDetails(${spot.id})">
            <div class="spot-image">
                <img src="${spot.image}" alt="${spot.name}">
            </div>
            <div class="spot-header">
                <div class="spot-info">
                    <h3 class="spot-title">${spot.name}</h3>
                    <p class="spot-location">${spot.address}</p>
                </div>
                <div class="spot-price">৳${spot.price} <span>/hour</span></div>
            </div>
            <div class="spot-features">
                <div class="vehicle-icons">
                    ${spot.vehicleTypes.map(type => `<span class="vehicle-icon">${getVehicleIcon(type)}</span>`).join('')}
                </div>
                <div class="spot-rating">
                    <span class="stars">${'★'.repeat(Math.floor(spot.rating))}${'☆'.repeat(5 - Math.floor(spot.rating))}</span>
                    <span>${spot.rating} (${spot.reviews})</span>
                </div>
            </div>
            <div class="spot-amenities">
                ${spot.amenities.map(amenity => `<span class="amenity-tag">${getAmenityName(amenity)}</span>`).join('')}
            </div>
            <div class="spot-actions">
                <span class="spot-distance">${spot.distance} km away</span>
                <button class="btn btn-primary btn-sm" onclick="event.stopPropagation(); viewSpotDetails(${spot.id})">View Details</button>
            </div>
        </div>
    `).join('');
}

function getVehicleIcon(type) {
    const icons = {
        sedan: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
            <circle cx="7" cy="17" r="2"></circle>
            <path d="M9 17h6"></path>
            <circle cx="17" cy="17" r="2"></circle>
        </svg>`,
        suv: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
            <circle cx="7" cy="17" r="2"></circle>
            <path d="M9 17h6"></path>
            <circle cx="17" cy="17" r="2"></circle>
            <path d="M2 8h20"></path>
        </svg>`,
        hatchback: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
            <circle cx="7" cy="17" r="2"></circle>
            <path d="M9 17h6"></path>
            <circle cx="17" cy="17" r="2"></circle>
        </svg>`,
        minivan: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
            <circle cx="7" cy="17" r="2"></circle>
            <path d="M9 17h6"></path>
            <circle cx="17" cy="17" r="2"></circle>
            <path d="M2 8h20"></path>
            <path d="M7 8v8"></path>
            <path d="M17 8v8"></path>
        </svg>`,
        truck: `<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path>
            <path d="M15 18H9"></path>
            <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path>
            <circle cx="17" cy="18" r="2"></circle>
            <circle cx="7" cy="18" r="2"></circle>
        </svg>`
    };
    return icons[type] || icons.sedan;
}

function getAmenityName(amenity) {
    const names = {
        covered: 'Covered',
        security: 'Security',
        ev: 'EV Charging',
        handicap: 'Handicap Access',
        '24_7': '24/7 Access'
    };
    return names[amenity] || amenity;
}

function searchParkingSpots() {
    const searchTerm = document.getElementById('heroLocationSearch').value.toLowerCase();
    
    if (searchTerm.trim() === '') {
        filteredSpots = spots.filter(spot => spot.available);
        // Reset map to default view if search is empty and search button is clicked
        if (map) {
            map.setView([23.6850, 90.3563], 7); // Default Bangladesh view
        }
    } else {
        filteredSpots = spots.filter(spot => 
            spot.available && (
                spot.name.toLowerCase().includes(searchTerm) ||
                spot.address.toLowerCase().includes(searchTerm) ||
                spot.city.toLowerCase().includes(searchTerm)
            )
        );
        // If searching for a specific city, center map on that city
        const citySpots = filteredSpots.filter(spot => 
            spot.city.toLowerCase().includes(searchTerm)
        );
        if (citySpots.length > 0 && map) {
            const firstSpot = citySpots[0];
            map.setView(firstSpot.coordinates, 12);
        }
    }
    
    updateSpotsList();
    if (map) {
        addMarkersToMap();
    }
    updateResultsCount();
}

function applyFilters() {
    const priceRange = document.getElementById('priceRange').value;
    const vehicleType = document.getElementById('vehicleType').value;
    const sortBy = document.getElementById('sortBy').value;
    const selectedAmenities = Array.from(document.querySelectorAll('input[name="amenities"]:checked'))
        .map(checkbox => checkbox.value);
    
    let filtered = spots.filter(spot => spot.available);
    
    // Apply search term if exists
    const searchTerm = document.getElementById('heroLocationSearch').value.toLowerCase();
    if (searchTerm.trim() !== '') {
        filtered = filtered.filter(spot => 
            spot.name.toLowerCase().includes(searchTerm) ||
            spot.address.toLowerCase().includes(searchTerm) ||
            spot.city.toLowerCase().includes(searchTerm)
        );
    }
    
    // Apply price filter
    filtered = filtered.filter(spot => spot.price <= priceRange);
    
    // Apply vehicle type filter
    if (vehicleType !== 'all') {
        filtered = filtered.filter(spot => spot.vehicleTypes.includes(vehicleType));
    }
    
    // Apply amenities filter
    if (selectedAmenities.length > 0) {
        filtered = filtered.filter(spot => 
            selectedAmenities.every(amenity => spot.amenities.includes(amenity))
        );
    }
    
    // Apply sorting
    switch (sortBy) {
        case 'price-low':
            filtered.sort((a, b) => a.price - b.price);
            break;
        case 'price-high':
            filtered.sort((a, b) => b.price - a.price);
            break;
        case 'rating':
            filtered.sort((a, b) => b.rating - a.rating);
            break;
        case 'distance':
        default:
            filtered.sort((a, b) => a.distance - b.distance);
            break;
    }
    
    filteredSpots = filtered;
    updateSpotsList();
    if (map) {
        addMarkersToMap();
    }
    updateResultsCount();
}

function resetFilters() {
    // Reset form elements
    document.getElementById('priceRange').value = 250;
    document.getElementById('priceValue').textContent = '250';
    document.getElementById('vehicleType').value = 'all';
    document.getElementById('sortBy').value = 'distance';
    
    // Update slider background
    updateSliderBackground(document.getElementById('priceRange'));
    
    // Uncheck all amenities
    document.querySelectorAll('input[name="amenities"]').forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Reset filtered spots
    filteredSpots = spots.filter(spot => spot.available);
    updateSpotsList();
    if (map) {
        addMarkersToMap();
    }
    updateResultsCount();
}

function updateResultsCount() {
    const resultsCount = document.getElementById('resultsCount');
    if (resultsCount) {
        const count = filteredSpots.length;
        resultsCount.textContent = `${count} spot${count !== 1 ? 's' : ''} found`;
    }
}

function highlightSpotCard(spotId) {
    // Remove previous highlights
    document.querySelectorAll('.spot-card').forEach(card => {
        card.classList.remove('highlighted');
    });
    
    // Highlight the selected card
    const card = document.querySelector(`[data-spot-id="${spotId}"]`);
    if (card) {
        card.classList.add('highlighted');
        card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
}

function viewSpotDetails(spotId) {
    // Check if user is logged in
    const userData = JSON.parse(localStorage.getItem('user_data') || sessionStorage.getItem('user_data') || '{}');
    
    if (!userData.email) {
        // User not logged in, redirect to register
        window.location.href = 'register.html';
        return;
    }
    
    // Store selected spot ID for the details page
    localStorage.setItem('selectedSpotId', spotId);
    window.location.href = 'spot-details.html';
}

function logout() {
    // Clear user data
    localStorage.removeItem('user_data');
    sessionStorage.removeItem('user_data');
    
    // Redirect to landing page
    window.location.href = 'index.html';
}
