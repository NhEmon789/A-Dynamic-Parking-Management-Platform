<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Spot</title>
    <!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

<!-- Optional: Leaflet Container Styling -->
<style>
  #addSpotMap {
    width: 100%;
    height: 300px;
    border: 1px solid #ccc;
    border-radius: 8px;
    margin-top: 10px;
  }
</style>

</head>
<body>

    <h2>Add Spot</h2>

    <form method="POST" action="" enctype="multipart/form-data">
        Spot Name:  <input type="text" id="spotName" name="spotName" placeholder="e.g., Downtown Garage, Residential Driveway" required><br><br>
        Description: <textarea id="description" name="description" rows="4" placeholder="Describe your parking spot, access instructions, and any important details..."></textarea>

        <!-- Description:<input type="text" id="description" name="description" rows="4" placeholder="Describe your parking spot, access instructions, and any important details..."></textarea><br><br> -->
        Address:<input type="text" id="address" name="address" placeholder="Enter full address" required><br><br>
        Pricing & Availability:<input type="number" id="hourlyRate" name="hourlyRate" min="1" step="0.50" placeholder="8.00" required>
        <select id="currency" name="currency">
        <option value="TK">TK (ট)</option>                       
        <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    
                                </select><br><br>
        Supported Vehicle Types: <br>
        <input type="checkbox" name="vehicleTypes[]" value="sedan" > Sedan<br>
        <input type="checkbox" name="vehicleTypes[]" value="suv" >Suv<br>
        <input type="checkbox" name="vehicleTypes[]" value="hatchback" >Hatchback<br>
        <input type="checkbox" name="vehicleTypes[]" value="minivan">Minivan<br>
        <input type="checkbox" name="vehicleTypes[]" value="truck">Truck<br>
        Amenities:  <br>
        <label><input type="checkbox" name="amenities[]" value="Covered"> Covered</label><br>
<label><input type="checkbox" name="amenities[]" value="security"> Security</label><br>
<label><input type="checkbox" name="amenities[]" value="EV Charging"> EV Charging</label><br>
<label><input type="checkbox" name="amenities[]" value="Handicap Access"> Handicap Access</label><br>
<label><input type="checkbox" name="amenities[]" value="24/7 Access"> 24/7 Access</label><br>
<label><input type="checkbox" name="amenities[]" value="Well lit"> Well Lit</label><br>
  <h2>Availabilty</h2>

  <div>
  <input type="checkbox" name="days[Monday][enabled]" >
  Monday:
  <input type="time" name="days[Monday][start]" value="08:00">
  to
  <input type="time" name="days[Monday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Tuesday][enabled]" >
  Tuesday:
  <input type="time" name="days[Tuesday][start]" value="08:00">
  to
  <input type="time" name="days[Tuesday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Wednesday][enabled]" >
  Wednesday:
  <input type="time" name="days[Wednesday][start]" value="08:00">
  to
  <input type="time" name="days[Wednesday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Thursday][enabled]" >
  Thursday:
  <input type="time" name="days[Thursday][start]" value="08:00">
  to
  <input type="time" name="days[Thursday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Friday][enabled]" >
  Friday:
  <input type="time" name="days[Friday][start]" value="08:00">
  to
  <input type="time" name="days[Friday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Saturday][enabled]" >
  Saturday:
  <input type="time" name="days[Saturday][start]" value="08:00">
  to
  <input type="time" name="days[Saturday][end]" value="18:00">
</div>

<div>
  <input type="checkbox" name="days[Sunday][enabled]" >
  Sunday:
  <input type="time" name="days[Sunday][start]" value="08:00">
  to
  <input type="time" name="days[Sunday][end]" value="18:00">
</div><br><br>
<!-- Location -->
                    <div class="form-section glass-card">
                        <h2>Location</h2>
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" placeholder="Enter full address" required>
                        </div>
                        <div class="map-section">
                            <label>Pin Your Location</label>
                            <div id="addSpotMap" class="map-container"></div>
                            <p class="map-help">Click on the map to set your parking spot location</p>
                        </div>
                        <input type="hidden" id="latitude" name="latitude">
<input type="hidden" id="longitude" name="longitude">

                    </div>

<br>
<br>

<!-- Photos -->
<div class="form-section glass-card">
    <h2>Photos</h2>
    <div class="photo-upload">
        <div class="upload-area" id="uploadArea">
            <div class="upload-icon">
                <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v11z"></path>
                    <circle cx="12" cy="13" r="4"></circle>
                </svg>
            </div>
            <h3>Upload Photos</h3>
            <p>Add up to 5 photos of your parking spot</p>

            <!-- ✅ Fixed input tag -->
            <input type="file" id="photoUpload" name="photos[]" multiple accept="image/*" style="display: none;">
            <button type="button" class="btn btn-primary" onclick="document.getElementById('photoUpload').click()">Choose Files</button>
        </div>

        <!-- ✅ Preview Area -->
        <div class="photo-preview" id="photoPreview" style="margin-top: 10px;"></div>
    </div>
</div>
    <!-- Repeat for other days: Tuesday, Wednesday, etc. -->

  <button type="submit">Save</button>
</form>

    </form>

  <?php
// Database connection
$con = mysqli_connect('localhost', 'root', '', 'parking_manage');
if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id     = $_SESSION['user_id'];
    $name        = $_POST['spotName'];
    $description = $_POST['description'];
    $address     = $_POST['address'];
    $hourlyRate  = $_POST['hourlyRate'];
    $currency    = $_POST['currency'];
    $latitude  = isset($_POST['latitude']) ? $_POST['latitude'] : null;
$longitude = isset($_POST['longitude']) ? $_POST['longitude'] : null;

    $vehicleTypes = isset($_POST['vehicleTypes']) ? implode(',', $_POST['vehicleTypes']) : '';
    $amenities    = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';

    // Handle photo upload and get first path
    $photoPath = '';
    if (!empty($_FILES['photos']['name'][0])) {
        $uploadDir = "uploads/";
        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
        $fileCount = count($_FILES['photos']['name']);
        $savedPhotos = [];

        for ($i = 0; $i < $fileCount && $i < 5; $i++) {
            $tmpName = $_FILES['photos']['tmp_name'][$i];
            $fileName = basename($_FILES['photos']['name'][$i]);
            $fileType = $_FILES['photos']['type'][$i];

            if (in_array($fileType, $allowedTypes)) {
                $targetPath = $uploadDir . uniqid() . "_" . $fileName;
                if (move_uploaded_file($tmpName, $targetPath)) {
                    $savedPhotos[] = mysqli_real_escape_string($con, $targetPath);
                }
            }
        }

        if (!empty($savedPhotos)) {
    $photoPath = $savedPhotos[0];
} else {
    echo "<p style='color:red;'>❌ No photo saved. Check file types, permissions, or errors.</p>";
}

    }

    // Insert into parking_spots including photo_path
    $query = "INSERT INTO `parking_spots`(`user_id`, `name`, `description`, `address`, `latitude`, `longitude`, `hourly_rate`, `currency`, `photo_path`, `vehicle_types`, `amenities`)
VALUES ('$user_id', '$name', '$description', '$address', '$latitude', '$longitude', '$hourlyRate', '$currency', '$photoPath', '$vehicleTypes', '$amenities')
";

    $run = mysqli_query($con, $query);

    if ($run) {
        $spot_id = mysqli_insert_id($con);

        // Insert availability
        if (isset($_POST['days']) && is_array($_POST['days'])) {
            foreach ($_POST['days'] as $day => $data) {
                if (isset($data['enabled'])) {
                    $start = mysqli_real_escape_string($con, $data['start']);
                    $end   = mysqli_real_escape_string($con, $data['end']);

                    $availability_query = "INSERT INTO `spot_availability` (`spot_id`, `day_of_week`, `start_time`, `end_time`)
                                           VALUES ('$spot_id', '$day', '$start', '$end')";
                    mysqli_query($con, $availability_query);
                }
            }
        }

        echo "<p style='color:green;'>Spot, availability, and photo uploaded successfully.</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($con) . "</p>";
    }
}
?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
let map, marker;

function initializeMap() {
    // Default center (Dhaka as example)
    const defaultLatLng = [23.8103, 90.4125];

    // Create map
    map = L.map('addSpotMap').setView(defaultLatLng, 13);

    // Add OpenStreetMap tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // On map click
    map.on('click', function (e) {
        const { lat, lng } = e.latlng;
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        placeMarker(lat, lng);
    });
}

function placeMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);

        // Update coordinates on drag
        marker.on('dragend', function () {
            const pos = marker.getLatLng();
            document.getElementById('latitude').value = pos.lat;
            document.getElementById('longitude').value = pos.lng;
        });
    }
}

document.addEventListener("DOMContentLoaded", function () {
    if (typeof L !== 'undefined') {
        initializeMap();
    } else {
        // Retry if not loaded yet
        const waitLeaflet = setInterval(() => {
            if (typeof L !== 'undefined') {
                clearInterval(waitLeaflet);
                initializeMap();
            }
        }, 100);
    }
});
</script>

<style>
.photo-preview img {
    box-shadow: 0 2px 6px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
}
.photo-preview img:hover {
    transform: scale(1.05);
}
</style>


</body>
</html>
