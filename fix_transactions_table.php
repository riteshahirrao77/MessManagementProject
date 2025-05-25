<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Include database connection
include('../includes/connection.php');

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Table Fix Utility</h2>";

// Check if the fee_transactions table exists
$check_table = mysqli_query($connection, "SHOW TABLES LIKE 'fee_transactions'");

if (mysqli_num_rows($check_table) > 0) {
    echo "<p>Table fee_transactions exists. Backing up data...</p>";
    
    // Create a backup of existing data
    $backup_data = [];
    $backup_query = "SELECT * FROM fee_transactions";
    $backup_result = mysqli_query($connection, $backup_query);
    
    if ($backup_result) {
        while ($row = mysqli_fetch_assoc($backup_result)) {
            $backup_data[] = $row;
        }
        echo "<p>Successfully backed up " . count($backup_data) . " records.</p>";
    } else {
        echo "<p>Warning: Failed to backup data: " . mysqli_error($connection) . "</p>";
    }
    
    // Drop the existing table
    echo "<p>Dropping existing table...</p>";
    $drop_table = mysqli_query($connection, "DROP TABLE fee_transactions");
    
    if (!$drop_table) {
        echo "<p>Error dropping table: " . mysqli_error($connection) . "</p>";
        exit;
    }
} else {
    echo "<p>Table fee_transactions does not exist. Will create a new one.</p>";
    $backup_data = [];
}

// Create the table with proper structure
echo "<p>Creating fee_transactions table with proper structure...</p>";
$create_table = "CREATE TABLE fee_transactions (
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

$create_result = mysqli_query($connection, $create_table);

if (!$create_result) {
    echo "<p>Error creating table: " . mysqli_error($connection) . "</p>";
    exit;
}

echo "<p>Table created successfully.</p>";

// Restore backed up data if any
if (count($backup_data) > 0) {
    echo "<p>Restoring " . count($backup_data) . " records...</p>";
    
    $success_count = 0;
    $error_count = 0;
    
    foreach ($backup_data as $record) {
        // Build a dynamic insert query based on available fields
        $columns = [];
        $placeholders = [];
        $values = [];
        $types = "";
        
        // Always include these core fields
        $core_fields = ['user_id', 'amount', 'type', 'admin_email'];
        
        foreach ($record as $column => $value) {
            if ($column != 'id' && ($value !== null || in_array($column, $core_fields))) {
                $columns[] = $column;
                $placeholders[] = "?";
                $values[] = $value;
                
                // Determine parameter type
                if ($column == 'user_id' || $column == 'admin_id') {
                    $types .= "i"; // integer
                } else if ($column == 'amount') {
                    $types .= "d"; // double
                } else {
                    $types .= "s"; // string
                }
            }
        }
        
        // Create and execute the prepared statement
        $insert_query = "INSERT INTO fee_transactions (" . implode(", ", $columns) . ") 
                         VALUES (" . implode(", ", $placeholders) . ")";
        
        $stmt = mysqli_prepare($connection, $insert_query);
        
        if ($stmt) {
            // Dynamically bind parameters
            if (!empty($values)) {
                $refs = [];
                foreach ($values as $key => $value) {
                    $refs[$key] = &$values[$key];
                }
                array_unshift($refs, $stmt, $types);
                call_user_func_array('mysqli_stmt_bind_param', $refs);
            }
            
            $execute_result = mysqli_stmt_execute($stmt);
            
            if ($execute_result) {
                $success_count++;
            } else {
                $error_count++;
                echo "<p>Error restoring record: " . mysqli_stmt_error($stmt) . "</p>";
            }
            
            mysqli_stmt_close($stmt);
        } else {
            $error_count++;
            echo "<p>Failed to prepare statement: " . mysqli_error($connection) . "</p>";
        }
    }
    
    echo "<p>Restoration complete. Successfully restored $success_count records. Failed to restore $error_count records.</p>";
}

echo "<p>Table fix completed. You can now <a href='user_bills.php'>return to the billing page</a>.</p>";

// End output buffering
ob_end_flush();
?> 