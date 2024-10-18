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

// Initialize filter and order variables
$department_filter = isset($_POST['department']) ? $_POST['department'] : '';
$order_by = isset($_POST['order_by']) ? $_POST['order_by'] : 'E_name';
$order_dir = isset($_POST['order_dir']) ? $_POST['order_dir'] : 'ASC'; // Default order direction
$search_name = isset($_POST['search_name']) ? $_POST['search_name'] : '';

// Prepare SQL query with filter and order
$sql = "SELECT E_ID, E_name, E_position, E_department, E_hireDate 
        FROM Employee 
        WHERE 1=1";

if ($department_filter) {
    $sql .= " AND E_department = '" . $conn->real_escape_string($department_filter) . "'";
}

if ($search_name) {
    $sql .= " AND E_name LIKE '%" . $conn->real_escape_string($search_name) . "%'";
}

$sql .= " ORDER BY";

if ($order_by == 'hire_date') {
    $sql .= " E_hireDate $order_dir";
} else {
    $sql .= " E_name $order_dir"; // Default ordering by name
}

$result = $conn->query($sql);

if (!$result) {
    die("Error fetching employees: " . $conn->error);
}

$employees = $result->fetch_all(MYSQLI_ASSOC);

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
            <br>
             <h2 style="margin-left:500px; color:#224483">Employees Management</h2>
    <div class="TFcontainer" style="  flex: 1;
            overflow-y: auto;
            padding: 20px;">
          <div style="margin-left: 1060px;">  <a href="EmployeeRegistration.php"><button class="view-button" >New Employee</button></a></div>
        <div class="input-group">   
            <!-- Filter and Order Form -->
 <form method="POST" action="">
       <label for="search_name" style="color:#19315b; font-weight: bold">Search by Name:</label>
    <input type="text" id="search_name" name="search_name" value="<?php echo htmlspecialchars($search_name); ?>" style="background-color:#ffff; width:10%; padding: 5px;">
    <label for="department" style=" color:#19315b;   font-weight: bold">Department:</label>
    <select id="department" name="department" style="background-color:#ffff; width:10%; padding: 5px;">
        <option value="">All</option>
        <option value="Marketing" <?php echo $department_filter === 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
        <option value="IT" <?php echo $department_filter === 'IT' ? 'selected' : ''; ?>>IT</option>
        <option value="Sales" <?php echo $department_filter === 'Sales' ? 'selected' : ''; ?>>Sales</option>
        <option value="Finance" <?php echo $department_filter === 'Finance' ? 'selected' : ''; ?>>Finance</option>
         <option value="Operations" <?php echo $department_filter === 'Operations' ? 'selected' : ''; ?>>Operations</option>
    </select>

    <label for="order_by" style=" color:#19315b;   font-weight: bold">Order By:</label>
    <select id="order_by" name="order_by" style="background-color:#ffff; width:10%; padding: 5px;">
        <option value="name" <?php echo $order_by === 'name' ? 'selected' : ''; ?>>Name</option>
        <option value="hire_date" <?php echo $order_by === 'hire_date' ? 'selected' : ''; ?>>Hire Date</option>
    </select>

    <label for="order_dir" style=" color:#19315b;   font-weight: bold">Order:</label>
    <select id="order_dir" name="order_dir" style="background-color:#ffff; width:10%; padding: 5px;">
        <option value="ASC" <?php echo $order_dir === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
        <option value="DESC" <?php echo $order_dir === 'DESC' ? 'selected' : ''; ?>>Descending</option>
    </select>

    <button type="submit" class="button-3" style="margin-left:20px">Apply</button>
</form>
    </div>     


            
            <!-- Display Employee Table -->
 <table class="ViewEmployee" style=" overflow-y: auto;">
    <thead>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Position</th>
            <th>Department</th>
            <th>Options</th>
        </tr>
    </thead>
    <tbody>
        <?php if (count($employees) > 0): ?>
            <?php foreach ($employees as $employee): ?>
                <tr>
                    <td><?php echo htmlspecialchars($employee['E_ID']); ?></td>
                    <td><?php echo htmlspecialchars($employee['E_name']); ?></td>
                    <td><?php echo htmlspecialchars($employee['E_position']); ?></td>
                    <td><?php echo htmlspecialchars($employee['E_department']); ?></td>
                    <td >
    <a href="AViewProfile.php?employee_id=<?php echo $employee['E_ID']; ?>" title="View Profile">
        <button class="icon-button" style="margin-left:40px"><i class="fa fa-user" ></i></button>
    </a>
    <a href="AViewEReq.php?employee_id=<?php echo $employee['E_ID']; ?>" title="View Requests">
        <button class="icon-button" style="margin-left:20px"><i class="fa fa-list"></i></button>
    </a>
</td>

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No employees found.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

        </div>
            </div>
    </div>
</body>
</html>

