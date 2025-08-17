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
    <title>Add New Parking Spot - FindMySpot</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
 .availability-section {
    padding: 20px;
    background-color: #f9f9f9;
    border-radius: 8px;
    font-family: sans-serif;
  }

  .availability-grid {
    display: flex;
    flex-direction: column;
    gap: 0; /* Controls spacing between days */
  }

  .day-availability {
    display: flex;
    align-items: center;
    gap: 20px; /* Space between checkbox and time */
    background: #ffffff;
    padding: 10px 15px;
    border-radius: 6px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
  }

  .checkbox-container {
    display: flex;
    align-items: center;
    gap: 8px;
    min-width: 100px;
    font-weight: 500;
  }

  .time-range {
    display: flex;
    align-items: center;
    gap: 8px;
  }

  .time-range input[type="time"] {
    padding: 5px;

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
                        <li><a href="host-dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="host-listings.php" class="nav-link">Listings</a></li>
                        <li><a href="host-bookings.php" class="nav-link">Bookings</a></li>
                        <li><a href="host-earnings.php" class="nav-link">Earnings</a></li>
                    </ul>
                    <div class="user-actions mobile">
                        <div class="role-switch">
                            <a href="customer-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle" style="color:#ffffff; border: 2px solid #cccccc;">Switch to Customer</a>
                        </div>
                        <a href="logout.php" class="btn btn-primary">Logout</a>

                    </div>
                    
                </nav>
                <div class="user-actions desktop">
                    <div class="role-switch">
<a href="customer-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle" style="color:#ffffff; border: 2px solid #cccccc;">Switch to Customer</a>
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

    <main class="add-spot-page">
        <div class="container">
            <div class="page-header">
                <h1>Add New Parking Spot</h1>
                <p>List your parking space and start earning</p>
            </div>

            <form class="add-spot-form" id="addSpotForm" method="post" enctype="multipart/form-data">

                <div class="form-sections">
                    <!-- Basic Information -->
                    <div class="form-section glass-card">
                        <h2>Basic Information</h2>
                        <div class="form-group">
                            <label for="spotName">Spot Name</label>
                            <input type="text" id="spotName" name="spotName" placeholder="e.g., Downtown Garage, Residential Driveway" required>
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="4" placeholder="Describe your parking spot, access instructions, and any important details..."></textarea>
                        </div>
                    </div>

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
                                    <input type="hidden" name="useDefaultImage" id="useDefaultImage" value="false">

                            <div class="photo-preview" id="photoPreview"></div>
                        </div>
                    </div>

                    <!-- Pricing & Availability -->
                    <div class="form-section glass-card">
                        <h2>Pricing & Availability</h2>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="hourlyRate">Hourly Rate ($)</label>
                                <input type="number" id="hourlyRate" name="hourlyRate" min="1" step="0.50" placeholder="8.00" required>
                            </div>
                            <div class="form-group">
                                <label for="currency">Currency</label>
                                <select id="currency" name="currency">
                                    <option value="TK">BDT (ট)</option>
                                    <option value="USD">USD ($)</option>
                                    <option value="EUR">EUR (€)</option>
                                    
                                </select>
                            </div>
                        </div>
                       <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto&display=swap');

    .time-input {
        width: 18%;
        padding: 0.6rem;
        border: 1px solid #BFBFBF;
        border-radius: 10px;
        font-size: 1rem;
        font-family: 'Poppins', sans-serif;

    }

    .availability-grid {
        display: flex;
        flex-direction: column;
    }

    .time-range {
        margin-top: 0.5rem;
    }

    /* Responsive adjustments */
    @media (max-width: 768px) {
        .time-input {
            width: 40%;
            font-size: 0.9rem;
        }
    }

    @media (max-width: 480px) {
        .time-input {
            width: 100%;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .time-range {
            display: flex;
            flex-direction: column;
        }
    }
</style>

<div class="availability-section">
    <h3>Availability</h3>
    <div class="availability-grid">

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Monday][enabled]">
                Monday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Monday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Monday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Tuesday][enabled]">
                Tuesday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Tuesday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Tuesday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Wednesday][enabled]">
                Wednesday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Wednesday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Wednesday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Thursday][enabled]">
                Thursday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Thursday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Thursday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Friday][enabled]">
                Friday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Friday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Friday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Saturday][enabled]">
                Saturday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Saturday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Saturday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

        <div class="day-availability">
            <label class="checkbox-container">
                <input type="checkbox" name="days[Sunday][enabled]">
                Sunday
            </label>
            <div class="time-range">
                <input class="time-input" type="time" name="days[Sunday][start]" value="08:00"required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00">
                to
                <input class="time-input" type="time" name="days[Sunday][end]" value="18:00"required 
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00">
            </div>
        </div>

    </div>
</div>

                    <!-- Vehicle Types -->
                    <div class="form-section glass-card">
                        <h2>Supported Vehicle Types</h2>
                        <div class="vehicle-types-grid">
                            <label class="vehicle-type-option">
                                 <input type="checkbox" name="vehicleTypes[]" value="sedan" checked> 
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
                                            <circle cx="7" cy="17" r="2"></circle>
                                            <path d="M9 17h6"></path>
                                            <circle cx="17" cy="17" r="2"></circle>
                                        </svg>
                                    </div>
                                    <h4>Sedan</h4>
                                    <p>14-16 feet</p>
                                </div>
                            </label>
                            <label class="vehicle-type-option">
                                <input type="checkbox" name="vehicleTypes[]" value="suv" >
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
                                            <circle cx="7" cy="17" r="2"></circle>
                                            <path d="M9 17h6"></path>
                                            <circle cx="17" cy="17" r="2"></circle>
                                            <path d="M2 8h20"></path>
                                        </svg>
                                    </div>
                                    <h4>SUV</h4>
                                    <p>16-18 feet</p>
                                </div>
                            </label>
                            <label class="vehicle-type-option">
                                 <input type="checkbox" name="vehicleTypes[]" value="hatchback" >
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
                                            <circle cx="7" cy="17" r="2"></circle>
                                            <path d="M9 17h6"></path>
                                            <circle cx="17" cy="17" r="2"></circle>
                                        </svg>
                                    </div>
                                    <h4>Hatchback</h4>
                                    <p>12-14 feet</p>
                                </div>
                            </label>
                            <label class="vehicle-type-option">
                                <input type="checkbox" name="vehicleTypes[]" value="minivan">
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M19 17h2c.6 0 1-.4 1-1v-3c0-.9-.7-1.7-1.5-1.9L18.4 9H5.6L3.5 11.1C2.7 11.3 2 12.1 2 13v3c0 .6.4 1 1 1h2"></path>
                                            <circle cx="7" cy="17" r="2"></circle>
                                            <path d="M9 17h6"></path>
                                            <circle cx="17" cy="17" r="2"></circle>
                                            <path d="M2 8h20"></path>
                                            <path d="M7 8v8"></path>
                                            <path d="M17 8v8"></path>
                                        </svg>
                                    </div>
                                    <h4>Minivan</h4>
                                    <p>18-20 feet</p>
                                </div>
                            </label>
                            <label class="vehicle-type-option">
                                <input type="checkbox" name="vehicleTypes[]" value="truck">
                                <div class="vehicle-card">
                                    <div class="vehicle-icon">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"></path>
                                            <path d="M15 18H9"></path>
                                            <path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"></path>
                                            <circle cx="17" cy="18" r="2"></circle>
                                            <circle cx="7" cy="18" r="2"></circle>
                                        </svg>
                                    </div>
                                    <h4>Truck</h4>
                                    <p>20+ feet</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Amenities -->
                    <div class="form-section glass-card">
                        <h2>Amenities</h2>
                        <div class="amenities-grid">
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="covered">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M3 21h18"></path>
                                            <path d="M5 21V7l8-4v18"></path>
                                            <path d="M19 21V11l-6-4"></path>
                                        </svg>
                                    </div>
                                    <span>Covered</span>
                                </div>
                            </label>
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="security">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                                        </svg>
                                    </div>
                                    <span>Security</span>
                                </div>
                            </label>
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="ev">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"></path>
                                        </svg>
                                    </div>
                                    <span>EV Charging</span>
                                </div>
                            </label>
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="handicap">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="4" r="2"></circle>
                                            <path d="M19 13v-2a7 7 0 0 0-14 0v2"></path>
                                            <path d="M12 14l-8 4 2 3h12l2-3-8-4Z"></path>
                                        </svg>
                                    </div>
                                    <span>Handicap Access</span>
                                </div>
                            </label>
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="24_7">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="10"></circle>
                                            <polyline points="12,6 12,12 16,14"></polyline>
                                        </svg>
                                    </div>
                                    <span>24/7 Access</span>
                                </div>
                            </label>
                            <label class="amenity-option">
                                <input type="checkbox" name="amenities[]" value="lighting">
                                <div class="amenity-card">
                                    <div class="amenity-icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <circle cx="12" cy="12" r="5"></circle>
                                            <line x1="12" y1="1" x2="12" y2="3"></line>
                                            <line x1="12" y1="21" x2="12" y2="23"></line>
                                            <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                                            <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                                            <line x1="1" y1="12" x2="3" y2="12"></line>
                                            <line x1="21" y1="12" x2="23" y2="12"></line>
                                            <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                                            <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                                        </svg>
                                    </div>
                                    <span>Well Lit</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="button" class="btn btn-ghost" onclick="saveDraft()">Save Draft</button>
                        <button type="submit" class="btn btn-primary">Publish Spot</button>
                    </div>
                </div>
            </form><br>
        </div>
       </main>

    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                </svg>
            </div>
            <h3>Spot Added Successfully!</h3>
            <p>Your parking spot has been listed and is now available for booking.</p>
            <div class="modal-actions">
                <button class="btn btn-ghost" onclick="window.location.href='add-spot.php'">Add Another Spot</button>
            <button class="btn btn-primary" onclick="window.location.href='host-listings.php'">View My Listings</button>
            </div>
        </div>
    </div>
    
    
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
    $latitude    = isset($_POST['latitude']) ? $_POST['latitude'] : null;
    $longitude   = isset($_POST['longitude']) ? $_POST['longitude'] : null;

    $vehicleTypes = isset($_POST['vehicleTypes']) ? implode(',', $_POST['vehicleTypes']) : '';
    $amenities    = isset($_POST['amenities']) ? implode(',', $_POST['amenities']) : '';

    // Handle photo upload and use default if empty
    $photoPath = '';
    $defaultImagePath = 'uploads/default-image.jpg'; // ✅ Make sure this exists!

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
            $photoPath = $savedPhotos[0]; // First image as preview
        } else {
            $photoPath = $defaultImagePath;
        }
    } else {
        // ✅ No file selected, fallback to default image
        $photoPath = $defaultImagePath;
    }

    // Insert into parking_spots including photo_path
    $query = "INSERT INTO `parking_spots`(`user_id`, `name`, `description`, `address`, `latitude`, `longitude`, `hourly_rate`, `currency`, `photo_path`, `vehicle_types`, `amenities`)
              VALUES ('$user_id', '$name', '$description', '$address', '$latitude', '$longitude', '$hourlyRate', '$currency', '$photoPath', '$vehicleTypes', '$amenities')";

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

        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('successModal').style.display = 'flex';
        });
        </script>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($con) . "</p>";
    }
}
?><script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="js/main.js"></script>

<script>
let map, marker;

function initializeMap() {
    const defaultLatLng = [23.8103, 90.4125];
    map = L.map('addSpotMap').setView(defaultLatLng, 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    map.on('click', function (e) {
        const { lat, lng } = e.latlng;
        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lng;
        placeMarker(lat, lng);
        // Fetch address using reverse geocoding
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.display_name) {
                    document.getElementById('address').value = data.display_name;
                } else {
                    document.getElementById('address').value = '';
                }
            })
            .catch(() => {
                document.getElementById('address').value = '';
            });
    });
}

function placeMarker(lat, lng) {
    if (marker) {
        marker.setLatLng([lat, lng]);
    } else {
        marker = L.marker([lat, lng], { draggable: true }).addTo(map);
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
        const waitLeaflet = setInterval(() => {
            if (typeof L !== 'undefined') {
                clearInterval(waitLeaflet);
                initializeMap();
            }
        }, 100);
    }

    const addressInput = document.getElementById('address');
    addressInput.addEventListener('blur', function () {
        const address = this.value.trim();
        if (!address) return;

        const url = `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`;
        fetch(url)
            .then(res => res.json())
            .then(data => {
                if (data.length > 0) {
                    const { lat, lon } = data[0];
                    map.setView([lat, lon], 16);
                    placeMarker(lat, lon);
                    document.getElementById('latitude').value = lat;
                    document.getElementById('longitude').value = lon;
                } else {
                    alert('Location not found. Please check the address.');
                }
            })
            .catch(err => {
                console.error('Geocoding error:', err);
                alert('Error searching address.');
            });
    });

    const photoUpload = document.getElementById('photoUpload');
    const photoPreview = document.getElementById('photoPreview');

    if (photoUpload && photoPreview) {
        photoUpload.addEventListener('change', function () {
            photoPreview.innerHTML = '';
            const files = photoUpload.files;

            if (files.length > 5) {
                alert('You can only upload up to 5 images.');
                photoUpload.value = '';
                return;
            }

            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.width = '120px';
                        img.style.margin = '5px';
                        img.style.borderRadius = '10px';
                        img.style.objectFit = 'cover';
                        img.style.boxShadow = '0 0 5px rgba(0,0,0,0.2)';
                        photoPreview.appendChild(img);
                    };
                    reader.readAsDataURL(file);
                }
            }
        });
    }

    const checkboxes = document.querySelectorAll(".day-availability input[type='checkbox']");
    checkboxes.forEach(checkbox => {
        const dayContainer = checkbox.closest(".day-availability");
        const timeInputs = dayContainer.querySelectorAll("input[type='time']");
        updateTimeInputs(checkbox, timeInputs);
        checkbox.addEventListener("change", () => updateTimeInputs(checkbox, timeInputs));
    });

    function updateTimeInputs(checkbox, timeInputs) {
        timeInputs.forEach(input => input.disabled = !checkbox.checked);
    }

    document.getElementById('addSpotForm').addEventListener('submit', function (e) {
        const days = document.querySelectorAll('.day-availability');
        let errorMessage = '';

        days.forEach(day => {
            const checkbox = day.querySelector('input[type="checkbox"]');
            if (checkbox.checked) {
                const startInput = day.querySelector('input[name*="[start]"]');
                const endInput = day.querySelector('input[name*="[end]"]');

                const startTime = startInput.value;
                const endTime = endInput.value;

                if (!startTime || !endTime) return;

                const [startH, startM] = startTime.split(':').map(Number);
                const [endH, endM] = endTime.split(':').map(Number);

                const start = startH * 60 + startM;
                const end = endH * 60 + endM;
                const duration = end - start;

                const label = checkbox.parentElement.innerText.trim();

                if (start >= end) {
                    errorMessage += `❌ ${label}: Start time must be before end time.\n`;
                } else if (duration < 60) {
                    errorMessage += `❌ ${label}: Duration must be at least 1 hour.\n`;
                } else if (duration % 60 !== 0) {
                    errorMessage += `❌ ${label}: Duration must be in full-hour increments (e.g., 1hr, 2hr).\n`;
                }
            }
        });

        if (errorMessage) {
            e.preventDefault();
            alert("Please correct the following time input errors:\n\n" + errorMessage);
        }
    });

    window.saveDraft = function () {
        alert('Save Draft feature coming soon!');
    };
});
</script>


</body>
</html>
