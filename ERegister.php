<?php
session_start();
include 'db_con.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("User not logged in.");
}

// Sanitize input data
function sanitize($data) {
    return htmlspecialchars(stripslashes(trim($data)));
}

// Retrieve and sanitize input data
$id = isset($_POST['id']) ? sanitize($_POST['id']) : '';
$name = isset($_POST['name']) ? sanitize($_POST['name']) : '';
$birth_date = isset($_POST['birth_date']) ? sanitize($_POST['birth_date']) : '';
$email = isset($_POST['email']) ? sanitize($_POST['email']) : '';
$phone = isset($_POST['phone']) ? sanitize($_POST['phone']) : '';
$hire_date = isset($_POST['hire_date']) ? sanitize($_POST['hire_date']) : '';
$department = isset($_POST['department']) ? sanitize($_POST['department']) : '';
$position = isset($_POST['position']) ? sanitize($_POST['position']) : '';
$basic_salary = isset($_POST['basic_salary']) ? (float)$_POST['basic_salary'] : '';
$housing_allowance = isset($_POST['housing_allowance_amount']) ? (float)$_POST['housing_allowance_amount'] : '';
$transportation_allowance = isset($_POST['transportation_allowance_amount']) ? (float)$_POST['transportation_allowance_amount'] : '';
$national_id = isset($_POST['national_id']) ? sanitize($_POST['national_id']) : '';

// Initialize an array to keep track of errors
$errors = [];

// Function to check for duplicates in the database
function checkDuplicate($conn, $field, $value) {
    $stmt = $conn->prepare("SELECT COUNT(*) FROM Employee WHERE $field = ?");
    $stmt->bind_param("s", $value);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Check for duplicates and add to errors array if found
$id_error = checkDuplicate($conn, 'E_ID', $id);
$email_error = checkDuplicate($conn, 'E_email', $email);
$phone_error = checkDuplicate($conn, 'E_phone', $phone);
$national_id_error = checkDuplicate($conn, 'EN_ID', $national_id);

// Redirect back with error details if any errors found
if ($id_error || $email_error || $phone_error || $national_id_error) {
    $errorQuery = http_build_query([
        'id_error' => $id_error ? '1' : '0',
        'email_error' => $email_error ? '1' : '0',
        'phone_error' => $phone_error ? '1' : '0',
        'national_id_error' => $national_id_error ? '1' : '0',
        'id' => $id,
        'name' => $name,
        'birth_date' => $birth_date,
        'email' => $email,
        'phone' => $phone,
        'hire_date' => $hire_date,
        'department' => $department,
        'position' => $position,
        'basic_salary' => $basic_salary,
        'housing_allowance_amount' => $housing_allowance,
        'transportation_allowance_amount' => $transportation_allowance,
        'national_id' => $national_id
    ]);
    header("Location: EmployeeRegistration.php?$errorQuery");
    exit();
}


// Generate password based on specified format
$first_letter = strtoupper(substr($name, 0, 1)); // First letter in uppercase
$first_4_digits = substr((string)$national_id, 0, 4); // First 4 digits of national ID
$generated_password = $first_letter . '*' . $first_4_digits;

// Hash the password
$hashed_password = password_hash($generated_password, PASSWORD_DEFAULT);

// Function to calculate vacation balance
function calculate_vbalance($hire_date) {
    $hire_date_obj = new DateTime($hire_date);
    $current_date = new DateTime();
    $interval = $current_date->diff($hire_date_obj);
    $months = $interval->y * 12 + $interval->m; // Total months
    return $months * 2; // 2 days per month
}

$vbalance = calculate_vbalance($hire_date);

// Check if the connection is successfully established
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Prepare and execute the insertion statement
$stmt = $conn->prepare("INSERT INTO Employee (EN_ID, E_ID, E_name, E_birthDate, E_email, E_phone, E_hireDate, VBalance, E_department, E_position, E_basicSalary, E_housingAllow, E_transportAllow, E_password) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

// Bind parameters
$stmt->bind_param(
    "iisssssdsssdds",
    $national_id,
    $id,
    $name,
    $birth_date,
    $email,
    $phone,
    $hire_date,
    $vbalance,
    $department,
    $position,
    $basic_salary,
    $housing_allowance,
    $transportation_allowance,
    $hashed_password
);

// Execute the statement
if (!$stmt->execute()) {
    die("Execute failed: " . $stmt->error);
}

// Close statement and connection
$stmt->close();
$conn->close();

// Redirect back to the form page to show the success message

header("Location: ViewEmployees.php");
exit();
?>






