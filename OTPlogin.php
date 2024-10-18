<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Riyadh');
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$servername = "localhost";
$username = "root";
$dbpassword = "";
$dbname = "ESS_DB";

$error = "";
$email = "";
$password = "";

// Start the session
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $password = isset($_POST['password']) ? trim($_POST['password']) : "";

    // Create connection to the database
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to get the password hash and user details
    $get_password_sql = "SELECT password, role, employee_id, employee_name, admin_id, admin_name FROM Users WHERE email = ?";
    $get_password_stmt = $conn->prepare($get_password_sql);
    if (!$get_password_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $get_password_stmt->bind_param("s", $email);
    $get_password_stmt->execute();
    $get_password_stmt->store_result();

    if ($get_password_stmt->num_rows == 0) {
        $error = "Invalid email or password. Please try again.";
    } else {
        $get_password_stmt->bind_result($stored_password, $role, $employee_id, $employee_name, $admin_id, $admin_name);
        $get_password_stmt->fetch();

        // Check if the stored password is hashed or plain text
        if (password_verify($password, $stored_password)) {
            // Correct hashed password
            $otp = mt_rand(1000, 9999); // Generate 6-digit OTP
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minutes')); // OTP expires in 1 minute

            // Update OTP in the database
            $update_otp_sql = "UPDATE Users SET otp_code = ?, otp_expiry_time = ? WHERE email = ?";
            $update_stmt = $conn->prepare($update_otp_sql);
            if (!$update_stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $update_stmt->bind_param("iss", $otp, $otp_expiry, $email);
            if (!$update_stmt->execute()) {
                die("Execute failed: " . $update_stmt->error);
            }

            // Send OTP via email
            if (!sendMail($email, $otp, $role, $employee_id, $employee_name, $admin_id, $admin_name)) {
                $error = "Failed to send email. Please try again.";
            }
        } else {
            // If not verified, assume stored password is plaintext
            // This part should generally not be reached if passwords are properly hashed
            if ($password === $stored_password) {
                // Hash the plaintext password
                $new_hashed_password = password_hash($password, PASSWORD_DEFAULT);
                // Update hashed password in the database
                $update_password_sql = "UPDATE Users SET password = ? WHERE email = ?";
                $update_password_stmt = $conn->prepare($update_password_sql);
                if (!$update_password_stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $update_password_stmt->bind_param("ss", $new_hashed_password, $email);
                if (!$update_password_stmt->execute()) {
                    die("Execute failed: " . $update_password_stmt->error);
                }

                // Generate and send OTP as above
                $otp = mt_rand(1000, 9999);
                $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minutes'));
                $last_otp_request = date('Y-m-d H:i:s');

                $update_otp_sql = "UPDATE Users SET otp_code = ?, otp_expiry_time = ?, last_otp_request =? WHERE email = ?";
                $update_stmt = $conn->prepare($update_otp_sql);
                if (!$update_stmt) {
                    die("Prepare failed: " . $conn->error);
                }
                $update_stmt->bind_param("isss", $otp, $otp_expiry, $last_otp_request, $email);
                if (!$update_stmt->execute()) {
                    die("Execute failed: " . $update_stmt->error);
                }

                if (!sendMail($email, $otp, $role, $employee_id, $employee_name, $admin_id, $admin_name)) {
                    $error = "Failed to send email. Please try again.";
                }
            } else {
                $error = "Invalid email or password. Please try again.";
            }
        }
    }

    $get_password_stmt->close();
    if (isset($update_stmt)) {
        $update_stmt->close();
    }
    if (isset($update_password_stmt)) {
        $update_password_stmt->close();
    }
    $conn->close();
}

function sendMail($email, $otp, $role, $employee_id, $employee_name, $admin_id, $admin_name) {
    // Create a new PHPMailer instance
    $mail = new PHPMailer(true);
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';  // SMTP server
        $mail->SMTPAuth   = true;      // Enable SMTP authentication
        $mail->Username   = 'almahasubaie@gmail.com';  // SMTP username
        $mail->Password   = 'bjci gkyr sfht glvw';  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port       = 587;       // TCP port

        // Recipients
        $mail->setFrom('almahasubaie@gmail.com', 'Employee Self-Service System');
        $mail->addAddress($email);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'OTP for Authentication';
        $mail->Body    = 'Your OTP (One Time Password) for authentication is: <strong>' . $otp . '</strong>';

        $mail->send();

        // Set session variables
        $_SESSION['user_id'] = ($role == 'Employee') ? $employee_id : $admin_id;
        $_SESSION['user_name'] = ($role == 'Employee') ? $employee_name : $admin_name;
        $_SESSION['user_role'] = $role;
        $_SESSION['email'] = $email;

        // Redirect to OTP verification page
        header("Location: OTPVerification.php");
        exit();
    } catch (Exception $e) {
        return false;
    }
    return true;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>Employee Self-Service Login</title>
    <style>
        .error {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-container">
        <div class="header">
            <h1>Employee Self-Service System</h1>
            <br>
           
        </div>
        
        <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" enctype="multipart/form-data">
            <div class="input-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            <div class="input-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button class="view-button-login" type="submit">Login</button>
            <br><br>
            <a href="forgetPassword.php" style="color: whitesmoke" class="forgot-password-link">Forgot Password?</a>
        </form>
       
        <?php if (!empty($error)) : ?>
            <div id="error-message" class="error"><?php echo $error; ?></div>
        <?php endif; ?>
    </div>
    <div class="loginLogo-container">
        <img src="Logo.png" alt="Company Logo" class="loginLogo">
    </div>
</div>
</body>
</html>

