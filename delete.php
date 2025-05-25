<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

  session_start();
  if(isset($_SESSION['email'])){
  include('../includes/connection.php');
  
  // Get the user ID from the URL and sanitize it
  $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
  
  if($id <= 0) {
    echo "<script type='text/javascript'>
      alert('Invalid user ID.');
      window.location.href = 'admin_dashboard.php';
    </script>";
    exit();
  }

  // Start transaction
  mysqli_begin_transaction($connection);
  
  try {
    // First, delete any fee transactions for this user
    $delete_transactions = "DELETE FROM fee_transactions WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $delete_transactions);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Next, delete any food orders for this user
    $delete_orders = "DELETE FROM food_orders WHERE user_id = ?";
    $stmt = mysqli_prepare($connection, $delete_orders);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // Delete attendance records
    $tables = ['attendance1', 'attendance2', 'attendance3', 'attendance4'];
    foreach($tables as $table) {
      $delete_attendance = "DELETE FROM $table WHERE id = ?";
      $stmt = mysqli_prepare($connection, $delete_attendance);
      mysqli_stmt_bind_param($stmt, "i", $id);
      mysqli_stmt_execute($stmt);
      mysqli_stmt_close($stmt);
    }
    
    // Finally, delete the user
    $delete_user = "DELETE FROM users WHERE sno = ?";
    $stmt = mysqli_prepare($connection, $delete_user);
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    // If we got here, commit the transaction
    mysqli_commit($connection);
    
    echo "<script type='text/javascript'>
      alert('User and all related records deleted successfully...');
      window.location.href = 'admin_dashboard.php';
    </script>";
  }
  catch (Exception $e) {
    // If there was an error, rollback the transaction
    mysqli_rollback($connection);
    
    echo "<script type='text/javascript'>
      alert('Failed to delete user. Error: " . mysqli_error($connection) . "');
      window.location.href = 'admin_dashboard.php';
    </script>";
  }
?>
<?php }
else{
  header('location:../index.php');
}
?>


// End output buffering
ob_end_flush();
?>