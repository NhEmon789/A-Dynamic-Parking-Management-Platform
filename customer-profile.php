<?php  
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// DB connection
$conn = new mysqli("localhost", "root", "", "parking_manage");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Success message after redirect
if (isset($_GET['success']) && $_GET['success'] == '1') {
    $success = "Profile updated successfully.";
}

// Handle profile update only on Save Changes button click
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
    $phone = trim($_POST['phone'] ?? '');
    $vehicleType = trim($_POST['vehicleType'] ?? '');
    $licensePlate = trim($_POST['licensePlate'] ?? '');

    if (!$firstName || !$lastName || !$email || !$phone) {
        $error = "Please fill in all required fields with valid data.";
    } else {
        $fullName = $firstName . ' ' . $lastName;
        $updateQuery = "UPDATE users SET name = ?, email = ?, phone = ?, vehicle_type = ?, license_plate = ? WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        if (!$stmt) {
            die("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("sssssi", $fullName, $email, $phone, $vehicleType, $licensePlate, $user_id);
        if ($stmt->execute()) {
            $stmt->close();
            // Redirect to avoid form resubmission
            header("Location: " . $_SERVER['PHP_SELF'] . "?success=1");
            exit();
        } else {
            $error = "Failed to update profile. Please try again.";
        }
        $stmt->close();
    }
}

// Fetch user data
$stmt = $conn->prepare("SELECT name, email, phone, created_at, vehicle_type, license_plate FROM users WHERE id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($fullName, $email, $phone, $memberSince, $vehicleType, $licensePlate);
$stmt->fetch();
$stmt->close();

$nameParts = explode(' ', $fullName, 2);
$firstName = $nameParts[0];
$lastName = $nameParts[1] ?? '';
$vehicleType = $vehicleType ?? '';
$licensePlate = $licensePlate ?? '';

// Fetch stats (exclude cancelled)
$statsQuery = "SELECT COUNT(*) AS total_bookings, COALESCE(SUM(total_cost), 0) AS total_spent
               FROM bookings
               WHERE customer_id = ? AND status != 'cancelled' AND payment_status != 'cancelled'";
$statsStmt = $conn->prepare($statsQuery);
if (!$statsStmt) {
    die("Prepare failed (stats): " . $conn->error);
}
$statsStmt->bind_param("i", $user_id);
$statsStmt->execute();
$statsStmt->bind_result($totalBookings, $totalSpent);
$statsStmt->fetch();
$statsStmt->close();

// Fetch currency (fallback to $)
$currency = 'TK';
$currencyQuery = "SELECT currency FROM parking_spots WHERE user_id = ? LIMIT 1";
$currencyStmt = $conn->prepare($currencyQuery);
if ($currencyStmt) {
    $currencyStmt->bind_param("i", $user_id);
    $currencyStmt->execute();
    $currencyStmt->bind_result($dbCurrency);
    if ($currencyStmt->fetch()) {
        $currency = $dbCurrency ?: $currency;
    }
    $currencyStmt->close();
}

// Handle AJAX requests for password change and account deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'change_password') {
        $currentPassword = $_POST['currentPassword'] ?? '';
        $newPassword = $_POST['newPassword'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';

        if (!$currentPassword || !$newPassword || !$confirmPassword) {
            echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
            exit;
        }
        if ($newPassword !== $confirmPassword) {
            echo json_encode(['status' => 'error', 'message' => 'New passwords do not match.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($passwordHash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($currentPassword, $passwordHash)) {
            echo json_encode(['status' => 'error', 'message' => 'Current password is incorrect.']);
            exit;
        }

        $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newPasswordHash, $user_id);
        if ($updateStmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Password updated successfully.']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update password.']);
        }
        $updateStmt->close();
        exit;
    }

    if ($action === 'delete_account') {
        $deletePassword = $_POST['deletePassword'] ?? '';
        $deleteConfirm = $_POST['deleteConfirmation'] ?? '';

        if (!$deletePassword || !$deleteConfirm) {
            echo json_encode(['status' => 'error', 'message' => 'Please fill all fields.']);
            exit;
        }
        
        $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($passwordHash);
        $stmt->fetch();
        $stmt->close();

        if (!password_verify($deletePassword, $passwordHash)) {
            echo json_encode(['status' => 'error', 'message' => 'Password is incorrect.']);
            exit;
        }

        $delStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $delStmt->bind_param("i", $user_id);
        if ($delStmt->execute()) {
            session_destroy();
            echo json_encode(['status' => 'success', 'message' => 'Account deleted. Redirecting...']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to delete account.']);
        }
        $delStmt->close();
        exit;
    }
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/animations.css">
    <style>
       .modal-content {
  background: #fff;
  border-radius: 8px;
  padding: 20px;
  max-width: 400px;
  width: 90%;
  box-shadow: 0 2px 10px rgba(0,0,0,0.2);
  text-align: center;
  position: relative;
}


    </style>
</head>
<body>

<header class="header">
    <div class="container">
        <div class="nav-wrapper">
            <div class="logo">
                <h2>FindMySpot</h2>
            </div>
            <button class="hamburger" id="hamburger" type="button" aria-label="Toggle navigation">
                <span class="bar"></span><span class="bar"></span><span class="bar"></span>
            </button>
            <nav class="nav-menu" id="navMenu">
                <ul class="nav-list">
                    <li><a href="customer-dashboard.php" class="nav-link">Dashboard</a></li>
                    <li><a href="search.php" class="nav-link">Search</a></li>
                    <li><a href="customer-bookings.php" class="nav-link">Bookings</a></li>
                    <li><a href="customer-profile.php" class="nav-link active">Profile</a></li>
                </ul>
                <div class="user-actions mobile">
                    <div class="role-switch">
                        <a href="host-dashboard.php" class="btn btn-ghost role-toggle">Switch to Host</a>
                    </div>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
            </nav>
            <div class="user-actions desktop">
                <div class="role-switch">
                    <a href="host-dashboard.php" class="btn btn-ghost role-toggle">Switch to Host</a>
                </div>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>
</header>

<main class="profile-page">
    <div class="container">
        <div class="page-header">
            <h1>My Profile</h1>
            <p>Manage your account settings and preferences</p>
        </div>
        <?php if (!empty($success)): ?>
    <div class="alert success"><?= htmlspecialchars($success) ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>


        <div class="profile-content">
            <div class="profile-card glass-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <img src="https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?w=150&h=150&fit=crop&crop=face" alt="Profile Picture" id="profileImage">
                        <div class="avatar-overlay">
                            <label for="photoUpload" class="upload-btn">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2v11z"/>
                                    <circle cx="12" cy="13" r="4"></circle>
                                </svg>
                            </label>
                            <input type="file" id="photoUpload" accept="image/*" style="display: none;">
                        </div>
                    </div>
                    <div class="profile-info">
                        <h2><?= htmlspecialchars($fullName) ?></h2>
                        <p><?= htmlspecialchars($email) ?></p>
                        <span class="member-since">Member since <?= htmlspecialchars(date("F Y", strtotime($memberSince))) ?></span>
                    </div>
                </div>

                <form class="profile-form" id="profileForm" method="POST" action="">

                    <div class="form-section">
                        <h3>Personal Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName">First Name</label>
                                <input type="text" id="firstName" name="firstName" value="<?= htmlspecialchars($firstName) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="lastName">Last Name</label>
                                <input type="text" id="lastName" name="lastName" value="<?= htmlspecialchars($lastName) ?>" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" required>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Vehicle Information</h3>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="vehicleType">Primary Vehicle Type</label>
                                <select id="vehicleType" name="vehicleType">
                                    <option value="sedan" <?= $vehicleType == "sedan" ? "selected" : "" ?>>Sedan</option>
                                    <option value="suv" <?= $vehicleType == "suv" ? "selected" : "" ?>>SUV</option>
                                    <option value="hatchback" <?= $vehicleType == "hatchback" ? "selected" : "" ?>>Hatchback</option>
                                    <option value="minivan" <?= $vehicleType == "minivan" ? "selected" : "" ?>>Minivan</option>
                                    <option value="truck" <?= $vehicleType == "truck" ? "selected" : "" ?>>Truck</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="licensePlate">License Plate</label>
                                <input type="text" id="licensePlate" name="licensePlate" value="<?= htmlspecialchars($licensePlate) ?>" placeholder="Optional">
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h3>Preferences</h3>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="emailNotifications" name="emailNotifications" checked>
                                Email notifications for bookings
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="smsNotifications" name="smsNotifications" checked>
                                SMS notifications for urgent updates
                            </label>
                        </div>
                        <div class="form-group">
                            <label class="checkbox-container">
                                <input type="checkbox" id="marketingEmails" name="marketingEmails">
                                Receive promotional emails and offers
                            </label>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                        <button type="button" class="btn btn-ghost" onclick="resetForm()">Reset</button>
                    </div>
                </form>
            </div>


                <div class="profile-sidebar">
                     <div class="sidebar-card glass-card">
        <h3>Account Statistics</h3>
        <div class="stat-item">
            <span class="stat-number"><?= $totalBookings ?></span>
            <span class="stat-label">Total Bookings</span>
        </div>
<div class="stat-item">
    <span class="stat-number"><?= htmlspecialchars($currency) . number_format($totalSpent, 2) ?></span>
    <span class="stat-label">Total Spent</span>
</div>


    
                        <div class="stat-item">
                            <span class="stat-number">8</span>
                            <span class="stat-label">Favorite Spots</span>
                        </div>
                    </div>

                    <div class="sidebar-card glass-card">
                        <h3>Security</h3>
                        <button class="btn btn-primary btn-full" onclick="changePassword()"
                            style="background: rgba(63, 63, 63, 0.8); color: white; margin-bottom: 1rem;">Change
                            Password</button>
                        <button class="btn btn-primary btn-full" onclick="enable2FA()"
                            style="background: rgba(63, 63, 63, 0.8); color: white;">Enable 2FA</button>
                    </div>

                    <div class="sidebar-card glass-card danger-zone">
                        <h3>Danger Zone</h3>
                        <button class="btn btn-danger btn-full" onclick="deleteAccount()"
                            style="background: rgba(255, 71, 87, 0.8); color: white;">Delete Account</button>
                    </div>
                </div>
            </div>
        </div>
    </main>
<!-- Success Modal -->
<div class="modal" id="successModal" style="display:none;">
  <div class="modal-content">
    <h3>Success!</h3>
    <p>Your profile has been updated successfully.</p>
    <button class="btn btn-primary" onclick="closeSuccessModal()">OK</button>
  </div>
</div>


    <!-- Change Password Modal -->
    <div class="modal" id="changePasswordModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Change Password</h3>
                <button class="modal-close" onclick="closeChangePasswordModal()">×</button>
            </div>
            <form id="changePasswordForm">
                <div class="form-group">
                    <label for="currentPassword">Current Password</label>
                    <input type="password" id="currentPassword" name="currentPassword" required>
                </div>
                <div class="form-group">
                    <label for="newPassword">New Password</label>
                    <input type="password" id="newPassword" name="newPassword" required>
                    <div class="password-strength" id="passwordStrength"></div>
                </div>
                <div class="form-group">
                    <label for="confirmPassword">Confirm New Password</label>
                    <input type="password" id="confirmPassword" name="confirmPassword" required>
                </div><br>
                <div class="modal-actions">
                    <button type="button" class="btn btn-ghost" onclick="closeChangePasswordModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary">Change Password</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div class="modal" id="deleteAccountModal">
        <div class="modal-content" style="background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);">
            <div class="modal-header">
                <h3>Delete Account</h3>
                <button class="modal-close" onclick="closeDeleteAccountModal()">×</button>
            </div>
            <div class="danger-content">
                <div class="warning-icon">
                    <svg width="60" height="60" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path>
                        <line x1="12" y1="9" x2="12" y2="13"></line>
                        <line x1="12" y1="17" x2="12.01" y2="17"></line>
                    </svg>
                </div>
                <h4 style="color: #262626;">Are you sure you want to delete your account?</h4>
                <p style="color: #666;">This action cannot be undone. All your data, bookings, and preferences will be
                    permanently deleted.</p>
                <div class="form-group">
                    <label for="deletePassword" style="color: #262626;">Enter your password to confirm:</label>
                    <input type="password" id="deletePassword" placeholder="Enter password" required>
                </div>
                <div class="form-group">
  <label for="deleteConfirmation" style="color: #262626;">Type "DELETE" to confirm:</label>
  <input type="text" id="deleteConfirmation" placeholder='Type "DELETE"' required>
</div>

                <br>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-ghost" onclick="closeDeleteAccountModal()">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteAccount()">Delete Account</button>
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
function deleteAccount() {
  document.getElementById('deleteAccountModal').style.display = 'flex';
}

function closeDeleteAccountModal() {
  document.getElementById('deleteAccountModal').style.display = 'none';
  // Clear inputs maybe:
  document.getElementById('deletePassword').value = '';
  document.getElementById('deleteConfirmation').value = '';
}

function closeSuccessModal() {
  document.getElementById('successModal').style.display = 'none';
}

document.addEventListener('DOMContentLoaded', function () {
  <?php if (!empty($success)): ?>
    document.getElementById('successModal').style.display = 'flex';
  <?php endif; ?>

});

// Password Change Modal
const changePasswordModal = document.getElementById('changePasswordModal');
const changePasswordForm = document.getElementById('changePasswordForm');

function changePassword() {
    changePasswordModal.style.display = 'flex';
}

function closeChangePasswordModal() {
    changePasswordModal.style.display = 'none';
    changePasswordForm.reset();
}

changePasswordForm.addEventListener('submit', async function (e) {
    e.preventDefault();

    const currentPassword = changePasswordForm.currentPassword.value.trim();
    const newPassword = changePasswordForm.newPassword.value.trim();
    const confirmPassword = changePasswordForm.confirmPassword.value.trim();

    if (!currentPassword || !newPassword || !confirmPassword) {
        alert('Please fill in all password fields.');
        return;
    }
    if (newPassword !== confirmPassword) {
        alert('New passwords do not match.');
        return;
    }

    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'change_password',
                currentPassword,
                newPassword,
                confirmPassword
            })
        });
        const result = await response.json();
        alert(result.message);
        if (result.status === 'success') {
            closeChangePasswordModal();
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
    }
});

// Enable 2FA alert
function enable2FA() {
    alert('Two-Factor Authentication (2FA) has been enabled successfully!');
}
async function confirmDeleteAccount() {
    const deletePassword = document.getElementById('deletePassword').value.trim();
    const deleteConfirmation = document.getElementById('deleteConfirmation').value.trim();

    console.log('Delete Password:', deletePassword);
    console.log('Delete Confirmation:', deleteConfirmation);

    if (!deletePassword || !deleteConfirmation) {
        alert('Please fill in all fields.');
        return;
    }
    if (deleteConfirmation !== 'DELETE') {
        alert('Please type "DELETE" exactly to confirm.');
        return;
    }

    if (!confirm('Are you sure you want to permanently delete your account?')) {
        return;
    }

    try {
        const response = await fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'delete_account',
                deletePassword,
                deleteConfirmation
            })
        });
        const result = await response.json();
        console.log('Server response:', result);
        alert(result.message);
        if (result.status === 'success') {
            window.location.href = 'logout.php';
        }
    } catch (error) {
        alert('An error occurred. Please try again.');
        console.error('Fetch error:', error);
    }
}

</script> 
   
</body>

</html>