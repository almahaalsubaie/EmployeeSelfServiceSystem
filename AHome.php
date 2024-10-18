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

// Get the user name and ID from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch statistics for the logged-in admin
$totalRequests = 0;
$rejectedRequests = 0;
$approvedRequests = 0;
$pendingRequests = 0;
$revisionRequests =0;

$sql = "SELECT 
            COUNT(*) AS total, 
            SUM(R_status = 'Rejected') AS rejected, 
            SUM(R_status = 'Approved') AS approved, 
            SUM(R_status = 'Pending') AS pending,
            SUM(R_status = 'Revision Requested') AS revision
        FROM Requests
        WHERE A_ID = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    // Fetch the row
    $row = $result->fetch_assoc();
    $totalRequests = $row['total'];
    $pendingRequests = $row['pending'];
    $approvedRequests = $row['approved'];
    $revisionRequests = $row['revision'];
    $rejectedRequests = $row['rejected'];
}

$stmt->close();

// Fetch recent requests for the logged-in admin
$recentRequests = [];

$sqlRecent = "SELECT R_ID, R_type, R_date, R_status 
              FROM Requests
              WHERE A_ID = ?
              ORDER BY R_date DESC
              LIMIT 3";

$stmtRecent = $conn->prepare($sqlRecent);
$stmtRecent->bind_param("i", $user_id);
$stmtRecent->execute();
$resultRecent = $stmtRecent->get_result();

if ($resultRecent->num_rows > 0) {
    while ($rowRecent = $resultRecent->fetch_assoc()) {
        $recentRequests[] = $rowRecent;
    }
}

$stmtRecent->close();
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
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <div class="homeContainer"> 
                   <h2 style="margin-left: 120px;">Quick Statistics</h2>
                <div class="dash1Container">
                    <table class="homeTable">
    <tr>
      <td style="font-size: 40px"><?php echo $totalRequests; ?></td>
<td style="font-size: 40px"><?php echo $pendingRequests; ?></td>
<td style="font-size: 40px;color:#6A9B5F"><?php echo $approvedRequests; ?></td>
 <td style="font-size: 40px;color:#5D90BD"><?php echo $revisionRequests; ?></td>
<td style="font-size: 40px; color:#AB3535"><?php echo $rejectedRequests; ?></td>
    </tr>
<tr>
      <td>Total Requests</td>
                            <td>Pending Requests</td>
                            <td>Approved Requests</td>
                            <td>Revision Requests</td>
                            <td>Rejected Requests</td>
    </tr>
  </table>
                </div>
                    <div class="AtitlesRow"><h2 style="margin-left: 120px;">Recent Requests</h2> 
                <h2> Employees</h2></div>
                <div class="dashRow">
                    <div class="dash2Container">
                 
                        <table class="recentTable">
                            <tr>
                                <th style="border: none;">Request ID</th>
                                <th style="border: none;">Type</th>
                                <th style="border: none;">Date</th>
                                <th style="border: none;">Status</th>
                            </tr>
                            <?php foreach ($recentRequests as $request) : ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($request['R_ID']); ?></td>
                                    <td><?php echo htmlspecialchars($request['R_type']); ?></td>
                                    <td><?php echo htmlspecialchars($request['R_date']); ?></td>
                                    <td><?php echo htmlspecialchars($request['R_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    </div>
                    <div class="dash3Container">
                        <br><br>
                        <a href="EmployeeRegistration.php"><button class="view-button"   > New Employee</button></a><br>
                        <a href="viewEmployees.php"><button class="view-button" style="padding-left:22px; padding-right:22px;">Management</button></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

