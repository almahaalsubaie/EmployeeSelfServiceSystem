<?php
session_start();
include 'db_con.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_name'])) {
    // Redirect to login page if not logged in
    header("Location: otplogin.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['employee_id'])) {
        $employee_id = $_POST['employee_id'];
        
        try {
            // Delete from the Employee table (cascade deletion will handle related records)
            $stmt = $pdo->prepare('DELETE FROM Employee WHERE E_ID = :employee_id');
            $stmt->execute(['employee_id' => $employee_id]);
            
            // Redirect after successful deletion
            header('Location: viewEmployees.php');
            exit;
        } catch (PDOException $e) {
            // Handle the exception appropriately (log, display error message, etc.)
            echo "Error: " . $e->getMessage();
        }
    }
}
?>
