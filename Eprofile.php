<?php
// Start the session
session_start();
include 'db_con.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name']) || !isset($_SESSION['user_role'])) {
    header("Location: otplogin.php");
    exit();
}

$user_name = $_SESSION['user_name'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Fetch user data based on the role
if ($user_role == 'Employee') {
    $query = "SELECT u.U_ID, u.email, u.password, e.E_name, e.E_email, e.E_phone, e.EN_ID, e.E_basicSalary, e.E_position, e.VBalance 
              FROM Users u
              LEFT JOIN Employee e ON u.employee_id = e.E_ID
              WHERE u.employee_id = ?";
    $role_id = $user_id;  // Employee ID
} elseif ($user_role == 'HR_Admin') {
    $query = "SELECT u.U_ID, u.email, u.password, hr.A_name, hr.A_email, hr.A_phone, hr.A_position 
              FROM Users u
              LEFT JOIN HR_Admin hr ON u.admin_id = hr.A_ID
              WHERE u.admin_id = ?";
    $role_id = $user_id;  // Admin ID
} else {
    die("Invalid user role.");
}

$stmt = $conn->prepare($query);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $role_id);

if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

$result = $stmt->get_result();
if (!$result) {
    die("Get result failed: " . $stmt->error);
}

$user_data = $result->fetch_assoc();
if (!$user_data) {
    die("User not found. Query: $query, User ID: $role_id");
}

$stmt->close();

// Update user data
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate required fields
    $required_fields = ['name', 'email', 'phone', 'national_id'];

    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            $_SESSION['error_message'] = ucfirst($field) . " is required.";
            header("Location: Eprofile.php");
            exit();
        }
    }

    // Validate password if provided
    $password = isset($_POST['password']) ? $_POST['password'] : null;
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : null;

    if ($password && $password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: Eprofile.php");
        exit();
    } elseif ($password && !validatePassword($password)) {
        $_SESSION['error_message'] = "Password must be greater than 8 characters, contain at least one uppercase letter, and one special character.";
        header("Location: Eprofile.php");
        exit();
    }

    // Hash password if valid and update user data
    if ($password) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $update_user_query = "UPDATE Users SET email = ?, password = ? WHERE " . ($user_role == 'Employee' ? "employee_id" : "admin_id") . " = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param("ssi", $_POST['email'], $hashed_password, $role_id);
    } else {
        $update_user_query = "UPDATE Users SET email = ? WHERE " . ($user_role == 'Employee' ? "employee_id" : "admin_id") . " = ?";
        $update_user_stmt = $conn->prepare($update_user_query);
        $update_user_stmt->bind_param("si", $_POST['email'], $role_id);
    }

    if (!$update_user_stmt->execute()) {
        $_SESSION['error_message'] = "User update failed: " . $conn->error;
        header("Location: Eprofile.php");
        exit();
    }

    // Update role-specific details
    if ($user_role == 'Employee') {
        $update_role_query = "UPDATE Employee SET E_name = ?, E_phone = ?, EN_ID = ? WHERE E_ID = ?";
        $update_role_stmt = $conn->prepare($update_role_query);
        $update_role_stmt->bind_param("sssi", $_POST['name'], $_POST['phone'], $_POST['national_id'], $role_id);
    } elseif ($user_role == 'HR_Admin') {
        $update_role_query = "UPDATE HR_Admin SET A_name = ?, A_phone = ? WHERE A_ID = ?";
        $update_role_stmt = $conn->prepare($update_role_query);
        $update_role_stmt->bind_param("ssi", $_POST['name'], $_POST['phone'], $role_id);
    }

    if (!$update_role_stmt->execute()) {
        $_SESSION['error_message'] = "Role update failed: " . $conn->error;
        header("Location: Eprofile.php");
        exit();
    }

    $_SESSION['success_message'] = "Profile updated successfully.";
    $update_user_stmt->close();
    $update_role_stmt->close();
    $conn->close();

    header("Location: Eprofile.php");
    exit();
}

function validatePassword($password) {
    // Check if password meets complexity criteria
    if (strlen($password) < 8 || !preg_match("/[A-Z]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        return false; // Password does not meet criteria
    }
    return true; // Password meets criteria
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>My Profile</title>
    <style>
        .hidden {
            display: none;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="homeLogo-container">
                <img src="logo.png" alt="Company Logo" class="homeLogo">
            </div>
            <nav>
                <ul>
                    <li><a href="EHome.php"><i class="fas fa-home icon"></i><span class="label">Home</span></a></li>
                    <li><a href="ViewRequests.php"><i class="fas fa-envelope icon"></i><span class="label">Requests</span></a></li>
                    <li><a href="Eprofile.php"><i class="fas fa-user icon"></i><span class="label">My Profile</span></a></li>
                    <li><br></li>
                    <li><br></li>
                    <li><a href="ESettings.php"><i class="fas fa-cog icon"></i><span class="label">Settings</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="label">Logout</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <h2 style="margin-left:530px;color:#224483">Your Profile</h2>
            <div class="container">
                <div class="formContainer" style="margin:20px; margin-left:250px">
                    <?php
                    if (isset($_SESSION['success_message'])) {
                        echo '<p style="color:green;">' . $_SESSION['success_message'] . '</p>';
                        unset($_SESSION['success_message']);
                    }
                    if (isset($_SESSION['error_message'])) {
                        echo '<p style="color:red;">' . $_SESSION['error_message'] . '</p>';
                        unset($_SESSION['error_message']);
                    }
                    ?>
                    <form action="Eprofile.php" method="POST">
                        <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Personal Information</label>
                     <br><br><br>
                        <div class="input-group" style="display:inline-block">
                            <label for="name" style="color:#19315b;font-weight:normal; margin-right:60px;">Name</label>
                            <input type="text" id="name" name="name" style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'E_name' : 'A_name']); ?>" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="email" style="color:#19315b;font-weight:normal; margin-right:62px;">Email</label>
                            <input type="email" id="email" name="email" style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'E_email' : 'A_email']); ?>" readonly>
                            <div id="email-error" style="color:red;display:none;">Email must end with @domain.com </div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="phone" style="color:#19315b;font-weight:normal; margin-right:55px;">Phone</label>
                            <input type="tel" id="phone" name="phone" minlength="10" maxlength="10"  pattern="[0-9]{10}" style="background-color:#ffff; width:35%;" value="0<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'E_phone' : 'A_phone']); ?>" required>
                            <div id="phone-error" style="color:red;display:none;">Phone number must start with 05 and be exactly 10 digits.</div>
                        </div>
                        
                        <div class="input-group" style="display:inline-block">
                            <label for="nationalID" style="color:#19315b;font-weight:normal; margin-right:15px;">National ID</label>
                            <input type="text" id="national_id" name="national_id" pattern="[A-Za-z0-9]{10}" minlength="10" maxlength="10"style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'EN_ID' : 'AN_ID']); ?>" required>
                             <div id="national_id-error1" style="color: red; display: none;">National ID must be exactly 10 digits.</div>
                        </div>

                        <div class="input-group" style="display:inline-block">
                            <label for="position" style="color:#19315b;font-weight:normal; margin-right:38px;">Position</label>
                            <input type="text" id="position" name="position" style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'E_position' : 'A_position']); ?>" readonly>
                        </div>
                        
                         <div class="input-group" style="display:inline-block">
                            <label for="bSalary" style="color:#19315b;font-weight:normal; margin-right:50px;">Salary</label>
                            <input type="text" id="position" name="position" style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'E_basicSalary' : 'A_position']); ?>" readonly>
                        </div>
                        
                        <div class="input-group" style="display:inline-block">
                            <label for="VBalance" style="color:#19315b;font-weight:normal; margin-right:33px;">Vacation Balance </label>
                            <input type="text" id="VBalance" name="VBalance" style="background-color:#ffff; width:35%;" value="<?php echo htmlspecialchars($user_data[$user_role == 'Employee' ? 'VBalance' : 'A_position']); ?>  Days" readonly>
                        </div>
                        <br>
                        <br>
                         <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Reset Password</label>
                     <br><br>
                        <br>
                        <div class="input-group" style="display:inline-block">
                            <label for="password" style="color:#19315b;font-weight:normal; margin-right:95px;">Password</label>
                            <input type="password" id="password" name="password" style="background-color:#ffff; width:35%;">
                            <div id="password-error" style="color:red;display:none;"></div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="confirm_password" style="color:#19315b;font-weight:normal; margin-right:25px;">Confirm Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" style="background-color:#ffff; width:35%;">
                            <div id="passwordC-error" style="color:red;display:none;">Passwords do not match.</div>
                        </div>
                        <br><br><br>
                        <button type="submit" class="view-button" style="margin-left:200px;">Update Profile</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        function validatePhone() {
            var phoneInput = document.getElementById('phone');
            var phoneError = document.getElementById('phone-error');
            var phoneValue = phoneInput.value.trim();

            // Check if input value is exactly 10 digits and starts with 05
            if (phoneValue.length !== 10 || !phoneValue.startsWith('05') || isNaN(phoneValue)) {
                phoneInput.classList.add('error');
                phoneError.style.display = 'block';
            } else {
                phoneInput.classList.remove('error');
                phoneError.style.display = 'none';
            }
        }

        function validateEmail() {
            var emailInput = document.getElementById('email');
            var emailError = document.getElementById('email-error');
            var emailValue = emailInput.value.trim().toLowerCase();

            // Check if input value ends with @domain.com
            if (!emailValue.endsWith('@domain.com')) {
                emailInput.classList.add('error');
                emailError.style.display = 'block';
            } else {
                emailInput.classList.remove('error');
                emailError.style.display = 'none';
            }
        }

        function validatePassword() {
            var password = document.getElementById('password').value;
            var passwordError = document.getElementById('password-error');

            // Reset previous error messages
            passwordError.style.display = 'none';

            // Check if password meets criteria: greater than 8 characters, contains at least one uppercase letter, and at least one special character
            if (password.length > 0 && (password.length < 8 || !/[A-Z]/.test(password) || !/[!@#$%^&*(),.?":{}|<>]/.test(password))) {
                passwordError.innerText = 'Password must be greater than 8 characters, contain at least one uppercase letter, and one special character.';
                passwordError.style.display = 'block';
                return false;
            }

            return true; // Password meets criteria or is empty
        }

        function validatePasswordsMatch() {
            var password = document.getElementById('password').value;
            var confirmPassword = document.getElementById('confirm_password').value;
            var passwordError = document.getElementById('passwordC-error');

            // Reset previous error messages
            passwordError.style.display = 'none';

            // Check if passwords match
            if (password !== confirmPassword) {
                passwordError.innerText = 'Passwords do not match.';
                passwordError.style.display = 'block';
                return false;
            }

            return true; // Passwords match
        }

        // Attach validation functions to relevant events
        document.getElementById('phone').addEventListener('input', validatePhone);
        document.getElementById('email').addEventListener('input', validateEmail);
        document.getElementById('password').addEventListener('input', validatePassword);
        document.getElementById('confirm_password').addEventListener('input', validatePasswordsMatch);
    </script>
</body>
</html>





