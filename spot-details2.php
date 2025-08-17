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

// DB Connection
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Spot ID from URL
$spot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($spot_id <= 0) die("Invalid spot ID.");

// Fetch spot details
$sql_spot = "SELECT * FROM parking_spots WHERE id = ?";
$stmt = $conn->prepare($sql_spot);
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Parking spot not found.");
$spot = $result->fetch_assoc();

// Fetch host name
$host_name = "Unknown";
$sql_user = "SELECT name FROM users WHERE id = ?";
$stmt_user = $conn->prepare($sql_user);
$stmt_user->bind_param("i", $spot['user_id']);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
if ($user_result->num_rows > 0) {
    $host_data = $user_result->fetch_assoc();
    $host_name = $host_data['name'];
}

// Prepare images
$spotImages = [];
if (!empty($spot['photo_path'])) {
    $paths = explode(',', $spot['photo_path']);
    foreach ($paths as $path) {
        $trimmed = trim($path);
        if (!empty($trimmed)) {
            $spotImages[] = htmlspecialchars($trimmed);
        }
    }
}
if (empty($spotImages)) {
    $spotImages[] = 'assets/images/default-parking.jpg';
}

// Prepare vehicle types
$vehicleTypes = !empty($spot['vehicle_types']) ? array_map('trim', explode(',', $spot['vehicle_types'])) : [];

// Prepare amenities
$amenities = !empty($spot['amenities']) ? array_map('trim', explode(',', $spot['amenities'])) : [];

// Spot availability
$sql_availability = "SELECT day_of_week, start_time, end_time FROM spot_availability WHERE spot_id = ? ORDER BY FIELD(day_of_week, 'monday','tuesday','wednesday','thursday','friday','saturday','sunday')";
$stmt2 = $conn->prepare($sql_availability);
$stmt2->bind_param("i", $spot_id);
$stmt2->execute();
$availability_result = $stmt2->get_result();
$availability = [];
while ($row = $availability_result->fetch_assoc()) {
    $availability[] = $row;
}

// Handle AJAX booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $booking_date = $_POST['date'] ?? null;
    $start_time = $_POST['startTime'] ?? null;
    $end_time = $_POST['endTime'] ?? null;
    $total_cost = $_POST['totalCost'] ?? 0.00;
    $payment_status = 'pending';
    $created_at = date('Y-m-d H:i:s');

    if (!$booking_date || !$start_time || !$end_time || !$total_cost) {
        echo json_encode(['success' => false, 'message' => 'Missing booking details.']);
        exit();
    }
$conflict_sql = "SELECT * FROM bookings WHERE spot_id = ? AND booking_date = ? AND (
    (start_time < ? AND end_time > ?) OR
    (start_time < ? AND end_time > ?) OR
    (start_time >= ? AND end_time <= ?)
)";
$check_stmt = $conn->prepare($conflict_sql);
$check_stmt->bind_param("isssssss", $spot_id, $booking_date, $end_time, $end_time, $start_time, $start_time, $start_time, $end_time);
$check_stmt->execute();
$conflict_result = $check_stmt->get_result();
if ($conflict_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Spot already booked during this time.']);
    exit();
}
    $insert_sql = "INSERT INTO bookings (spot_id, customer_id, booking_date, start_time, end_time, total_cost, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iisssdss", $spot_id, $customer_id, $booking_date, $start_time, $end_time, $total_cost, $payment_status, $created_at);

    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
    }

    $insert_stmt->close();
    $conn->close();
    exit();
}

// Close connections for non-POST flow
$stmt->close();
$stmt2->close();
$stmt_user->close();
$conn->close();
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parking Spot Details - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <link rel="stylesheet" href="css/payment-modal.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

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
                        <li><a href="search.php" class="nav-link">Search</a></li>
                        <li><a href="customer-bookings.php" class="nav-link">Bookings</a></li>
                        <li><a href="customer-profile.php" class="nav-link">Profile</a></li>
                    </ul>
                    <div class="user-actions mobile">
                        <div class="role-switch">
                            <a href="host-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle">Switch to Host</a>
                        </div>
                        <a href="logout.php" class="btn btn-primary">Logout</a>

                    </div>
                </nav>
                <div class="user-actions desktop">
                    <div class="role-switch">
<a href="host-dashboard.php" class="btn btn-ghost role-toggle" id="roleToggle" style="color:#fff;">Switch to Host</a>
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

    <main class="spot-details-page" style="padding-top: 120px;">
        <div class="container">
            <div class="spot-details-grid" style="display: grid; grid-template-columns: 2fr 1fr; gap: 2rem; margin-top: 2rem;">
                <!-- Main Content -->
                <div class="spot-main-content">
                    <!-- Image Carousel -->
                    <div class="spot-carousel glass-card" style="margin-bottom: 2rem;">
                        <div class="carousel-container" style="position: relative; height: 400px; overflow: hidden; border-radius: 15px;">
                            <div class="carousel-track" id="carouselTrack" style="display: flex; transition: transform 0.5s ease;">
                                <div class="carousel-slide" style="min-width: 100%; height: 400px;">
    <?php
        if (!empty($spot['photo_path']) && file_exists($spot['photo_path'])) {
            echo '<img src="' . htmlspecialchars($spot['photo_path']) . '" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">';
        } else {
            echo '<img src="assets/images/default-parking.jpg" alt="Default Parking" style="width: 100%; height: 100%; object-fit: cover;">';
        }
    ?>
</div>


                                <div class="carousel-slide" style="min-width: 100%; height: 400px;">
                                    <img src="https://images.unsplash.com/photo-1652060273109-535205e5c79e?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="carousel-slide" style="min-width: 100%; height: 400px;">
                                    <img src="https://images.unsplash.com/photo-1647922608588-b8570896a0b5?q=80&w=670&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="carousel-slide" style="min-width: 100%; height: 400px;">
                                    <img src="https://images.unsplash.com/photo-1692450932066-785f248b4013?q=80&w=1976&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                                <div class="carousel-slide" style="min-width: 100%; height: 400px;">
                                    <img src="https://images.unsplash.com/photo-1647925694186-30ae1d2b0d25?q=80&w=1974&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">
                                </div>
                            </div>
                            <button class="carousel-btn prev" onclick="previousSlide()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 18px;">‹</button>
                            <button class="carousel-btn next" onclick="nextSlide()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 18px;">›</button>
                        </div>
                        <div class="carousel-indicators" style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
                            <button class="indicator active" onclick="goToSlide(0)" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: #3F3F3F; cursor: pointer;"></button>
                            <button class="indicator" onclick="goToSlide(1)" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: #BFBFBF; cursor: pointer;"></button>
                            <button class="indicator" onclick="goToSlide(2)" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: #BFBFBF; cursor: pointer;"></button>
                            <button class="indicator" onclick="goToSlide(3)" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: #BFBFBF; cursor: pointer;"></button>
                            <button class="indicator" onclick="goToSlide(4)" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: #BFBFBF; cursor: pointer;"></button>
                        </div>
                    </div>

                    <!-- Spot Information -->
<div class="spot-info-card glass-card" style="padding: 2rem; margin-bottom: 2rem;">
    <div class="spot-header" style="margin-bottom: 2rem;">
        <h1><?= htmlspecialchars($spot['name']) ?></h1>
    </div>


    <div class="spot-details" style="margin-bottom: 2rem;">
        <div class="detail-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1rem;">
            <div class="detail-item">
                <span class="label" style="font-weight: 600;">Address:</span>
                <span class="value"><?= htmlspecialchars($spot['address']) ?></span>
            </div>
            <div class="detail-item">
                <span class="label" style="font-weight: 600;">Host:</span>
                <span class="value"><?= htmlspecialchars($host_name) ?></span>
            </div>
        </div>

        <div class="detail-row" style="display: grid; grid-template-columns: 1fr; margin-top: 1rem; gap: 1rem;">
            <div class="detail-item">
                <span class="label" style="font-weight: 600;">Hourly Rate:</span>
                <span class="value" style="font-size: 1.2rem; font-weight: bold;">
                    <?= htmlspecialchars($spot['currency']) . number_format($spot['hourly_rate'], 2) ?>/hour
                </span>
            </div>

            <?php if (!empty($availability)): ?>
                <div class="detail-item">
                    <span class="label" style="font-weight: 600;">Availability:</span>
                    <div style="margin-top: 0.3rem;">
                        <?php foreach ($availability as $a): ?>
                            <div style="margin-bottom: 0.3rem;">
                                <i class="fa-regular fa-clock" style="margin-right: 6px; color: #3F3F3F;"></i>
                                <strong style="text-transform: capitalize;"><?= htmlspecialchars($a['day_of_week']) ?>:</strong>
                                <?= htmlspecialchars(date("g:i A", strtotime($a['start_time']))) ?> - <?= htmlspecialchars(date("g:i A", strtotime($a['end_time']))) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($vehicleTypes)): ?>
    <div class="supported-vehicles" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.3rem;">Supported Vehicle Types</h3>
        <div style="display: flex; gap: 0.75rem; flex-wrap: wrap;">
            <?php foreach ($vehicleTypes as $type): 
                $icon = 'fa-car';
                $lower = strtolower($type);
                if (strpos($lower, 'suv') !== false)        $icon = 'fa-truck-monster';
                elseif (strpos($lower, 'bike') !== false)   $icon = 'fa-motorcycle';
                elseif (strpos($lower, 'truck') !== false)  $icon = 'fa-truck';
                elseif (strpos($lower, 'van') !== false)    $icon = 'fa-shuttle-van';
                elseif (strpos($lower, 'bus') !== false)    $icon = 'fa-bus';
                elseif (strpos($lower, 'scooter') !== false) $icon = 'fa-bicycle';
            ?>
                <span style="display: flex; align-items: center; gap: 0.5rem; background: #f1f1f1; padding: 0.5rem 1rem; border-radius: 20px; font-weight: 500; color: #333;">
                    <i class="fa-solid <?= $icon ?>"></i>
                    <?= htmlspecialchars($type) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($amenities)): ?>
    <div class="amenities" style="margin-bottom: 2rem;">
        <h3 style="font-size: 1.3rem;">Amenities</h3>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <?php foreach ($amenities as $amenity): ?>
                <span style="background-color: #e6f4ea; color: #2e7d32; font-weight: 500; padding: 0.5rem 1rem; border-radius: 20px;">
                    <?= htmlspecialchars($amenity) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>



                    <!-- Map Section -->
                    <div class="spot-map-card glass-card" style="padding: 2rem; margin-bottom: 2rem;">
                        <h3 style="font-size: 1.3rem; font-weight: 600; color: #262626; margin-bottom: 1rem;">Location</h3>
                        <div id="spotMap" class="spot-map" style="height: 300px; border-radius: 15px; border: 2px solid #E7E7E7;"></div>
                    </div>

                    <!-- Reviews Section -->
                    <div class="reviews-section glass-card" style="padding: 2rem;">
                        <h3 style="font-size: 1.3rem; font-weight: 600; color: #262626; margin-bottom: 2rem;">Reviews</h3>
                        <div class="reviews-list">
                            <div class="review-item" style="border-bottom: 1px solid #E7E7E7; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div class="reviewer-info" style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=50&h=50&fit=crop&crop=face" alt="Reviewer" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                        <div>
                                            <h4 style="font-weight: 600; color: #262626; margin-bottom: 0.2rem;">Mike Johnson</h4>
                                            <span class="review-date" style="color: #999; font-size: 0.9rem;">2 weeks ago</span>
                                        </div>
                                    </div>
                                    <div class="review-rating" style="color: #FFD700; font-size: 1.1rem;">★★★★★</div>
                                </div>
                                <p style="color: #666; line-height: 1.6;">Excellent parking spot! Very convenient location and the host was very responsive. The spot was exactly as described and very secure.</p>
                            </div>

                            <div class="review-item" style="border-bottom: 1px solid #E7E7E7; padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
                                <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div class="reviewer-info" style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?q=80&w=764&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Reviewer" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                        <div>
                                            <h4 style="font-weight: 600; color: #262626; margin-bottom: 0.2rem;">Sarah Davis</h4>
                                            <span class="review-date" style="color: #999; font-size: 0.9rem;">1 month ago</span>
                                        </div>
                                    </div>
                                    <div class="review-rating" style="color: #FFD700; font-size: 1.1rem;">★★★★☆</div>
                                </div>
                                <p style="color: #666; line-height: 1.6;">Great location in downtown. Easy access and good security. The only minor issue was that the spot was a bit tight for my SUV, but it worked out fine.</p>
                            </div>

                            <div class="review-item">
                                <div class="review-header" style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                    <div class="reviewer-info" style="display: flex; align-items: center; gap: 1rem;">
                                        <img src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?w=50&h=50&fit=crop&crop=face" alt="Reviewer" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">
                                        <div>
                                            <h4 style="font-weight: 600; color: #262626; margin-bottom: 0.2rem;">David Wilson</h4>
                                            <span class="review-date" style="color: #999; font-size: 0.9rem;">1 month ago</span>
                                        </div>
                                    </div>
                                    <div class="review-rating" style="color: #FFD700; font-size: 1.1rem;">★★★★★</div>
                                </div>
                                <p style="color: #666; line-height: 1.6;">Perfect spot for business meetings downtown. Host provided clear instructions and the EV charging station was exactly what I needed.</p>
                            </div>
                        </div>
                    </div>
                </div>

               <!-- Booking Sidebar -->
<div class="booking-sidebar">
 <div class="booking-card glass-card sticky-booking" style="padding: 2rem;">
    <div class="booking-header" style="text-align: center; margin-bottom: 2rem;">
        <h3 style="font-size: 1.5rem; font-weight: 600; color: #262626; margin-bottom: 0.5rem;">Book This Spot</h3>
        <div class="price-display" id="bookingPrice" style="font-size: 2rem; font-weight: 600; color: #3F3F3F;">
            <?= htmlspecialchars($spot['currency']) . number_format((float)$spot['hourly_rate'], 2) ?>
            <span style="font-size: 1rem; font-weight: 400; color: #666;">/hour</span>
        </div>
    </div>


        <form class="booking-form" id="bookingForm">
            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="bookingDate" style="display: block; font-weight: 600; color: #3F3F3F; margin-bottom: 0.5rem;">Date</label>
                <input type="date" id="bookingDate" name="date" required style="width: 100%; padding: 0.8rem; border: 1px solid #BFBFBF; border-radius: 10px; font-size: 1rem;">
            </div>

            <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="startTime" style="display: block; font-weight: 600; color: #3F3F3F; margin-bottom: 0.5rem;">Start Time</label>
                    <select id="startTime" name="startTime" required style="width: 100%; padding: 0.8rem; border: 1px solid #BFBFBF; border-radius: 10px; font-size: 1rem;">
    
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
    <option value="00:00">12:00 AM</option>
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
</select>

                </div>

                <div class="form-group">
                    <label for="endTime" style="display: block; font-weight: 600; color: #3F3F3F; margin-bottom: 0.5rem;">End Time</label>
                    <select id="endTime" name="endTime" required style="width: 100%; padding: 0.8rem; border: 1px solid #BFBFBF; border-radius: 10px; font-size: 1rem;">
                        
                    <option value="18:00">6:00 PM</option>
    <option value="19:00">7:00 PM</option>
    <option value="20:00">8:00 PM</option>
    <option value="21:00">9:00 PM</option>
    <option value="22:00">10:00 PM</option>
    <option value="23:00">11:00 PM</option>
                    <option value="00:00">12:00 AM</option>
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
    
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 2rem;">
                <label for="vehicleType" style="display: block; font-weight: 600; color: #3F3F3F; margin-bottom: 0.5rem;">Vehicle Type</label>
                <select id="vehicleType" name="vehicleType" required style="width: 100%; padding: 0.8rem; border: 1px solid #BFBFBF; border-radius: 10px; font-size: 1rem;">
                    <option value="sedan">Sedan</option>
                    <option value="suv">SUV</option>
                    <option value="hatchback">Hatchback</option>
                    <option value="minivan">Minivan</option>
                    <option value="truck">Truck</option> <!-- ✅ ADDED -->
                </select>
            </div>
<div class="cost-summary" style="background: rgba(247, 247, 247, 0.8); padding: 1.5rem; border-radius: 15px; margin-bottom: 2rem;">
    <div class="cost-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="color: #666;">Duration:</span>
        <span id="duration" style="color: #262626; font-weight: 500;">0 hours</span>
    </div>
    <div class="cost-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="color: #666;">Rate:</span>
        <span id="hourlyRate" style="color: #262626; font-weight: 500;">
            <?= htmlspecialchars($spot['currency']) . number_format((float)$spot['hourly_rate'], 2) ?>/hour
        </span>
    </div>
    <div class="cost-row" style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
        <span style="color: #666;">Service Fee:</span>
        <span id="serviceFee" style="color: #262626; font-weight: 500;">TK0.00</span>
    </div>
    <div class="cost-row total" style="display: flex; justify-content: space-between; padding-top: 0.5rem; border-top: 1px solid #BFBFBF; font-weight: 600; font-size: 1.1rem;">
        <span style="color: #262626;">Total:</span>
        <span id="totalCost" style="color: #3F3F3F;">$0.00</span>
    </div>
</div>


            <button type="submit" class="btn btn-primary btn-full" style="width: 100%; background: rgba(63, 63, 63, 0.9); color: white; padding: 1rem; border: none; border-radius: 25px; font-size: 1.1rem; font-weight: 600; cursor: pointer; transition: all 0.3s ease;">Book Now</button>
        </form>
    </div>
</div>

                </div>
            </div>
        </div>
    </main>
    
    <!-- Payment Modal -->
<div class="modal" id="paymentModal">
        <div class="payment-modal">


        <!-- Header -->
        <div class="payment-modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <h2 style="font-size: 1.4rem; font-weight: 600;">Choose Payment Method</h2>
            <button onclick="closePaymentModal()" style="font-size: 1.5rem; background: none; border: none; cursor: pointer;">&times;</button>
        </div>

        <!-- Payment Options -->
        <div class="payment-options" style="display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem;">
            <div class="payment-option" onclick="selectPaymentMethod('bkash')" style="display: flex; align-items: center; gap: 1rem; border: 2px solid #ccc; padding: 1rem; border-radius: 10px; cursor: pointer;">
                <img src="https://download.logo.wine/logo/BKash/BKash-Logo.wine.png" alt="bKash" style="width: 40px;">
                <span style="font-weight: 500;">bKash</span>
            </div>
            <div class="payment-option" onclick="selectPaymentMethod('nagad')" style="display: flex; align-items: center; gap: 1rem; border: 2px solid #ccc; padding: 1rem; border-radius: 10px; cursor: pointer;">
                <img src="https://download.logo.wine/logo/Nagad/Nagad-Logo.wine.png" alt="Nagad" style="width: 40px;">
                <span style="font-weight: 500;">Nagad</span>
            </div>
            <div class="payment-option" onclick="selectPaymentMethod('rocket')" style="display: flex; align-items: center; gap: 1rem; border: 2px solid #ccc; padding: 1rem; border-radius: 10px; cursor: pointer;">
                <img src="https://download.logo.wine/logo/Dutch_Bangla_Bank/Dutch_Bangla_Bank-Logo.wine.png" alt="Rocket" style="width: 40px;">
                <span style="font-weight: 500;">Rocket</span>
            </div>
        </div>

        <!-- Booking Summary -->
        <div class="cost-summary" style="background: #f9f9f9; padding: 1rem 1.5rem; border-radius: 10px; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #666;">Booking Duration:</span>
                <span id="summaryDuration" style="font-weight: 500;">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #666;">Rate:</span>
                <span id="summaryRate" style="font-weight: 500;">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                <span style="color: #666;">Service Fee:</span>
                <span id="summaryFee" style="font-weight: 500;">--</span>
            </div>
            <div style="display: flex; justify-content: space-between; font-weight: 600; font-size: 1.1rem; padding-top: 0.5rem; border-top: 1px solid #ccc;">
                <span>Total Amount:</span>
                <span id="summaryTotal">--</span>
            </div>
        </div>

        <!-- Proceed Button -->
        <button id="proceedPayment" disabled onclick="finalizePayment()" style="width: 100%; padding: 1rem; font-size: 1rem; font-weight: 600; background: #3f3f3f; color: white; border: none; border-radius: 10px; cursor: not-allowed; opacity: 0.6;">
            Proceed to Payment
        </button>
    </div>
</div>

 <script>
let selectedPaymentMethod = null;

function openPaymentModal(summaryData = {}) {
    const modal = document.getElementById('paymentModal');
    modal.classList.add('show');

    // Fill in summary
    document.getElementById('summaryDuration').textContent = summaryData.duration || '--';
    document.getElementById('summaryRate').textContent = summaryData.rate || '--';
    document.getElementById('summaryFee').textContent = summaryData.fee || '--';
    document.getElementById('summaryTotal').textContent = summaryData.total || '--';
}

function closePaymentModal() {
    const modal = document.getElementById('paymentModal');
    modal.classList.remove('show');

    selectedPaymentMethod = null;
    const btn = document.getElementById('proceedPayment');
    btn.disabled = true;
    btn.style.opacity = 0.6;
    btn.style.cursor = 'not-allowed';

    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.style.borderColor = '#ccc';
        opt.style.backgroundColor = 'white';
    });
}

function selectPaymentMethod(method) {
    selectedPaymentMethod = method;

    const btn = document.getElementById('proceedPayment');
    btn.disabled = false;
    btn.style.opacity = 1;
    btn.style.cursor = 'pointer';

    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.style.borderColor = '#ccc';
        opt.style.backgroundColor = 'white';
    });

    const selected = [...document.querySelectorAll('.payment-option')].find(opt =>
        opt.innerText.toLowerCase().includes(method)
    );

    if (selected) {
        selected.style.borderColor = '#3f3f3f';
        selected.style.backgroundColor = '#f0f0f0';
    }
}

function finalizePayment() {
    if (!selectedPaymentMethod) return;

    alert(`Proceeding with ${selectedPaymentMethod} payment...`);
    closePaymentModal();
    document.getElementById('successModal').style.display = 'flex';
}
</script>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const dateInput = document.getElementById('bookingDate');
    const startTime = document.getElementById('startTime');
    const endTime = document.getElementById('endTime');
    const vehicleType = document.getElementById('vehicleType');
    const durationSpan = document.getElementById('duration');
    const hourlyRateSpan = document.getElementById('hourlyRate');
    const totalCostSpan = document.getElementById('totalCost');
    const bookingForm = document.getElementById('bookingForm');

    const supportedVehicles = <?= json_encode($vehicleTypes) ?>;
    const rate = <?= (float) $spot['hourly_rate'] ?>;
    const currency = <?= json_encode($spot['currency']) ?>;
    const availability = <?= json_encode($availability) ?>;

    // Auto-set today's date
    const today = new Date().toISOString().split('T')[0];
    dateInput.value = today;

    // Initialize map
    const lat = <?= json_encode($spot['latitude']) ?>;
    const lng = <?= json_encode($spot['longitude']) ?>;
    if (lat && lng) {
        const map = L.map('spotMap').setView([lat, lng], 15);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/">OpenStreetMap</a> contributors'
        }).addTo(map);

        L.marker([lat, lng]).addTo(map)
            .bindPopup('<?= htmlspecialchars($spot['name']) ?>')
            .openPopup();
    } else {
        document.getElementById('spotMap').innerHTML = '<p style="text-align:center;color:#999;padding-top:100px;">Location not available.</p>';
    }

    function parseHour(timeStr) {
        const [h, m] = timeStr.split(":").map(Number);
        return h + m / 60;
    }

    function getDayName(dateStr) {
        const days = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
        const date = new Date(dateStr);
        return days[date.getDay()];
    }

    function calculateCost() {
        const start = parseHour(startTime.value);
        const end = parseHour(endTime.value);
        let duration = end - start;
        if (duration < 0) duration += 24; // Overnight

        const total = duration * rate;
        durationSpan.textContent = `${duration} hour${duration !== 1 ? 's' : ''}`;
        hourlyRateSpan.textContent = `${currency}${rate.toFixed(2)}/hour`;
        totalCostSpan.textContent = `${currency}${total.toFixed(2)}`;
    }

    startTime.addEventListener('change', calculateCost);
    endTime.addEventListener('change', calculateCost);

    bookingForm.addEventListener('submit', function (e) {
        e.preventDefault();

        if (!dateInput.value) return alert("Please select a booking date.");
        if (!startTime.value || !endTime.value) return alert("Please select both start and end times.");

        const start = parseHour(startTime.value);
        const end = parseHour(endTime.value);
        if (start === end) return alert("Start and End time cannot be the same.");

        const selectedVehicle = vehicleType.value.trim().toLowerCase();
        const isSupported = supportedVehicles.some(v => v.trim().toLowerCase() === selectedVehicle);
        if (!isSupported) return alert(`Sorry, ${selectedVehicle} is not supported for this parking spot.`);

        const bookingDay = getDayName(dateInput.value);
        const match = availability.find(slot => slot.day_of_week.toLowerCase() === bookingDay);
        if (!match) return alert(`This spot is not available on ${bookingDay.charAt(0).toUpperCase() + bookingDay.slice(1)}.`);

        const availStart = parseHour(match.start_time.slice(0, 5));
        const availEnd = parseHour(match.end_time.slice(0, 5));
        if (start < availStart || end > availEnd) {
            return alert(`Selected time (${startTime.value} - ${endTime.value}) is outside availability (${match.start_time} - ${match.end_time}).`);
        }

        const formData = new FormData(bookingForm);
        const totalCost = parseFloat(totalCostSpan.textContent.replace(currency, ''));
        formData.append("totalCost", totalCost);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                const serviceFee = 10;
                const totalWithFee = totalCost + serviceFee;

                openPaymentModal({
                    duration: durationSpan.textContent,
                    rate: hourlyRateSpan.textContent,
                    fee: `${currency}${serviceFee.toFixed(2)}`,
                    total: `${currency}${totalWithFee.toFixed(2)}`
                });
            } else {
                alert(data.message || "Booking failed.");
            }
        })
        .catch(error => {
            console.error("Booking Error:", error);
            alert("An unexpected error occurred.");
        });
    });

    calculateCost(); // Initial calculation
});
</script>
<style>
.modal {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    height: 100%;
    z-index: 9999;
    background: rgba(0, 0, 0, 0.6);
    justify-content: center;
    align-items: center;
}

.modal.show {
    display: flex;
}

.payment-modal {
    background: #fff;
    border-radius: 15px;
    padding: 2rem;
    width: 90%;
    max-width: 500px;
    position: relative;
    z-index: 10000; /* ensures it's above everything */
}
</style>



   
</body>
</html>