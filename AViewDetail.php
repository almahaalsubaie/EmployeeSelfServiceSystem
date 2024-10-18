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


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request ID.");
}

if (!isset($_GET['employee_id']) || !is_numeric($_GET['employee_id'])) {
    die("Invalid employee ID.");
}

$request_id = intval($_GET['id']);
$employee_id = intval($_GET['employee_id']);


// Fetch request details using LEFT JOIN to handle NULL A_ID
$sql_request = "SELECT r.R_ID, r.R_Type, a.A_name, r.R_date, r.R_status, r.R_responseDate, r.RA_comment, r.RE_comment
                 FROM Requests r
                 LEFT JOIN HR_Admin a ON r.A_ID = a.A_ID
                 WHERE r.R_ID = ? AND r.E_ID = ?";
$stmt_request = $conn->prepare($sql_request);

if (!$stmt_request) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_request->bind_param("ii", $request_id, $employee_id);
$stmt_request->execute();
$result_request = $stmt_request->get_result();

if (!$result_request) {
    die("Execute statement failed: " . $stmt_request->error);
}

$request_details = $result_request->fetch_assoc();
$stmt_request->close();

$request_mapping = [
    'R_ID' => 'Request ID',
    'R_Type' => 'Request Type',
    'A_name' => 'Assigned To',
    'R_date' => 'Request Date',
    'R_status' => 'Status',
    'R_responseDate' => 'Response Date',
    'RA_comment' => 'Notes',
    'RE_comment' => 'Employee Comment'
];

// Initialize variables for additional details
$additional_details = [];
$additional_mapping = []; // This will hold the mapping array for additional details

switch ($request_details['R_Type']) {
    case 'Business Trip':
        $sql_additional = "SELECT B_destination, B_startDate, B_endDate, B_purpose, B_financialNeed, B_amount, B_file FROM BusinessTrip_Req WHERE R_ID = ?";
        $additional_mapping = [
            'B_destination' => 'Destination',
            'B_startDate' => 'Start Date',
            'B_endDate' => 'End Date',
            'B_purpose' => 'Purpose',
            'B_financialNeed' => 'Financial Support?',
            'B_amount' => 'Amount',
            'B_file' => 'Attachment'
        ];
        break;
    case 'Cash Advance':
        $sql_additional = "SELECT CA_reason, CA_amount, CA_file FROM CashAdvance_Req WHERE R_ID = ?";
        $additional_mapping = [
            'CA_reason' => 'Reason',
            'CA_amount' => 'Amount',
            'CA_file' => 'Attachment'
        ];
        break;
    case 'Certificate':
        $sql_additional = "SELECT C_type FROM Certificate_Req WHERE R_ID = ?";
        $additional_mapping = [
            'C_type' => 'Certificate Type'
        ];
        break;
    case 'Leave':
        $sql_additional = "SELECT L_type, L_startDate, L_endDate, L_report FROM Leave_Req WHERE R_ID = ?";
        $additional_mapping = [
            'L_type' => 'Leave Type',
            'L_startDate' => 'Start Date',
            'L_endDate' => 'End Date',
            'L_report' => 'Attachment'
        ];
        break;
        case 'Vacation':
        $sql_additional = "SELECT NuOfDays, VBalance, V_startDate, V_endDate FROM Vacation_Req WHERE R_ID = ?";
        $additional_mapping = [
            'NuOfDays' => 'Vacation Duration',
            'V_startDate' => 'Vacation Start Date',
            'V_endDate' => 'Vacation End Date',
            'VBalance' => 'Vacations Balance'
        ];
        break;
    default:
        $sql_additional = "";
        break;
}

if ($sql_additional) {
    $stmt_additional = $conn->prepare($sql_additional);

    if (!$stmt_additional) {
        die("Prepare statement failed: " . $conn->error);
    }

    $stmt_additional->bind_param("i", $request_id);
    $stmt_additional->execute();
    $result_additional = $stmt_additional->get_result();

    if ($result_additional) {
        $additional_details = $result_additional->fetch_assoc();
    }

    $stmt_additional->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Details</title>
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
                <h2 style="margin-left:500px; color:#224483">Request Details</h2>
             <a href="AViewEReq.php?employee_id=<?php echo htmlspecialchars($_GET['employee_id'] ?? ''); ?>" class="icon-button" style="margin-left: 20px;">
  <button class="back-button">
    <i class="fa fa-arrow-left"></i> 
  </button>
</a>

    <div class="TFcontainer" style=" overflow-y: auto;">
    
<?php if ($request_details) : ?>
    <table>
        <?php foreach ($request_mapping as $column_key => $column_label) : ?>
            <tr>
                <th><?php echo htmlspecialchars($column_label); ?></th>
                <td>
                    <?php
                  
                        echo htmlspecialchars($request_details[$column_key] ?? '');

                    ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if ($additional_details) : ?>
 <?php foreach ($additional_mapping as $column_key => $column_label) : ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($column_label); ?></th>
                                    <td>
                                        <?php
                                        // Custom display logic for financial need
                                        if ($column_key === 'B_financialNeed') {
                                            echo ($additional_details[$column_key] == 1) ? 'Yes' : 'No';
                                        } elseif ($column_key === 'B_amount') {
                                            if ($additional_details['B_financialNeed'] == 1) {
        echo htmlspecialchars($additional_details[$column_key] ?? '');
    } else {
        echo 'None';
    }
                                           
                                        }  elseif ($column_key === 'VBalance') {
                                            echo htmlspecialchars($additional_details[$column_key] ?? '') . ' Days';
                                        }
                                        
                                        elseif ($column_key === 'NuOfDays') {
                                            echo htmlspecialchars($additional_details[$column_key] ?? '') . ' Days';
                                        } elseif ($column_key === 'L_report' || $column_key === 'B_file' || $column_key === 'CA_file') {
                                            $pdfData = $additional_details[$column_key] ?? null;
                                            if ($pdfData) {
                                                $base64Pdf = base64_encode($pdfData);
                                                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="100%" height="500px"></iframe>';
                                            } else {
                                                echo 'No PDF available';
                                            }
                                        } else {
                                            echo htmlspecialchars($additional_details[$column_key] ?? 'None');
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
        <?php endif; ?>
    </table>
<?php else : ?>
    <p>No details found for this request.</p>
<?php endif; ?>
    </div>
        </div>
    </div>
</body>
</html>

