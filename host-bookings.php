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
$host_id = $_SESSION['user_id'];

$sql = "
    SELECT 
        b.*, 
        u.name AS customer_name, 
        u.email AS customer_email, 
        u.phone AS customer_phone,
        s.name AS spot_name,
        s.currency
    FROM bookings b
    JOIN parking_spots s ON b.spot_id = s.id
    JOIN users u ON b.customer_id = u.id
    WHERE s.user_id = ?
    ORDER BY b.booking_date DESC, b.start_time DESC
";


$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$result = $stmt->get_result();
$upcoming = [];
$past = [];
$cancelled = [];

$now = new DateTime();

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

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Bookings - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
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
                        <li><a href="host-bookings.php" class="nav-link active">Bookings</a></li>
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

    <main class="bookings-page" style="padding-top: 120px;">
        <div class="container">
            <div class="page-header">
                <h1>My Bookings</h1>
                <p>Manage all your parking spot bookings</p>
            </div>

            <div class="bookings-tabs">
                <button class="tab-btn active" onclick="showTab('upcoming', event)">Upcoming</button>
<button class="tab-btn" onclick="showTab('past', event)">Past</button>
<button class="tab-btn" onclick="showTab('cancelled', event)">Cancelled</button>

            </div>

            <div class="bookings-content">
                <!-- Upcoming Bookings -->
                <div class="tab-content active" id="upcoming">
                    <div class="bookings-table glass-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Spot</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($upcoming as $booking): ?>
<tr>
    <td>
        <div class="customer-info">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($booking['customer_name']); ?>&background=random" alt="Customer">
            <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
        </div>
    </td>
    <td><?php echo htmlspecialchars($booking['spot_name']); ?></td>
    <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
    <td>
        <?php
            $start = strtotime($booking['start_time']);
            $end = strtotime($booking['end_time']);
            $duration = ($end - $start) / 3600;
            echo $duration . ' hours';
        ?>
    </td>
    <td><?php echo number_format($booking['total_cost'], 2) . ' ' . htmlspecialchars($booking['currency']); ?></td>

    <td><span class="status-badge active"><?php echo htmlspecialchars($booking['status']); ?></span></td>
    <td><button class="btn btn-sm btn-ghost">Contact</button></td>
</tr>
<?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Past Bookings -->
                <div class="tab-content" id="past">
                    <div class="bookings-table glass-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Spot</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Rating</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($past as $booking): ?>
                                <tr>
    <td>
        <div class="customer-info">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($booking['customer_name']); ?>&background=random" alt="Customer">
            <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
        </div>
    </td>
    <td><?php echo htmlspecialchars($booking['spot_name']); ?></td>
    <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
    <td>
        <?php
            $start = strtotime($booking['start_time']);
            $end = strtotime($booking['end_time']);
            $duration = ($end - $start) / 3600;
            echo $duration . ' hours';
        ?>
    </td>
    <td><?php echo number_format($booking['total_cost'], 2) . ' ' . htmlspecialchars($booking['currency']); ?></td>

    <td><span class="status-badge active"><?php echo htmlspecialchars($booking['status']); ?></span></td>
    <td><button class="btn btn-sm btn-ghost">Contact</button></td>
</tr>
<?php endforeach; ?>

                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Cancelled Bookings -->
                <div class="tab-content" id="cancelled">
                    <div class="bookings-table glass-card">
                        <table>
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Spot</th>
                                    <th>Date</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cancelled as $booking): ?>
                                <tr>
    <td>
        <div class="customer-info">
            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($booking['customer_name']); ?>&background=random" alt="Customer">
            <span><?php echo htmlspecialchars($booking['customer_name']); ?></span>
        </div>
    </td>
    <td><?php echo htmlspecialchars($booking['spot_name']); ?></td>
    <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
    <td>
        <?php
            $start = strtotime($booking['start_time']);
            $end = strtotime($booking['end_time']);
            $duration = ($end - $start) / 3600;
            echo $duration . ' hours';
        ?>
    </td>
    <td><?php echo number_format($booking['total_cost'], 2) . ' ' . htmlspecialchars($booking['currency']); ?></td>

    <td><span class="status-badge active"><?php echo htmlspecialchars($booking['status']); ?></span></td>
    <td><button class="btn btn-sm btn-ghost">Contact</button></td>
</tr>
<?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
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
<script src="js/main.js"></script>

<script>
     // Tab switch logic
    function showTab(tabId) {
        const tabButtons = document.querySelectorAll('.tab-btn');
        const tabContents = document.querySelectorAll('.tab-content');
  event.target.classList.add('active');

        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(tab => tab.classList.remove('active'));

        document.getElementById(tabId).classList.add('active');
        event.target.classList.add('active');
    }
</script>
    
</body>
</html>
