<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB connection
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "parking_manage";
$conn       = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$customer_id = $_SESSION['user_id'];

/**
 * Helper: send JSON response & exit
 */
function sendJson($data) {
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

// === Handle POST Actions === //
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Cancel Booking
    if (isset($_POST['booking_id'])) {
        $booking_id = intval($_POST['booking_id']);

        // Verify booking ownership
        $verifyStmt = $conn->prepare("SELECT id FROM bookings WHERE id = ? AND customer_id = ?");
        $verifyStmt->bind_param("ii", $booking_id, $customer_id);
        $verifyStmt->execute();
        $verifyResult = $verifyStmt->get_result();

        if ($verifyResult->num_rows === 0) {
            sendJson(["success" => false, "message" => "Unauthorized or invalid booking."]);
        }

        // Cancel booking
        $updateStmt = $conn->prepare("
            UPDATE bookings 
            SET payment_status = 'cancelled', status = 'cancelled' 
            WHERE id = ?");
        $updateStmt->bind_param("i", $booking_id);

        if ($updateStmt->execute()) {
            sendJson(["success" => true, "message" => "Booking cancelled successfully."]);
        } else {
            sendJson(["success" => false, "message" => "Failed to cancel booking."]);
        }
    }
// Rebook Booking
if (isset($_POST['rebook_id'])) {
    $booking_id = intval($_POST['rebook_id']);

    // Verify booking ownership & get details
    $verifyStmt = $conn->prepare("
        SELECT spot_id, booking_date, start_time, end_time
        FROM bookings 
        WHERE id = ? AND customer_id = ?");
    $verifyStmt->bind_param("ii", $booking_id, $customer_id);
    $verifyStmt->execute();
    $bookingData = $verifyStmt->get_result()->fetch_assoc();

    if (!$bookingData) {
        sendJson(["success" => false, "message" => "Unauthorized or invalid booking."]);
    }

    // Ensure rebook date is in the future
    $bookingEnd = DateTime::createFromFormat('Y-m-d H:i:s', $bookingData['booking_date'] . ' ' . $bookingData['end_time']);
    if ($bookingEnd && $bookingEnd < new DateTime()) {
        sendJson(["success" => false, "message" => "Cannot rebook a past booking."]);
    }

    // Check if the spot is already booked for that date & time (status active)
    $conflictStmt = $conn->prepare("
        SELECT id FROM bookings
        WHERE spot_id = ?
          AND booking_date = ?
          AND status = 'active'
          AND payment_status != 'cancelled'
          AND (
                (start_time < ? AND end_time > ?) OR
                (start_time >= ? AND start_time < ?)
              )
          AND id != ?");
    $conflictStmt->bind_param(
        "isssssi",
        $bookingData['spot_id'],
        $bookingData['booking_date'],
        $bookingData['end_time'], 
        $bookingData['start_time'],
        $bookingData['start_time'],
        $bookingData['end_time'],
        $booking_id
    );
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result();

    if ($conflictResult->num_rows > 0) {
        sendJson(["success" => false, "message" => "Unable to rebook. Spot is booked."]);
    }

    // If available, update booking
    $rebookStmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'pending', status = 'active' 
        WHERE id = ?");
    $rebookStmt->bind_param("i", $booking_id);

    if ($rebookStmt->execute()) {
        sendJson(["success" => true, "message" => "Booking rebooked successfully."]);
    } else {
        sendJson(["success" => false, "message" => "Failed to rebook."]);
    }
}

    // No recognized action
    sendJson(["success" => false, "message" => "Invalid request."]);
}

// === Fetch User's Bookings === //
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
$stmt->bind_param("i", $customer_id);
$stmt->execute();
$result = $stmt->get_result();

$upcoming = $past = $cancelled = [];
$now = new DateTime();

while ($row = $result->fetch_assoc()) {
    $bookingEndStr = trim($row['booking_date']) . ' ' . trim($row['end_time']);
    $bookingEnd    = DateTime::createFromFormat('Y-m-d H:i:s', $bookingEndStr);

    $isCancelled = strtolower($row['payment_status']) === 'cancelled' 
                || strtolower($row['status']) === 'cancelled';

    if ($isCancelled) {
        $cancelled[] = $row;
    } elseif ($bookingEnd && $bookingEnd < $now) {
        $past[] = $row;
    } else {
        $upcoming[] = $row;
    }
}

// === Render Booking Card === //
function renderBookingCard($booking, $context = '') {
    $date     = date("M j, Y", strtotime($booking['booking_date']));
    $start    = date("g:i A", strtotime($booking['start_time']));
    $end      = date("g:i A", strtotime($booking['end_time']));
    $cost     = number_format((float)$booking['total_cost'], 2);
    $currency = htmlspecialchars($booking['currency']);
    $spotName = htmlspecialchars($booking['spot_name']);
    $address  = htmlspecialchars($booking['address']);
    $vehicle  = htmlspecialchars($booking['vehicle_types']);

    $statusClass = strtolower($booking['payment_status']) === 'completed' ? 'completed' :
                   (strtolower($booking['payment_status']) === 'cancelled' ? 'cancelled' : 'confirmed');

    $actionButton = '';
    if ($context === 'cancelled') {
        $actionButton = "<button class='btn btn-warning btn-sm' onclick=\"rebookBooking('{$booking['id']}')\">Rebook</button>";
    } elseif ($context === 'upcoming') {
        $actionButton = "<button class='btn btn-danger btn-sm' onclick=\"cancelBooking('{$booking['id']}')\">Cancel</button>";
    } elseif ($context === 'past') {
        $actionButton = "<a class='btn btn-primary btn-sm' href='spot-details.php?id={$booking['spot_id']}'>Rebook</a>";
    }

    return "
    <div class='booking-card glass-card'>
        <div class='booking-header'>
            <h3>$spotName</h3>
            <span class='booking-status $statusClass'>" . ucfirst($booking['payment_status']) . "</span>
        </div>
        <div class='booking-details'>
            <div class='detail-item'><span class='label'>Date:</span><span class='value'>$date</span></div>
            <div class='detail-item'><span class='label'>Time:</span><span class='value'>$start - $end</span></div>
            <div class='detail-item'><span class='label'>Location:</span><span class='value'>$address</span></div>
            <div class='detail-item'><span class='label'>Vehicle:</span><span class='value'>$vehicle</span></div>
            <div class='detail-item'><span class='label'>Total Cost:</span><span class='value'>{$currency} $cost</span></div>
        </div>
        <div class='booking-actions'>
            <button class='btn btn-ghost btn-sm' onclick=\"viewBookingDetails('{$booking['id']}')\">View Details</button>
            $actionButton
        </div>
    </div>";
}
?>




<!DOCTYPE html>  
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Bookings - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
        /* Modal Background */
.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    display: none;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    justify-content: center;
    align-items: center;
    font-family: 'Poppins', sans-serif;
}

/* Modal Content */
.modal-content {
    background: #ffffff;
    border-radius: 20px;
    max-width: 420px;
    width: 100%;
    padding: 28px 24px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    position: relative;
}

/* Modal Header */
.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.modal-header h3 {
    font-size: 22px;
    font-weight: 600;
    margin: 0;
    color: #111;
}

.modal-close {
    font-size: 20px;
    background: none;
    border: none;
    cursor: pointer;
    color: #444;
}

/* Section Titles */
.booking-details-content h4 {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 10px;
    color: #333;
}

/* Info Rows */
.booking-details-content p {
    font-size: 15px;
    color: #333;
    margin: 6px 0;
    display: flex;
    justify-content: space-between;
    line-height: 1.5;
}

.booking-details-content p strong {
    font-weight: 500;
    color: #666;
    width: 140px;
    display: inline-block;
}

/* Host Info */
.host-info {
    margin-top: 22px;
    border-top: 1px solid #eee;
    padding-top: 16px;
}

.host-info .host-details {
    font-size: 14px;
    color: #222;
    font-weight: 500;
}

.host-info .host-since {
    font-size: 13px;
    color: #888;
    margin-top: 2px;
}
.modal-title {
    font-size: 1.5rem;
    font-weight: 700;
    margin-bottom: 1rem;
}

.section-title {
    font-weight: 600;
    font-size: 1.1rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
    border-bottom: 1px solid #ddd;
    padding-bottom: 0.3rem;
}

.detail-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.5rem;
}

.detail-row .label {
    font-weight: 500;
    color: #444;
}

.detail-row .value {
    font-weight: 400;
    color: #222;
    text-align: right;
}

.host-info {
    margin-top: 1rem;
    padding-top: 0.5rem;
}

.host-name {
    font-weight: 600;
    font-size: 1rem;
}

.host-since {
    font-size: 0.9rem;
    color: #666;
    
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
                        <li><a href="customer-dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="search.php" class="nav-link">Search</a></li>
                        <li><a href="customer-bookings.php" class="nav-link active">Bookings</a></li>
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
            </div>
        </div>
    </header>

    <main class="bookings-page">
        <div class="container">
            <div class="page-header">
                <h1 style="text-align:center;">My Bookings</h1>
                <p style="text-align:center;">Manage your parking reservations</p>
            </div>

            <div class="bookings-tabs">
                <button class="tab-btn active" onclick="showTab('upcoming', event)">Upcoming</button>
<button class="tab-btn" onclick="showTab('past', event)">Past</button>
<button class="tab-btn" onclick="showTab('cancelled', event)">Cancelled</button>

            </div>

            <div class="bookings-content">
                <!-- Upcoming Tab -->
<div class="tab-content active" id="upcoming">
    <div class="bookings-grid">
        <?php echo empty($upcoming) 
            ? "<p style='text-align:center;color:#888;'>No upcoming bookings found.</p>" 
            : implode('', array_map(fn($b) => renderBookingCard($b, 'upcoming'), $upcoming)); ?>
    </div>
</div>

<!-- Past Tab -->
<div class="tab-content" id="past">
    <div class="bookings-grid">
        <?php echo empty($past) 
            ? "<p style='text-align:center;color:#888;'>No past bookings.</p>" 
            : implode('', array_map(fn($b) => renderBookingCard($b, 'past'), $past)); ?>
    </div>
</div>

<!-- Cancelled Tab -->
<div class="tab-content" id="cancelled">
    <div class="bookings-grid">
        <?php echo empty($cancelled) 
            ? "<p style='text-align:center;color:#888;'>No cancelled bookings.</p>" 
            : implode('', array_map(fn($b) => renderBookingCard($b, 'cancelled'), $cancelled)); ?>
    </div>
</div>

            </div>
        </div>
    </main>

    <!-- Booking Modals -->
    <div class="modal" id="bookingDetailsModal" style="display:none; justify-content:center; align-items:center;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Booking Details</h3>
            <button class="modal-close" onclick="closeBookingDetailsModal()">×</button>
        </div>
        <div class="booking-details-content"></div>
    </div>
</div>

<div class="modal" id="cancelBookingModal" style="display:none; justify-content:center; align-items:center;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Cancel Booking</h3>
            <button class="modal-close" onclick="closeCancelBookingModal()">×</button>
        </div>
        <div class="cancel-content">
            <p>Are you sure you want to cancel this booking?</p>
            <div class="cancel-details">
                <p><strong>Booking ID:</strong> <span id="cancelBookingId"></span></p>
                <p><strong>Refund Amount:</strong> <span id="refundAmount"></span></p>
            </div>
        </div>
        <div class="modal-actions">
            <button class="btn btn-ghost" onclick="closeCancelBookingModal()">Keep Booking</button>
            <button class="btn btn-danger" onclick="confirmCancellation()">Cancel Booking</button>
        </div>
    </div>
</div>


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
    
  const bookingsData = {
    upcoming: <?php echo json_encode($upcoming); ?>,
    past: <?php echo json_encode($past); ?>,
    cancelled: <?php echo json_encode($cancelled); ?>
  };
</script>

<script>
    // Assume bookingsData is available globally as shown previously:
    // const bookingsData = { upcoming: [...], past: [...], cancelled: [...] };

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

    // Helper to find booking by ID in all categories
    function findBookingById(id) {
        for (const category of Object.values(bookingsData)) {
            const booking = category.find(b => b.id == id);
            if (booking) return booking;
        }
        return null;
    }

    // View Booking Details (receives booking ID)
function viewBookingDetails(bookingId) {
    const booking = findBookingById(bookingId);
    if (!booking) return alert('Booking not found.');

    const modal = document.getElementById('bookingDetailsModal');
    const content = modal.querySelector('.booking-details-content');

    const dateOptions = { month: 'short', day: 'numeric' };
    const timeOptions = { hour: 'numeric', minute: '2-digit', hour12: true };
    const startDate = new Date(`${booking.booking_date}T${booking.start_time}`);
    const endDate = new Date(`${booking.booking_date}T${booking.end_time}`);

    const dateRange = `${startDate.toLocaleDateString(undefined, dateOptions)} - ${endDate.toLocaleDateString(undefined, dateOptions)}`;
    const timeRange = `${startDate.toLocaleTimeString(undefined, timeOptions)} - ${endDate.toLocaleTimeString(undefined, timeOptions)}`;

    const hostSinceYear = booking.host_created_at ? new Date(booking.host_created_at).getFullYear() : '';
    const hostName = booking.host_name || 'N/A';

    content.innerHTML = `
        <div class="section-title">Booking Information</div>
        <div class="detail-row"><span class="label">Booking ID:</span> <span class="value">${booking.id}</span></div>
        <div class="detail-row"><span class="label">Spot Name:</span> <span class="value">${booking.spot_name}</span></div>
        <div class="detail-row"><span class="label">Date & Time:</span> <span class="value">${dateRange} | ${timeRange}</span></div>
        <div class="detail-row"><span class="label">Location:</span> <span class="value">${booking.address}</span></div>
        <div class="detail-row"><span class="label">Vehicle:</span> <span class="value">${booking.vehicle_types}</span></div>
        <div class="detail-row"><span class="label">Status:</span> <span class="value">${booking.payment_status.charAt(0).toUpperCase() + booking.payment_status.slice(1)}</span></div>
        <div class="detail-row"><span class="label">Total Cost:</span> <span class="value">${booking.currency} ${parseFloat(booking.total_cost).toFixed(2)}</span></div>

        <div class="section-title">Host Information</div>
        <div class="host-info">
            <div class="host-name">${hostName}</div>
            <div class="host-since">Host since ${hostSinceYear}</div>
        </div>
    `;

    modal.style.display = 'flex';
}


    function closeBookingDetailsModal() {
        document.getElementById('bookingDetailsModal').style.display = 'none';
    }

    // Open cancel modal (receives booking ID)
    function cancelBooking(bookingId) {
        const booking = findBookingById(bookingId);
        if (!booking) return alert('Booking not found.');

        document.getElementById('cancelBookingId').textContent = booking.id;
        document.getElementById('refundAmount').textContent = `${booking.currency} ${parseFloat(booking.total_cost).toFixed(2)}`;
        document.getElementById('cancelBookingModal').style.display = 'flex';
    }

    function closeCancelBookingModal() {
        document.getElementById('cancelBookingModal').style.display = 'none';
    }

   function confirmCancellation() {
    const bookingId = document.getElementById('cancelBookingId').textContent;

    fetch('customer-bookings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'booking_id=' + encodeURIComponent(bookingId)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) {
            closeCancelBookingModal();
            location.reload(); // Refresh to update the booking list
        }
    })
    .catch(error => {
        alert('Failed to cancel booking.');
        console.error(error);
    });
}


    // Close modals on outside click
    window.addEventListener('click', function (e) {
        ['bookingDetailsModal', 'cancelBookingModal'].forEach(modalId => {
            const modal = document.getElementById(modalId);
            if (e.target === modal) {
                modal.style.display = 'flex';
            }
        });
    });
    function rebookBooking(bookingId) {
    if (!confirm("Do you want to rebook this reservation?")) return;

    fetch('customer-bookings.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'rebook_id=' + encodeURIComponent(bookingId)
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        if (data.success) location.reload();
    })
    .catch(error => {
        console.error('Rebooking failed', error);
        alert("Something went wrong while rebooking.");
    });
}

</script>


</body>
</html>


