<?php
// Start the session
session_start();
include 'db_con.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirect to login page if not logged in
    header("Location: otplogin.php");
    exit();
}

// Get the user ID from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Initialize message variable
$message = '';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['isEnabled'])) {
    $isEnabled = intval($_POST['isEnabled']) ? 1 : 0;

    // Update the E_isNotifiEnabled value in the Employee table
    $sql = "UPDATE Employee SET E_isNotifiEnabled = ? WHERE E_ID = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $isEnabled, $user_id);

    if ($stmt->execute()) {
        $message = 'Notification setting updated successfully.';
    } else {
        $message = 'Error updating notification setting: ' . $conn->error;
    }

    $stmt->close();
}

// Fetch the current setting from the database
$sql = "SELECT E_isNotifiEnabled FROM Employee WHERE E_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($current_isNotifiEnabled_value);
$stmt->fetch();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Settings</title>
        <style>
     

        .checkbox-container label {
            margin-left: 1px;
            font-weight: bold;
            color: #19315b;
        }

        .checkbox-container input[type="checkbox"] {
            width: 18px;
            height: 18px;
        }

        .message {
            margin-left: 500px;
            padding: 10px;
            color: #00796b;
    
        }

        .error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #c62828;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="homeLogo-container">
                <img src="EsadW.png" alt="Company Logo" class="homeLogo">
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
            <br>
             <h2 style="margin-left:530px;color:#224483">Settings</h2>
            <div class="container" style="display:grid;">
                 <?php if (!empty($message)): ?>
                <div class="message <?= strpos($message, 'Error') === false ? '' : 'error' ?>" style="margin-left:400px;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
                <div class="formContainer" style="margin:20px; margin-left:250px; padding: 80px;">
                    <div class="input-group checkbox-container" style="display:inline-block">
                        <label for="notification-checkbox" style="margin-right:110px; ">Enable Notifications</label>
                        <input type="checkbox" style="margin-top: 2px; " name="isEnabled" id="notification-checkbox" value="1" form="notification-form" <?php echo $current_isNotifiEnabled_value ? 'checked' : ''; ?>>
                    </div>
                    <br>
                    
                   <hr style="border: 0; height: 1px; background-color: grey; margin: 20px 0;">
                    <br>
                    <div class="input-group">
                        <label style="color:#19315b; font-weight: bold">Language</label>
                        <select id="language-select" style="background-color:#ffff; width:40%">
                            <option value="en">English</option>
                            <option value="ar">Arabic</option>
                        </select>
                        <br>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden form for submission -->
    <form id="notification-form" method="POST" style="display:none;">
        <input type="hidden" name="isEnabled" id="isEnabledHidden">
    </form>

    <script>
        document.getElementById('notification-checkbox').addEventListener('change', function() {
            // Update hidden input with checkbox state and submit form
            document.getElementById('isEnabledHidden').value = this.checked ? 1 : 0;
            document.getElementById('notification-form').submit();
        });

        // Set the initial language preference from localStorage
        document.addEventListener('DOMContentLoaded', function() {
            var selectedLang = localStorage.getItem('selectedLang');
            if (selectedLang) {
                document.getElementById('language-select').value = selectedLang;
                document.documentElement.lang = selectedLang;
            }
        });

        // Event listener for language selection change
        document.getElementById('language-select').addEventListener('change', function() {
            var selectedLang = this.value; // Get the selected language code

            // Update the lang attribute of the HTML tag
            document.documentElement.lang = selectedLang;

            // Store the selected language preference in localStorage
            localStorage.setItem('selectedLang', selectedLang);

            // Optional: Reload the page to apply language-specific changes
            location.reload();
        });
    </script>
</body>
</html>




