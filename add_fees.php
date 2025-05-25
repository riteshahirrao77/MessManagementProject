<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include('header.php');
if(isset($_SESSION['email'])){
    include('../includes/connection.php');
    
    $success_message = '';
    $error_message = '';
    
    // Check if fee_amount column exists in users table
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_amount'");
    if(mysqli_num_rows($check_column) == 0) {
        // If fee_amount column doesn't exist, add it
        mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 1000.00");
    }

    // Check if fee_status column exists
    $check_status = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_status'");
    if(mysqli_num_rows($check_status) == 0) {
        // Add fee_status column if it doesn't exist
        mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_status TINYINT(1) DEFAULT 0");
    }

    // Check if fee_transactions table exists
    $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'fee_transactions'");
    if(mysqli_num_rows($check_table) == 0) {
        // Create the table if it doesn't exist
        $create_table = "CREATE TABLE fee_transactions (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            type ENUM('add', 'payment') NOT NULL,
            reason VARCHAR(255),
            payment_method VARCHAR(50),
            transaction_ref VARCHAR(100),
            admin_id INT,
            transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            admin_email VARCHAR(255) NOT NULL
        )";
        mysqli_query($connection, $create_table);
    } else {
        // Check if transaction_ref column exists and add it if it doesn't
        $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'transaction_ref'");
        if(mysqli_num_rows($check_column) == 0) {
            mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN transaction_ref VARCHAR(100) AFTER reason");
        }
        
        // Check if payment_method column exists and add it if it doesn't
        $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'payment_method'");
        if(mysqli_num_rows($check_column) == 0) {
            mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN payment_method VARCHAR(50) AFTER reason");
        }
        
        // Check if admin_id column exists and add it if it doesn't
        $check_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'admin_id'");
        if(mysqli_num_rows($check_column) == 0) {
            mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN admin_id INT AFTER transaction_ref");
        }
    }
    
    // Process form submission
    if(isset($_POST['add_fee'])) {
        $user_id = intval($_POST['user_id']);
        $amount = floatval($_POST['amount']);
        $reason = mysqli_real_escape_string($connection, $_POST['reason']);
        
        if($user_id <= 0) {
            $error_message = "Please select a valid user.";
        } else if($amount <= 0) {
            $error_message = "Amount must be greater than 0.";
        } else {
            // Check if user exists
            $check_user = "SELECT * FROM users WHERE sno = ?";
            $check_stmt = mysqli_prepare($connection, $check_user);
            mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            
            if(mysqli_num_rows($check_result) > 0) {
                $user_data = mysqli_fetch_assoc($check_result);
                
                // Begin transaction
                mysqli_begin_transaction($connection);
                
                try {
                    // Update the user's fee amount
                    $update_query = "UPDATE users SET fee_amount = fee_amount + ? WHERE sno = ?";
                    $update_stmt = mysqli_prepare($connection, $update_query);
                    mysqli_stmt_bind_param($update_stmt, "di", $amount, $user_id);
                    mysqli_stmt_execute($update_stmt);
                    
                    // Insert into fee_transactions for record keeping
                    $admin_email = $_SESSION['email'];
                    $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, admin_email) VALUES (?, ?, 'add', ?, ?)";
                    $insert_stmt = mysqli_prepare($connection, $insert_query);
                    mysqli_stmt_bind_param($insert_stmt, "idss", $user_id, $amount, $reason, $admin_email);
                    mysqli_stmt_execute($insert_stmt);
                    
                    mysqli_commit($connection);
                    $success_message = "Added ₹" . number_format($amount, 2) . " to " . htmlspecialchars($user_data['fname'] . ' ' . $user_data['lname']) . "'s fee account successfully.";
                } catch (Exception $e) {
                    mysqli_rollback($connection);
                    $error_message = "Error adding fee: " . mysqli_error($connection);
                }
            } else {
                $error_message = "User not found. Please select a valid user.";
            }
            
            mysqli_stmt_close($check_stmt);
        }
    }
    
    // Get recent transactions
    $transactions_query = "SELECT t.*, u.fname, u.lname 
                          FROM fee_transactions t 
                          INNER JOIN users u ON t.user_id = u.sno 
                          WHERE t.type = 'add' 
                          ORDER BY t.transaction_date DESC 
                          LIMIT 10";
    $transactions_result = mysqli_query($connection, $transactions_query);
    
    // Get all users for dropdown
    $users_query = "SELECT sno, fname, lname FROM users ORDER BY fname, lname";
    $users_result = mysqli_query($connection, $users_query);
    
    // Get pending orders (if any) for quick reference
    $orders_table_exists = mysqli_query($connection, "SHOW TABLES LIKE 'food_orders'");
    if(mysqli_num_rows($orders_table_exists) > 0) {
        // First check if the required columns exist in the food_orders table
        $check_columns = mysqli_query($connection, "SHOW COLUMNS FROM food_orders LIKE 'food_items'");
        $food_items_exists = mysqli_num_rows($check_columns) > 0;
        
        $check_columns = mysqli_query($connection, "SHOW COLUMNS FROM food_orders LIKE 'food_item'");
        $food_item_exists = mysqli_num_rows($check_columns) > 0;
        
        $check_columns = mysqli_query($connection, "SHOW COLUMNS FROM food_orders LIKE 'total_amount'");
        $total_amount_exists = mysqli_num_rows($check_columns) > 0;
        
        $pending_orders_query = "SELECT o.*, u.fname, u.lname, u.sno as user_id";
        
        // Add food_items or food_item column if it exists
        if ($food_items_exists) {
            $pending_orders_query .= ", o.food_items";
        } else if ($food_item_exists) {
            $pending_orders_query .= ", o.food_item AS food_items";
        } else {
            $pending_orders_query .= ", 'Item not available' AS food_items";
        }
        
        // Add total_amount column if it exists, otherwise use price if it exists
        if ($total_amount_exists) {
            $pending_orders_query .= ", o.total_amount";
        } else {
            // Check if price column exists
            $check_price = mysqli_query($connection, "SHOW COLUMNS FROM food_orders LIKE 'price'");
            if (mysqli_num_rows($check_price) > 0) {
                $pending_orders_query .= ", o.price AS total_amount";
            } else {
                $pending_orders_query .= ", 0 AS total_amount";
            }
        }
        
        $pending_orders_query .= " FROM food_orders o 
                                INNER JOIN users u ON o.user_id = u.sno 
                                WHERE o.status = 'Approved' 
                                ORDER BY o.order_date DESC 
                                LIMIT 5";
        
        try {
            $pending_orders_result = mysqli_query($connection, $pending_orders_query);
            if (!$pending_orders_result) {
                // If query fails, set to false and log error
                error_log("Error in pending orders query: " . mysqli_error($connection));
                $pending_orders_result = false;
            }
        } catch (Exception $e) {
            error_log("Exception in pending orders query: " . $e->getMessage());
            $pending_orders_result = false;
        }
    } else {
        $pending_orders_result = false;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Fees</title>
    <style>
        .fee-container {
            padding: 20px;
        }
        .fee-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .fee-header {
            background-color: #194350;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .fee-body {
            padding: 20px;
        }
        .form-control {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #194350;
            color: white;
        }
        .btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
        }
        .table th, .table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table thead th {
            background-color: #f2f2f2;
            color: #333;
            font-weight: bold;
        }
        .table tbody tr:hover {
            background-color: #f9f9f9;
        }
        .order-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #194350;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        .badge-add {
            background-color: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container fee-container">
        <div class="fee-card">
            <div class="fee-header">
                <i class="fas fa-money-bill-wave mr-2"></i> Add Fees
            </div>
            <div class="fee-body">
                <?php if(!empty($success_message)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>
                
                <?php if(!empty($error_message)): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle mr-2"></i> <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="user_id">
                                    <i class="fas fa-user mr-2"></i> Select User:
                                </label>
                                <select name="user_id" id="user_id" class="form-control" required>
                                    <option value="">- Select User -</option>
                                    <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                        <option value="<?php echo $user['sno']; ?>">
                                            <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname'] . ' (ID: ' . $user['sno'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="amount">
                                    <i class="fas fa-rupee-sign mr-2"></i> Amount (₹):
                                </label>
                                <input type="number" name="amount" id="amount" class="form-control" min="1" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reason">
                                    <i class="fas fa-clipboard-list mr-2"></i> Reason:
                                </label>
                                <select name="reason" id="reason" class="form-control" required>
                                    <option value="Food Order">Food Order</option>
                                    <option value="Monthly Fee">Monthly Fee</option>
                                    <option value="Special Meal">Special Meal</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <button type="submit" name="add_fee" class="btn btn-primary btn-block">
                                <i class="fas fa-plus-circle mr-2"></i> Add Fee
                            </button>
                        </form>
                    </div>
                    
                    <div class="col-md-6">
                        <?php if($pending_orders_result && mysqli_num_rows($pending_orders_result) > 0): ?>
                            <h5><i class="fas fa-clipboard-check mr-2"></i> Recent Approved Orders</h5>
                            <div class="recent-orders">
                                <?php while($order = mysqli_fetch_assoc($pending_orders_result)): ?>
                                    <div class="order-card">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <strong>
                                                <?php echo htmlspecialchars($order['fname'] . ' ' . $order['lname']); ?>
                                            </strong>
                                            <span class="badge badge-approved">Approved</span>
                                        </div>
                                        <p class="mb-1">
                                            <i class="fas fa-utensils mr-1"></i> 
                                            <?php echo htmlspecialchars($order['food_items'] ?? 'Food item not specified'); ?>
                                        </p>
                                        <p class="mb-1">
                                            <i class="fas fa-money-bill-wave mr-1"></i> 
                                            ₹<?php echo number_format(floatval($order['total_amount'] ?? 0), 2); ?>
                                        </p>
                                        <button class="btn btn-sm btn-info mt-2 copy-order" 
                                                data-id="<?php echo $order['user_id']; ?>" 
                                                data-amount="<?php echo floatval($order['total_amount'] ?? 0); ?>" 
                                                data-reason="Food Order">
                                            <i class="fas fa-copy mr-1"></i> Add to Fees
                                        </button>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> No recent approved orders found.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="fee-card">
            <div class="fee-header">
                <i class="fas fa-history mr-2"></i> Recent Fee Additions
            </div>
            <div class="fee-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>User</th>
                                <th>Amount</th>
                                <th>Reason</th>
                                <th>Admin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if($transactions_result && mysqli_num_rows($transactions_result) > 0): ?>
                                <?php while($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                    <tr>
                                        <td><?php echo date('d M Y, h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['fname'] . ' ' . $transaction['lname']); ?></td>
                                        <td>₹<?php echo number_format($transaction['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['reason'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($transaction['admin_email']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center">No recent fee additions found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Handle copy from order to fee form
        document.addEventListener('DOMContentLoaded', function() {
            var copyButtons = document.querySelectorAll('.copy-order');
            copyButtons.forEach(function(button) {
                button.addEventListener('click', function() {
                    var userId = this.getAttribute('data-id');
                    var amount = this.getAttribute('data-amount');
                    var reason = this.getAttribute('data-reason');
                    
                    document.getElementById('user_id').value = userId;
                    document.getElementById('amount').value = amount;
                    document.getElementById('reason').value = reason;
                    
                    // Scroll to form
                    document.getElementById('user_id').scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                });
            });
        });
    </script>
</body>
</html>
<?php
} else {
    header('location:../index.php');
    exit();
}
?> 

// End output buffering
ob_end_flush();
?>