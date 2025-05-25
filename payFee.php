<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

  session_start();
  if(isset($_SESSION['email'])){
    include('../includes/connection.php');
    
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
          transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          admin_email VARCHAR(255) NOT NULL
      )";
      mysqli_query($connection, $create_table);
    } else {
      // Check if the table has admin_email column
      $check_email_column = mysqli_query($connection, "SHOW COLUMNS FROM fee_transactions LIKE 'admin_email'");
      if(mysqli_num_rows($check_email_column) == 0) {
        // Add admin_email column if it doesn't exist
        mysqli_query($connection, "ALTER TABLE fee_transactions ADD COLUMN admin_email VARCHAR(255) NOT NULL");
      }
    }
    
    // Handle fee payment
    if(isset($_POST['pay_fee'])) {
      $user_id = intval($_POST['id']);
      
      // Get user's fee amount before marking as paid
      $get_fee = "SELECT fee_amount FROM users WHERE sno = ?";
      $stmt = mysqli_prepare($connection, $get_fee);
      mysqli_stmt_bind_param($stmt, "i", $user_id);
      mysqli_stmt_execute($stmt);
      $result = mysqli_stmt_get_result($stmt);
      
      if(mysqli_num_rows($result) > 0) {
        $fee_data = mysqli_fetch_assoc($result);
        $fee_amount = $fee_data['fee_amount'];
        
        // Begin transaction
        mysqli_begin_transaction($connection);
        
        try {
          // Update user's fee status
          $update_query = "UPDATE users SET fee_status = 1 WHERE sno = ?";
          $stmt = mysqli_prepare($connection, $update_query);
          mysqli_stmt_bind_param($stmt, "i", $user_id);
          mysqli_stmt_execute($stmt);
          
          // Record the transaction
          $admin_email = $_SESSION['email'];
          $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, admin_email) VALUES (?, ?, 'payment', 'Fee Payment', ?)";
          $stmt = mysqli_prepare($connection, $insert_query);
          mysqli_stmt_bind_param($stmt, "ids", $user_id, $fee_amount, $admin_email);
          mysqli_stmt_execute($stmt);
          
          mysqli_commit($connection);
          echo "<script>alert('Fee payment recorded successfully.');</script>";
        } catch (Exception $e) {
          mysqli_rollback($connection);
          echo "<script>alert('Error processing payment: " . mysqli_error($connection) . "');</script>";
        }
      } else {
        echo "<script>alert('User not found. Please check the ID.');</script>";
      }
    }
    
    // Handle fee amount update
    if(isset($_POST['update_fee'])) {
      $update_id = intval($_POST['update_id']);
      $fee_amount = floatval($_POST['fee_amount']);
      $reason = mysqli_real_escape_string($connection, $_POST['reason']);
      
      // Begin transaction
      mysqli_begin_transaction($connection);
      
      try {
        // Update user's fee amount
        $query = "UPDATE users SET fee_amount = ? WHERE sno = ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "di", $fee_amount, $update_id);
        mysqli_stmt_execute($stmt);
        
        // Record the transaction
        $admin_email = $_SESSION['email'];
        $insert_query = "INSERT INTO fee_transactions (user_id, amount, type, reason, admin_email) VALUES (?, ?, 'add', ?, ?)";
        $stmt = mysqli_prepare($connection, $insert_query);
        mysqli_stmt_bind_param($stmt, "idss", $update_id, $fee_amount, $reason, $admin_email);
        mysqli_stmt_execute($stmt);
        
        mysqli_commit($connection);
        echo "<script>alert('Fee amount updated successfully!');</script>";
      } catch (Exception $e) {
        mysqli_rollback($connection);
        echo "<script>alert('Error updating fee amount: " . mysqli_error($connection) . "');</script>";
      }
    }
    
    // Calculate total fees
    $query = "SELECT 
              SUM(CASE WHEN fee_status = 0 THEN fee_amount ELSE 0 END) as total_fees_due,
              SUM(CASE WHEN fee_status = 1 THEN fee_amount ELSE 0 END) as total_fees_collected,
              SUM(fee_amount) as overall_total
              FROM users";
    $result = mysqli_query($connection, $query);
    $row = mysqli_fetch_assoc($result);
    $total_fees_due = $row['total_fees_due'] ?? 0;
    $total_fees_collected = $row['total_fees_collected'] ?? 0;
    $overall_total = $row['overall_total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Fee Management</title>
    <link rel="stylesheet" href="../assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
      .fee-form-container {
        background-color: white;
        border-radius: 10px;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        padding: 20px;
      }
      .form-title {
        color: #194350;
        text-align: center;
        margin-bottom: 20px;
        font-weight: 600;
      }
      .form-control {
        border-radius: 5px;
        padding: 10px;
        margin-bottom: 15px;
      }
      .btn-pay {
        background-color: #194350;
        color: white;
        border: none;
        border-radius: 5px;
        padding: 10px 20px;
        font-weight: 500;
        transition: all 0.3s;
        width: 100%;
      }
      .btn-pay:hover {
        background-color: #0d2b36;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      }
      .fee-stats {
        background-color: #f8f9fa;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
      }
      .stat-card {
        background-color: white;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      }
      .stat-title {
        color: #6c757d;
        font-size: 0.9rem;
        margin-bottom: 5px;
      }
      .stat-value {
        font-size: 1.5rem;
        font-weight: 600;
        color: #194350;
      }
      .table th {
        background-color: #194350;
        color: white;
      }
      .badge {
        padding: 8px 12px;
        border-radius: 4px;
      }
      .transaction-history {
        max-height: 400px;
        overflow-y: auto;
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="col-md-12 fee-form-container">
          <h4 class="form-title">
            <i class="fas fa-money-bill-wave mr-2"></i> Fee Management System
          </h4>
          
          <!-- Fee Statistics -->
          <div class="fee-stats">
            <div class="row">
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-title">
                    <i class="fas fa-exclamation-circle mr-1"></i> Total Fees Due
                  </div>
                  <div class="stat-value text-danger">
                    ₹<?php echo number_format($total_fees_due, 2); ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-title">
                    <i class="fas fa-check-circle mr-1"></i> Total Fees Collected
                  </div>
                  <div class="stat-value text-success">
                    ₹<?php echo number_format($total_fees_collected, 2); ?>
                  </div>
                </div>
              </div>
              <div class="col-md-4">
                <div class="stat-card">
                  <div class="stat-title">
                    <i class="fas fa-calculator mr-1"></i> Overall Total
                  </div>
                  <div class="stat-value text-primary">
                    ₹<?php echo number_format($overall_total, 2); ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Users with Unpaid Fees -->
          <div class="card mb-4">
            <div class="card-header bg-danger text-white">
              <h5 class="mb-0">
                <i class="fas fa-user-clock mr-2"></i> Users with Unpaid Fees
              </h5>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Name</th>
                      <th>Email</th>
                      <th>Fee Amount</th>
                      <th>Status</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $query = "SELECT sno, fname, lname, email, fee_status, fee_amount FROM users WHERE fee_status = 0";
                      $result = mysqli_query($connection, $query);
                      if(mysqli_num_rows($result) > 0) {
                        while($row = mysqli_fetch_assoc($result)) {
                          echo "<tr>
                            <td>{$row['sno']}</td>
                            <td>" . htmlspecialchars($row['fname'] . ' ' . $row['lname']) . "</td>
                            <td>" . htmlspecialchars($row['email']) . "</td>
                            <td>₹" . number_format($row['fee_amount'], 2) . "</td>
                            <td><span class='badge badge-danger'>Unpaid</span></td>
                            <td>
                              <button class='btn btn-sm btn-primary edit-fee' data-id='{$row['sno']}' data-amount='{$row['fee_amount']}'>
                                <i class='fas fa-edit'></i> Edit
                              </button>
                              <button class='btn btn-sm btn-success mark-paid' data-id='{$row['sno']}'>
                                <i class='fas fa-check'></i> Mark Paid
                              </button>
                            </td>
                          </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='6' class='text-center'>All users have paid their fees!</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
          <div class="row">
            <!-- Mark Fee as Paid Form -->
            <div class="col-md-6">
              <div class="card mb-4">
                <div class="card-header bg-success text-white">
                  <h5 class="mb-0">
                    <i class="fas fa-check-circle mr-2"></i> Mark Fee as Paid
                  </h5>
                </div>
                <div class="card-body">
                  <form action="" method="post">
                    <div class="form-group">
                      <label for="id">
                        <i class="fas fa-id-card mr-2"></i> User ID:
                      </label>
                      <input type="text" name="id" id="id" class="form-control" placeholder="Enter User ID" required>
                      <small class="form-text text-muted">Enter the User ID from the table above.</small>
                    </div>
                    <button type="submit" class="btn btn-success btn-block" name="pay_fee">
                      <i class="fas fa-check-circle mr-2"></i> Mark as Paid
                    </button>
                  </form>
                </div>
              </div>
            </div>
            
            <!-- Update Fee Amount Form -->
            <div class="col-md-6">
              <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">
                    <i class="fas fa-edit mr-2"></i> Update Fee Amount
                  </h5>
                </div>
                <div class="card-body">
                  <form action="" method="post" id="updateFeeForm">
                    <div class="form-group">
                      <label for="update_id">User ID:</label>
                      <input type="text" name="update_id" id="update_id" class="form-control" required readonly>
                    </div>
                    <div class="form-group">
                      <label for="fee_amount">Fee Amount (₹):</label>
                      <input type="number" name="fee_amount" id="fee_amount" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="form-group">
                      <label for="reason">Reason:</label>
                      <select name="reason" id="reason" class="form-control" required>
                        <option value="Food Order">Food Order</option>
                        <option value="Monthly Fee">Monthly Fee</option>
                        <option value="Special Meal">Special Meal</option>
                        <option value="Other">Other</option>
                      </select>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block" name="update_fee">
                      <i class="fas fa-save mr-2"></i> Update Fee Amount
                    </button>
                  </form>
                </div>
              </div>
            </div>
          </div>
          
          <!-- Recent Transactions -->
          <div class="card">
            <div class="card-header bg-info text-white">
              <h5 class="mb-0">
                <i class="fas fa-history mr-2"></i> Recent Transactions
              </h5>
            </div>
            <div class="card-body transaction-history">
              <div class="table-responsive">
                <table class="table table-striped">
                  <thead>
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
                      // Show transactions with modified query to handle the new schema
                      $transactions_query = "SELECT t.*, u.fname, u.lname 
                                          FROM fee_transactions t 
                                          INNER JOIN users u ON t.user_id = u.sno 
                                          ORDER BY t.transaction_date DESC 
                                          LIMIT 10";
                      $transactions_result = mysqli_query($connection, $transactions_query);
                      
                      if($transactions_result && mysqli_num_rows($transactions_result) > 0) {
                        while($transaction = mysqli_fetch_assoc($transactions_result)) {
                          $type_badge = $transaction['type'] == 'add' ? 
                              '<span class="badge badge-danger">Fee Added</span>' : 
                              '<span class="badge badge-success">Payment</span>';
                          
                          echo "<tr>
                            <td>" . date('d M Y, h:i A', strtotime($transaction['transaction_date'])) . "</td>
                            <td>" . htmlspecialchars($transaction['fname'] . ' ' . $transaction['lname']) . "</td>
                            <td>{$type_badge}</td>
                            <td>₹" . number_format($transaction['amount'], 2) . "</td>
                            <td>" . htmlspecialchars($transaction['reason'] ?? 'N/A') . "</td>
                            <td>" . htmlspecialchars($transaction['admin_email']) . "</td>
                          </tr>";
                        }
                      } else {
                        echo "<tr><td colspan='6' class='text-center'>No transactions found</td></tr>";
                      }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          
        </div>
      </div>
    </div>

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
      $(document).ready(function() {
        // Handle Edit button click
        $(".edit-fee").click(function() {
          var userId = $(this).data('id');
          var feeAmount = $(this).data('amount');
          
          // Populate the form
          $("#update_id").val(userId);
          $("#fee_amount").val(feeAmount);
          
          // Scroll to the form
          $('html, body').animate({
            scrollTop: $("#updateFeeForm").offset().top - 100
          }, 500);
        });
        
        // Handle Mark Paid button click
        $(".mark-paid").click(function() {
          var userId = $(this).data('id');
          $("#id").val(userId);
          
          // Scroll to the pay form
          $('html, body').animate({
            scrollTop: $("#id").offset().top - 100
          }, 500);
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