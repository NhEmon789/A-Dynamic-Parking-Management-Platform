<?php
session_start();  // Start the session at the top

// Enable error reporting (helpful during development, turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database credentials
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "parking_manage";

// Create database connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize user inputs
    $email = trim($_POST['email'] ?? '');
    $password_input = $_POST['password'] ?? '';

    // Validate required fields
    if (empty($email) || empty($password_input)) {
        echo "<script>alert('Email and password are required.'); window.history.back();</script>";
        exit;
    }

    // Prepare SQL query to get user by email
    $stmt = $conn->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if user exists
    if ($user = $result->fetch_assoc()) {
        // Verify the input password against the hashed password
        if (password_verify($password_input, $user['password_hash'])) {
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];

            // Redirect based on role
            if ($user['role'] === 'admin') {
                header("Location: admin.php");
            } elseif ($user['role'] === 'host') {
                header("Location: host-dashboard.php");
            } else {
                header("Location: customer-dashboard.php");
            }
            exit;
        } else {
            echo "<script>alert('Incorrect password.'); window.history.back();</script>";
            exit;
        }
    } else {
        echo "<script>alert('User not found.'); window.history.back();</script>";
        exit;
    }

    $stmt->close(); // Close the statement
}

$conn->close(); // Close DB connection
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - FindMySpot</title>

    <!-- Fonts and styles -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
</head>
<body>

    <!-- Page container -->
    <div class="login-page">

        <!-- Background image and overlay -->
        <div class="login-background">
            <img src="https://images.unsplash.com/photo-1590674899484-d5640e854abe?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80" alt="Parking" class="bg-image">
            <div class="login-overlay"></div>
        </div>

        <!-- Main login card -->
        <div class="login-container">
            <div class="login-card glass-card">

                <!-- Header -->
                <div class="login-header">
                    <h2>Welcome Back</h2>
                    <div class="logo-small">FindMySpot</div>
                </div>

               

                <!-- Login form -->
                <form class="login-form" id="loginForm" method="POST" action="login.php">
                    <!-- Email input -->
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" required>
                        <div class="error-message"></div>
                    </div>

                    <!-- Password input -->
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" required>
                        <div class="error-message"></div>
                    </div>

                    <!-- Remember me & forgot password -->
                    <div class="form-options">
                        <label class="checkbox-container">
                            <input type="checkbox" id="remember" name="remember">
                            <span class="checkmark"></span>
                            Remember me
                        </label>
                        <a href="#" class="forgot-password">Forgot Password?</a>
                    </div>

                    <!-- Submit button -->
                    <button type="submit" class="btn btn-primary btn-full">Login</button>
                </form>

                <!-- Footer with register link -->
                <div class="login-footer">
                    <p>Don't have an account? <a href="register.php">Register here</a></p>
                </div>

            </div>
        </div>
    </div>

    <!-- Optional JS file -->
    <!-- <script src="js/login.js"></script> -->

</body>
</html>

