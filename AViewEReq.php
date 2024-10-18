<?php
session_start();
include 'db_con.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirect to login page if not logged in
    header("Location: otplogin.php");
    exit();
}

// Get the user name and ID from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Check if employee_id is provided
if (!isset($_GET['employee_id'])) {
    // Redirect or show an error message if employee_id is not provided
    header('Location: viewEmployees.php'); // Redirect to the main page or wherever appropriate
    exit;
}

$employee_id = $_GET['employee_id'];

// Fetch active requests using LEFT JOIN
$sql_active = "SELECT r.R_ID, r.R_Type, a.A_name, r.R_date, r.R_status
                FROM Requests r
                LEFT JOIN HR_Admin a ON r.A_ID = a.A_ID
                WHERE r.E_ID = ? AND (r.R_status IS NULL OR r.R_status = 'Pending')";
$stmt_active = $conn->prepare($sql_active);

if (!$stmt_active) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_active->bind_param("i", $employee_id);
$stmt_active->execute();
$result_active = $stmt_active->get_result();

if (!$result_active) {
    die("Execute statement failed: " . $stmt_active->error);
}

$active_requests = $result_active->fetch_all(MYSQLI_ASSOC);

// Fetch past requests using LEFT JOIN
$sql_past = "SELECT r.R_ID, r.R_Type, a.A_name, r.R_date, r.R_status
             FROM Requests r
             LEFT JOIN HR_Admin a ON r.A_ID = a.A_ID
             WHERE r.E_ID = ? AND r.R_status IN ('Approved', 'Rejected', 'Revision Requested')";
$stmt_past = $conn->prepare($sql_past);

if (!$stmt_past) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_past->bind_param("i", $employee_id);
$stmt_past->execute();
$result_past = $stmt_past->get_result();

if (!$result_past) {
    die("Execute statement failed: " . $stmt_past->error);
}

$past_requests = $result_past->fetch_all(MYSQLI_ASSOC);

$stmt_active->close();
$stmt_past->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
   
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="homeLogo-container">
                <img src="logo.png" alt="Company Logo" class="homeLogo">
            </div>
            <nav>
                <ul>
                    <li><a href="AHome.php"><i class="fas fa-home icon"></i><span class="label">Home</span></a></li>
                    <li><a href="AView.php"><i class="fas fa-envelope icon"></i><span class="label">Requests</span></a></li>
                    <li><a href="viewEmployees.php"><i class="fas fa-users icon"></i><span class="label">Employees</span></a></li>
                    <li><a href="Aprofile.php"><i class="fas fa-user icon"></i><span class="label">My Profile</span></a></li>
                    <li><br></li>
                    <li><br></li>
                    <li><a href="ASettings.php"><i class="fas fa-cog icon"></i><span class="label">Settings</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="label">Logout</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($user_name ?? ''); ?></h1>
             <h2 style="margin-left:500px; color:#224483">Employee Requests</h2>
                   <a href="viewEmployees.php" class="icon-button" style="margin-left: 20px;">
  <button class="back-button">
    <i class="fa fa-arrow-left"></i>
  </button>
</a>

            <div class="tabs-container">
              
                <div class="nav nav-pills">
                    <button class="nav-link tab-link" onclick="openTab(event, 'active-requests')">Active Requests</button>
                    <button class="nav-link tab-link" onclick="openTab(event, 'past-requests')">Past Requests</button>
                </div>
                <div id="active-requests" class="tab-content">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Assigned To</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($active_requests) > 0) : ?>
                                <?php foreach ($active_requests as $request) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['R_ID'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_Type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['A_name'] ?? 'Not Assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_status'] ?? ''); ?></td>
                                        <td>
    <a href="AViewDetail.php?id=<?php echo htmlspecialchars($request['R_ID'] ?? ''); ?>&employee_id=<?php echo htmlspecialchars($_GET['employee_id'] ?? ''); ?>" class="view-button">View</a>
</td>

                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6">No active requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="past-requests" class="tab-content">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Type</th>
                                <th>Assigned To</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($past_requests) > 0) : ?>
                                <?php foreach ($past_requests as $request) : ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($request['R_ID'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_Type'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['A_name'] ?? 'Not Assigned'); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_date'] ?? ''); ?></td>
                                        <td><?php echo htmlspecialchars($request['R_status'] ?? ''); ?></td>
                                        <td><a href="AViewDetail.php?id=<?php echo htmlspecialchars($request['R_ID'] ?? ''); ?>&employee_id=<?php echo htmlspecialchars($_GET['employee_id'] ?? ''); ?>" class="view-button">View</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="6">No past requests found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
     function openTab(event, tabId) {
    // Get all elements with class="tab-content" and hide them
    var tabContents = document.getElementsByClassName("tab-content");
    for (var i = 0; i < tabContents.length; i++) {
        tabContents[i].style.display = "none";
    }

    // Get all elements with class="tab-button" and remove the class "active" from all
    var tabButtons = document.getElementsByClassName("tab-link");
    for (var i = 0; i < tabButtons.length; i++) {
        tabButtons[i].classList.remove("active");
    }

    // Show the current tab and add an "active" class to the button that opened the tab
    document.getElementById(tabId).style.display = "block";
    event.currentTarget.classList.add("active");
}

// Set the default active tab
document.addEventListener("DOMContentLoaded", function() {
    // Click on the first tab button to make it active and show its corresponding tab content
    document.getElementsByClassName("tab-link")[0].click();
});


    </script>
</body>
</html>
