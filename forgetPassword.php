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

// Start the session
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";

    // Create connection to the database
    $conn = new mysqli($servername, $username, $dbpassword, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to get user details based on email
    $get_user_sql = "SELECT role, employee_id, employee_name, admin_id, admin_name FROM Users WHERE email = ?";
    $get_user_stmt = $conn->prepare($get_user_sql);
    if (!$get_user_stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $get_user_stmt->bind_param("s", $email);
    $get_user_stmt->execute();
    $get_user_stmt->store_result();

    if ($get_user_stmt->num_rows == 0) {
        $error = "Invalid email. Please try again.";
    } else {
        $get_user_stmt->bind_result($role, $employee_id, $employee_name, $admin_id, $admin_name);
        $get_user_stmt->fetch();

        $otp = mt_rand(1000, 9999); // Generate 6-digit OTP
        $otp_expiry = date('Y-m-d H:i:s', strtotime('+1 minutes')); // OTP expires in 3 minutes

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
    }

    $get_user_stmt->close();
    if (isset($update_stmt)) {
        $update_stmt->close();
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
        $mail->Username   = 'email@gmail.com';  // SMTP username
        $mail->Password   = 'password';  // SMTP password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;  // Enable TLS encryption
        $mail->Port       = 587;       // TCP port

        // Recipients
        $mail->setFrom('email@gmail.com', 'Employee Self-Service System');
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
        header("Location: PasswordAuthentication.php");
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
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styles.css">
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
                <h1>Forgot Password</h1>
            </div>
            <?php if (!empty($error)) : ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                <div class="input-group">
                    <label for="email">Enter your email address:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                <button class="view-button-login" type="submit">Validate Email</button>
            </form>
        </div>
        <div class="loginLogo-container">
            <img src="Logo.png" alt="Company Logo" class="loginLogo">
        </div>
    </div>
</body>
</html>


