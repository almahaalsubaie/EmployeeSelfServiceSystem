<?php
session_start();

$errors = isset($_GET['errors']) ? explode(',', $_GET['errors']) : [];
$success = isset($_GET['success']) ? $_GET['success'] : null;

// Retrieve individual error flags from query parameters
$id_error = isset($_GET['id_error']) ? $_GET['id_error'] : '0';
$email_error = isset($_GET['email_error']) ? $_GET['email_error'] : '0';
$phone_error = isset($_GET['phone_error']) ? $_GET['phone_error'] : '0';
$national_id_error = isset($_GET['national_id_error']) ? $_GET['national_id_error'] : '0';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    header("Location: otplogin.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Employee Registration</title>
      <style>
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type="number"] {
            -moz-appearance: textfield;
        }
        .hidden {
            display: none;
        }
  
    </style>
</head>
<body>
    <div class="dashboard-container">
        <div class="sidebar">
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
        </div>
        <div class="main-content">
            <h1>Welcome, <?php echo htmlspecialchars($user_name); ?></h1>
            <br>
            <h2 style="margin-left:500px;color:#224483">Register New Employee</h2>
           
            <div class="container">
                <div class="formContainer" style="margin-left:250px">
                     <label style=" color:#19315b;   font-weight: bold; font-size: 21px;">Employee's Information</label>
                    <br><br><br>

                    <form action="ERegister.php" method="POST">
                        <div class="input-group" style="display:inline-block">
                            <label for="id" style="color:#19315b; font-weight: normal; margin-right:140px;"> ID</label>
                            <input type="text" placeholder="ID" style="background-color:#ffff; width:30%" id="id" name="id" minlength="10" maxlength="10" pattern="[0-9]{10}" required>
                             <div id="id-error1" style="color: red; display: none;">ID must be exactly 10 digits.</div>
                            <div id="id-error2" style="color: red; display: none;">The provided ID is already existed.</div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="name" style="color:#19315b; font-weight: normal; margin-right:110px;"> Name</label>
                            <input type="text" style="background-color:#ffff; width:30%" pattern="[A-Za-z]+"  placeholder="Name" id="name" name="name" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="birth_date" style="color:#19315b; font-weight: normal; margin-right:72px;">Birth Date</label>
                            <input type="date" min="1940-01-01" max="2006-12-31" style="background-color:#ffff; width:30%" id="birth_date" placeholder="YYYY/MM/DD" name="birth_date" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="email" style="color:#19315b; font-weight: normal; margin-right:110px;"> Email</label>
                            <input type="email" placeholder="example@esadservices.com" style="background-color:#ffff; width:30%" id="email" name="email" required>
                            <div id="email-error1" style="color: red; display: none;">Email must be in @domain.com format.</div>
                             <div id="email-error2" style="color: red; display: none;">The provided Email is already existed.</div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="phone" style="color:#19315b; font-weight: normal; margin-right:30px;"> Phone Number</label>
                            <input type="tel" placeholder="05XXXXXXXX" style="background-color:#ffff; width:30%" id="phone" name="phone" minlength="10" maxlength="10" pattern="05[0-9]{8}" required>
                               <div id="phone-error1" style="color: red; display: none;">Phone number must start with 05 and be exactly 10 digits.</div>
                            <div id="phone-error2" style="color: red; display: none;">The provided phone number is already existed.</div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="national_id" style="color:#19315b; font-weight: normal; margin-right:65px;"> National ID</label>
                            <input type="text" placeholder="National ID" style="background-color:#ffff; width:30%" id="national_id" name="national_id" pattern="[A-Za-z0-9]{10}" minlength="10" maxlength="10" required>
                            <div id="national_id-error1" style="color: red; display: none;">National ID must be exactly 10 digits.</div>
                              <div id="national_id-error2" style="color: red; display: none;">The provided National ID is already existed.</div>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="hire_date" style="color:#19315b; font-weight: normal; margin-right:78px;">Hire Date</label>
                            <input type="date" placeholder="YYYY/MM/DD" style="background-color:#ffff; width:30%" id="hire_date" name="hire_date" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="department" style="color: #19315b; font-weight: normal; margin-right:57px;">Department</label>
                            <select id="department" name="department" style="background-color: #ffff; width: 36%;" required>
                                <option value="Marketing">Marketing</option>
                                <option value="IT">IT</option>
                                <option value="Sales">Sales</option>
                                <option value="Finance">Finance</option>
                                <option value="Operations">Operations</option>
                            </select>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="position" style="color:#19315b; font-weight: normal; margin-right:85px;">Position</label>
                            <input type="text" placeholder="Position" style="background-color:#ffff; width:30%" id="position" name="position" required>
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="basic_salary" style="color:#19315b; font-weight: normal; margin-right:46px;">Basic Salary</label>
                            <input type="number" min="0" placeholder="00.00$" style="background-color:#ffff; width:30%" id="basic_salary" name="basic_salary" required>
                        </div>
                        <br>  <br>
                        <div class="input-group" style="display:inline-block;">
                            <label for="housing_allowance" style="color:#19315b; font-size:17px; font-weight: normal; margin-right:74px;" class="checkbox-label">Housing Allowance</label>
                            <input type="checkbox" style="background-color:#ffff; margin-left:-90px; width:30%" id="housing_allowance" name="housing_allowance">
                        </div>
                        <div id="housing_allowance_field" class="input-group hidden">
                           
                            <input type="number" placeholder="00.00$" style="background-color:#ffff; width:30%" id="housing_allowance_amount" min="0" name="housing_allowance_amount">
                        </div>
                        <div class="input-group" style="display:inline-block">
                            <label for="transportation_allowance" class="checkbox-label" style="color:#19315b; font-size:16px; font-weight: normal; margin-right:38px;">Transportation Allowance</label>
                            <input type="checkbox" style="background-color:#ffff; margin-left:-90px; width:30%" id="transportation_allowance" name="transportation_allowance">
                        </div>
                        <div id="transportation_allowance_field" class="input-group hidden" >
                      
                            <input type="number" min="0" placeholder="00.00$" style="background-color:#ffff; width:30%" id="transportation_allowance_amount" name="transportation_allowance_amount">
                        </div>
                        <button type="submit" class="view-button">Register</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Toggle hidden fields based on checkbox state
    document.getElementById("housing_allowance").addEventListener("change", function() {
        var field = document.getElementById("housing_allowance_field");
        field.classList.toggle("hidden", !this.checked);
    });

    document.getElementById("transportation_allowance").addEventListener("change", function() {
        var field = document.getElementById("transportation_allowance_field");
        field.classList.toggle("hidden", !this.checked);
    });

    // Validate ID field
    function validateID() {
        var idInput = document.getElementById('id');
        var idError = document.getElementById('id-error1');
        var idValue = idInput.value.trim();

        if (idValue.length !== 10 || isNaN(idValue)) {
            idInput.classList.add('error');
            idError.style.display = 'block';
        } else {
            idInput.classList.remove('error');
            idError.style.display = 'none';
        }
    }

    // Validate Phone field
    function validatePhone() {
        var phoneInput = document.getElementById('phone');
        var phoneError = document.getElementById('phone-error1');
        var phoneValue = phoneInput.value.trim();

        if (phoneValue.length !== 10 || !phoneValue.startsWith('05') || isNaN(phoneValue)) {
            phoneInput.classList.add('error');
            phoneError.style.display = 'block';
        } else {
            phoneInput.classList.remove('error');
            phoneError.style.display = 'none';
        }
    }

    // Validate Email field
    function validateEmail() {
        var emailInput = document.getElementById('email');
        var emailError = document.getElementById('email-error1');
        var emailValue = emailInput.value.trim().toLowerCase();

        if (!emailValue.endsWith('@domain.com') {
            emailInput.classList.add('error');
            emailError.style.display = 'block';
        } else {
            emailInput.classList.remove('error');
            emailError.style.display = 'none';
        }
    }

    // Validate National ID field
    function validateNationalID() {
        var nationalIDInput = document.getElementById('national_id');
        var nationalIDError = document.getElementById('national_id-error1');
        var nationalIDValue = nationalIDInput.value.trim();

        if (nationalIDValue.length !== 10 || !/^[A-Za-z0-9]+$/.test(nationalIDValue)) {
            nationalIDInput.classList.add('error');
            nationalIDError.style.display = 'block';
        } else {
            nationalIDInput.classList.remove('error');
            nationalIDError.style.display = 'none';
        }
    }

    // Add event listeners for input change
    document.getElementById('id').addEventListener('input', validateID);
    document.getElementById('phone').addEventListener('input', validatePhone);
    document.getElementById('email').addEventListener('input', validateEmail);
    document.getElementById('national_id').addEventListener('input', validateNationalID);

    // Show error messages based on query parameters
   function showErrorMessages() {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.get('id_error') === '1') {
        document.getElementById('id-error2').style.display = 'block';
    }
    if (urlParams.get('email_error') === '1') {
        document.getElementById('email-error2').style.display = 'block';
    }
    if (urlParams.get('phone_error') === '1') {
        document.getElementById('phone-error2').style.display = 'block';
    }
    if (urlParams.get('national_id_error') === '1') {
        document.getElementById('national_id-error2').style.display = 'block';
    }
}

// Call showErrorMessages on page load
document.addEventListener("DOMContentLoaded", showErrorMessages);


    // Call showErrorMessages on page load
    showErrorMessages();

    // Set max date for hire date input
    const today = new Date();
    const todayFormatted = today.toISOString().split('T')[0];
    document.getElementById('hire_date').setAttribute('max', todayFormatted);
});
</script>



    

</body>
</html>
