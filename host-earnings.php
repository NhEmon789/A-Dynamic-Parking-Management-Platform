<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$host_id = $_SESSION['user_id'];
$conn = new mysqli("localhost", "root", "", "parking_manage");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// ✅ Spot IDs
$spotIds = [];
$spotQuery = $conn->query("SELECT id FROM parking_spots WHERE user_id = $host_id");
while ($row = $spotQuery->fetch_assoc()) {
    $spotIds[] = $row['id'];
}
$spotIdsStr = count($spotIds) > 0 ? implode(',', $spotIds) : '0';

// ✅ Currency
$currency = 'TK';
$currencyQuery = $conn->query("SELECT DISTINCT currency FROM parking_spots WHERE user_id = $host_id LIMIT 1");
if ($currencyRow = $currencyQuery->fetch_assoc()) {
    $currency = $currencyRow['currency'];
}

// ✅ Dates
$weekStart = date('Y-m-d', strtotime('monday this week'));
$monthStart = date('Y-m-01');
$lastWeekStart = date('Y-m-d', strtotime('monday last week'));
$lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
$lastMonthStart = date('Y-m-01', strtotime('-1 month'));
$lastMonthEnd = date('Y-m-t', strtotime('-1 month'));

// ✅ Earnings Function
function getEarnings($conn, $spotIdsStr, $startDate = null, $endDate = null) {
    $dateCond = '';
    if ($startDate && $endDate) {
        $dateCond = "AND booking_date BETWEEN '$startDate' AND '$endDate'";
    } elseif ($startDate) {
        $dateCond = "AND booking_date >= '$startDate'";
    }

    $sql = "SELECT SUM(total_cost) AS total, COUNT(*) AS count FROM bookings
            WHERE spot_id IN ($spotIdsStr) AND status = 'active' $dateCond";
    $result = $conn->query($sql);
    return $result->fetch_assoc();
}

// ✅ Earnings Data
$weekData = getEarnings($conn, $spotIdsStr, $weekStart);
$monthData = getEarnings($conn, $spotIdsStr, $monthStart);
$totalData = getEarnings($conn, $spotIdsStr);
$prevWeekData = getEarnings($conn, $spotIdsStr, $lastWeekStart, $lastWeekEnd);
$prevMonthData = getEarnings($conn, $spotIdsStr, $lastMonthStart, $lastMonthEnd);

// ✅ Averages and Changes
$average = ($totalData['count'] > 0) ? intval($totalData['total'] / $totalData['count']) : 0;
function calculateChange($current, $previous) {
    if ($previous > 0) {
        return round((($current - $previous) / $previous) * 100);
    }
    return $current > 0 ? 100 : 0;
}
$weekChange = calculateChange($weekData['total'], $prevWeekData['total']);
$monthChange = calculateChange($monthData['total'], $prevMonthData['total']);
$avgChange = 5; // Set your own logic if needed

// ✅ New Listings This Month
$startOfMonth = date('Y-m-01');
$endOfMonth = date('Y-m-t');
$newListingsQuery = $conn->prepare("SELECT COUNT(*) as count FROM parking_spots WHERE user_id = ? AND created_at BETWEEN ? AND ?");
$newListingsQuery->bind_param("iss", $host_id, $startOfMonth, $endOfMonth);
$newListingsQuery->execute();
$newListingsResult = $newListingsQuery->get_result();
$newListings = $newListingsResult->fetch_assoc()['count'] ?? 0;

// ✅ Bookings This Week
$today = date('Y-m-d');
$weeklyBookingsSql = "SELECT COUNT(*) AS count FROM bookings 
    WHERE spot_id IN ($spotIdsStr) AND status = 'active' AND booking_date BETWEEN ? AND ?";
$stmt = $conn->prepare($weeklyBookingsSql);
$stmt->bind_param("ss", $weekStart, $today);
$stmt->execute();
$result = $stmt->get_result();
$thisWeekBookings = $result->fetch_assoc()['count'] ?? 0;

// ✅ Recent Transactions
$transactions = [];
$transactionQuery = $conn->query("
    SELECT b.*, u.name AS customer_name, s.name AS spot_name
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    JOIN parking_spots s ON b.spot_id = s.id
    WHERE b.spot_id IN ($spotIdsStr)
    ORDER BY b.booking_date DESC
    LIMIT 5
");

if (!$transactionQuery) {
    die("Transaction query failed: " . $conn->error);
}
while ($row = $transactionQuery->fetch_assoc()) {
    if (!empty($row['start_time']) && !empty($row['end_time'])) {
        $start = new DateTime($row['start_time']);
        $end = new DateTime($row['end_time']);
        $interval = $start->diff($end);
        $duration = $interval->days * 24 + $interval->h;
        $row['duration_hours'] = $duration;
    } else {
        $row['duration_hours'] = 0;
    }
    $transactions[] = $row;
}

// ✅ Chart Data (12 months)
$monthlyEarnings = [];
$monthLabels = [];

for ($i = 11; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $label = date('M Y', strtotime("-$i months"));
    $monthLabels[] = $label;

    $start = $month . "-01";
    $end = date('Y-m-t', strtotime($start));

    $query = "SELECT SUM(total_cost) as total 
              FROM bookings 
              WHERE spot_id IN ($spotIdsStr) 
              AND booking_date BETWEEN '$start' AND '$end'
              AND status = 'active'";
    $result = $conn->query($query);
    $row = $result->fetch_assoc();
    $monthlyEarnings[] = intval($row['total'] ?? 0);
}

$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Host Earnings - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        /* By default: hide hamburger, show nav menu */
#hamburger {
  display: none;
  cursor: pointer;
}

#navMenu {
  display: flex;
  gap: 1rem;
}

/* Mobile view: show hamburger, hide menu until active */
@media (max-width: 768px) {
  #hamburger {
    display: block;
  }

  #navMenu {
    display: none;
    flex-direction: column;
    background: white; /* or your theme color */
    position: absolute;
    top: 60px; /* adjust for header height */
    right: 0;
    padding: 1rem;
  }

  #navMenu.active {
    display: flex;
  }
}

        .negative {
    color: #ff4f5e;
}
.positive {
    color: #2fd675;
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
                        <li><a href="host-earnings.php" class="nav-link active">Earnings</a></li>
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
        </div>
    </header>

    <main class="earnings-page" style="padding-top: 120px;">
        <div class="container">
            <div class="page-header">
                <h1>My Earnings</h1>
                <p>Track your parking spot income and performance</p>
            </div>

            <!-- Earnings Summary -->
            <section class="earnings-summary">
                <div class="earnings-grid">
                    <div class="earning-card glass-card">
                        <div class="earning-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <line x1="12" y1="1" x2="12" y2="23"></line>
                                <path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="earning-content">
                            <h3>This Week</h3>
                            <div class="earning-value"><?= intval($weekData['total']) . ' ' . $currency ?></div>


                            <div class="earning-change positive">+12% from last week</div>
                        </div>
                    </div>

                    <div class="earning-card glass-card">
                        <div class="earning-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="16" y1="2" x2="16" y2="6"></line>
                                <line x1="8" y1="2" x2="8" y2="6"></line>
                                <line x1="3" y1="10" x2="21" y2="10"></line>
                            </svg>
                        </div>
                        <div class="earning-content">
                            <h3>This Month</h3>
                            <div class="earning-value"><?= intval($monthData['total']) . ' ' . $currency ?></div>



                            <div class="earning-change <?= $weekChange >= 0 ? 'positive' : 'negative' ?>">
    <?= ($weekChange >= 0 ? '+' : '') . $weekChange ?>% from last week
</div>

                        </div>
                    </div>

                    <div class="earning-card glass-card">
                        <div class="earning-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path>
                            </svg>
                        </div>
                        <div class="earning-content">
                            <h3>Total Earnings</h3>
                            <div class="earning-value"><?= intval($totalData['total']) . ' ' . $currency ?></div>



                            <div class="earning-change neutral">Since joining</div>
                        </div>
                    </div>

                    <div class="earning-card glass-card">
                        <div class="earning-icon">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="m22 21-3-3m0 0a5 5 0 1 0-7-7 5 5 0 0 0 7 7z"></path>
                            </svg>
                        </div>
                        <div class="earning-content">
                            <h3>Average per Booking</h3>
                            <div class="earning-value"><?= $average . ' ' . $currency ?></div>



                            <div class="earning-change <?= $avgChange >= 0 ? 'positive' : 'negative' ?>">
    <?= ($avgChange >= 0 ? '+' : '') . $avgChange ?>% this month
</div>

                        </div>
                    </div>
                </div>
            </section>

            <!-- Earnings Chart -->
            <section class="earnings-chart">
                <div class="chart-card glass-card">
                    <div class="chart-header">
                        <h2>Monthly Earnings Trend</h2>
                        <div class="chart-controls">
                            <select id="chartPeriod">
                                <option value="6months">Last 6 Months</option>
                                <option value="year">Last Year</option>
                            </select>
                        </div>
                    </div>
                    <div class="chart-container">
                        <canvas id="earningsChart" width="1000" height="400"></canvas>

                    </div>
                </div>
            </section>

            <!-- Recent Transactions -->
            <section class="recent-transactions">
                <div class="transactions-card glass-card">
                    <div class="transactions-header">
                        <h2>Recent Transactions</h2>
                        <button class="btn btn-primary">Download Report</button>
                    </div>
                    <div class="transactions-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Spot</th>
                                    <th>Duration</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($transactions) > 0): ?>
    <?php foreach ($transactions as $tx): ?>
        <tr>
            <td><?= date('M d, Y', strtotime($tx['booking_date'])) ?></td>
            <td><?= htmlspecialchars($tx['customer_name']) ?></td>
            <td><?= htmlspecialchars($tx['spot_name']) ?></td>
            <td><?= intval($tx['duration_hours']) ?> hours</td>
            <td><?= intval($tx['total_cost']) . ' ' . $currency ?></td>
            <td>
    <span class="status-badge <?= $tx['status'] === 'active' ? 'active-status' : 'cancelled-status' ?>">
        <?= ucfirst($tx['status']) ?>
    </span>
</td>


<style>
#earningsChart {
    width: 100% !important;
    height: auto !important;
    display: block;
}
.chart-container {
    width: 100%;
    max-width: 100%;
    height: auto;
}

.status-badge {
    padding: 4px 10px;
    border-radius: 20px;
    font-weight: 500;
    display: inline-block;
    font-size: 14px;
    text-transform: capitalize;
}

.active-status {
    background-color: #d3f5e1;
    color: #2fd675;
    border: 1px solid #d3f5e1;
}

.cancelled-status {
    background-color: #fcd7da;
    color: #ff4f5e;
    border: 1px solid #fcd7da;
}
</style>
        </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr>
        <td colspan="6">No recent transactions found.</td>
    </tr>
<?php endif; ?>

                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Payout Information -->
            <section class="payout-info">
                <div class="payout-card glass-card">
                    <div class="payout-header">
                        <h2>Payout Information</h2>
                        <button class="btn btn-ghost">Update Payment Method</button>
                    </div>
                    <div class="payout-details">
                        <div class="payout-item">
                            <span class="label">Next Payout:</span>
                            <span class="value">$245.00 on Dec 20, 2024</span>
                        </div>
                        <div class="payout-item">
                            <span class="label">Payment Method:</span>
                            <span class="value">Bank Account ****1234</span>
                        </div>
                        <div class="payout-item">
                            <span class="label">Payout Schedule:</span>
                            <span class="value">Weekly (Every Friday)</span>
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
<script src="js/main.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    const chartLabels = <?= json_encode($monthLabels) ?>;
    const chartData = <?= json_encode($monthlyEarnings) ?>;
    const currencySymbol = "<?= $currency ?>";
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('earningsChart').getContext('2d');

    // Preloaded data
    const fullLabels = <?= json_encode($monthLabels) ?>;
    const fullData = <?= json_encode($monthlyEarnings) ?>;

    // Slice for 6 months
    const labels6mo = fullLabels.slice(-6);
    const data6mo = fullData.slice(-6);

    // Initial chart (6 months default)
    let chart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels6mo,
            datasets: [{
                label: 'Earnings (<?= $currency ?>)',
                data: data6mo,
                borderWidth: 2,
                fill: true,
                tension: 0.3
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function (value) {
                            return value + ' <?= $currency ?>';
                        }
                    }
                }
            }
        }
    });

    // Chart period toggle
    document.getElementById('chartPeriod').addEventListener('change', function () {
        const period = this.value;

        if (period === '6months') {
            chart.data.labels = labels6mo;
            chart.data.datasets[0].data = data6mo;
        } else {
            chart.data.labels = fullLabels;
            chart.data.datasets[0].data = fullData;
        }

        chart.update();
    });
});
</script>



</body>
</html>
