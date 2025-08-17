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

$user_id = $_SESSION['user_id'];

// Fetch user's name
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($userName);
$stmt->fetch();
$stmt->close();

// Fetch total bookings (not cancelled)
$totalBookings = 0;
$bookingStmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM bookings 
    WHERE customer_id = ? 
    AND status != 'cancelled' 
    AND payment_status != 'cancelled'
");
$bookingStmt->bind_param("i", $user_id);
$bookingStmt->execute();
$bookingStmt->bind_result($totalBookings);
$bookingStmt->fetch();
$bookingStmt->close();
$upcoming = $past = $cancelled = [];
$now = new DateTime();

$sql = "
    SELECT 
        b.*, 
        p.name AS spot_name, 
        p.address, 
        p.vehicle_types, 
        p.currency,
        u.name AS host_name,
        u.created_at AS host_created_at
    FROM bookings b
    JOIN parking_spots p ON b.spot_id = p.id
    JOIN users u ON p.user_id = u.id
    WHERE b.customer_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $bookingEndStr = $row['booking_date'] . ' ' . $row['end_time'];
    $bookingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $bookingEndStr);
    $isCancelled = strtolower($row['payment_status']) === 'cancelled' || strtolower($row['status']) === 'cancelled';

    if ($isCancelled) {
        $cancelled[] = $row;
    } elseif ($bookingEnd && $bookingEnd < $now) {
        $past[] = $row;
    } else {
        $upcoming[] = $row;
    }
}
function renderBookingCard($booking, $context = '') {
    $date = date("M j, Y", strtotime($booking['booking_date']));
    $start = date("g:i A", strtotime($booking['start_time']));
    $end = date("g:i A", strtotime($booking['end_time']));
    $cost = number_format((float)$booking['total_cost'], 2);
    $currency = htmlspecialchars($booking['currency']);
    $statusClass = strtolower($booking['payment_status']) === 'completed' ? 'completed' :
                   (strtolower($booking['payment_status']) === 'cancelled' ? 'cancelled' : 'confirmed');

    $actionButton = '';
if ($context === 'cancelled') {
    $actionButton = "<a class='btn btn-warning btn-sm' href='customer-bookings.php'>Rebook</a>";
} elseif ($context === 'upcoming') {
    $actionButton = "<a class='btn btn-danger btn-sm' href='customer-bookings.php'>Cancel</a>";
} elseif ($context === 'past') {
    $actionButton = "<a class='btn btn-primary btn-sm' href='spot-details.php?id={$booking['spot_id']}'>Rebook</a>";
}

    return "
    <div class='booking-card glass-card'>
        <div class='booking-header'>
            <h3>" . htmlspecialchars($booking['spot_name']) . "</h3>
            <span class='booking-status $statusClass'>" . ucfirst($booking['payment_status']) . "</span>
        </div>
        <div class='booking-details'>
            <div class='detail-item'><span class='label'>Date:</span><span class='value'>$date</span></div>
            <div class='detail-item'><span class='label'>Time:</span><span class='value'>$start - $end</span></div>
            <div class='detail-item'><span class='label'>Location:</span><span class='value'>" . htmlspecialchars($booking['address']) . "</span></div>
            <div class='detail-item'><span class='label'>Vehicle:</span><span class='value'>" . htmlspecialchars($booking['vehicle_types']) . "</span></div>
            <div class='detail-item'><span class='label'>Total Cost:</span><span class='value'>{$currency} $cost</span></div>
        </div>
        <div class='booking-actions'>
            <a class='btn btn-ghost btn-sm' href='customer-bookings.php'>View Details</a>

            $actionButton
        </div>
    </div>";
}


?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Customer Dashboard - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/animations.css" />
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
    <li><a href="customer-dashboard.php" class="nav-link active">Dashboard</a></li>
    <li><a href="search.php" class="nav-link">Search</a></li>
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
                    <!-- Logout as a link -->
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

    <main class="dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-card glass-card" style="background: linear-gradient(135deg, rgba(63, 63, 63, 0.1), rgba(255, 255, 255, 0.3)); border: 2px solid rgba(63, 63, 63, 0.2);">
                    <div class="welcome-content">
                        <h1>Welcome back, <span><?= htmlspecialchars($userName) ?></span>!</h1>
                        <p>Ready to find your perfect parking spot?</p>
                        <div class="stats-summary">
                            <div class="stat-item">
    <span class="stat-number" id="totalBookings"><?= $totalBookings ?></span>
    <span class="stat-label">Total Bookings</span>
</div>
<div class="stat-item">
    <span class="stat-number" id="upcomingBookings"><?= count($upcoming) ?></span>

    <span class="stat-label">Upcoming</span>
</div>
<div class="stat-item">
    <span class="stat-number" id="savedSpots">5</span> <!-- Keep static -->
    <span class="stat-label">Saved Spots</span>
</div>

                        </div>
                    </div>
                    <div class="welcome-actions">
                        <a href="search.php" class="btn btn-primary">Find Parking</a>
                    </div>
                </div>
            </section>

            <!-- Upcoming Bookings -->
<section class="upcoming-bookings">
    <h2>Upcoming Bookings</h2>

    <?php if (empty($upcoming)): ?>
        <p>No upcoming bookings found.</p>
    <?php else: ?>
        <div class="bookings-grid">
            <?php foreach ($upcoming as $booking): ?>
                <?= renderBookingCard($booking, 'upcoming') ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>



            <!-- Suggested Locations -->
            <section class="suggested-locations">
                <h2>Suggested Locations</h2>
                <div class="locations-grid">
                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1555472492-816b516932ea?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car in Downtown" />
                        </div>
                        <div class="location-content">
                            <h3>Dhaka</h3>
                            <p>Available spots: 24</p>
                            <p>Starting from $5/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>

                        </div>
                    </div>

                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1740593353242-a8a5c51ca202?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car in Downtown" />
                        </div>
                        <div class="location-content">
                            <h3>Chittagong</h3>
                            <p>Available spots: 18</p>
                            <p>Starting from $5/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>
                        </div>
                    </div>

                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1607893467292-d32723a87a6e?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car at Shopping Mall" />
                        </div>
                        <div class="location-content">
                            <h3>Rajshahi</h3>
                            <p>Available spots: 13</p>
                            <p>Starting from $3/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>
                        </div>
                    </div>

                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1660322530320-b55453166f31?q=80&w=1982&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car at Airport" />
                        </div>
                        <div class="location-content">
                            <h3>Khulna</h3>
                            <p>Available spots: 21</p>
                            <p>Starting from $8/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>
                        </div>
                    </div>

                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1750509009064-8cacc53d18a9?q=80&w=2029&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car in Business District" />
                        </div>
                        <div class="location-content">
                            <h3>Bogura</h3>
                            <p>Available spots: 11</p>
                            <p>Starting from $6/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>
                        </div>
                    </div>

                    <div class="location-card glass-card">
                        <div class="location-image">
                            <img src="https://images.unsplash.com/photo-1559384403-c23988dd4219?q=80&w=2070&auto=format&fit=crop&ixlib=rb-4.1.0&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D" alt="Car in Business District" />
                        </div>
                        <div class="location-content">
                            <h3>Dinajpur</h3>
                            <p>Available spots: 16</p>
                            <p>Starting from $6/hour</p>
                            <a href="search.php" class="btn btn-primary btn-sm">Find Parking</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Notifications -->
            <section class="notifications">
                <h2>Recent Notifications</h2>
                <div class="notification-list">
                    <div class="notification-item glass-card">
                        <div class="notification-icon success">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                <polyline points="22,4 12,14.01 9,11.01"></polyline>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <h4>Booking Confirmed</h4>
                            <p>Your parking spot at Downtown Garage has been confirmed for Dec 15.</p>
                            <span class="notification-time">2 hours ago</span>
                        </div>
                    </div>
                    <div class="notification-item glass-card">
                        <div class="notification-icon warning">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12" y2="16"></line>
                            </svg>
                        </div>
                        <div class="notification-content">
                            <h4>Payment Reminder</h4>
                            <p>Your payment for Airport Long-term Parking is due on Dec 17.</p>
                            <span class="notification-time">1 day ago</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>Help Center</h4>
                    <ul>
                        <li><a href="#">Support</a></li>
                        <li><a href="#">Safety</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Contact Info</h4>
                    <ul>
                        <li>Email: support@findmyspot.com</li>
                        <li>Phone: +1 (555) 123-4567</li>
                        <li>Address: 123 Parking St, City, Country</li>
                        <li>Hours: 24/7 Support</li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>
<script>

</script>
<script src="js/main.js"></script>

  
</body>
</html>
