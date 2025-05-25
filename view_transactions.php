<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include "header.php";
if (isset($_SESSION['user_id'])) {
    include "../includes/connection.php";
?>
<div class="container mt-4">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h4 class="mb-0"><i class="fas fa-history mr-2"></i> Fee Transaction History</h4>
        </div>
        <div class="card-body">
            <?php
            // Check if fee_transactions table exists
            $check_table = $conn->query("SHOW TABLES LIKE 'fee_transactions'");
            if ($check_table->num_rows == 0) {
                // Create the table if it doesn't exist
                $create_table = "CREATE TABLE fee_transactions (
                    id INT(11) AUTO_INCREMENT PRIMARY KEY,
                    user_id INT(11) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    type ENUM('add', 'payment') NOT NULL,
                    reason VARCHAR(255),
                    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    admin_id INT(11) NOT NULL
                )";
                $conn->query($create_table);
            }

            // Check if 'name' column exists in users table
            $check_name_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'name'");
            $name_exists = mysqli_num_rows($check_name_column) > 0;

            // Check if 'id' column exists in users table
            $check_id_column = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'id'");
            $id_exists = mysqli_num_rows($check_id_column) > 0;
            $id_field = $id_exists ? 'id' : 'sno';

            // Get all transactions with user and admin details
            if ($name_exists) {
                $transactions_query = "SELECT t.*, u.name as user_name, u.email as user_email, 
                                      a.name as admin_name 
                                      FROM fee_transactions t
                                      JOIN users u ON t.user_id = u.$id_field
                                      JOIN users a ON t.admin_id = a.$id_field
                                      ORDER BY t.transaction_date DESC";
            } else {
                $transactions_query = "SELECT t.*, CONCAT(u.fname, ' ', u.lname) as user_name, u.email as user_email, 
                                      CONCAT(a.fname, ' ', a.lname) as admin_name 
                                      FROM fee_transactions t
                                      JOIN users u ON t.user_id = u.$id_field
                                      JOIN users a ON t.admin_id = a.$id_field
                                      ORDER BY t.transaction_date DESC";
            }
            $transactions_result = $conn->query($transactions_query);
            
            if ($transactions_result->num_rows > 0) {
                ?>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="thead-dark">
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            while ($row = $transactions_result->fetch_assoc()) {
                                $type_badge = $row['type'] == 'add' ? 
                                    '<span class="badge badge-danger">Fee Added</span>' : 
                                    '<span class="badge badge-success">Payment</span>';
                                echo "<tr>
                                    <td>" . date('M d, Y h:i A', strtotime($row['transaction_date'])) . "</td>
                                    <td>{$row['user_name']} <small class='text-muted'>({$row['user_email']})</small></td>
                                    <td>{$type_badge}</td>
                                    <td>₹" . number_format($row['amount'], 2) . "</td>
                                    <td>" . ($row['reason'] ? $row['reason'] : 'N/A') . "</td>
                                    <td>{$row['admin_name']}</td>
                                </tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4">
                    <button class="btn btn-info" onclick="window.print()">
                        <i class="fas fa-print mr-2"></i> Print Report
                    </button>
                </div>
                <?php
            } else {
                echo '<div class="alert alert-info">No fee transactions found in the system.</div>';
            }
            ?>
        </div>
    </div>
</div>

<style>
@media print {
    .navbar, .card-header, button {
        display: none !important;
    }
    .container {
        width: 100% !important;
        max-width: 100% !important;
    }
    body {
        padding: 0 !important;
        margin: 0 !important;
    }
    .card {
        border: none !important;
        box-shadow: none !important;
    }
    .card-body {
        padding: 0 !important;
    }
    @page {
        size: landscape;
    }
}
</style>

<?php
} else {
    header('location:../index.php');
}
?> 

// End output buffering
ob_end_flush();
?>