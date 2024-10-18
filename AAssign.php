<?php

session_start();
include 'db_con.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirect to login page if not logged in
    header("Location: otplogin.html");
    exit();
}

// Get the user ID and name from the session
$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get the request ID from the URL parameter
$request_id = $_GET['id'];

// Fetch the request details
$sql_request = "SELECT * FROM Requests WHERE R_ID = ?";
$stmt_request = $conn->prepare($sql_request);

if (!$stmt_request) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_request->bind_param("i", $request_id);
$stmt_request->execute();
$result_request = $stmt_request->get_result();

if (!$result_request) {
    die("Execute statement failed: " . $stmt_request->error);
}

$request = $result_request->fetch_assoc();
$stmt_request->close();

// Determine the request type
$request_type = $request['R_type'];

$specific_request = null;

switch ($request_type) {
    case 'BusinessTrip':
        $sql_specific = "SELECT * FROM BusinessTrip_Req WHERE R_ID = ?";
        break;
    case 'CashAdvance':
        $sql_specific = "SELECT * FROM CashAdvance_Req WHERE R_ID = ?";
        break;
    case 'Certificate':
        $sql_specific = "SELECT * FROM Certificate_Req WHERE R_ID = ?";
        break;
    case 'Leave':
        $sql_specific = "SELECT * FROM Leave_Req WHERE R_ID = ?";
        break;
    default:
        die("Unknown request type");
}

$stmt_specific = $conn->prepare($sql_specific);

if (!$stmt_specific) {
    die("Prepare statement failed: " . $conn->error);
}

$stmt_specific->bind_param("i", $request_id);
$stmt_specific->execute();
$result_specific = $stmt_specific->get_result();

if (!$result_specific) {
    die("Execute statement failed: " . $stmt_specific->error);
}

$specific_request = $result_specific->fetch_assoc();
$stmt_specific->close();

// Pass the data 
$_SESSION['request'] = $request;
$_SESSION['specific_request'] = $specific_request;

header("Location: AManage.php");
exit();

?>
