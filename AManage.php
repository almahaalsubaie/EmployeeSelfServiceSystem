<?php
session_start();
include 'db_con.php';

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
        $mail->Username   = 'email@gmail.com';
        $mail->Password   = 'password'; // GOOGLE Paswword/ Ensure this password is secured
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('email@gmail.com', 'Employee Self-Service System');
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

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: otplogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$status = '';

if (isset($_POST['status'])) {
    $status = $_POST['status'];
    $r_id = $_POST['r_id'] ?? null;

    if ($r_id) {
        // Fetch request details
        $sql = "SELECT R.R_Type, E.E_email, E.E_name, E_isNotifiEnabled, E.E_ID 
                FROM Requests R 
                JOIN Employee E ON R.E_ID = E.E_ID 
                WHERE R.R_ID = ? AND R.A_ID = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            error_log('Prepare statement failed: ' . $conn->error);
            exit();
        }
        $stmt->bind_param('ii', $r_id, $user_id);
        if (!$stmt->execute()) {
            error_log('SQL execution error: ' . $stmt->error);
        } else {
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
        }
        $stmt->close();

        $isNotifiEnabled = $request['E_isNotifiEnabled'];
        $email = $request['E_email'];
        $recipient_name = $request['E_name'];

        if ($status === "Revision Requested") {
            $revision_comment = $_POST['RA_comment'] ?? '';
            $sql = "UPDATE Requests SET R_status = ?, R_responseDate = NOW(), RA_comment = ? WHERE R_ID = ? AND A_ID = ?";
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                error_log('Prepare statement failed: ' . $conn->error);
                exit();
            }
            $stmt->bind_param('ssii', $status, $revision_comment, $r_id, $user_id);
            if (!$stmt->execute()) {
                error_log('SQL execution error: ' . $stmt->error);
            }
            $stmt->close();

            $subject = 'Revision Requested';
            $message = "Dear {$recipient_name},<br><br>";
            $message .= "Your request ID #{$r_id}, Type: '{$request['R_Type']}', needs revision.\n\n";
            if (!empty($revision_comment)) {
                $message .= "Notes: {$revision_comment}\n";
            } else {
                $message .= "Please review and make necessary changes.\n";
            }

            if ($isNotifiEnabled) {
                sendMail($email, $subject, $message);
            } else {
                error_log('Email notifications are disabled for: ' . $email);
            }
        } elseif ($status === "Approved") {
            $sql_update_request = "UPDATE Requests SET R_status = ?, R_responseDate = NOW() WHERE R_ID = ? AND A_ID = ?";
            $stmt_update_request = $conn->prepare($sql_update_request);
            if (!$stmt_update_request) {
                error_log('Prepare statement failed: ' . $conn->error);
                exit();
            }
            $stmt_update_request->bind_param('sii', $status, $r_id, $user_id);
            if (!$stmt_update_request->execute()) {
                error_log('SQL execution error: ' . $stmt_update_request->error);
            }
            $stmt_update_request->close();

            $subject = 'Request Approved';
            $message = "Dear {$recipient_name},<br><br>";
            $message .= "Your request ID #{$r_id}, Type: {$request['R_Type']} has been approved.\n\n";

            if ($request['R_Type'] === "Vacation") {
                $sql_fetch_vacation = "SELECT NuOfDays FROM Vacation_Req WHERE R_ID = ?";
                $stmt_fetch_vacation = $conn->prepare($sql_fetch_vacation);
                if (!$stmt_fetch_vacation) {
                    error_log('Prepare statement failed: ' . $conn->error);
                    exit();
                }
                $stmt_fetch_vacation->bind_param('i', $r_id);
                if (!$stmt_fetch_vacation->execute()) {
                    error_log('SQL execution error: ' . $stmt_fetch_vacation->error);
                } else {
                    $stmt_fetch_vacation->bind_result($nu_of_days);
                    $stmt_fetch_vacation->fetch();
                }
                $stmt_fetch_vacation->close();

                if ($nu_of_days > 0) {
                    $e_id = $request['E_ID'];
                    $sql_update_employee = "UPDATE Employee SET VBalance = VBalance - ? WHERE E_ID = ?";
                    $stmt_update_employee = $conn->prepare($sql_update_employee);
                    if (!$stmt_update_employee) {
                        error_log('Prepare statement failed: ' . $conn->error);
                        exit();
                    }
                    $stmt_update_employee->bind_param('ii', $nu_of_days, $e_id);
                    if (!$stmt_update_employee->execute()) {
                        error_log('SQL execution error: ' . $stmt_update_employee->error);
                    } else {
                        if ($stmt_update_employee->affected_rows > 0) {
                            error_log("Vacation balance decremented successfully for employee ID: {$e_id}");
                        } else {
                            error_log("Failed to decrement vacation balance for employee ID: {$e_id}. No rows affected.");
                        }
                    }
                    $stmt_update_employee->close();
                } else {
                    error_log('Invalid number of days requested.');
                }
            }

            if ($isNotifiEnabled) {
                sendMail($email, $subject, $message);
            } else {
                error_log('Email notifications are disabled for: ' . $email);
            }
        } else {
            // Handle other statuses, excluding 'Pending'
            $email_subject = '';
            $email_message = '';

            switch ($status) {
                case 'Rejected':
                    $email_subject = 'Request Rejected';
                    $email_message = "Dear {$recipient_name},<br><br>";
                    $email_message .= "Your request ID #{$r_id}, Type: {$request['R_Type']} has been rejected.\n\n";
                    break;
                // Additional cases can be added here if necessary
                default:
                    break;
            }

            if ($email_subject && $email_message && $isNotifiEnabled) {
                sendMail($email, $email_subject, $email_message);
            } elseif (!$isNotifiEnabled) {
                error_log('Email notifications are disabled for: ' . $email);
            }
        }

        header("Location: AView.php");
        exit();
    }
}

$r_id = $_GET['r_id'] ?? null;

if ($r_id) {
    $sql = "SELECT R.*, E.E_email, E.E_name 
            FROM Requests R 
            JOIN Employee E ON R.E_ID = E.E_ID 
            WHERE R.R_ID = ? AND R.A_ID = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        error_log('Prepare statement failed: ' . $conn->error);
        exit();
    }
    
    $stmt->bind_param('ii', $r_id, $user_id);
    if (!$stmt->execute()) {
        error_log('SQL execution error: ' . $stmt->error);
    } else {
        $result = $stmt->get_result();
        if ($result) {
            $request = $result->fetch_assoc();
        } else {
            error_log('Failed to get result: ' . $stmt->error);
        }
    }
    $stmt->close();
} else {
    $request = [];
}

$is_active = isset($request['R_status']) && in_array($request['R_status'], ['Revision Requested', 'Pending']);

$additional_details = [];
$additional_mapping = [];

switch ($request['R_Type'] ?? '') {
    case 'Business Trip':
        $sql_additional = "SELECT B_destination, B_startDate, B_endDate, B_purpose, B_financialNeed, B_amount, B_file 
                           FROM BusinessTrip_Req 
                           WHERE R_ID = ?";
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
        $sql_additional = "SELECT CA_reason, CA_amount, CA_file 
                           FROM CashAdvance_Req 
                           WHERE R_ID = ?";
        $additional_mapping = [
            'CA_reason' => 'Reason',
            'CA_amount' => 'Amount',
            'CA_file' => 'Attachment'
        ];
        break;
    case 'Certificate':
        $sql_additional = "SELECT C_type 
                           FROM Certificate_Req 
                           WHERE R_ID = ?";
        $additional_mapping = [
            'C_type' => 'Certificate Type'
        ];
        break;
    case 'Leave':
        $sql_additional = "SELECT L_type, L_startDate, L_endDate, L_report 
                           FROM Leave_Req 
                           WHERE R_ID = ?";
        $additional_mapping = [
            'L_type' => 'Leave Type',
            'L_startDate' => 'Start Date',
            'L_endDate' => 'End Date',
            'L_report' => 'Attachment'
        ];
        break;
    case 'Vacation':
        $sql_additional = "SELECT NuOfDays, VBalance, V_startDate, V_endDate 
                           FROM Vacation_Req 
                           WHERE R_ID = ?";
        $additional_mapping = [
            'NuOfDays' => 'Vacation Duration',
              'V_startDate' => 'Vacation Start Date',
            'V_endDate' => 'Vacation End Date',
            'VBalance' => 'Vacation Balance'
        ];
        break;
    default:
        break;
}

if (!empty($sql_additional)) {
    $stmt_additional = $conn->prepare($sql_additional);
    if (!$stmt_additional) {
        error_log('Prepare statement failed: ' . $conn->error);
        exit();
    }
    $stmt_additional->bind_param("i", $r_id);
    if (!$stmt_additional->execute()) {
        error_log('SQL execution error: ' . $stmt_additional->error);
    } else {
        $result_additional = $stmt_additional->get_result();
        if ($result_additional) {
            $additional_details = $result_additional->fetch_assoc();
        }
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
    <title>Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="dashboard-container" style="  display: flex;
            height: 100vh;">
        <div class="sidebar">
            <div class="homeLogo-container">
                <img src="logo.png" alt="Company Logo" class="homeLogo">
            </div>
            <nav>
                <ul>
                    <li><a href="AHome.php"><i class="fas fa-home icon"></i><span class="label">Home</span></a></li>
                    <li><a href="AView.php"><i class="fas fa-envelope icon"></i><span class="label">Requests</span></a></li>
                    <li><a href="viewEmployees.php"><i class="fas fa-users icon"></i><span class="label">Employees</span></a></li>
                    <li><a href="profile.php"><i class="fas fa-user icon"></i><span class="label">My Profile</span></a></li>
                    <li><br></li>
                    <li><br></li>
                    <li><a href="ASettings.php"><i class="fas fa-cog icon"></i><span class="label">Settings</span></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt icon"></i><span class="label">Logout</span></a></li>
                </ul>
            </nav>
        </div>
        <div class="main-content" >
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <h2 style="margin-left: 500px; color:#224483">Request Details</h2>
            <a href="AView.php" class="icon-button" style="margin-left: 20px;">
                <button class="back-button">
                    <i class="fa fa-arrow-left"></i>
                </button>
            </a>
            <div class="TFcontainer" style="  flex: 1;
            overflow-y: auto;
            padding: 20px;">
                
                <?php if ($request) : ?>
                    <table>
                        <tr>
                            <th>Request ID</th>
                            <td><?php echo htmlspecialchars($request['R_ID'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Employee Name</th>
                            <td><?php echo htmlspecialchars($request['E_name'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Request Type</th>
                            <td><?php echo htmlspecialchars($request['R_Type'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Request Date</th>
                            <td><?php echo htmlspecialchars($request['R_date'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Status</th>
                            <td><?php echo htmlspecialchars($request['R_status'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Response Date</th>
                            <td><?php echo htmlspecialchars($request['R_responseDate'] ?? ''); ?></td>
                        </tr>
                         <tr>
                         <tr>
                            <th>Notes</th>
                            <td><?php echo htmlspecialchars($request['RA_comment'] ?? ''); ?></td>
                        </tr>
                        <tr>
                            <th>Employee Comment</th>
                            <td><?php echo htmlspecialchars($request['RE_comment'] ?? ''); ?></td>
                        </tr>
                    </table>
<br>
                    <?php if (!empty($additional_details)) : ?>
                        <table>
                            <?php foreach ($additional_mapping as $column_key => $column_label) : ?>
                                <tr>
                                    <th><?php echo htmlspecialchars($column_label); ?></th>
                                    <td>
                                        <?php
                                        // Custom display logic for specific fields
                                        if ($column_key === 'B_financialNeed') {
                                            echo ($additional_details[$column_key] == 1) ? 'Yes' : 'No';
                                        }elseif ($column_key === 'B_amount') {
    // Check the financial need first
    if (isset($additional_details['B_financialNeed']) && $additional_details['B_financialNeed'] == 0) {
        echo 'None'; // If financial need is 'No', set amount to 'None'
    } else {
        echo htmlspecialchars($additional_details[$column_key] ?? 'None');
    }
}
                                        
                                        elseif ($column_key === 'L_report') {
                                            // Assuming this is for displaying a PDF report
                                            $pdfData = $additional_details[$column_key];
                                            if ($pdfData) {
                                                // Convert binary data to base64 format
                                                $base64Pdf = base64_encode($pdfData);
                                                // Output an iframe to display the PDF
                                                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="90%" height="300px"></iframe>';
                                            } else {
                                                echo 'No PDF Provided';
                                            }
                                        }
                                         elseif($column_key === 'NuOfDays') {
                                            echo htmlspecialchars($additional_details[$column_key] ?? '') . ' Days';
                                            
                                        }
                                        elseif($column_key === 'VBalance') {
                                            echo htmlspecialchars($additional_details[$column_key] ?? '') . ' Days';
                                            
                                        }
                                        elseif ($column_key === 'B_file') {
                                            // Assuming this is for displaying a PDF report
                                            $pdfData = $additional_details[$column_key];
                                            if ($pdfData) {
                                                // Convert binary data to base64 format
                                                $base64Pdf = base64_encode($pdfData);
                                                // Output an iframe to display the PDF
                                                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="90%" height="300px"></iframe>';
                                            } else {
                                                echo 'No PDF Provided';
                                            }
                                        }
                                        elseif ($column_key === 'CA_file') {
                                            // Assuming this is for displaying a PDF report
                                            $pdfData = $additional_details[$column_key];
                                            if ($pdfData) {
                                                // Convert binary data to base64 format
                                                $base64Pdf = base64_encode($pdfData);
                                                // Output an iframe to display the PDF
                                                echo '<iframe src="data:application/pdf;base64,' . $base64Pdf . '" width="90%" height="300px"></iframe>';
                                            } else {
                                                echo 'No PDF Provided';
                                            }
                                        }
                                        
                                        else {
                                            echo htmlspecialchars($additional_details[$column_key] ?? '');
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </table>
                    <?php endif; ?>

                    <br>
                    <br>

                    <?php if ($is_active) : ?>
                        <form id="revisionForm" method="POST" style="margin-left: 400px">
                            <input type="hidden" name="r_id" value="<?php echo $request['R_ID']; ?>">
<button type="button" class="view-button" style="background-color: lightgrey;margin-left: 60px; color:black" onclick="showRevisionComment()"> Need Revision?</button>
    <br>
                            <br>
                             

                            <!-- Hidden div for selecting fields to revise -->
                            <div id="revisionComment"  style="display: none; margin-right:20% ">
                                <br>
                                <br>
                                <!-- Text area for revision comment -->
                                <label for="RA_comment" style=" color:#19315b;   font-weight: bold">Revision Notes:</label>
                                <br>
                                <textarea id="RA_comment" name="RA_comment" rows="6" cols="40" style="background-color:#ffff; width:auto; hight:auto"></textarea>
<br>
                                <button type="submit" class="view-button-V" style="margin-left:65px;" name="status" value="Revision Requested">Request Revision</button>
                            </div>
                            <br>
                            <br>
                            
                            <button type="submit" class="view-button-A" style="margin-left: 30px" name="status" value="Approved" onclick="return confirm('Are you sure you want to approve this request?');">Approve</button>
                            <button type="submit" class="view-button-R" style="margin-left: 40px" name="status" value="Rejected" onclick="return confirm('Are you sure you want to reject this request?');">Reject</button>
                            
                           
                        </form>
                    <?php endif; ?>

                <?php else : ?>
                    <p>No details found for this request.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        function showRevisionComment() {
            document.getElementById('revisionComment').style.display = 'block';
        }
        
    function showRevisionComment() {
        var revisionComment = document.getElementById('revisionComment');
        if (revisionComment.style.display === 'none') {
            revisionComment.style.display = 'block';
        } else {
            revisionComment.style.display = 'none';
        }
    }
        
        
    </script>
</body>
</html>

