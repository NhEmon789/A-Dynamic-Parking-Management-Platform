<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// DB Connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_manage";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <style>
        /* [Same styles as before] */
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: "Segoe UI", sans-serif; }
        body { display: flex; min-height: 100vh; background-color: #f4f6f8; }
        .sidebar { width: 220px; background-color: #2c3e50; color: white; padding-top: 20px; }
        .sidebar h2 { text-align: center; margin-bottom: 30px; }
        .sidebar a { display: block; padding: 15px 20px; color: white; text-decoration: none; transition: background 0.3s; }
        .sidebar a:hover { background-color: #34495e; }
        .main { flex-grow: 1; padding: 20px; }
        .header { background-color: #fff; padding: 15px 20px; border-bottom: 1px solid #ddd; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
        .header h1 { font-size: 20px; }
        .content { padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.05); overflow-x: auto; }
        .logout-btn { padding: 8px 12px; background: #e74c3c; color: white; text-decoration: none; border-radius: 4px; }
        .logout-btn:hover { background: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
        table, th, td { border: 1px solid #ccc; }
        th, td { padding: 10px; text-align: left; }
        th { background-color: #ecf0f1; }
        h3 { margin-top: 30px; margin-bottom: 10px; }
        
    </style>
</head>
<body>

<div class="sidebar">
    <h2>Admin</h2>
    <a href="#">Dashboard</a>
    <a href="#">Users</a>
    <a href="#">Parking Spots</a>
    <a href="#">Bookings</a>
    <a href="#">Reports</a>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="main">
    <div class="header">
        <h1>Welcome to Admin Dashboard</h1>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>

    <div class="content">
        <h2>Database Overview</h2>

        <!-- USERS -->
        <h3>Users</h3>
        <table>
            <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Vehicle</th><th>License</th></tr>
            <?php
            $users = $conn->query("SELECT * FROM users");
            while ($row = $users->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['email']}</td>
                        <td>{$row['phone']}</td>
                        <td>{$row['role']}</td>
                        <td>{$row['vehicle_type']}</td>
                        <td>{$row['license_plate']}</td>
                    </tr>";
            }
            ?>
        </table>

        <!-- PARKING SPOTS -->
        <h3>Parking Spots</h3>
        <table>
            <tr><th>ID</th><th>User ID</th><th>Name</th><th>Address</th><th>Rate</th><th>Types</th></tr>
            <?php
            $spots = $conn->query("SELECT * FROM parking_spots");
            while ($row = $spots->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['user_id']}</td>
                        <td>{$row['name']}</td>
                        <td>{$row['address']}</td>
                        <td>{$row['hourly_rate']} {$row['currency']}</td>
                        <td>{$row['vehicle_types']}</td>
                    </tr>";
            }
            ?>
        </table>

        <!-- BOOKINGS -->
        <h3>Bookings</h3>
        <table>
            <tr><th>ID</th><th>Spot ID</th><th>Customer ID</th><th>Date</th><th>Time</th><th>Cost</th><th>Status</th></tr>
            <?php
            $bookings = $conn->query("SELECT * FROM bookings");
            while ($row = $bookings->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['spot_id']}</td>
                        <td>{$row['customer_id']}</td>
                        <td>{$row['booking_date']}</td>
                        <td>{$row['start_time']} - {$row['end_time']}</td>
                        <td>{$row['total_cost']}</td>
                        <td>{$row['status']}</td>
                    </tr>";
            }
            ?>
        </table>

        <!-- PAYMENTS -->
        <h3>Payments</h3>
        <table>
            <tr><th>ID</th><th>Booking ID</th><th>Amount</th><th>Status</th><th>Method</th><th>Paid At</th></tr>
            <?php
            $payments = $conn->query("SELECT * FROM payments");
            while ($row = $payments->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['booking_id']}</td>
                        <td>{$row['amount']}</td>
                        <td>{$row['payment_status']}</td>
                        <td>{$row['payment_method']}</td>
                        <td>{$row['paid_at']}</td>
                    </tr>";
            }
            ?>
        </table>

        <!-- SPOT AVAILABILITY -->
        <h3>Spot Availability</h3>
        <table>
            <tr><th>ID</th><th>Spot ID</th><th>Day</th><th>Time</th></tr>
            <?php
            $avail = $conn->query("SELECT * FROM spot_availability");
            while ($row = $avail->fetch_assoc()) {
                echo "<tr>
                        <td>{$row['id']}</td>
                        <td>{$row['spot_id']}</td>
                        <td>{$row['day_of_week']}</td>
                        <td>{$row['start_time']} - {$row['end_time']}</td>
                    </tr>";
            }
            ?>
        </table>
    </div>
</div>

</body>
</html>
