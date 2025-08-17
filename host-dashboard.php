<?php  
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host_id = $_SESSION['user_id'];

// Database connection
$conn = new mysqli("localhost", "root", "", "parking_manage");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Get host/user name
$hostName = "Host"; // fallback
$user_sql = "SELECT name FROM users WHERE id = ?";
$stmt_user = $conn->prepare($user_sql);
$stmt_user->bind_param("i", $host_id);
$stmt_user->execute();
$result_user = $stmt_user->get_result();
if ($row = $result_user->fetch_assoc()) {
    $hostName = $row['name'];
}

// ✅ Function to get availability for a spot
function getAvailability($conn, $spot_id) {
    $sql = "SELECT day_of_week, start_time, end_time FROM spot_availability 
            WHERE spot_id = ? 
            ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $spot_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $availability = "";
    while ($row = $result->fetch_assoc()) {
        $availability .= "<li>{$row['day_of_week']}: " . date("g:i A", strtotime($row['start_time'])) . " - " . date("g:i A", strtotime($row['end_time'])) . "</li>";
    }

    return $availability ? "<ul class='availability-list'>{$availability}</ul>" : "<em>No availability set</em>";
}

// ✅ Fetch user parking spot listings
$listings_sql = "SELECT * FROM parking_spots WHERE user_id = ?";
$stmt = $conn->prepare($listings_sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$listings = $stmt->get_result();

// ✅ Count total listings
$count_sql = "SELECT COUNT(*) AS total FROM parking_spots WHERE user_id = ?";
$count_stmt = $conn->prepare($count_sql);
$count_stmt->bind_param("i", $host_id);
$count_stmt->execute();
$count_stmt->bind_result($totalListings);
$count_stmt->fetch();
$count_stmt->close();

// ✅ Get host's spot IDs
$spotIds = [];
$spotQuery = $conn->query("SELECT id FROM parking_spots WHERE user_id = $host_id");
if ($spotQuery) {
    while ($row = $spotQuery->fetch_assoc()) {
        $spotIds[] = $row['id'];
    }
}
$spotIdsStr = count($spotIds) > 0 ? implode(',', $spotIds) : '0';

// ✅ Set currency
// Get currency from any one of the host's spots
$currency = 'TK'; // default fallback
$currencyQuery = $conn->prepare("SELECT currency FROM parking_spots WHERE user_id = ? LIMIT 1");
$currencyQuery->bind_param("i", $host_id);
$currencyQuery->execute();
$currencyResult = $currencyQuery->get_result();
if ($row = $currencyResult->fetch_assoc()) {
    $currency = $row['currency'] ?: 'TK'; // fallback if NULL
}
// ✅ Get current month's date range
$firstDay = date('Y-m-01');
$lastDay = date('Y-m-t');

// ✅ This Month's Earnings
$earnings_sql = "SELECT SUM(total_cost) AS total FROM bookings 
                 WHERE spot_id IN ($spotIdsStr)
                 AND status = 'active'
                 AND booking_date BETWEEN ? AND ?";
$stmt = $conn->prepare($earnings_sql);
$stmt->bind_param("ss", $firstDay, $lastDay);
$stmt->execute();
$result = $stmt->get_result();
$thisMonthEarnings = 0;
if ($row = $result->fetch_assoc()) {
    $thisMonthEarnings = $row['total'] ? (float)$row['total'] : 0;
}
$stmt->close();

// ✅ Total Bookings (all-time for this host)
$bookings_sql = "SELECT COUNT(*) AS total FROM bookings 
                 WHERE spot_id IN ($spotIdsStr)";
$res = $conn->query($bookings_sql);
$row = $res ? $res->fetch_assoc() : ['total' => 0];
$totalBookings = $row['total'] ?? 0;


// ✅ Fetch monthly earnings (last 12 months)
$monthlyEarnings = array_fill(0, 12, 0);
$monthLabels = [];

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $monthLabels[] = $label;

    $start = $month . '-01';
    $end = date('Y-m-t', strtotime($start));

    $sql = "SELECT SUM(total_cost) AS total FROM bookings 
            WHERE spot_id IN ($spotIdsStr) 
              AND booking_date BETWEEN '$start' AND '$end'
              AND status = 'active'";

    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : ['total' => 0];
    $monthlyEarnings[11 - $i] = $row['total'] ? (int)$row['total'] : 0;
}
// ✅ Fetch 5 most recent bookings for this host
$recentBookings = [];

$recent_sql = "
    SELECT 
        b.*, 
        u.name AS customer_name, 
        u.id AS customer_id,
        ps.name AS spot_name 
    FROM bookings b
    INNER JOIN users u ON b.customer_id = u.id
    INNER JOIN parking_spots ps ON b.spot_id = ps.id
    WHERE ps.user_id = ?
    ORDER BY b.booking_date DESC, b.created_at DESC
    LIMIT 5
";

$stmt = $conn->prepare($recent_sql);
$stmt->bind_param("i", $host_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recentBookings[] = $row;
}
// ✅ New Listings This Month
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');

$newListingsQuery = $conn->prepare("SELECT COUNT(*) as count FROM parking_spots WHERE user_id = ? AND created_at BETWEEN ? AND ?");
$newListingsQuery->bind_param("iss", $host_id, $startOfMonth, $endOfMonth);
$newListingsQuery->execute();
$newListingsResult = $newListingsQuery->get_result();
$newListings = $newListingsResult->fetch_assoc()['count'] ?? 0;

// ✅ Last Month's Earnings
$lastMonthStart = date('Y-m-01', strtotime('first day of last month'));
$lastMonthEnd = date('Y-m-t', strtotime('last day of last month'));

$lastMonthEarningsSql = "SELECT SUM(total_cost) as total FROM bookings 
    WHERE spot_id IN ($spotIdsStr)
    AND status = 'active'
    AND booking_date BETWEEN ? AND ?";
$stmt = $conn->prepare($lastMonthEarningsSql);
$stmt->bind_param("ss", $lastMonthStart, $lastMonthEnd);
$stmt->execute();
$result = $stmt->get_result();
$lastMonthEarnings = $result->fetch_assoc()['total'] ?? 0;

// ✅ Earnings Percentage Change
$percentChange = 0;
if ($lastMonthEarnings > 0) {
    $percentChange = (($thisMonthEarnings - $lastMonthEarnings) / $lastMonthEarnings) * 100;
}
$formattedChange = ($percentChange >= 0 ? "+" : "") . round($percentChange) . "% from last month";
$changeClass = $percentChange >= 0 ? 'positive' : 'negative';

// ✅ This Week's Bookings
$startOfWeek = date('Y-m-d', strtotime('monday this week'));
$today = date('Y-m-d');

$weeklyBookingsSql = "SELECT COUNT(*) AS count FROM bookings 
    WHERE spot_id IN ($spotIdsStr)
    AND status = 'active'
    AND booking_date BETWEEN ? AND ?";
$stmt = $conn->prepare($weeklyBookingsSql);
$stmt->bind_param("ss", $startOfWeek, $today);
$stmt->execute();
$result = $stmt->get_result();
$thisWeekBookings = $result->fetch_assoc()['count'] ?? 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Dashboard - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
.negative {
    color: #ff4f5e;
}
.positive {
    color: #2fd675;
}

        .chart-container {
    position: relative;
    height: 300px;
    width: 100%;
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
                <button class="hamburger" id="hamburger" type="button" aria-label="Toggle navigation" aria-expanded="false" aria-controls="navMenu">
                    <span class="bar"></span>
                    <span class="bar"></span>
                    <span class="bar"></span>
                </button>
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-list">
                        <li><a href="host-dashboard.php" class="nav-link active">Dashboard</a></li>
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
                    </div>
                    <a href="logout.php" class="btn btn-primary">Logout</a>

                </div>
            </div>
        </div>
    </header>

    <main class="dashboard">
        <div class="container">
            <!-- Welcome Section -->
            <section class="welcome-section">
                <div class="welcome-card glass-card">
                    <div class="welcome-content">
                        <h1>Welcome back, <span id="hostName"><?= htmlspecialchars($hostName) ?></span>!</h1>

                        <p>Here's your hosting overview</p>
                    </div>
                    <div class="welcome-actions">
                        <a href="add-spot.php" class="btn btn-primary">Add New Spot</a>
                    </div>
                </div>
            </section>

            <!-- KPI Cards -->
            <section class="kpi-section">
                <div class="kpi-grid">
                    <div class="kpi-card glass-card">
                        <div class="kpi-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                                <circle cx="12" cy="10" r="3"></circle>
                            </svg>
                        </div>
                        <div class="kpi-content">
                            <h3>Total Listings</h3>
                            <div class="kpi-value" id="totalListings"><?= $totalListings ?></div>

                           <div class="kpi-change positive"><?= ($newListings > 0 ? "+$newListings" : "0") ?> this month</div>

                        </div>
                    </div>

                    <div class="kpi-card glass-card">
                        <div class="kpi-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="kpi-content">
                            <h3>This Month's Earnings</h3>
                            <div class="kpi-value" id="monthlyEarnings"><?=intval($thisMonthEarnings) . ' ' . $currency ?></div>

                          <div class="kpi-change <?= $percentChange >= 0 ? 'positive' : 'negative' ?>">
    <?= $formattedChange ?>
</div>

                        </div>
                    </div>

                    <div class="kpi-card glass-card">
                        <div class="kpi-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3"></path>
                            </svg>
                        </div>
                        <div class="kpi-content">
                            <h3>Total Bookings</h3>
                            <div class="kpi-value" id="totalBookings"><?= $totalBookings ?></div>

                            <div class="kpi-change positive"><?= ($thisWeekBookings > 0 ? "+$thisWeekBookings" : "0") ?> this week</div>

                        </div>
                    </div>

                    <div class="kpi-card glass-card">
                        <div class="kpi-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path>
                            </svg>
                        </div>
                        <div class="kpi-content">
                            <h3>Average Rating</h3>
                            <div class="kpi-value" id="averageRating">4.8</div>
                            <div class="kpi-change neutral">From 23 reviews</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Recent Bookings -->
            <section class="recent-bookings">
                <div class="section-header">
                    <h2>Recent Bookings</h2>
                    <a href="host-bookings.php" class="btn btn-primary">View All</a>
                </div>
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
                            </tr>
                        </thead>
                        <tbody>
                            <tbody>
    <?php if (count($recentBookings) > 0): ?>
        <?php foreach ($recentBookings as $booking): 
            $duration = (strtotime($booking['end_time']) - strtotime($booking['start_time'])) / 3600;
        ?>
        <tr>
            <td>
                <div class="customer-info">
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($booking['customer_name']) ?>&background=random&size=40" alt="Customer">
                    <span><?= htmlspecialchars($booking['customer_name']) ?></span>
                </div>
            </td>
            <td><?= htmlspecialchars($booking['spot_name']) ?></td>
            <td><?= date("M d, Y", strtotime($booking['booking_date'])) ?></td>
            <td><?= round($duration) ?> hour<?= $duration > 1 ? 's' : '' ?></td>
            <td><?= $currency . number_format($booking['total_cost'], 2) ?></td>
            <td>
                <span class="status-badge <?= $booking['status'] === 'active' ? 'active' : 'cancelled' ?>">
                    <?= ucfirst($booking['status']) ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr><td colspan="6"><em>No recent bookings found</em></td></tr>
    <?php endif; ?>
</tbody>

                        </tbody>
                    </table>
                </div>
            </section>

            <section class="listings-overview">
    <div class="section-header">
        <h2>Your Listings</h2>
        <a href="host-listings.php" class="btn btn-primary" style="background: rgba(63, 63, 63, 0.8); color: white;">Manage All</a>
    </div>
    <div class="listings-grid">
        <?php if ($listings->num_rows > 0): ?>
            <?php while ($spot = $listings->fetch_assoc()):
                $availability = getAvailability($conn, $spot['id']);
            ?>
            <div class="listing-card glass-card">
                <div class="listing-image">
                    <img src="<?= htmlspecialchars($spot['photo_path'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>" alt="Listing">
                </div>
                <div class="listing-content">
                    <h3><?= htmlspecialchars($spot['name']); ?></h3>
                    <p><?= htmlspecialchars($spot['address']); ?></p>
                    <div class="listing-stats">
                        <span><?= htmlspecialchars($spot['currency'] . number_format($spot['hourly_rate'], 2)); ?>/hour</span>
                        <span>0 bookings</span>
                    </div>
                    <div class="listing-status">
                <span class="status-badge <?= ($spot['spot_status'] === 'active' ? 'active' : 'inactive') ?>">
                    <?= ucfirst(htmlspecialchars($spot['spot_status'])) ?>
                </span>
            </div>
                </div>
            </div>
            <?php endwhile; ?>
        <?php endif; ?>

        <!-- Add listing card is always shown -->
        <div class="add-listing-card glass-card">
            <div class="add-listing-content">
                <div class="add-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                </div>
                <h3>Add New Spot</h3>
                <p>Start earning by listing your parking space</p>
                <a href="add-spot.php" class="btn btn-primary">Add Spot</a>
            </div>
        </div>
    </div>
</section>


            <!-- Performance Chart -->
            <section class="performance-chart">
                <div class="chart-card glass-card">
                    <div class="chart-header">
                        <h2>Monthly Performance</h2>
                        <div class="chart-controls">
                            <select id="chartPeriod">
                                <option value="6months">Last 6 Months</option>
                                <option value="year">Last Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container" style="position: relative; height: 300px; width: 100%;">
    
    <canvas id="performanceChart"></canvas>
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
<script src="js/main.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const allLabels = <?= json_encode($monthLabels) ?>;
    const allData = <?= json_encode($monthlyEarnings) ?>;
    const currency = '<?= $currency ?>';

    const labels6mo = allLabels.slice(-6);
    const data6mo = allData.slice(-6);

    const ctx = document.getElementById('performanceChart').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels6mo,
            datasets: [{
                label: 'Earnings (' + currency + ')',
                data: data6mo,
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value + ' ' + currency;
                        }
                    }
                }
            }
        }
    });

    document.getElementById('chartPeriod').addEventListener('change', function () {
        const period = this.value;
        if (period === '6months') {
            chart.data.labels = labels6mo;
            chart.data.datasets[0].data = data6mo;
        } else {
            chart.data.labels = allLabels;
            chart.data.datasets[0].data = allData;
        }
        chart.update();
    });
</script>

   
</body>
</html>
