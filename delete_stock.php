<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
include '../includes/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

if (isset($_GET['id'])) {
    $stock_id = $_GET['id'];
    
    $sql = "DELETE FROM mess_stock WHERE id = ?";
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $stock_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Stock item deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting stock item: " . mysqli_error($connection);
    }
}

echo "<script>window.location.href = 'manage_stock.php';</script>";
exit(); 

// End output buffering
ob_end_flush();
?>