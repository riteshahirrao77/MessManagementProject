<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include database connection
include('../includes/connection.php');

echo "<h2>Quick Fix Utility</h2>";
echo "<p>Attempting to fix the 'transaction_ref' column issue...</p>";

// Check if the fee_transactions table exists
$check_table = mysqli_query($connection, "SHOW TABLES LIKE 'fee_transactions'");

if (mysqli_num_rows($check_table) > 0) {
    // Check if transaction_ref column exists
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'transaction_ref'");
    
    if (mysqli_num_rows($check_column) > 0) {
        // The column exists but might have issues. Try to recreate it
        echo "<p>The 'transaction_ref' column exists. Attempting to drop and recreate it...</p>";
        
        $drop_column = mysqli_query($connection, "ALTER TABLE fee_transactions DROP COLUMN transaction_ref");
        
        if (!$drop_column) {
            echo "<p>Error dropping column: " . mysqli_error($connection) . "</p>";
        } else {
            echo "<p>Successfully dropped the column.</p>";
        }
    } else {
        echo "<p>The 'transaction_ref' column doesn't exist. Will create it.</p>";
    }
    
    // Add the column with proper definition
    $add_column = mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN transaction_ref VARCHAR(100) AFTER reason");
    
    if (!$add_column) {
        echo "<p>Error adding column: " . mysqli_error($connection) . "</p>";
    } else {
        echo "<p>Successfully added 'transaction_ref' column.</p>";
    }
    
    // Also check and fix payment_method column
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'payment_method'");
    
    if (mysqli_num_rows($check_column) == 0) {
        $add_column = mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN payment_method VARCHAR(50) AFTER reason");
        
        if (!$add_column) {
            echo "<p>Error adding payment_method column: " . mysqli_error($connection) . "</p>";
        } else {
            echo "<p>Successfully added 'payment_method' column.</p>";
        }
    }
    
    // Check and fix admin_id column
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'admin_id'");
    
    if (mysqli_num_rows($check_column) == 0) {
        $add_column = mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN admin_id INT AFTER transaction_ref");
        
        if (!$add_column) {
            echo "<p>Error adding admin_id column: " . mysqli_error($connection) . "</p>";
        } else {
            echo "<p>Successfully added 'admin_id' column.</p>";
        }
    }
    
    echo "<p>Quick fix completed. You can now <a href='user_bills.php'>return to the billing page</a>.</p>";
} else {
    echo "<p>The fee_transactions table doesn't exist. Please run the full fix utility script.</p>";
    echo "<p><a href='fix_transactions_table.php'>Run full table fix utility</a></p>";
}

// End output buffering
ob_end_flush();
?> 