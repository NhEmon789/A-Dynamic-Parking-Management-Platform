<?php
// Database connection
$con = mysqli_connect('localhost', 'root', '', 'parking_manage');

if (!$con) {
    die("Connection failed: " . mysqli_connect_error());
}

$success = false; // <-- You forgot to define this earlier

// If form is submitted

if (isset($_POST['submit'])) {
    $name      = trim($_POST['name'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $password  = $_POST['password'] ?? '';
    $cpassword = $_POST['confirmPassword'] ?? '';
    $role      = $_POST['role'] ?? '';

    $errorMsg = '';

    // Validate required fields
    $missingFields = [];
    if ($name === '') $missingFields['name'] = 'Full Name is required.';
    if ($email === '') $missingFields['email'] = 'Email is required.';
    if ($phone === '') $missingFields['phone'] = 'Phone Number is required.';
    if ($password === '') $missingFields['password'] = 'Password is required.';
    if ($cpassword === '') $missingFields['confirmPassword'] = 'Confirm Password is required.';
    if ($role === '') $missingFields['role'] = 'Role is required.';
    if (count($missingFields) > 0) {
        foreach ($missingFields as $field => $msg) {
            echo "<script>document.addEventListener('DOMContentLoaded', function() { var f = document.getElementsByName('$field')[0]; if(f) { var group = f.closest('.form-group'); var err = group.querySelector('.error-message'); if(err) { err.textContent = '$msg'; err.style.display = 'block'; group.classList.add('error'); } } });</script>";
        }
        $errorMsg = 'missing';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errorMsg = 'Please provide a valid email address.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $errorMsg = 'Please provide a valid phone number.';
    } elseif ($password !== $cpassword) {
        $errorMsg = 'Passwords do not match!';
    } elseif (strlen($password) < 8 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $errorMsg = 'Password must be at least 8 characters and contain uppercase, lowercase, and a number.';
    } else {
        // Check for duplicate email
        $checkEmailQuery = "SELECT id FROM users WHERE email = '" . mysqli_real_escape_string($con, $email) . "' LIMIT 1";
        $checkEmailResult = mysqli_query($con, $checkEmailQuery);
        if (mysqli_num_rows($checkEmailResult) > 0) {
            $errorMsg = 'This email is already registered. Please use another email.';
        }
    }

    if ($errorMsg) {
        // Do not show any generic error message at the top
        // All error messages are now shown under each input field via JS
        if ($errorMsg !== 'missing') {
            echo "<script>document.addEventListener('DOMContentLoaded', function() { var emailField = document.getElementById('customerEmail'); if(emailField) { var group = emailField.closest('.form-group'); var err = group.querySelector('.error-message'); if(err) { err.textContent = '" . addslashes($errorMsg) . "'; err.style.display = 'block'; group.classList.add('error'); } } });</script>";
        }
    } else {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (name, email, phone, password_hash, role) 
                  VALUES ('$name', '$email', '$phone', '$hashedPassword', '$role')";
        $run = mysqli_query($con, $query);
        if ($run) {
            $success = true; // ✅ Enables the modal
        } else {
            echo "<p style='color:red;'>Error: " . mysqli_error($con) . "</p>";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Register - FindMySpot</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link rel="stylesheet" href="css/style.css" />
    <link rel="stylesheet" href="css/animations.css" />
    <style>
.modal {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background: rgba(0,0,0,0.4);
  display: flex; /* <-- important */
  align-items: center;
  justify-content: center;
  z-index: 999;
}  input.input-error {
    border: 2px solid red;
  }
  input.input-success {
    border: 2px solid green;
  }
  .error-message {
    color: red;
    font-size: 0.9em;
    margin-top: 4px;
  }


    </style>
</head>

<body>
    <div class="register-page">
        <div class="register-background">
            <img src="https://images.unsplash.com/photo-1590674899484-d5640e854abe?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80"
                alt="Parking" class="bg-image" />
            <div class="register-overlay"></div>
        </div>

        <div class="register-container">
            <div class="register-card glass-card">
                <div class="register-header">
                    <h2>Join FindMySpot</h2>
                    <div class="logo-small">FindMySpot</div>
                </div>

               

                <!-- User Registration Form -->
                <form class="register-form customer-form active <?php echo (!isset($_POST['role']) || $_POST['role'] === 'customer') ? 'active' : ''; ?>" id="customerForm" action="register.php" method="POST" enctype="multipart/form-data" novalidate>
        

                    
                    <div class="form-group">
                        <label for="customerName">Full Name</label>
                        <input type="text" id="customerName" name="name" required
                        value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" />
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="customerEmail">Email</label>
                        <input type="email" id="customerEmail" name="email" required
                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" />
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="customerPhone">Phone Number</label>
                        <input type="tel" id="customerPhone" name="phone" required
                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" />
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="customerPassword">Password</label>
                        <input type="password" id="customerPassword" name="password" required />
                        <div id="passwordCriteria" class="password-criteria-message"></div>
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="customerConfirmPassword">Confirm Password</label>
                        <input type="password" id="customerConfirmPassword" name="confirmPassword" required />
                        <div class="error-message"></div>
                    </div>

                    <div class="form-group">
                        <label for="customerID">ID Verification (Optional)</label>
                        <input type="file" id="customerID" name="idFile" accept="image/*" />
                    </div>
<div>
    <label>Select Role:</label><br>
    <input type="radio" name="role" value="customer" required checked> Customer
    <input type="radio" name="role" value="host"> Host
    <br><br>
</div>

                    <button type="submit" name="submit" class="btn btn-primary btn-full">Create Account</button>


                </form>

             <p style="text-align: center; margin-top: 1rem;">
  Already have an account? 
  <a href="login.php" style="color: #ffffffff; text-decoration: none;">Login</a>
</p>
   
    <!-- Success Modal -->

    <div class="modal" id="successModal" style="<?php echo $success ? 'display: block;' : 'display: none;'; ?>">
        <div class="modal-content glass-card">
            <div class="success-icon">
                <svg width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                    <polyline points="22,4 12,14.01 9,11.01"></polyline>
                </svg>
            </div>
            <h3>Registration Successful!</h3>
            <p>Welcome to FindMySpot! You can now start using our platform.</p>
            <button class="btn btn-primary" onclick="closeSuccessModal()">Continue</button>
        </div>
    </div>


</script>

<script src="js/register.js"></script>



</body>

</html>
