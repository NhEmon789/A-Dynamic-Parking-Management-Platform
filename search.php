<?php  
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_manage";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'filter') {
    $date = $_POST['date'] ?? null;
    $startTime = $_POST['startTime'] ?? null;
    $endTime = $_POST['endTime'] ?? null;
    $vehicleType = $_POST['vehicleType'] ?? 'all';
    $maxPrice = isset($_POST['maxPrice']) ? (float)$_POST['maxPrice'] : 500;
    $amenities_json = $_POST['amenities'] ?? '[]';
    $amenities = json_decode($amenities_json, true);
    $sortBy = $_POST['sortBy'] ?? null;

    $dayOfWeek = null;
    if ($date) {
        $timestamp = strtotime($date);
        $dayOfWeek = ucfirst(strtolower(date('l', $timestamp))); // Capitalize to match ENUM
    }

    // If we have time filters, use INNER JOIN to ensure spot availability is matched
    $joinType = ($dayOfWeek && $startTime && $endTime) ? "INNER" : "LEFT";

    $sql = "SELECT DISTINCT ps.* 
            FROM parking_spots ps
            $joinType JOIN spot_availability sa ON ps.id = sa.spot_id
            WHERE 1=1 
              AND ps.spot_status = 'active' ";

    $params = [];
    $types = '';

    // Vehicle type filter
    if ($vehicleType !== 'all') {
        $sql .= " AND FIND_IN_SET(?, ps.vehicle_types) > 0 ";
        $types .= 's';
        $params[] = $vehicleType;
    }

    // Max price filter
    $sql .= " AND ps.hourly_rate <= ? ";
    $types .= 'd';
    $params[] = $maxPrice;

    // Amenities filter
    if (is_array($amenities) && count($amenities) > 0) {
        foreach ($amenities as $amenity) {
            $sql .= " AND ps.amenities LIKE ? ";
            $types .= 's';
            $params[] = "%$amenity%";
        }
    }

    // Availability day/time filter
    if ($dayOfWeek && $startTime && $endTime) {
        $sql .= " AND sa.day_of_week = ? AND sa.start_time <= ? AND sa.end_time >= ? ";
        $types .= 'sss';
        $params[] = $dayOfWeek;
        $params[] = $startTime;
        $params[] = $endTime;
    }

    // Exclude already booked spots for the same date & time (single day only)
    if ($date && $startTime && $endTime) {
        $sql .= " AND ps.id NOT IN (
            SELECT b.spot_id 
            FROM bookings b
            WHERE b.booking_date = ?
              AND b.status = 'active'
              AND b.start_time < ?
              AND b.end_time > ?
        )";
        $types .= 'sss';
        $params[] = $date;
        $params[] = $endTime;   // filterEndTime
        $params[] = $startTime; // filterStartTime
    }

    // Sorting
    if ($sortBy === 'price-low') {
        $sql .= " ORDER BY ps.hourly_rate ASC";
    } elseif ($sortBy === 'price-high') {
        $sql .= " ORDER BY ps.hourly_rate DESC";
    }

    // Prepare and bind
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        echo json_encode(['error' => 'DB prepare failed']);
        exit();
    }

    if ($types) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $spots = [];
    while ($row = $result->fetch_assoc()) {
        $spots[] = $row;
    }

    header('Content-Type: application/json');
    echo json_encode(['spots' => $spots]);
    exit();
}

// Fallback: Load all spots (initial page load)
$spots_sql = "SELECT id, name, description, address, latitude, longitude, hourly_rate, currency, photo_path, vehicle_types, amenities 
              FROM parking_spots
              WHERE spot_status = 'active'";
$spots_result = $conn->query($spots_sql);

$spots = [];
if ($spots_result && $spots_result->num_rows > 0) {
    while ($row = $spots_result->fetch_assoc()) {
        $spots[] = $row;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Parking - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="css/leaflet-custom.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
    .vehicle-icon {
    font-size: 18px;
    margin-right: 8px;
    color: #555;
}
.spot-icons {
    margin: 10px 0;
}
.spot-amenities .badge {
    display: inline-block;
    background-color: #d4f8d4; /* light green */
    color: #2e7d32;           /* dark green text */
    border-radius: 999px;     /* capsule shape */
    padding: 4px 12px;
    margin: 4px 4px 0 0;
    font-size: 13px;
    font-weight: 500;
    text-transform: capitalize;
}
.view-details {
    background-color: #3f3f3f;       /* Dark coffee color */
    color: #ffffff;
    padding: 10px 24px;
    font-size: 14px;
    font-weight: 600;
    border: none;
    border-radius: 999px;            /* Capsule shape */
    cursor: pointer;
    margin-top: 12px;
    float: right;                    /* Right align */
    transition: background-color 0.3s, transform 0.2s;
    display: inline-block;
    text-decoration: none; /* remove underline for links */
    text-align: center;
}

.view-details:hover {
    background-color: #666666;       /* Slightly darker on hover */
    transform: translateY(-2px);
}

.view-details:active {
    background-color: #3f3f3f;
    transform: scale(0.98);
}


</style>
</head>
<body>
   <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="nav-wrapper">
                <div class="logo">
                    <h2>FindMySpot</h2>
                </div>
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-list">
                        <li><a href="customer-dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="search.php" class="nav-link active">Search</a></li>
                        <li><a href="customer-bookings.php" class="nav-link">Bookings</a></li>
                        <li><a href="customer-profile.php" class="nav-link">Profile</a></li>
                    </ul>
                    <div class="user-actions mobile">
                        <div class="role-switch">
                            <a href="host-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle" style="color:#ffffff; border: 2px solid #cccccc;">Switch to Host</a>
                        </div>
                        <a href="logout.php" class="btn btn-primary">Logout</a>
                    </div>
                </nav>
                <div class="user-actions desktop">
                    <div class="role-switch">
<a href="host-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle" style="color:#ffffff; border: 2px solid #cccccc;">Switch to Host</a>
                    </div>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
                <div class="hamburger" id="hamburger">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </header>

    <main class="search-page">
        <!-- Hero Search Section -->
        <section class="search-hero">
            <div class="container">
                <h1>Find Your Perfect Parking Spot</h1>
                <p>Discover convenient parking solutions in Bangladesh</p>
                <div class="hero-search-bar">
                    <input type="text" id="heroLocationSearch" placeholder="Enter city or area...">
                    <button class="hero-search-btn" onclick="searchParkingSpots()">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="m21 21-4.35-4.35"></path>
                        </svg>
                        Search
                    </button>
                </div>
            </div>
        </section>

        <!-- Main Content Layout -->
        <div class="search-layout">
            <!-- Sidebar Toggle Button -->
            <button class="sidebar-toggle" onclick="toggleSidebar()" id="sidebarToggle">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>

            <!-- Filters Sidebar -->
            <div class="search-sidebar" id="searchSidebar">
    <div class="sidebar-header">
        <h3>Filters</h3>
        <button class="sidebar-close" onclick="toggleSidebar()">×</button>
    </div>

    <div class="filter-group">
        <label>Date</label>
        <input type="date" id="dateFilter">
    </div>

    <div class="filter-group">
        <label>Start Time</label>
        <select id="startTime">
            <option value="00:00" >12:00 AM</option>
                        <option value="01:00">1:00 AM</option>
                        <option value="02:00">2:00 AM</option>
                        <option value="03:00">3:00 AM</option>
                        <option value="04:00">4:00 AM</option>
                        <option value="05:00">5:00 AM</option>
                        <option value="06:00">6:00 AM</option>
                        <option value="07:00">7:00 AM</option>
                        <option value="08:00" selected>8:00 AM</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                        <option value="17:00">5:00 PM</option>
                        <option value="18:00">6:00 PM</option>
                        <option value="19:00">7:00 PM</option>
                        <option value="20:00">8:00 PM</option>
                        <option value="21:00">9:00 PM</option>
                        <option value="22:00">10:00 PM</option>
                        <option value="23:00">11:00 PM</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label>End Time</label>
                    <select id="endTime">
                        <option value="01:00">1:00 AM</option>
                        <option value="02:00">2:00 AM</option>
                        <option value="03:00">3:00 AM</option>
                        <option value="04:00">4:00 AM</option>
                        <option value="05:00">5:00 AM</option>
                        <option value="06:00">6:00 AM</option>
                        <option value="07:00">7:00 AM</option>
                        <option value="08:00">8:00 AM</option>
                        <option value="09:00">9:00 AM</option>
                        <option value="10:00">10:00 AM</option>
                        <option value="11:00">11:00 AM</option>
                        <option value="12:00">12:00 PM</option>
                        <option value="13:00">1:00 PM</option>
                        <option value="14:00">2:00 PM</option>
                        <option value="15:00">3:00 PM</option>
                        <option value="16:00">4:00 PM</option>
                        <option value="17:00">5:00 PM</option>
                        <option value="18:00" selected>6:00 PM</option>
                        <option value="19:00">7:00 PM</option>
                        <option value="20:00">8:00 PM</option>
                        <option value="21:00">9:00 PM</option>
                        <option value="22:00">10:00 PM</option>
                        <option value="23:00">11:00 PM</option>
                    </select>
                </div>

                  <div class="filter-group">
        <label>Vehicle Type</label>
        <select id="vehicleType">
            <option value="all">All Types</option>
            <option value="sedan">Sedan</option>
            <option value="suv">SUV</option>
            <option value="hatchback">Hatchback</option>
            <option value="minivan">Minivan</option>
            <option value="truck">Truck</option>
        </select>
    </div>

                <div class="filter-group">
        <label>Sort By</label>
        <select id="sortBy" onchange="applyFilters()">

            <option value="price-low">Price (Low to High)</option>
            <option value="price-high">Price (High to Low)</option>
        </select>
    </div>

             <div class="filter-group">
        <label>Price Range</label>
        <div class="price-slider">
            <input type="range" id="priceRange" min="0" max="500" value="250" 
       oninput="updatePriceRange(); applyFilters();">

            <div class="price-display">৳0 - ৳<span id="priceValue">250</span>/hr</div>
        </div>
    </div>
                 <div class="filter-group">
        <label>Amenities</label>
        <div class="amenities-filter">
            <label class="checkbox-container"><input type="checkbox" name="amenities" value="covered">Covered</label>
<label class="checkbox-container"><input type="checkbox" name="amenities" value="security">Security</label>
<label class="checkbox-container"><input type="checkbox" name="amenities" value="ev">EV Charger</label>
<label class="checkbox-container"><input type="checkbox" name="amenities" value="handicap">Handicap Access</label>
<label class="checkbox-container"><input type="checkbox" name="amenities" value="24_7">24/7 Access</label>
<label class="checkbox-container"><input type="checkbox" name="amenities" value="lighting">Well Lit</label>

        </div>
    </div>

    <div class="filter-actions">
        <button class="btn btn-outline" onclick="resetFilters()">Reset Filters</button>
        <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
    </div>
</div>

            <!-- Main Content -->
            <div class="search-main">
                <!-- Map Container -->
                <div class="search-map-container">
                    <div id="map" class="map"></div>
                </div>

                <!-- Results Section -->
<div class="search-results">
    <div class="results-header">
        <h3>Available Spots</h3>
        <span class="results-count" id="resultsCount"><?= count($spots) ?> spots found</span>
    </div>

    <div class="spots-list" id="spotsList">
        <?php if (count($spots) > 0): ?>
            <?php foreach ($spots as $spot): ?>
                <div class="spot-card glass-card">
                    <img src="<?= htmlspecialchars($spot['photo_path']) ?>" alt="Spot Image" class="spot-image">

                    <div class="spot-details">
                        <h4><?= htmlspecialchars($spot['name']) ?></h4>
                        <p class="spot-address"><?= htmlspecialchars($spot['address']) ?></p>

                        <div class="spot-pricing-rating">
                            <span class="spot-price">৳<?= htmlspecialchars($spot['hourly_rate']) ?>/hour</span>
                        </div>

                        <!-- Vehicle Type Icons -->
                        <div class="spot-icons">
                            <?php 
                            $vehicles = explode(',', $spot['vehicle_types']);
                            foreach ($vehicles as $vehicle):
                                $vehicle = strtolower(trim($vehicle));
                                switch ($vehicle):
                                    case 'sedan': echo '<i class="fas fa-car vehicle-icon" title="Sedan"></i>'; break;
                                    case 'suv': echo '<i class="fas fa-truck-monster vehicle-icon" title="SUV"></i>'; break;
                                    case 'hatchback': echo '<i class="fas fa-car-side vehicle-icon" title="Hatchback"></i>'; break;
                                    case 'minivan': echo '<i class="fas fa-shuttle-van vehicle-icon" title="Minivan"></i>'; break;
                                    case 'truck': echo '<i class="fas fa-truck vehicle-icon" title="Truck"></i>'; break;
                                endswitch;
                            endforeach;
                            ?>
                        </div>

                        <!-- Amenities -->
                        <div class="spot-amenities">
                            <?php 
                            $amenities = explode(',', $spot['amenities']);
                            foreach ($amenities as $amenity):
                                echo '<span class="badge">' . ucfirst(trim($amenity)) . '</span>';
                            endforeach;
                            ?>
                        </div>

                        <a href="spot-details.php?id=<?= urlencode($spot['id']) ?>" class="view-details">View Details</a>

                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No parking spots found.</p>
        <?php endif; ?>
    </div>
</div>


<!-- Load Font Awesome -->

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map; // Declare globally

document.addEventListener("DOMContentLoaded", function () {
    map = L.map('map').setView([23.6850, 90.3563], 7);

    // Add tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    // Add custom markers with cost as label
    <?php foreach ($spots as $spot): ?>
        const costIcon<?= $spot['id'] ?> = L.divIcon({
            className: 'cost-icon',
            html: '৳<?= round($spot['hourly_rate']) ?>',
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        L.marker([<?= $spot['latitude'] ?>, <?= $spot['longitude'] ?>], { icon: costIcon<?= $spot['id'] ?> })
            .addTo(map)
            .bindPopup(`
                <strong><?= addslashes($spot['name']) ?></strong><br>
                <?= addslashes($spot['address']) ?><br>
                ৳<?= round($spot['hourly_rate']) ?>/hour<br>
                <a href="spot-details.php?id=<?= $spot['id'] ?>" class="popup-button">View Details</a>
            `);
    <?php endforeach; ?>

    // Add Enter key listener for search input
    const searchInput = document.getElementById('heroLocationSearch');
    searchInput.addEventListener('keydown', function(event) {
        if (event.key === 'Enter') {
            event.preventDefault(); // Prevent form submit or page reload
            searchParkingSpots();
        }
    });

    // Dynamic event listeners for sortBy and priceRange
    document.getElementById('sortBy').addEventListener('change', applyFilters);
    document.getElementById('priceRange').addEventListener('input', function () {
        updatePriceRange();
        applyFilters();
    });
});

function searchParkingSpots() {
    const query = document.getElementById('heroLocationSearch').value.trim();
    if (!query) {
        alert('Please enter an area or city name.');
        return;
    }

    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1&addressdetails=1`)
        .then(response => response.json())
        .then(data => {
            if (data && data.length > 0) {
                const lat = parseFloat(data[0].lat);
                const lon = parseFloat(data[0].lon);
                map.setView([lat, lon], 13);
            } else {
                alert('Location not found. Please try another query.');
            }
        })
        .catch(error => {
            console.error('Error fetching location:', error);
            alert('Failed to search location. Please try again later.');
        });
}

function updatePriceRange() {
    document.getElementById('priceValue').textContent = document.getElementById('priceRange').value;
}

function applyFilters() {
    const date = document.getElementById('dateFilter').value;
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const vehicleType = document.getElementById('vehicleType').value;
    const maxPrice = document.getElementById('priceRange').value;
    const sortBy = document.getElementById('sortBy').value;

    const amenitiesChecked = Array.from(document.querySelectorAll('.amenities-filter input[type="checkbox"]:checked'));
    const amenities = amenitiesChecked.map(input => input.value);

    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'filter',
            date,
            startTime,
            endTime,
            vehicleType,
            maxPrice,
            sortBy,
            amenities: JSON.stringify(amenities)
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            alert(data.error);
            return;
        }
        updateResultsAndMap(data.spots);
    })
    .catch(err => {
        console.error(err);
        alert('Failed to load filtered spots.');
    });
}

function updateResultsAndMap(spots) {
    document.getElementById('resultsCount').textContent = `${spots.length} spots found`;
    const spotsList = document.getElementById('spotsList');
    spotsList.innerHTML = '';

    if (window.currentMarkers) {
        window.currentMarkers.forEach(m => map.removeLayer(m));
    }
    window.currentMarkers = [];

    if (spots.length === 0) {
        spotsList.innerHTML = '<p>No parking spots found.</p>';
        return;
    }

    spots.forEach(spot => {
        const card = document.createElement('div');
        card.className = 'spot-card glass-card';
        card.innerHTML = `
            <img src="${spot.photo_path}" alt="Spot Image" class="spot-image">
            <div class="spot-details">
                <h4>${spot.name}</h4>
                <p class="spot-address">${spot.address}</p>
                <div class="spot-pricing-rating">
                    <span class="spot-price">৳${spot.hourly_rate}/hour</span>
                </div>
                <div class="spot-icons">${getVehicleIconsHtml(spot.vehicle_types)}</div>
                <div class="spot-amenities">${getAmenitiesHtml(spot.amenities)}</div>
                <a href="spot-details.php?id=${encodeURIComponent(spot.id)}" class="view-details">View Details</a>
            </div>
        `;

        spotsList.appendChild(card);

        const costIcon = L.divIcon({
            className: 'cost-icon',
            html: `৳${Math.round(spot.hourly_rate)}`,
            iconSize: [40, 40],
            iconAnchor: [20, 20]
        });

        const marker = L.marker([spot.latitude, spot.longitude], { icon: costIcon })
            .addTo(map)
            .bindPopup(`
                <strong>${escapeHtml(spot.name)}</strong><br>
                ${escapeHtml(spot.address)}<br>
                ৳${Math.round(spot.hourly_rate)}/hour<br>
                <a href="spot-details.php?id=${spot.id}" class="popup-button">View Details</a>
            `);

        window.currentMarkers.push(marker);
    });
}

function getVehicleIconsHtml(vehicle_types_csv) {
    const vehicles = vehicle_types_csv.split(',');
    let html = '';
    vehicles.forEach(v => {
        const vehicle = v.trim().toLowerCase();
        switch (vehicle) {
            case 'sedan': html += '<i class="fas fa-car vehicle-icon" title="Sedan"></i>'; break;
            case 'suv': html += '<i class="fas fa-truck-monster vehicle-icon" title="SUV"></i>'; break;
            case 'hatchback': html += '<i class="fas fa-car-side vehicle-icon" title="Hatchback"></i>'; break;
            case 'minivan': html += '<i class="fas fa-shuttle-van vehicle-icon" title="Minivan"></i>'; break;
            case 'truck': html += '<i class="fas fa-truck vehicle-icon" title="Truck"></i>'; break;
        }
    });
    return html;
}

function getAmenitiesHtml(amenities_csv) {
    const amenities = amenities_csv.split(',');
    let html = '';
    amenities.forEach(a => {
        const amenity = a.trim();
        if (amenity) {
            html += `<span class="badge">${amenity.charAt(0).toUpperCase() + amenity.slice(1)}</span>`;
        }
    });
    return html;
}

function escapeHtml(text) {
    if (!text) return '';
    return text.replace(/&/g, "&amp;")
               .replace(/</g, "&lt;")
               .replace(/>/g, "&gt;")
               .replace(/"/g, "&quot;")
               .replace(/'/g, "&#039;");
}

function resetFilters() {
    document.getElementById('dateFilter').value = '';
    document.getElementById('startTime').value = '08:00';
    document.getElementById('endTime').value = '18:00';
    document.getElementById('vehicleType').value = 'all';
    document.getElementById('priceRange').value = 250;
    updatePriceRange();
    document.querySelectorAll('.amenities-filter input[type="checkbox"]').forEach(cb => cb.checked = false);
    document.getElementById('sortBy').value = 'price-low';
    applyFilters();
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

</script>

<script src="js/main.js"></script>
<script src="js/search.js"></script>
<script src="js/search-sidebar-close.js"></script>
<script src="js/search-filter-responsive.js"></script>

<style>
   .cost-icon {
    background-color: #343434;
    color: #ffffff;
    border: 2px solid #ffffff;
    border-radius: 50%;
    font-weight: bold;
    text-align: center;
    line-height: 40px;
    font-size: 14px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.leaflet-popup-content {
    font-family: 'Poppins', sans-serif;
    font-size: 14px;
    text-align: center; /* Center content including the button */
}

.popup-button {
    display: inline-block;
    margin-top: 8px;
    padding: 8px 0;
    width: 90%;
    background-color: #3f3f3f;
    color: #ffffff;
    border-radius: 999px;
    font-size: 13px;
    text-decoration: none;
    font-weight: 600;
    transition: background-color 0.3s ease;
    text-align: center;
}

.popup-button:hover {
    background-color: #5a5a5a;
}
.popup-button,
.popup-button:visited,
.popup-button:active {
    color: #ffffff !important;
}



</style>
</body>
</html>
