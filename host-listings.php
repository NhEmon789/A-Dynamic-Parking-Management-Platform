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

// Handle AJAX request to update spot_status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['spot_id'], $data['status'])) {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
        exit();
    }

    $spot_id = (int)$data['spot_id'];
    $status = $data['status'] === 'active' ? 'active' : 'inactive'; // sanitize

    // Verify spot ownership
    $stmt = $conn->prepare("SELECT id FROM parking_spots WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $spot_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        $stmt->close();
        $conn->close();
        exit();
    }
    $stmt->close();

    // Update spot_status
    $update = $conn->prepare("UPDATE parking_spots SET spot_status = ? WHERE id = ?");
    $update->bind_param("si", $status, $spot_id);

    if ($update->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update']);
    }
    $update->close();
    $conn->close();
    exit();
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];

    // Verify spot belongs to the logged-in user
    $stmt = $conn->prepare("SELECT id FROM parking_spots WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Delete related availability first
        $delAvail = $conn->prepare("DELETE FROM spot_availability WHERE spot_id = ?");
        $delAvail->bind_param("i", $delete_id);
        $delAvail->execute();
        $delAvail->close();

        // Delete the parking spot
        $delSpot = $conn->prepare("DELETE FROM parking_spots WHERE id = ?");
        $delSpot->bind_param("i", $delete_id);
        $delSpot->execute();
        $delSpot->close();

        $message = "Spot deleted successfully.";
    } else {
        $message = "Spot not found or you don't have permission to delete it.";
    }
}

// Fetch user's parking spots
$sql = "SELECT * FROM parking_spots WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// Helper function to get availability for a spot
function getAvailability($conn, $spot_id) {
    $avail_sql = "SELECT day_of_week, start_time, end_time FROM spot_availability WHERE spot_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $stmt = $conn->prepare($avail_sql);
    $stmt->bind_param("i", $spot_id);
    $stmt->execute();
    $avail_result = $stmt->get_result();

    $availability = [];
    while ($row = $avail_result->fetch_assoc()) {
        $availability[] = $row;
    }
    return $availability;
}

// Format availability for display
function formatAvailability($availability) {
    if (empty($availability)) return "<em>No availability set</em>";
    $formatted = "<ul class='availability-list'>";
    foreach ($availability as $a) {
        $start = date("g:i A", strtotime($a['start_time']));
        $end = date("g:i A", strtotime($a['end_time']));
        $formatted .= "<li>{$a['day_of_week']}: {$start} - {$end}</li>";
    }
    $formatted .= "</ul>";
    return $formatted;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>My Listings - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/animations.css" />
    <style>
        .availability-list {
            margin: 8px 0 0;
            padding-left: 18px;
            font-size: 0.9rem;
            color: #444;
        }
        .availability-list li {
            line-height: 1.3;
        }
        .message {
            margin: 1rem 0;
            padding: 0.75rem 1rem;
            border-radius: 5px;
            background-color: #d4edda;
            color: #155724;
            font-weight: 600;
        }
        .btn-danger {
            background-color: #e74c3c;
            color: white;
            border: none;
            font-family:arial;
            padding: 10px 24px 4px 24px;
            font-size: 0.9rem;
            border-radius: 25px;
            cursor: pointer;
            transition: background-color 0.3s ease, box-shadow 0.3s ease, transform 0.2s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-danger:hover,
        .btn-danger:focus {
            background-color: #c0392b;
            box-shadow: 0 6px 12px rgba(192, 57, 43, 0.4);
            transform: translateY(-1px);
            color: #fff;
            outline: none;
            text-decoration: none;
        }
        .availability-toggle {
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            user-select: none;
            margin-top: 12px;
        }
        .toggle-switch {
            width: 40px;
            height: 20px;
            border-radius: 12px;
            background-color: #ccc;
            position: relative;
            transition: background-color 0.3s ease;
            margin-right: 8px;
        }
        .toggle-switch.active {
            background-color: #27ae60;
        }
        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            transition: transform 0.3s ease;
        }
        .toggle-switch.active::before {
            transform: translateX(20px);
        }
        .toggle-label {
            font-weight: 600;
            color: #333;
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
                    <span class="bar"></span><span class="bar"></span><span class="bar"></span>
                </button>
                <nav class="nav-menu" id="navMenu">
                    <ul class="nav-list">
                        <li><a href="host-dashboard.php" class="nav-link">Dashboard</a></li>
                        <li><a href="host-listings.php" class="nav-link active">Listings</a></li>
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

    <main class="listings-page">
        <div class="container">
            <div class="listings-header">
                <h1>My Listings</h1>
                <div class="listing-actions">
                    <a href="add-spot.php" class="btn btn-primary">Add New Spot</a>
                </div>
            </div>

            <?php if (isset($message)): ?>
                <div class="message"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="listings-grid">
                <?php if ($result->num_rows > 0): ?>
                    <?php while ($row = $result->fetch_assoc()):
                        $availability = getAvailability($conn, $row['id']);
                        $availability_html = formatAvailability($availability);
                        // Use spot_status for toggle active state
                        $isActive = ($row['spot_status'] === 'active');
                    ?>
                    <div class="listing-card glass-card">
                        <div class="listing-image">
                            <img src="<?php echo htmlspecialchars($row['photo_path'] ?: 'https://via.placeholder.com/400x300?text=No+Image'); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" />
                        </div>
                        <div class="listing-content">
                            <h3><?php echo htmlspecialchars($row['name']); ?></h3>
                            <p><?php echo htmlspecialchars($row['address']); ?></p>
                            <div class="listing-stats">
                                <span><?php echo htmlspecialchars($row['currency'] . number_format($row['hourly_rate'], 2)); ?>/hour</span>
                                <?php
                                // Count confirmed bookings for this spot
                                $spot_id = $row['id'];
                                $booking_stmt = $conn->prepare("SELECT COUNT(*) as booking_count FROM bookings WHERE spot_id = ?");
                                $booking_stmt->bind_param("i", $spot_id);
                                $booking_stmt->execute();
                                $booking_result = $booking_stmt->get_result();
                                $booking_data = $booking_result->fetch_assoc();
                                $booking_count = $booking_data['booking_count'];
                                $booking_stmt->close();
                                ?>
                                <span><?php echo $booking_count; ?> booking<?php echo $booking_count != 1 ? 's' : ''; ?></span>
                            </div>

                            <div class="availability-toggle" data-spot-id="<?php echo $row['id']; ?>" onclick="toggleAvailability(this)">
                                <div class="toggle-switch <?php echo $isActive ? 'active' : ''; ?>"></div>
                                <span class="toggle-label"><?php echo $isActive ? 'Available' : 'Unavailable'; ?></span>
                            </div>

                            <div class="availability-times">
                                <?php echo $availability_html; ?>
                            </div>

                            <div class="listing-actions">
                                <button class="btn btn-ghost btn-sm" onclick="alert('Edit feature coming soon!')">Edit</button>
                                <a href="?delete_id=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this spot?');">Delete</a>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>You have no listings yet. <a href="add-spot.php">Add a new spot</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

<script src="js/main.js"></script>
<script>
function toggleAvailability(element) {
    const toggleSwitch = element.querySelector('.toggle-switch');
    const label = element.querySelector('.toggle-label');
    const isActive = toggleSwitch.classList.contains('active');
    const newStatus = isActive ? 'inactive' : 'active'; // toggle state

    // Optimistically toggle UI
    toggleSwitch.classList.toggle('active');
    label.textContent = newStatus === 'active' ? 'Available' : 'Unavailable';

    // Get spot ID from data attribute
    const spotId = element.getAttribute('data-spot-id');

    // Send AJAX request to update spot_status in DB
    fetch('', {  // POST to same PHP file
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ spot_id: spotId, status: newStatus })
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            alert('Failed to update spot status.');
            // Revert UI toggle on failure
            toggleSwitch.classList.toggle('active');
            label.textContent = isActive ? 'Available' : 'Unavailable';
        }
    })
    .catch(() => {
        alert('Error updating spot status.');
        toggleSwitch.classList.toggle('active');
        label.textContent = isActive ? 'Available' : 'Unavailable';
    });
}
</script>
</body>
</html>

<?php $conn->close(); ?>
