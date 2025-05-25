<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
    $_SESSION['error_message'] = "User ID is required";
    header("Location: user_bills.php");
    exit();
}

$user_id = intval($_GET['user_id']);

// Connect to the database
include '../includes/connection.php';

// Determine user table structure
$id_column = 'id';
$name_column = 'name';
$columns = [];

// Check table structure
$result = mysqli_query($connection, "DESCRIBE users");
if ($result) {
    $has_id = false;
    $has_sno = false;
    $has_name = false;
    $has_fname = false;
    $has_lname = false;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
        if ($row['Field'] == 'id') {
            $has_id = true;
        } else if ($row['Field'] == 'sno') {
            $has_sno = true;
        } else if ($row['Field'] == 'name') {
            $has_name = true;
        } else if ($row['Field'] == 'fname') {
            $has_fname = true;
        } else if ($row['Field'] == 'lname') {
            $has_lname = true;
        }
    }
    
    // Determine primary key column
    if ($has_sno && !$has_id) {
        $id_column = 'sno';
    }
    
    // Determine name display
    if (!$has_name && $has_fname && $has_lname) {
        $name_column = "CONCAT(fname, ' ', lname)";
    } else if (!$has_name && $has_fname) {
        $name_column = "fname";
    }
}

// Fetch user details
$sql = "SELECT *, $name_column AS display_name FROM users WHERE $id_column = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    $_SESSION['error_message'] = "User not found";
    header("Location: user_bills.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Now that all redirects are handled, include the header
include 'header.php';

// Fetch bill data from fee_transactions or other relevant tables
$transactions_sql = "SELECT * FROM fee_transactions WHERE user_id = ? ORDER BY transaction_date DESC";
$stmt = mysqli_prepare($connection, $transactions_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$transactions_result = mysqli_stmt_get_result($stmt);

// Calculate totals
$total_charges = 0;
$total_payments = 0;

// Get current month and year
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>User Bill: <?php echo htmlspecialchars($user['display_name']); ?></h2>
                <a href="user_bills.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Bills
                </a>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- User Information -->
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>User Information</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($user['display_name']); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                            <?php 
                            $phone_field = isset($user['phone']) ? 'phone' : (isset($user['mobile']) ? 'mobile' : '');
                            if (!empty($phone_field) && !empty($user[$phone_field])): 
                            ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($user[$phone_field]); ?></p>
                            <?php endif; ?>
                            
                            <?php
                            // Calculate totals from transactions
                            $charges_query = "SELECT SUM(amount) as total FROM fee_transactions 
                                            WHERE user_id = ? AND type = 'add'
                                            AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
                            $stmt = mysqli_prepare($connection, $charges_query);
                            mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
                            mysqli_stmt_execute($stmt);
                            $charges_result = mysqli_stmt_get_result($stmt);
                            $charges = mysqli_fetch_assoc($charges_result);
                            $total_charges = $charges['total'] ?: 0;
                            
                            $payments_query = "SELECT SUM(amount) as total FROM fee_transactions 
                                             WHERE user_id = ? AND type = 'payment'
                                             AND MONTH(transaction_date) = ? AND YEAR(transaction_date) = ?";
                            $stmt = mysqli_prepare($connection, $payments_query);
                            mysqli_stmt_bind_param($stmt, "iss", $user_id, $month, $year);
                            mysqli_stmt_execute($stmt);
                            $payments_result = mysqli_stmt_get_result($stmt);
                            $payments = mysqli_fetch_assoc($payments_result);
                            $total_payments = $payments['total'] ?: 0;
                            
                            $balance = $total_charges - $total_payments;
                            ?>
                            
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Charges:</span>
                                    <span>₹<?php echo number_format($total_charges, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Payments:</span>
                                    <span>₹<?php echo number_format($total_payments, 2); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="fw-bold">Outstanding Balance:</span>
                                    <span class="fw-bold text-<?php echo $balance > 0 ? 'danger' : 'success'; ?>">
                                        ₹<?php echo number_format($balance, 2); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Actions</h4>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="user_bills.php?user_id=<?php echo $user_id; ?>" class="btn btn-primary">
                                    <i class="fas fa-receipt me-1"></i> View All Bills
                                </a>
                                <a href="edit_users.php?user_id=<?php echo $user_id; ?>" class="btn btn-info">
                                    <i class="fas fa-user-edit me-1"></i> Edit User
                                </a>
                                <button type="button" class="btn btn-success" onclick="window.print();">
                                    <i class="fas fa-print me-1"></i> Print Bill
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transactions -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Transaction History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Date</th>
                                            <th>Description</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Method</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($transactions_result) > 0): ?>
                                            <?php while ($transaction = mysqli_fetch_assoc($transactions_result)): ?>
                                                <tr>
                                                    <td><?php echo date('M d, Y', strtotime($transaction['transaction_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['reason'] ?? 'N/A'); ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo $transaction['type'] == 'payment' ? 'success' : 'warning'; ?>">
                                                            <?php echo ucfirst($transaction['type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>₹<?php echo number_format($transaction['amount'], 2); ?></td>
                                                    <td><?php echo htmlspecialchars($transaction['payment_method'] ?? 'N/A'); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No transactions found.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 