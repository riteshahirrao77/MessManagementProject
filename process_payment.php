<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
if(isset($_SESSION['email'])){
    include('../includes/connection.php');
    
    if(isset($_POST['pay_bill'])) {
        // Get form data
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $month = mysqli_real_escape_string($connection, $_POST['month']);
        $year = mysqli_real_escape_string($connection, $_POST['year']);
        $payment_method = mysqli_real_escape_string($connection, $_POST['payment_method'] ?? '');
        $transaction_ref = mysqli_real_escape_string($connection, $_POST['transaction_ref'] ?? '');
        $notes = mysqli_real_escape_string($connection, $_POST['notes'] ?? '');
        
        // Validate the data
        if ($user_id <= 0) {
            echo "<script>
                alert('Invalid user ID');
                window.location.href = 'user_bills.php';
            </script>";
            exit;
        }
        
        if ($amount <= 0) {
            echo "<script>
                alert('Invalid payment amount');
                window.location.href = 'user_bills.php?user_id=" . $user_id . "&month=" . $month . "&year=" . $year . "';
            </script>";
            exit;
        }
        
        try {
            // Start transaction
            mysqli_begin_transaction($connection);
            
            // Check if fee_transactions table exists and has admin_id column
            $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'fee_transactions'");
            $has_admin_id = false;
            $has_transaction_ref = false;
            $has_payment_method = false;
            
            if(mysqli_num_rows($check_table) > 0) {
                $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'admin_id'");
                $has_admin_id = mysqli_num_rows($check_column) > 0;
                
                $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'transaction_ref'");
                $has_transaction_ref = mysqli_num_rows($check_column) > 0;
                
                $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'payment_method'");
                $has_payment_method = mysqli_num_rows($check_column) > 0;
            }
            
            // Create fee_transactions table if it doesn't exist
            if(mysqli_num_rows($check_table) == 0) {
                $create_table = "CREATE TABLE IF NOT EXISTS fee_transactions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    reason TEXT,
                    payment_method VARCHAR(50),
                    transaction_ref VARCHAR(100),
                    admin_id INT,
                    admin_email VARCHAR(100),
                    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )";
                mysqli_query($connection, $create_table);
                $has_admin_id = true;
                $has_transaction_ref = true;
                $has_payment_method = true;
            }
            
            // Add missing columns if they don't exist
            if(!$has_admin_id) {
                mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN admin_id INT AFTER transaction_ref");
                $has_admin_id = true;
            }
            
            if(!$has_transaction_ref) {
                mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN transaction_ref VARCHAR(100) AFTER reason");
                $has_transaction_ref = true;
            }
            
            if(!$has_payment_method) {
                mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN payment_method VARCHAR(50) AFTER reason");
                $has_payment_method = true;
            }
            
            // Add payment record
            $reason = "Bill payment for " . date('F Y', strtotime("$year-$month-01"));
            if($transaction_ref) {
                $reason .= " (Ref: $transaction_ref)";
            }
            if($notes) {
                $reason .= " - Notes: $notes";
            }
            
            // Get admin_id from session if available
            $admin_id = isset($_SESSION['admin_id']) ? intval($_SESSION['admin_id']) : 0;
            
            // Insert payment transaction
            if($has_admin_id && $has_transaction_ref && $has_payment_method) {
                // All columns exist - use the full insert
                $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, payment_method, transaction_ref, admin_id, admin_email) 
                               VALUES (?, ?, 'payment', ?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insert_query);
                mysqli_stmt_bind_param($stmt, "idsssis", $user_id, $amount, $reason, $payment_method, $transaction_ref, $admin_id, $_SESSION['email']);
            } else if($has_transaction_ref && $has_payment_method) {
                // Missing admin_id but has transaction_ref and payment_method
                $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, payment_method, transaction_ref, admin_email) 
                               VALUES (?, ?, 'payment', ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insert_query);
                mysqli_stmt_bind_param($stmt, "idssss", $user_id, $amount, $reason, $payment_method, $transaction_ref, $_SESSION['email']);
            } else if($has_transaction_ref) {
                // Only has transaction_ref but not payment_method or admin_id
                $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, transaction_ref, admin_email) 
                               VALUES (?, ?, 'payment', ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insert_query);
                mysqli_stmt_bind_param($stmt, "idsss", $user_id, $amount, $reason, $transaction_ref, $_SESSION['email']);
            } else {
                // Basic insert with only required fields
                $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, admin_email) 
                               VALUES (?, ?, 'payment', ?, ?)";
                $stmt = mysqli_prepare($connection, $insert_query);
                mysqli_stmt_bind_param($stmt, "idss", $user_id, $amount, $reason, $_SESSION['email']);
            }
            
            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Error inserting payment record: " . mysqli_error($connection));
            }
            mysqli_stmt_close($stmt);
            
            // Check if the user has a fee_amount field
            $check_field = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_amount'");
            if (mysqli_num_rows($check_field) > 0) {
                // Update user's fee amount
                $update_query = "UPDATE users SET fee_amount = GREATEST(0, fee_amount - ?) WHERE sno = ?";
                $stmt = mysqli_prepare($connection, $update_query);
                mysqli_stmt_bind_param($stmt, "di", $amount, $user_id);
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new Exception("Error updating user fee amount: " . mysqli_error($connection));
                }
                mysqli_stmt_close($stmt);
            }
            
            // Update food orders status if any
            $update_orders = "UPDATE food_orders SET status = 'Paid' 
                            WHERE user_id = ? 
                            AND MONTH(order_date) = ? 
                            AND YEAR(order_date) = ? 
                            AND status = 'Approved'";
            $stmt = mysqli_prepare($connection, $update_orders);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Check if fee_status field exists
            $check_field = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_status'");
            if (mysqli_num_rows($check_field) > 0) {
                // Clear any pending fees for the month
                $clear_fees = "UPDATE users SET fee_status = 1 WHERE sno = ?";
                $stmt = mysqli_prepare($connection, $clear_fees);
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
            
            // Commit the transaction
            mysqli_commit($connection);
            
            echo "<script>
                alert('Payment Successful');
                window.location.href = 'user_bills.php?user_id=" . $user_id . "&month=" . $month . "&year=" . $year . "&payment=success&amount=" . $amount . "';
            </script>";
            
        } catch (Exception $e) {
            // Rollback on error
            mysqli_rollback($connection);
            
            $error_message = $e->getMessage();
            
            // Check if it's a "transaction_ref" column error
            if (strpos($error_message, "Unknown column 'transaction_ref'") !== false) {
                echo "<script>
                    alert('Database structure needs to be updated. Redirecting to the fix utility...');
                    window.location.href = 'quick_fix.php';
                </script>";
                exit;
            }
            
            echo "<script>
                alert('Payment Failed: " . addslashes($error_message) . "');
                window.location.href = 'user_bills.php?user_id=" . $user_id . "&month=" . $month . "&year=" . $year . "&payment=error';
            </script>";
        }
    } else {
        // No pay_bill parameter
        header('location: user_bills.php');
        exit;
    }
} else {
    // Not logged in
    header('location: ../index.php');
    exit;
}
?> 

// End output buffering
ob_end_flush();
?>