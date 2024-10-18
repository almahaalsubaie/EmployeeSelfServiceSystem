<?php
session_start();
// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

date_default_timezone_set('Asia/Riyadh');
require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Database connection details
$servername = "localhost";
$username = "root";
$dbpassword = "";
$dbname = "ESS_DB";

// Initialize variables
$error = "";
$success_message = "";
$show_resend_link = false;

// Check if the user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_role'])) {
    header("Location: otplogin.php"); // Redirect to login if not logged in
    exit();
}

// Handle OTP submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['otp_submit'])) {
    $email = $_SESSION['email']; // Get email from session
    $otp = (int)trim($_POST['otp']);

    // Create connection to the database
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $sql = "SELECT otp_code, otp_expiry_time FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($stored_otp, $otp_expiry);
        $stmt->fetch();

        // Convert expiry time to timestamp
        $expiry_time = $otp_expiry ? strtotime($otp_expiry) : 0;
        $current_time = time();

        // Check if OTP is valid and not expired
        if ($otp === $stored_otp && $expiry_time > $current_time) {
            // Clear OTP from database
            $clear_otp_sql = "UPDATE Users SET otp_code = NULL, otp_expiry_time = NULL WHERE email = ?";
            $clear_stmt = $conn->prepare($clear_otp_sql);
            if (!$clear_stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $clear_stmt->bind_param("s", $email);
            $clear_stmt->execute();

            // Redirect to the appropriate home page based on role
            if ($_SESSION['user_role'] == 'Employee') {
                header("Location: EHome.php");
                exit();
            } elseif ($_SESSION['user_role'] == 'HR_Admin') {
                header("Location: AHome.php");
                exit();
            } else {
                $error = "Invalid user role.";
            }
        } else {
            $error = "Invalid or expired OTP. Please try again.";
            $show_resend_link = true;
        }
    } else {
        $error = "Email not found. Please check your email and try again.";
    }

    $stmt->close();
    $conn->close();
}
// Resend OTP logic
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['resend_otp'])) {
    $email = $_SESSION['email']; // Get email from session
    $current_time = time();

    // Create connection to the database
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute SQL statement to get last OTP request time
    $sql = "SELECT last_otp_request FROM Users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($last_request);
        $stmt->fetch();
        $last_request_time = strtotime($last_request);

        // Check if a minute has passed since the last request
        if (($current_time - $last_request_time) >= 60) {
            $otp = mt_rand(1000, 9999); // Generate new OTP
            $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minute')); // OTP expires in 1 minute

            // Update OTP in the database
            $update_otp_sql = "UPDATE Users SET otp_code = ?, otp_expiry_time = ?, last_otp_request = NOW() WHERE email = ?";
            $update_stmt = $conn->prepare($update_otp_sql);
            if (!$update_stmt) {
                die("Prepare failed: " . $conn->error);
            }
            $update_stmt->bind_param("sss", $otp, $otp_expiry, $email);
            $update_stmt->execute();

            // Send OTP to user
            if (sendMail($email, $otp, $_SESSION['user_role'], $_SESSION['user_id'] ?? null, $_SESSION['user_name'] ?? null, $_SESSION['admin_id'] ?? null, $_SESSION['admin_name'] ?? null)) {
                $success_message = "OTP has been resent. Please check your email.";
            } else {
                $error = "Failed to send OTP. Please try again.";
            }
        } else {
            $error = "You can only request a new OTP once every minute.";
        }
    } else {
        $error = "Email not found. Please check your email and try again.";
    }
    
    $stmt->close();
    $conn->close();
}


function sendMail($email, $otp, $role, $employee_id = null, $employee_name = null, $admin_id = null, $admin_name = null) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; 
        $mail->SMTPAuth   = true; 
        $mail->Username   = 'almahasubaie@gmail.com'; 
        $mail->Password   = 'bjci gkyr sfht glvw'; 
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; 
        $mail->Port       = 587; 

        $mail->setFrom('almahasubaie@gmail.com', 'Employee Self-Service System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = 'OTP for Authentication';
        $mail->Body    = 'Your OTP (One Time Password) for authentication is: <strong>' . $otp . '</strong>';

        $mail->send();

       

        return true;
    } catch (Exception $e) {
        return false;
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <title>OTP Verification</title>
    <style>
        .error {
            color: red;
            font-weight: bold;
        }
        .resend-link {
            display: none; /* Initially hidden */
           
        }
    </style>
</head>
<body>
<div class="container">
    <div class="login-container">
        <div class="header">
            <h1>OTP Verification</h1>
            <br>
        </div>

        <form class="login-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
            <div class="input-group">
                <label for="otp">Enter OTP</label>
                <input type="text" id="otp" name="otp" required>
            </div>
            <button class="view-button-login" type="submit" name="otp_submit">Verify OTP</button>
        </form>

        <?php if (!empty($error)) : ?>
            <div id="error-message" class="error"><?php echo $error; ?></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var errorMessage = document.getElementById('error-message').textContent;
                    var resendLink = document.querySelector('.resend-link');
                    if (errorMessage.includes('Invalid or expired OTP')) {
                        resendLink.style.display = 'inline'; // Show the resend link if the error message is about invalid/expired OTP
                    }
                });
            </script>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" style="display:inline;">
            <input type="hidden" name="resend_otp" value="1">
            <br>
            <a href="#" class="resend-link" style="color:floralwhite" onclick="this.closest('form').submit(); return false;">Resend OTP Code</a>
        </form>
    </div>
    <div class="loginLogo-container">
        <img src="Logo.png" alt="Company Logo" class="loginLogo">
    </div>
</div>
</body>
</html>

