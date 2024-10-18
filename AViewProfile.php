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

// Query to fetch employee information
$stmt = $pdo->prepare('SELECT * FROM Employee WHERE E_ID = :employee_id');
$stmt->execute(['employee_id' => $employee_id]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if employee exists
if (!$employee) {
    // Redirect or show an error message if employee does not exist
    header('Location: viewEmployees.php'); // Redirect to the main page or wherever appropriate
    exit;
}
function safeOutput($value) {
    return isset($value) ? htmlspecialchars($value) : '';
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Profile</title>
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
    <h2 style="margin-left:500px; color:#224483">Employee Profile</h2>
       <a href="viewEmployees.php" class="icon-button" style="margin-left: 20px;">
  <button class="back-button">
    <i class="fa fa-arrow-left"></i>
  </button>
</a>

           <div class="TFcontainer">

 <form method="post" action="updateEmployee.php">
                    <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['E_ID']); ?>">
                    
                    <table>
                        <tr>
                            <td><strong>ID:</strong></td>
                            <td><?php echo htmlspecialchars($employee['E_ID']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td><?php echo safeOutput($employee['E_name']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Birth Date:</strong></td>
                            <td><?php echo safeOutput($employee['E_birthDate']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Email:</strong></td>
                            <td><input type="email" name="E_email" value="<?php echo safeOutput($employee['E_email']); ?>" required></td>
                        </tr>
                        <tr>
                            <td><strong>Phone:</strong></td>
                            <td>0<?php echo safeOutput($employee['E_phone']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Hire Date:</strong></td>
                            <td><?php echo safeOutput($employee['E_hireDate']); ?></td>
                        </tr>
                        <tr>
                            <td><strong>Department:</strong></td>
                            <td>     <select name="E_department" required>
                                    <option value="IT" <?php echo $employee['E_department'] == 'IT' ? 'selected' : ''; ?>>IT</option>
                                    <option value="Marketing" <?php echo $employee['E_department'] == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                    <option value="Sales" <?php echo $employee['E_department'] == 'Sales' ? 'selected' : ''; ?>>Sales</option>
                                    <option value="Operations" <?php echo $employee['E_department'] == 'Operations' ? 'selected' : ''; ?>>Operations</option>
                                    <option value="Finance" <?php echo $employee['E_department'] == 'Finance' ? 'selected' : ''; ?>>Finance</option>
                                </select></td>
                        </tr>
                        <tr>
                            <td><strong>Position:</strong></td>
                            <td><input type="text" name="E_position" value="<?php echo safeOutput($employee['E_position']); ?>" required></td>
                        </tr>
                         <tr>
                            <td><strong>Vacation Balance:</strong></td>
                            <td><?php echo safeOutput($employee['VBalance']).' Days'; ?></td>
                        </tr>
                        <tr>
                            <td><strong>Basic Salary:</strong></td>
                            <td><input type="number" name="E_basicSalary" value="<?php echo safeOutput($employee['E_basicSalary']); ?>" step="0.01" required></td>
                        </tr>
                        <tr>
                            <td><strong>Housing Allowance:</strong></td>
                            <td><input type="number" name="E_housingAllow" value="<?php echo safeOutput($employee['E_housingAllow']); ?>" step="0.01"></td>
                        </tr>
                        <tr>
                            <td><strong>Transport Allowance:</strong></td>
                            <td><input type="number" name="E_transportAllow" value="<?php echo safeOutput($employee['E_transportAllow']); ?>" step="0.01"></td>
                        </tr>
                    </table>
                    
                    <br>
                    <button type="submit"  style="margin-left:420px" class="view-button">Update Account</button>
                </form>

               <br><br>
               <form method="post" action="deleteE.php" style="margin-left:420px">
        <input type="hidden" name="employee_id" value="<?php echo htmlspecialchars($employee['E_ID']); ?>">
        <button type="submit" class="view-button-R" onclick="return confirm('Are you sure you want to delete this account?');">
            Delete Account
        </button>
    </form>
   
</div>

        </div>
    </div>
</body>
</html>
