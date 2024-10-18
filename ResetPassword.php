<?php
// Enable error reporting for development purposes
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection details
$servername = "localhost";
$username = "root";
$password_db = "";
$dbname = "ESS_DB";

// Initialize variables for form inputs and error handling
$error = "";
$success = "";

// Start session to access user information
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role'])) {
    header("Location: otplogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Validate passwords
    if (empty($password) || empty($confirm_password)) {
        $error = 'Both fields are required.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match("/[A-Z]/", $password) || !preg_match("/[!@#$%^&*(),.?\":{}|<>]/", $password)) {
        $error = 'Password must contain at least one uppercase letter and one special character.';
    } else {
        // Create connection to database
        $conn = new mysqli($servername, $username, $password_db, $dbname);

        // Check connection
        if ($conn->connect_error) {
            $error = "Connection failed: " . $conn->connect_error;
        } else {
            // Update password in the appropriate table
            if ($user_role == 'Employee') {
                $stmt = $conn->prepare('UPDATE Users SET password = ? WHERE employee_id = ?');
            } else {
                $stmt = $conn->prepare('UPDATE Users SET password = ? WHERE admin_id = ?');
            }

            if ($stmt) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->bind_param('si', $hashed_password, $user_id); // Note: changed 'ss' to 'si' for proper binding
                $stmt->execute();
                if ($stmt->affected_rows > 0) {
                    $success = 'Password reset successfully. You will be redirected to the login page.';
                    header("refresh:2;url=otplogin.php");
                    exit();
                } else {
                    $error = 'Failed to update password. Please try again.';
                }
                $stmt->close();
            } else {
                $error = 'Failed to prepare SQL statement.';
            }
            $conn->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="header">
                <h1>Reset Password</h1>
            </div>
            <?php if (!empty($error)) : ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php elseif (!empty($success)) : ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="input-group">
                    <label for="password">New Password:</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="input-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                <button class="view-button-login" type="submit">Reset Password</button>
            </form>
        </div>
        <div class="loginLogo-container">
            <img src="Logo.png" alt="Company Logo" class="loginLogo">
        </div>
    </div>
</body>
</html>




