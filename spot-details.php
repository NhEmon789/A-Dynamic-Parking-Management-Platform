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

$spot_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($spot_id <= 0) die("Invalid spot ID.");

// Fetch spot
$sql_spot = "SELECT * FROM parking_spots WHERE id = ?";
$stmt = $conn->prepare($sql_spot);
$stmt->bind_param("i", $spot_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Parking spot not found.");
$spot = $result->fetch_assoc();

if ($spot['spot_status'] !== 'active') die("This spot is not currently active.");


// Host name
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

// Images
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

// Vehicle types
$vehicleTypes = !empty($spot['vehicle_types']) ? array_map('strtolower', array_map('trim', explode(',', $spot['vehicle_types']))) : [];


// Amenities
$amenities = !empty($spot['amenities']) ? array_map('trim', explode(',', $spot['amenities'])) : [];

// Availability
$sql_availability = "SELECT day_of_week, start_time, end_time FROM spot_availability WHERE spot_id = ?";
$stmt2 = $conn->prepare($sql_availability);
$stmt2->bind_param("i", $spot_id);
$stmt2->execute();
$availability_result = $stmt2->get_result();
$availability = [];
while ($row = $availability_result->fetch_assoc()) {
    $availability[] = $row;
}

// Booking handler
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_id = $_SESSION['user_id'];
    $booking_date = $_POST['date'] ?? null;
    $start_time = $_POST['startTime'] ?? null;
    $end_time = $_POST['endTime'] ?? null;
    $total_cost = $_POST['totalCost'] ?? 0.00;
    $vehicle_type = $_POST['vehicleType'] ?? null;
    $created_at = date('Y-m-d H:i:s');

    // Basic validation
    if (!$booking_date || !$start_time || !$end_time || !$total_cost) {
        echo json_encode(['success' => false, 'message' => 'Missing booking details.']);
        exit();
    }

    // Date not in past
    $today = date('Y-m-d');
    if ($booking_date < $today) {
        echo json_encode(['success' => false, 'message' => 'Booking date cannot be in the past.']);
        exit();
    }

    // Time logic
    if ($booking_date === $today) {
        $current_time = date('H:i');
        if ($start_time <= $current_time) {
            echo json_encode(['success' => false, 'message' => 'Start time cannot be in the past.']);
            exit();
        }
    }

    if ($start_time >= $end_time) {
        echo json_encode(['success' => false, 'message' => 'Start time must be before end time.']);
        exit();
    }

    // Duration
    $start = new DateTime($start_time);
    $end = new DateTime($end_time);
    $interval = $start->diff($end);
    $duration_hours = $interval->h + ($interval->i / 60);
    if ($duration_hours < 1) {
        echo json_encode(['success' => false, 'message' => 'Minimum booking duration is 1 hour.']);
        exit();
    }
    if (fmod($duration_hours, 1) !== 0.0) {
        echo json_encode(['success' => false, 'message' => 'Only full-hour bookings are allowed.']);
        exit();
    }

    // Spot available on selected day
    $day = strtolower(date('l', strtotime($booking_date)));
    $valid_day = false;
    $day_start = null;
    $day_end = null;

    foreach ($availability as $slot) {
        if (strtolower($slot['day_of_week']) === $day) {
            $valid_day = true;
            $day_start = $slot['start_time'];
            $day_end = $slot['end_time'];
            break;
        }
    }
    if (!$valid_day) {
        echo json_encode(['success' => false, 'message' => 'This spot is not available on selected day.']);
        exit();
    }


function normalize_time($time_str) {
    return date('H:i', strtotime($time_str));
}

$start_time_norm = normalize_time($start_time);
$end_time_norm = normalize_time($end_time);
$day_start_norm = normalize_time($day_start);
$day_end_norm = normalize_time($day_end);

if ($start_time_norm < $day_start_norm || $end_time_norm > $day_end_norm) {
    echo json_encode(['success' => false, 'message' => "Booking must be between $day_start_norm and $day_end_norm."]);
    exit();
}


    // Vehicle compatibility
    if (!in_array($vehicle_type, $vehicleTypes)) {
        echo json_encode(['success' => false, 'message' => 'Selected vehicle type is not compatible with this spot.']);
        exit();
    }
// Spot booking conflict (exclude cancelled)
$conflict_sql = "SELECT * FROM bookings 
    WHERE spot_id = ? 
    AND booking_date = ? 
    AND status = 'active'
    AND (
        (start_time < ? AND end_time > ?) OR
        (start_time < ? AND end_time > ?) OR
        (start_time >= ? AND end_time <= ?)
    )";
$check_stmt = $conn->prepare($conflict_sql);
$check_stmt->bind_param(
    "isssssss",
    $spot_id, $booking_date,
    $end_time, $end_time,
    $start_time, $start_time,
    $start_time, $end_time
);
$check_stmt->execute();
$conflict_result = $check_stmt->get_result();
if ($conflict_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'This time slot is already booked.']);
    exit();
}

// User double booking (exclude cancelled)
$conflict_sql_user = "SELECT * FROM bookings 
    WHERE customer_id = ? 
    AND booking_date = ? 
    AND status = 'active'
    AND (
        (start_time < ? AND end_time > ?) OR
        (start_time < ? AND end_time > ?) OR
        (start_time >= ? AND end_time <= ?)
    )";
$check_user_stmt = $conn->prepare($conflict_sql_user);
$check_user_stmt->bind_param(
    "isssssss",
    $customer_id, $booking_date,
    $end_time, $end_time,
    $start_time, $start_time,
    $start_time, $end_time
);
$check_user_stmt->execute();
$user_result = $check_user_stmt->get_result();
if ($user_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'You already have a booking during this time.']);
    exit();
}

    // Insert booking
    $insert_sql = "INSERT INTO bookings (spot_id, customer_id, booking_date, start_time, end_time, total_cost, payment_status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?)";
    $insert_stmt = $conn->prepare($insert_sql);
    $insert_stmt->bind_param("iisssds", $spot_id, $customer_id, $booking_date, $start_time, $end_time, $total_cost, $created_at);

    if ($insert_stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Booking successful.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Booking failed. Please try again.']);
    }

    $insert_stmt->close();
    $conn->close();
    exit();
}
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
                <?php
if (!empty($spotImages)) {
    foreach ($spotImages as $photo) {
        echo '<div class="carousel-slide" style="min-width: 100%; height: 400px;">
                <img src="' . $photo . '" alt="Parking Spot" style="width: 100%; height: 100%; object-fit: cover;">
              </div>';
    }
} else {
    echo '<div class="carousel-slide" style="min-width: 100%; height: 400px;">
            <img src="assets/images/default-parking.jpg" alt="Default Parking" style="width: 100%; height: 100%; object-fit: cover;">
          </div>';
}
?>

            </div>

            <button class="carousel-btn prev" onclick="previousSlide()" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 18px;">‹</button>
            <button class="carousel-btn next" onclick="nextSlide()" style="position: absolute; right: 20px; top: 50%; transform: translateY(-50%); background: rgba(0,0,0,0.5); color: white; border: none; border-radius: 50%; width: 50px; height: 50px; cursor: pointer; font-size: 18px;">›</button>
        </div>

        <div class="carousel-indicators" style="display: flex; justify-content: center; gap: 10px; margin-top: 15px;">
            <?php
            $count = !empty($spot['photos']) && is_array($spot['photos']) ? count($spot['photos']) : 1;
            for ($i = 0; $i < $count; $i++) {
                $activeClass = $i === 0 ? 'active' : '';
                $bgColor = $activeClass ? '#3F3F3F' : '#BFBFBF';
                echo '<button class="indicator ' . $activeClass . '" onclick="goToSlide(' . $i . ')" style="width: 12px; height: 12px; border-radius: 50%; border: none; background: ' . $bgColor . '; cursor: pointer;"></button>';
            }
            ?>
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
        <input 
            type="time" 
            id="startTime" 
            name="startTime" 
            required 
            class="time-input" 
            step="3600" 
            min="00:00" 
            max="23:00" 
            value="08:00"
        >
    </div>

    <div class="form-group">
        <label for="endTime" style="display: block; font-weight: 600; color: #3F3F3F; margin-bottom: 0.5rem;">End Time</label>
        <input 
            type="time" 
            id="endTime" 
            name="endTime" 
            required
            class="time-input" 
            step="3600" 
            min="01:00" 
            max="23:59" 
            value="18:00"
        >
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
    <!-- Payment Modal will be moved below -->
    </div>
</div>

                </div>
            </div>
        </div>
    </main>

<!-- Payment Modal (moved outside main content for proper overlay) -->
<div class="payment-modal-overlay" id="paymentModalOverlay">
    <div class="payment-modal">
        <div class="payment-modal-header">
            <h2>Select Payment Method</h2>
            <button class="close-modal" id="closePaymentModal">&times;</button>
        </div>
        <div class="payment-options" id="paymentOptions">
            <div class="payment-option" data-method="bkash">
                <img src="uploads/bkash.png" alt="Bkash">
                <h3>Bkash</h3>
            </div>
            <div class="payment-option" data-method="nagad">
                <img src="uploads/Nagad.png" alt="Nagad">
                <h3>Nagad</h3>
            </div>
            <div class="payment-option" data-method="rocket">
                <img src="uploads/rocket.png" alt="Rocket">
                <h3>Rocket</h3>
            </div>
            <div class="payment-option" data-method="card">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Card">
                <h3>Card</h3>
            </div>
        </div>
        <div class="payment-details">
            <div class="payment-details-row"><span>Amount:</span> <span id="modalTotalCost">0.00</span></div>
            <div class="payment-details-row"><span>Service Fee:</span> <span id="modalServiceFee">0.00</span></div>
            <div class="payment-details-row"><span>Total:</span> <span id="modalGrandTotal">0.00</span></div>
        </div>
        <button class="proceed-payment" id="confirmPaymentBtn" disabled>Confirm Payment</button>
    </div>
</div>
    
    <!-- Success Modal -->
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                </svg>
            </div>
            <h3>Booking Confirmed</h3>
            <p>Your parking spot is booked successfully.</p>
            <div class="modal-actions">
<button class="btn btn-ghost" onclick="window.location.href='spot-details.php?id=<?= $spot_id ?>'">Close</button>
            <button class="btn btn-primary" onclick="window.location.href='customer-bookings.php'">View My Bookings</button>
            </div>
        </div>
    </div>
<script src="js/main.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    // Payment Modal Logic
    const paymentModalOverlay = document.getElementById('paymentModalOverlay');
    const closePaymentModal = document.getElementById('closePaymentModal');
    const paymentOptions = document.querySelectorAll('.payment-option');
    const confirmPaymentBtn = document.getElementById('confirmPaymentBtn');
    let selectedPayment = null;

    // Show modal when Book Now is clicked
    document.querySelector('.btn.btn-primary.btn-full').addEventListener('click', function(e) {
        e.preventDefault();
        const bookingForm = document.getElementById('bookingForm');
        // Use HTML5 form validation
        if (!bookingForm.checkValidity()) {
            bookingForm.reportValidity();
            return;
        }
        // Extra JS validation for time range
        const startTime = document.getElementById('startTime').value;
        const endTime = document.getElementById('endTime').value;
        const start = new Date(`1970-01-01T${startTime}:00`);
        const end = new Date(`1970-01-01T${endTime}:00`);
        if (end <= start) {
            alert('End time must be after start time.');
            return;
        }
        // Update modal cost details
        document.getElementById('modalTotalCost').textContent = document.getElementById('totalCost').textContent;
        document.getElementById('modalServiceFee').textContent = document.getElementById('serviceFee').textContent;
        document.getElementById('modalGrandTotal').textContent = document.getElementById('totalCost').textContent;
        paymentModalOverlay.style.display = 'flex';
        setTimeout(() => {
            document.querySelector('.payment-modal').classList.add('active');
        }, 10);
    });

    // Close modal
    closePaymentModal.addEventListener('click', function() {
        document.querySelector('.payment-modal').classList.remove('active');
        setTimeout(() => {
            paymentModalOverlay.style.display = 'none';
        }, 300);
        selectedPayment = null;
        paymentOptions.forEach(opt => opt.classList.remove('selected'));
        confirmPaymentBtn.disabled = true;
    });

    // Select payment option
    paymentOptions.forEach(opt => {
        opt.addEventListener('click', function() {
            paymentOptions.forEach(o => o.classList.remove('selected'));
            this.classList.add('selected');
            selectedPayment = this.getAttribute('data-method');
            confirmPaymentBtn.disabled = false;
        });
    });

    // Confirm payment
    confirmPaymentBtn.addEventListener('click', function() {
        // Hide modal
        document.querySelector('.payment-modal').classList.remove('active');
        setTimeout(() => {
            paymentModalOverlay.style.display = 'none';
        }, 300);
        // Submit booking form
        document.getElementById('bookingForm').dispatchEvent(new Event('submit', {cancelable: true}));
    });

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

    let currentSlide = 0;
const track = document.getElementById('carouselTrack');
const slides = document.querySelectorAll('.carousel-slide');
const indicators = document.querySelectorAll('.indicator');

function updateCarousel() {
    track.style.transform = `translateX(-${currentSlide * 100}%)`;
    indicators.forEach((dot, index) => {
        dot.classList.toggle('active', index === currentSlide);
        dot.style.background = index === currentSlide ? '#3F3F3F' : '#BFBFBF';
    });
}

function nextSlide() {
    currentSlide = (currentSlide + 1) % slides.length;
    updateCarousel();
}

function previousSlide() {
    currentSlide = (currentSlide - 1 + slides.length) % slides.length;
    updateCarousel();
}

function goToSlide(index) {
    currentSlide = index;
    updateCarousel();
}

// Auto-slide every 5 seconds
setInterval(nextSlide, 5000);

// ========== COST CALCULATION ==========
const hourlyRate = <?= (float)$spot['hourly_rate'] ?>;
const currencySymbol = <?= json_encode($spot['currency']) ?>;

function updateCostSummary() {
    const startTime = document.getElementById('startTime').value;
    const endTime = document.getElementById('endTime').value;
    const durationEl = document.getElementById('duration');
    const totalCostEl = document.getElementById('totalCost');
    const serviceFeeEl = document.getElementById('serviceFee');

    if (!startTime || !endTime) return;

    const start = new Date(`1970-01-01T${startTime}:00`);
    const end = new Date(`1970-01-01T${endTime}:00`);
    const diff = (end - start) / (1000 * 60 * 60); // hours

    let duration = Math.max(0, diff);
    duration = Math.floor(duration); // Ensure whole hours

    const serviceFee = duration > 0 ? 10 : 0;
    const total = duration * hourlyRate + serviceFee;

    durationEl.textContent = `${duration} hour${duration !== 1 ? 's' : ''}`;
    serviceFeeEl.textContent = `${currencySymbol}${serviceFee.toFixed(2)}`;
    totalCostEl.textContent = `${currencySymbol}${total.toFixed(2)}`;
}

document.getElementById('startTime').addEventListener('change', updateCostSummary);
document.getElementById('endTime').addEventListener('change', updateCostSummary);

// Initial calculation
updateCostSummary();

// ========== BOOKING FORM HANDLER ==========
document.getElementById('bookingForm').addEventListener('submit', function(e) {
    // Only handle submit if triggered by Confirm Payment
    if (!selectedPayment) {
        e.preventDefault();
        return;
    }

    e.preventDefault();
    const formData = new FormData(this);
    const start = formData.get('startTime');
    const end = formData.get('endTime');
    const duration = (new Date(`1970-01-01T${end}:00`) - new Date(`1970-01-01T${start}:00`)) / (1000 * 60 * 60);
    const serviceFee = duration > 0 ? 10 : 0;
    const totalCost = duration * hourlyRate + serviceFee;
    formData.append('totalCost', totalCost.toFixed(2));
    formData.append('paymentMethod', selectedPayment);
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('successModal').style.display = 'flex';
        } else {
            alert(data.message || 'Booking failed.');
        }
    })
    .catch(() => {
        alert('An error occurred while booking. Please try again.');
    });
});
</script>


   
</body>
</html>