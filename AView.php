<?php
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

// Handle Assign to Me action
if (isset($_POST['assign_to_me'])) {
    $r_id = $_POST['r_id'];
    $sql = "UPDATE Requests SET A_ID = ?, R_status = 'Pending' WHERE R_ID = ? AND A_ID IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ii', $user_id, $r_id);
    $stmt->execute();
    header("Location: AView.php");
    exit();
}

// Initialize filter and order variables with defaults
$request_type_filter = '';
$order_by = 'R.R_date DESC';

// Handle form submission for filtering and ordering
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_type']) && isset($_POST['order_by'])) {
    $request_type_filter = $_POST['request_type'];
    $order_by = $_POST['order_by'];
    $status_filter = $_POST['status_filter'];
}

// Function to fetch requests with optional filter and order
function fetch_requests($conn, $conditions, $order_by, $type_filter, $status_condition) {
    $base_sql = "SELECT R.R_ID, R.R_Type, R.R_date, R.R_status, E.E_name 
                 FROM Requests R 
                 JOIN Employee E ON R.E_ID = E.E_ID 
                 WHERE 1=1 ";
    if (!empty($conditions)) {
        $base_sql .= ' AND ' . implode(' AND ', $conditions);
    }
    if (!empty($type_filter)) {
        $base_sql .= ' AND ' . $type_filter;
    }
    
    if (!empty($status_condition)) {
        $base_sql .= ' AND ' . $status_condition;
    }
    
    $base_sql .= " ORDER BY " . $order_by;

    $result = $conn->query($base_sql);
    if (!$result) {
        throw new mysqli_sql_exception($conn->error);
    }
    
       if ($result->num_rows === 0) {
        return NULL;
    }
    
    return $result;
}

// Define conditions for each tab
$coming_conditions = ["R.A_ID IS NULL", "R.R_status = 'Pending'"];
$active_conditions = ["R.A_ID = $user_id", "R.R_status IN ('Revision Requested', 'Pending')"];
$past_conditions = ["R.A_ID = $user_id", "R.R_status IN ('Approved', 'Rejected')"];

// Apply type filter if specified
$type_filter = '';
if (!empty($request_type_filter)) {
    $type_filter = "R.R_Type = '" . $conn->real_escape_string($request_type_filter) . "'";
}

$status_condition = '';
if (!empty($status_filter)) {
    $status_condition = "R.R_status = '" . $conn->real_escape_string($status_filter) . "'";
}


// Fetch requests for each tab
$coming_requests = fetch_requests($conn, $coming_conditions, $order_by, $type_filter, $status_condition);
$active_requests = fetch_requests($conn, $active_conditions, $order_by, $type_filter, $status_condition);
$past_requests = fetch_requests($conn, $past_conditions, $order_by, $type_filter, $status_condition);
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
              <br>
             <h2 style="margin-left:500px; color:#224483">Requests Management</h2>
            <div class="tabs-container">
              

                <div class="nav nav-pills">
                    <button class="nav-link tab-link" id="com" onclick="openTab(event, 'ComingRequests')">Coming Requests</button>
                    <button class="nav-link tab-link" onclick="openTab(event, 'ActiveRequests')">Active Requests</button>
                    <button class="nav-link tab-link" onclick="openTab(event, 'PastRequests')">Past Requests</button>
                </div>
                <div class="input-group">
                  <form method="post" id="filterForm">
                       
                        <label for="request_type" style=" color:#19315b;   font-weight: bold">Filter by Type:</label>
                        <select name="request_type" id="request_type" style="background-color:#ffff; width:10%; padding: 5px;">
                            <option value="">All Types</option>
                            <option value="Business Trip">Business Trip</option>
                            <option value="Cash Advance">Cash Advance</option>
                            <option value="Certificate">Certificate</option>
                            <option value="Leave">Leave</option>
                        </select>
                      <label for="status_filter" style="color:#19315b; font-weight: bold">Filter by Status:</label>
    <select name="status_filter" id="status_filter" style="background-color:#ffff; width:10%; padding: 5px;">
        <option value="">All Statuses</option>
        <option value="Pending">Pending</option>
        <option value="Revision Requested">Revision Requested</option>
        <option value="Approved">Approved</option>
        <option value="Rejected">Rejected</option>
    </select>
                        <label for="order_by" style=" color:#19315b;   font-weight: bold">Order by Date:</label>
                        <select name="order_by" id="order_by" style="background-color:#ffff; width:10%; padding: 5px;">
                            <option value="R.R_date DESC">Newest First</option>
                            <option value="R.R_date ASC">Oldest First</option>
                        </select>
                        <button type="submit" class="button-3" style="margin-left:20px">Apply</button>
                    </form>
                    </div>
               <div id="ComingRequests" class="tab-content">
    <h2>Coming Requests</h2>
    <?php if ($coming_requests && $coming_requests->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $coming_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['R_ID']; ?></td>
                <td><?php echo $row['E_name']; ?></td>
                <td><?php echo $row['R_Type']; ?></td>
                <td><?php echo $row['R_date']; ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="r_id" value="<?php echo $row['R_ID']; ?>">
                        <button type="submit" name="assign_to_me" class="button-3">Assign to me</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No Requests found.</p>
    <?php endif; ?>
</div>

                <div id="ActiveRequests" class="tab-content">
    <h2>Active Requests</h2>
    <?php if ($active_requests && $active_requests->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $active_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['R_ID']; ?></td>
                <td><?php echo $row['E_name']; ?></td>
                <td><?php echo $row['R_Type']; ?></td>
                <td><?php echo $row['R_date']; ?></td>
                <td><?php echo $row['R_status']; ?></td>
                <td>
                    <a href="AManage.php?r_id=<?php echo htmlspecialchars($row['R_ID']); ?>" class="view-button">View</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No Requests found.</p>
    <?php endif; ?>
</div>

               <div id="PastRequests" class="tab-content">
    <h2>Past Requests</h2>
    <?php if ($past_requests && $past_requests->num_rows > 0): ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Type</th>
                <th>Date</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
            <?php while ($row = $past_requests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $row['R_ID']; ?></td>
                <td><?php echo $row['E_name']; ?></td>
                <td><?php echo $row['R_Type']; ?></td>
                <td><?php echo $row['R_date']; ?></td>
                <td><?php echo $row['R_status']; ?></td>
                <td>
                    <form method="GET" action="AManage.php">
                        <input type="hidden" name="r_id" value="<?php echo $row['R_ID']; ?>">
                        <button type="submit" class="button-3">View</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p>No Requests found.</p>
    <?php endif; ?>
</div>

            </div>
        </div>
    </div>

    <script>
// Function to open a tab
function openTab(evt, tabName) {
    var i, tabcontent, tablinks;
    tabcontent = document.getElementsByClassName("tab-content");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }
    tablinks = document.getElementsByClassName("tab-link");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }
    document.getElementById(tabName).style.display = "block";
    if (evt) {
        evt.currentTarget.className += " active";
    }
}

// Function to set the default tab on page load
function setDefaultTab() {
    // Get all tab links
    var tablinks = document.getElementsByClassName("tab-link");
    
    // Activate the first tab by default
    if (tablinks.length > 0) {
        tablinks[0].click(); // Simulate a click on the first tab link
    }
}

// Set default tab when the page finishes loading
window.onload = function() {
    setDefaultTab();
}
  

    </script>
</body>
</html>

