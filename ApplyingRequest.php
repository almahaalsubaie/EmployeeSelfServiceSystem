<?php
session_start();
include 'db_con.php';

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: otplogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$formData = isset($_SESSION['formData']) ? $_SESSION['formData'] : [];
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';

unset($_SESSION['error_message']);
unset($_SESSION['success_message']);
unset($_SESSION['formData']);


$_SESSION['form_token'] = bin2hex(random_bytes(32));

$vacationErrorMessage = isset($_SESSION['vacation_error_message']) ? $_SESSION['vacation_error_message'] : '';
unset($_SESSION['vacation_error_message']); // Clear the message after displaying it

$requestType = isset($_POST['requestType']) ? $_POST['requestType'] : '';

$sql = "SELECT VBalance FROM Employee WHERE E_ID = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($vBalance);
$stmt->fetch();
$stmt->close();
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
    <script>
     

        function validateForm() {
            var requestType = document.getElementById("requestType").value;
            var isValid = true;

            switch (requestType) {
                case 'BusinessTrip':
                    var destination = document.getElementById("destination").value;
                    var startDate = document.getElementById("startDate").value;
                    var endDate = document.getElementById("endDate").value;
                    var purpose = document.getElementById("purpose").value;

                    if (destination.trim() === "" || startDate.trim() === "" || endDate.trim() === "" || purpose.trim() === "") {
                        isValid = false;
                    }
                    break;

                case 'CashAdvance':
                    var reason = document.getElementById("reason").value;
                    var amount = document.getElementById("Camount").value;

                    if (reason.trim() === "" || amount.trim() === "") {
                        isValid = false;
                    }
                    break;

                case 'Certificate':
                    var certificateType = document.getElementById("certificateType").value;

                    if (certificateType.trim() === "") {
                        isValid = false;
                    }
                    break;

                case 'Leave':
                    var leaveType = document.getElementById("leaveType").value;
                    var leaveStartDate = document.getElementById("startDate").value;
                    var leaveEndDate = document.getElementById("endDate").value;

                    if (leaveType.trim() === "" || leaveStartDate.trim() === "" || leaveEndDate.trim() === "") {
                        isValid = false;
                    }
                    break;

                case 'Vacation':
                    var NuOfDays = document.getElementById("VNuOfDays").value;
                    if (NuOfDays.trim() === "") {
                        isValid = false;
                    }
                    break;

                default:
                    isValid = false;
                    break;
            }

            if (!isValid) {
                alert("Please fill in all required fields.");
            }

            return isValid;
        }

        function toggleSubmitButton() {
            var submitButton = document.getElementById('submitReq');
            submitButton.style.display = 'block';
        }

        function showFormFields() {
            var requestType = document.getElementById("requestType").value;
            var formContainer = document.getElementById("formContainer");

            formContainer.style.display = 'block';
            switch (requestType) {
                case 'Certificate':
                    formContainer.innerHTML = `
 <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Certificate Request Form</label>
                     <br><br><br><br>
                        <div class="input-group" style="display:inline-block">
                            <label for="certificateType" style="color:#19315b; font-weight: normal; margin-right:25px;">Certificate Type:</label>
                            <select id="certificateType" style="background-color:#ffff; width:40%" name="certificateType" required>
                                <option value="Salary">Salary</option>
                                <option value="Experience">Experience</option>
                            </select>
                        </div><br>`;
                    break;

                case 'Vacation':
                      var vacationErrorMessage = "<?php echo isset($_SESSION['vacation_error_message']) ? htmlspecialchars($_SESSION['vacation_error_message']) : ''; ?>";
                    formContainer.innerHTML = `
 <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Vacation Request Form</label>
                     <br><br><br><br>

                        <div class="input-group" style="display:inline-block">
                            <label for="VBalance" style="color:#19315b;font-weight:normal; margin-right:25px;">Vacation Balance: </label>
                            <input type="text" id="VBalance" name="VBalance" style="background-color:#ffff; width:40%;" value="<?php echo htmlspecialchars($vBalance); ?> Days" readonly>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="startDate" style="color:#19315b; font-weight: normal; margin-right:82px;">Start Date:</label>
                            <input type="date" style="background-color:#ffff; width:40%;" id="startDate" name="startDate" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="endDate" style="color:#19315b; font-weight: normal; margin-right:89px;">End Date:</label>
                            <input type="date" style="background-color:#ffff; width:40%;" id="endDate" name="endDate" required>
                        </div><br>
                        <div id="vacationErrorMessage" style="color: red; font-weight: bold; display: ${vacationErrorMessage ? 'block' : 'none'};">
                            ${vacationErrorMessage}
                        </div>
                    `;
                    break;

                case 'BusinessTrip':
                    formContainer.innerHTML = `

    <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Business Trip Request Form</label>
                     <br><br><br><br>
                        <div class="input-group" style="display:inline-block">
                            <label for="destination" style="color:#19315b; font-weight: normal; margin-right:45px;">Destination:</label>
                            <input type="text" pattern="[A-Za-z]+" placeholder="Destination" style="background-color:#ffff; width:40%" id="destination" name="destination" required><br>
<br>
                            <label for="startDate" style="color:#19315b; font-weight: normal; margin-right:54px;">Start Date:</label>
                            <input type="date" placeholder="YYYY/MM/DD" style="background-color:#ffff; width:40%" id="startDate" name="startDate" required><br>
<br>
                            <label for="endDate" style="color:#19315b; font-weight: normal; margin-right:58px;">End Date:</label>
                            <input type="date" placeholder="YYYY/MM/DD" style="background-color:#ffff; width:40%" id="endDate" name="endDate" required><br>
<br>
                            <label for="purpose" style="color:#19315b; font-weight: normal; margin-right:63px;">Purpose:</label>
                            <input type="text" placeholder="Purpose" style="background-color:#ffff; width:40%" id="purpose" name="purpose" required><br>
<br>
                            <div style="display:inline-block">
                                <label for="financialNeed" style="color:#19315b; font-weight: normal; display: inline-block">Financial Need:</label>
                                <input type="checkbox" style="background-color:#ffff; margin-left:10px;" id="financialNeed" name="financialNeed" onchange="toggleAmountField(this)">
                            </div>
                            <div id="amountField" style="display: none;">
                               
                                <br>
                                <input type="number" min="0" placeholder="00.00$" style="background-color:#ffff; width:40%;" id="Bamount" name="Bamount">
                            </div>
<br><br>
                            <label for="B_file" style="color:#19315b; font-weight: normal; margin-right:37px;">Attachment:</label>
                            <input type="file" style="background-color:#ffff; width:40%" id="B_file" name="B_file" accept=".pdf,.doc,.docx,.txt" >
                        </div><br>`;
                    break;

                case 'CashAdvance':
                    formContainer.innerHTML = `
    <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Cash Advance Request Form</label>
                     <br><br><br><br>
                        <div class="input-group" style="display:inline-block">
                            <label for="reason" style="color:#19315b; font-weight: normal; margin-right:55px;">Reason:</label>
                            <input type="text" placeholder="Reason" style="background-color:#ffff; width:40%" id="reason" name="reason" required><br>
<br>
                            <label for="Camount" style="color:#19315b; font-weight: normal; margin-right:55px;">Amount:</label>
                            <input type="number" min="0" placeholder="00.00$" style="background-color:#ffff; width:40%" id="Camount" name="Camount" required>
<br><br>
                            <label for="CA_file" style="color:#19315b; font-weight: normal; margin-right:25px;">Attachment:</label>
                            <input type="file" style="background-color:#ffff; width:40%" id="CA_file" name="CA_file" accept=".pdf,.doc,.docx,.txt">
                        </div><br>`;
                    break;

                case 'Leave':
                    formContainer.innerHTML = `
 <label style=" color:#19315b;   font-weight: bold; font-size: 20px;">Leave Request Form</label>
                     <br><br><br><br>
                        <div class="input-group" style="display:inline-block">
                            <label for="leaveType" style="color:#19315b; font-weight: normal; margin-right:25px;">Leave Type:</label>
                            <select id="leaveType" style="background-color:#ffff; width:40%" name="leaveType" required>
                                <option value="Unpaid">Unpaid</option>
                                <option value="Sick">Sick</option>
                            </select><br>
<br>
                            <label for="startDate" style="color:#19315b; font-weight: normal; margin-right:35px;">Start Date:</label>
                            <input type="date" placeholder="YYYY/MM/DD" style="background-color:#ffff; width:40%" id="startDate" name="startDate" required><br>
<br>
                            <label for="endDate" style="color:#19315b; font-weight: normal; margin-right:40px;">End Date:</label>
                            <input type="date" placeholder="YYYY/MM/DD" style="background-color:#ffff; width:40%" id="endDate" name="endDate" required><br>
<br>
                            <label for="reportFile" style="color:#19315b; font-weight: normal; margin-right:25px;">Attachment:</label>
                            <input type="file" style="background-color:#ffff; width:40%" id="reportFile" name="reportFile" accept=".pdf,.doc,.docx,.txt" >
                        </div><br>`;
                    break;

                default:
                    formContainer.innerHTML = '';
                    break;
            }
        }

        function toggleAmountField(checkbox) {
            var amountField = document.getElementById("amountField");
            amountField.style.display = checkbox.checked ? "block" : "none";
        }

    function checkVacationDays() {
    var vBalanceText = document.getElementById('VBalance').value;
    var vBalance = parseFloat(vBalanceText.replace(' Days', ''));
    var numberOfDays = parseFloat(document.getElementById('VNuOfDays')?.value || 0);
    var messageElement = document.getElementById('vacationMessage');

    if (!isNaN(numberOfDays) && numberOfDays > vBalance) {
        messageElement.style.display = 'block';
    } else {
        messageElement.style.display = 'none';
    }
}
    </script>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
            <div class="homeLogo-container">
                <img src="logo.png" alt="Company Logo" class="homeLogo">
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
            <h2 style="margin-left:530px;color:#224483">Create Request</h2>
                <?php if (!empty($error_message)): ?>
                        <div class="error-message" style="margin-left: 440px; color:red; font-weight: bold;">
                            <?php echo htmlspecialchars($error_message); ?>
                        </div>
                    <?php endif; ?>
            <form id="requestForm" action="SubmitRequest.php" method="post" enctype="multipart/form-data">
                <div class="TFcontainer" style="overflow-y: auto;">
                    <div class="typesContainer" style="width:55%; padding:10px;">
                        <input type="hidden" style="background-color:#ffff; width:40%" name="form_token" value="<?php echo $_SESSION['form_token']; ?>">
                        <div class="input-group">
                            <label for="requestType" style="color:#19315b; font-weight: bold">Select Request Type: </label>
                            <select name="requestType" style="background-color:#ffff; width:40%; padding:5px;" id="requestType" onsubmit="return validateForm()" onchange="showFormFields(); toggleSubmitButton()">
                                <option value="Select">Select a type</option>
                                <option value="BusinessTrip">Business Trip</option>
                                <option value="CashAdvance">Cash Advance</option>
                                <option value="Certificate">Certificate</option>
                                <option value="Leave">Leave</option>
                                <option value="Vacation">Vacation</option>
                            </select>
                        </div>
                    </div>
                    <div id="formContainer" class="formContainer" style="display:none; overflow-y: auto;">
                    </div>
                    <br>
                    <input type="submit" value="Submit" id="submitReq" class="view-button" style="margin-left:450px; display:none">
                </div>
            </form>
        </div>
    </div>
</body>
</html>


