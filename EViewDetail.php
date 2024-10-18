<?php

require 'PHPMailer/src/Exception.php';
require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendMail($email, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'almahasubaie@gmail.com';
        $mail->Password   = 'bjci gkyr sfht glvw'; // Ensure this password is secured
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('almahasubaie@gmail.com', 'Employee Self-Service System');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail error: ' . $mail->ErrorInfo);
        return false;
    }
}
// Function to determine if a field is editable based on its key
function isEditable($column_key)
{
    // Define editable keys based on your business logic
    $editable_keys = ['B_destination', 'B_startDate', 'B_endDate', 'B_purpose', 'B_financialNeed', 'B_amount', 'L_type', 'L_startDate', 'L_endDate', 'CA_reason', 'CA_amount', 'C_type', 'RE_comment', 'CA_file', 'B_file','V_startDate','V_endDate']; // Add more keys as needed

    return in_array($column_key, $editable_keys);
}

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

// Check if the request ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid request ID.");
}

$request_id = intval($_GET['id']);

// Fetch request details using LEFT JOIN to handle NULL A_ID
$sql_request = "SELECT r.R_ID, r.R_Type, a.A_name, r.R_date, r.R_status, r.R_responseDate, r.RA_comment, r.RE_comment
                 FROM Requests r
                 LEFT JOIN HR_Admin a ON r.A_ID = a.A_ID
                 WHERE r.R_ID = ? AND r.E_ID = ?";
$stmt_request = $conn->prepare($sql_request);

if (!$stmt_request) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_request->bind_param("ii", $request_id, $user_id);
$stmt_request->execute();
$result_request = $stmt_request->get_result();

if (!$result_request) {
    die("Execute statement failed: " . $stmt_request->error);
}

$request_details = $result_request->fetch_assoc();
$stmt_request->close();

$editable = ($request_details['R_status'] == 'Revision Requested');

$request_mapping = [
    'R_ID' => 'Request ID',
    'R_Type' => 'Request Type',
    'A_name' => 'Assigned To',
    'R_date' => 'Request Date',
    'R_status' => 'Status',
    'R_responseDate' => 'Response Date',
    'RA_comment' => 'Notes',
      'RE_comment' => 'My Comment'
];

// Initialize variables for additional details
$additional_details = [];
$additional_mapping = []; // This will hold the mapping array for additional details
$editable_additional = false;
$v_balance = null;

// Fetch additional details based on request type and set appropriate mapping
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
        case 'Vacation':
        $sql_additional = "SELECT VBalance, V_startDate, V_endDate FROM Vacation_Req WHERE R_ID = ?";
        $additional_mapping = [
            'V_startDate' => 'Vacation Start Date',
            'V_endDate' => 'Vacation end Date',
             'VBalance' => 'Vacation Balance'
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
        $editable_additional = ($request_details['R_status'] == 'Revision Requested');
    }

    $stmt_additional->close();
}

// Handle form submission for editing additional details
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit']) && $editable_additional) {
    // Validate and update fields based on request type

     $errors = [];
    
    
   switch ($request_details['R_Type']) {
 case 'Business Trip':
            $new_destination = $_POST['B_destination']; 
            $new_amount = isset($_POST['B_amount']) ? $_POST['B_amount'] : null; 
            $new_purpose = $_POST['B_purpose']; 
            $new_start_date = $_POST['B_startDate']; 
            $new_end_date = $_POST['B_endDate'];
            $financial_need = isset($_POST['B_financialNeed']) ? 1 : 0;

            if (strtotime($new_end_date) < strtotime($new_start_date)) {
                $errors[] = "End date cannot be earlier than start date.";
            }     

            $uploadFile = '';
            if (!empty($_FILES['B_file']['name'])) {
                // Check for file upload errors
                if ($_FILES['B_file']['error'] !== UPLOAD_ERR_OK) {
                    $errors[] = "File upload error: " . $_FILES['B_file']['error'];
                }

                // Validate file size and type if necessary
                if ($_FILES['B_file']['size'] > 1000000) { // Example: 1MB limit
                    $errors[] = "File size exceeds the limit.";
                }

                // Define a predefined upload directory
                $uploadDir = 'uploads/';
                $uploadFile = $uploadDir . basename($_FILES['B_file']['name']);
                
                if (!move_uploaded_file($_FILES['B_file']['tmp_name'], $uploadFile)) {
                    $errors[] = "File upload failed.";
                }
            }

            if (empty($errors)) {
                $sql_update_business_trip = "UPDATE BusinessTrip_Req SET B_destination = ?, B_amount = ?, B_purpose = ?, B_startDate = ?, B_endDate = ?, B_file = ?, B_financialNeed = ? WHERE R_ID = ?";
                $stmt_update_business_trip = $conn->prepare($sql_update_business_trip);
                $stmt_update_business_trip->bind_param("sdsssisi", $new_destination, $new_amount, $new_purpose, $new_start_date, $new_end_date, $uploadFile, $financial_need, $request_id);

                if ($stmt_update_business_trip->execute()) {
                    echo "Business trip details updated successfully.";
                } else {
                    $errors[] = "Error updating business trip details: " . $stmt_update_business_trip->error;
                }

                $stmt_update_business_trip->close();
            }

            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo $error . "<br>";
                }
            }
            break;


        
    case 'Cash Advance':
        $new_reason = $_POST['CA_reason']; // Validate as needed
        $new_amount = $_POST['CA_amount']; // Validate as needed

        // Handling file upload for CA_file if present
        if (!empty($_FILES['CA_file']['name'])) {
            $uploadDir = 'uploads/cash_advance/';
            $uploadFile = $uploadDir . basename($_FILES['CA_file']['name']);

            if (move_uploaded_file($_FILES['CA_file']['tmp_name'], $uploadFile)) {
                // File uploaded successfully, update database
                $sql_update_cash_advance = "UPDATE CashAdvance_Req SET CA_reason = ?, CA_amount = ?, CA_file = ? WHERE R_ID = ?";
                $stmt_update_cash_advance = $conn->prepare($sql_update_cash_advance);
                $stmt_update_cash_advance->bind_param("sdi", $new_reason, $new_amount, $uploadFile, $request_id);
                $stmt_update_cash_advance->execute();
                $stmt_update_cash_advance->close();
            } else {
                echo "File upload failed.";
            }
        } else {
            // No file uploaded, update only other fields
            $sql_update_cash_advance = "UPDATE CashAdvance_Req SET CA_reason = ?, CA_amount = ? WHERE R_ID = ?";
            $stmt_update_cash_advance = $conn->prepare($sql_update_cash_advance);
            $stmt_update_cash_advance->bind_param("sdi", $new_reason, $new_amount, $request_id);
            $stmt_update_cash_advance->execute();
            $stmt_update_cash_advance->close();
        }
        break;

    case 'Certificate':
        $new_type = $_POST['C_type']; // Validate as needed

        $sql_update_certificate = "UPDATE Certificate_Req SET C_type = ? WHERE R_ID = ?";
        $stmt_update_certificate = $conn->prepare($sql_update_certificate);
        $stmt_update_certificate->bind_param("si", $new_type, $request_id);
        $stmt_update_certificate->execute();
        $stmt_update_certificate->close();

        // Repeat for other fields of Certificate
        break;
   case 'Vacation':
    // Fetch the current vacation request details
    $sql_select_vacation = "SELECT V_startDate, V_endDate FROM Vacation_Req WHERE R_ID = ?";
    $stmt_select_vacation = $conn->prepare($sql_select_vacation);
    $stmt_select_vacation->bind_param("i", $request_id);
    $stmt_select_vacation->execute();
    $result_vacation = $stmt_select_vacation->get_result();
    $vacation_details = $result_vacation->fetch_assoc();
    $stmt_select_vacation->close();

    if (!$vacation_details) {
        die("Vacation request not found.");
    }

    // Get new values from the form
    $new_startDate = $_POST['V_startDate'];
    $new_endDate = $_POST['V_endDate'];

    if (empty($new_startDate) || empty($new_endDate)) {
        throw new Exception("Start date and End date are required.");
    }

    $startDateTime = new DateTime($new_startDate);
    $endDateTime = new DateTime($new_endDate);

    if ($endDateTime < $startDateTime) {
        throw new Exception("End date cannot be earlier than start date.");
    }

    $interval = $startDateTime->diff($endDateTime);
    $new_VNuOfDays = $interval->days + 1; // +1 to include the end day

    // Fetch the current vacation balance
    $sql_v_balance = "SELECT VBalance FROM Employee WHERE E_ID = ?";
    $stmt_v_balance = $conn->prepare($sql_v_balance);
    $stmt_v_balance->bind_param("i", $user_id);
    $stmt_v_balance->execute();
    $result_v_balance = $stmt_v_balance->get_result();
    $v_balance = $result_v_balance->fetch_assoc()['VBalance'];
    $stmt_v_balance->close();

    if ($v_balance === false) {
        die("User not found.");
    }

    // Update the vacation request
    $sql_update_vacation = "UPDATE Vacation_Req SET V_startDate = ?, V_endDate = ? WHERE R_ID = ?";
    $stmt_update_vacation = $conn->prepare($sql_update_vacation);
    $stmt_update_vacation->bind_param("ssi", $new_startDate, $new_endDate, $request_id);
    if (!$stmt_update_vacation->execute()) {
        die("Error updating Vacation request: " . $conn->error);
    }
    $stmt_update_vacation->close();

    // Optionally, you may still want to check if the updated days are within the available balance
    if ($v_balance < $new_VNuOfDays) {
        echo "Warning: Insufficient vacation balance.";
    }
    break;


    case 'Leave':
        $new_type = $_POST['L_type']; // Validate as needed
        $new_startDate = $_POST['L_startDate']; // Validate as needed
        $new_endDate = $_POST['L_endDate']; // Validate as needed
        $new_report = $_POST['L_report']; // Validate as needed - handle file upload separately if needed

        // Handling file upload for L_report if present
        if (!empty($_FILES['L_report']['name'])) {
            $uploadDir = 'uploads/leave/';
            $uploadFile = $uploadDir . basename($_FILES['L_report']['name']);

            if (move_uploaded_file($_FILES['L_report']['tmp_name'], $uploadFile)) {
                // File uploaded successfully, update database
                $sql_update_leave = "UPDATE Leave_Req SET L_type = ?, L_startDate = ?, L_endDate = ?, L_report = ? WHERE R_ID = ?";
                $stmt_update_leave = $conn->prepare($sql_update_leave);
                $stmt_update_leave->bind_param("ssssi", $new_type, $new_startDate, $new_endDate, $uploadFile, $request_id);
                $stmt_update_leave->execute();
                $stmt_update_leave->close();
            } else {
                echo "File upload failed.";
            }
        } else {
            // No file uploaded, update only other fields
            $sql_update_leave = "UPDATE Leave_Req SET L_type = ?, L_startDate = ?, L_endDate = ? WHERE R_ID = ?";
            $stmt_update_leave = $conn->prepare($sql_update_leave);
            $stmt_update_leave->bind_param("sssi", $new_type, $new_startDate, $new_endDate, $request_id);
            $stmt_update_leave->execute();
            $stmt_update_leave->close();
        }
        break;

    default:
        // Handle other cases or provide an error message
        break;
}


    // Update request status after submission
    $new_r_status = 'Pending'; 
    $new_comment = $_POST['RE_comment'] ?? '';
    $sql_update_r_status = "UPDATE Requests SET R_status = ?, R_responseDate = NULL, RE_comment = ? WHERE R_ID = ?";
    $stmt_update_r_status = $conn->prepare($sql_update_r_status);
    $stmt_update_r_status->bind_param("ssi", $new_r_status, $new_comment, $request_id);
    $stmt_update_r_status->execute();
    $stmt_update_r_status->close();

    
$sql_request = "SELECT a.A_name, a.A_email, a.A_isNotifiEnabled, r.R_ID, r.R_Type 
                FROM Requests r 
                LEFT JOIN HR_Admin a ON r.A_ID = a.A_ID 
                WHERE r.R_ID = ?";
$stmt_request = $conn->prepare($sql_request);
$stmt_request->bind_param("i", $request_id);
$stmt_request->execute();
$result_request = $stmt_request->get_result();
$request_data = $result_request->fetch_assoc();
$stmt_request->close();

// Extract details
$a_name = $request_data['A_name'];
$a_email = $request_data['A_email'];
$a_isNotifiEnabled = $request_data['A_isNotifiEnabled'];
$r_id = $request_data['R_ID'];
$r_type = $request_data['R_Type'];

// Prepare email content
$subject = "Request Resubmission Notification";
$message = "Dear $a_name,<br><br>The request with ID #$r_id and type: $r_type, has been resubmitted and its status is now 'Pending'.";

// Check if notifications are enabled and status is 'Pending' before sending the email
if ($a_isNotifiEnabled && $new_r_status === 'Pending') {
    if (sendMail($a_email, $subject, $message)) {
        // Email sent successfully
        echo "Notification email sent to $a_email.";
    } else {
        // Handle email failure
        error_log('Failed to send email to: ' . $a_email);
    }
} else {
    if (!$a_isNotifiEnabled) {
        error_log('Email notifications are disabled for: ' . $a_email);
    }
    if ($new_r_status !== 'Pending') {
        error_log('Request status is not "Pending".');
    }
}

// Redirect to avoid resubmission on page refresh
header("Location: ".$_SERVER['PHP_SELF']."?id=".$request_id);
exit();

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
    
    <script>
        function toggleAmountVisibility() {
    var financialNeedChecked = document.getElementById('B_financialNeed').checked;
    var amountInput = document.getElementById('B_amount');
    amountInput.style.display = financialNeedChecked ? 'inline' : 'none';
}
    </script>
    
    
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
            <h2 style="margin-left: 500px; color: #224483">Request Details</h2>
            <a href="ViewRequests.php" class="icon-button" style="margin-left: 20px;">
                <button class="back-button">
                    <i class="fa fa-arrow-left"></i> 
                </button>
            </a>
            <div class="TFcontainer" style="  flex: 1;
            overflow-y: auto;
            padding: 20px;">
                <?php if ($request_details) : ?>
                    <form method="post" action="" enctype="multipart/form-data"  >
                        <table >
                            <?php foreach ($request_mapping as $column_key => $column_label) : ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($column_label); ?></th>
                                    <td>
                                        <?php echo htmlspecialchars($request_details[$column_key] ?? ''); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>

                             <?php if ($additional_details) : ?>
            <?php foreach ($additional_mapping as $column_key => $column_label) : ?>
                <tr>
                    <th><?php echo htmlspecialchars($column_label); ?></th>
                    <td>
                        <?php
                        // Check if the field is editable
                        $editable = ($request_details['R_status'] == 'Revision Requested') && isEditable($column_key);

                        // Display logic based on whether the field is editable
                        if ($editable) {
                            // Display editable format
                           switch ($column_key) {
      case 'B_financialNeed':
    // Render the checkbox
    echo '<input type="checkbox" name="B_financialNeed" id="B_financialNeed" ' . (($additional_details['B_financialNeed'] ?? 0) ? 'checked' : '') . ' onclick="toggleAmountVisibility(); updateLabelText()">';
    
    // Render the label
    $labelText = ($additional_details['B_financialNeed'] ?? 0) ? 'Yes' : 'No';
    echo '<label for="B_financialNeed" id="B_financialNeedLabel">' . $labelText . '</label>';
    
    // Include JavaScript to handle label text update
    echo '<script>
    function updateLabelText() {
        var checkbox = document.getElementById("B_financialNeed");
        var label = document.getElementById("B_financialNeedLabel");
        if (checkbox.checked) {
            label.innerText = "Yes";
        } else {
            label.innerText = "No";
        }
    }
    </script>';
    break;


                                                    case 'B_amount':
                                                        // Display amount only if financial need is checked
                                                        if (($additional_details['B_financialNeed'] ?? 0)) {
                                                            echo '<input type="number" step="0.01" name="' . $column_key . '" id="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '">';
                                                        } else {
                                                            echo '<input type="number" step="0.01" name="' . $column_key . '" id="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '" style="display:none;">';
                                                        }
                                                        break;

        case 'L_report':
        case 'B_file':
        case 'CA_file':
            $pdfData = $additional_details[$column_key] ?? null;
            if ($pdfData) {
                // Convert binary data to base64 format
                $base64Pdf = base64_encode($pdfData);
                // Output an iframe to display the PDF
                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="100%" height="500px"></iframe>';
            } else {
                echo 'No Attachments <br>';
            }
            echo '<input type="file" style="background-color:#ffff; width:40%" id="' . $column_key . '" name="' . $column_key . '" accept=".pdf,.doc,.docx,.txt">';
            break;

        case 'B_destination':
        case 'L_destination':
            echo '<input type="text" name="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '">';
            break;

        case 'B_startDate':
        case 'L_startDate':
        case 'L_endDate':
        case 'B_endDate':
                 case 'V_startDate':
        case 'V_endDate':                      
            echo '<input type="date" name="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '">';
            break;

        case 'B_purpose':
        case 'CA_reason':
            echo '<textarea name="' . $column_key . '">' . htmlspecialchars($additional_details[$column_key] ?? '') . '</textarea>';
            break;

      
        case 'CA_amount':
            echo '<input type="number" step="0.01" name="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '">';
            break;

        case 'L_type':
            $options = ['Sick', 'Unpaid']; // Define your enum options here
            echo '<select name="L_type">';
            foreach ($options as $option) {
                $selected = ($additional_details['L_type'] ?? '') === $option ? 'selected' : '';
                echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
            }
            echo '</select>';
            break;

        case 'C_type':
            $options = ['Salary', 'Experience']; // Define your enum options here
            echo '<select name="C_type">';
            foreach ($options as $option) {
                $selected = ($additional_details['C_type'] ?? '') === $option ? 'selected' : '';
                echo '<option value="' . $option . '" ' . $selected . '>' . $option . '</option>';
            }
            echo '</select>';
            break;

        default:
            // Handle other editable fields
            echo '<input type="text" name="' . $column_key . '" value="' . htmlspecialchars($additional_details[$column_key] ?? '') . '">';
    }
} else {
    // Display non-editable format
    switch ($column_key) {
        case 'B_financialNeed':
                           echo ($additional_details[$column_key] == 1) ? 'Yes' : 'No';
                            break; 
            

                        case 'B_amount':
                           if (($additional_details['B_financialNeed'] ?? 0)) {
                                                        echo htmlspecialchars($additional_details[$column_key] ?? '');
                                                    } else {
                                                        echo '<span style="display:none;">' . htmlspecialchars($additional_details[$column_key] ?? '') . '</span>';
                                                    }
                            break;
            
              case 'VBalance':
                       echo htmlspecialchars($additional_details[$column_key] ?? '') . ' Days';

                            break;
            

        case 'L_report':
        case 'B_file':
        case 'CA_file':
            $pdfData = $additional_details[$column_key] ?? null;
            if ($pdfData) {
                // Convert binary data to base64 format
                $base64Pdf = base64_encode($pdfData);
                // Output an iframe to display the PDF
                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="100%" height="500px"></iframe>';
            } else {
                echo 'No PDF available';
            }
            break;
            
    

        default:
            echo htmlspecialchars($additional_details[$column_key] ?? '');
    }
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if ($request_details['R_status'] == 'Revision Requested') : ?>
             
                            <tr>
                            <th>
                               <label for="RE_comment" style="  font-weight: bold"> Comments:</label>  
                            </th>
                            <td>
                                <textarea id="RE_comment" name="RE_comment" rows="6" cols="70" style="background-color:#ffff; width:auto; hight:auto"></textarea> 
                                <br>
                                 <button type="submit" name="submit" class="view-button" style="margin-left: 145px;">Save Changes</button>
                            </td>
                            </tr>
                            
            <?php endif; ?>
        <?php endif; ?>
    </table>
<?php else : ?>
    <p>No details found for this request.</p>
<?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>

