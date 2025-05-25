<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include('header.php');
if(isset($_SESSION['email'])){
    include('../includes/connection.php');
    
    // Check if needed tables exist
    $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'food_prices'");
    if(mysqli_num_rows($check_table) == 0) {
        header('location: attendance_reports.php');
        exit();
    }
    
    // Get the month and year for filtering
    $current_month = date('m');
    $current_year = date('Y');
    
    // Check for payment success messages
    $payment_success = false;
    $payment_amount = 0;
    if(isset($_GET['payment']) && $_GET['payment'] == 'success' && isset($_GET['amount'])) {
        $payment_success = true;
        $payment_amount = floatval($_GET['amount']);
    }
    
    if(isset($_GET['month']) && isset($_GET['year'])) {
        $month = mysqli_real_escape_string($connection, $_GET['month']);
        $year = mysqli_real_escape_string($connection, $_GET['year']);
    } else {
        $month = $current_month;
        $year = $current_year;
    }
    
    // Get selected user ID if any
    $selected_user = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    
    // Get food prices
    $query = "SELECT * FROM food_prices";
    $result = mysqli_query($connection, $query);
    $prices = [];
    while($row = mysqli_fetch_assoc($result)) {
        $prices[$row['meal_type']] = $row['price'];
    }
    
    // Function to calculate user bill
    function calculateUserBill($connection, $user_id, $month, $year, $prices, $additional_payment = 0) {
        $start_date = "$year-$month-01";
        $end_date = date('Y-m-t', strtotime($start_date));
        
        $bill_details = [
            'breakfast' => ['count' => 0, 'amount' => 0],
            'lunch' => ['count' => 0, 'amount' => 0],
            'snacks' => ['count' => 0, 'amount' => 0],
            'dinner' => ['count' => 0, 'amount' => 0],
            'food_orders' => ['count' => 0, 'amount' => 0],
            'other_fees' => ['count' => 0, 'amount' => 0],
            'total' => 0,
            'dates' => [],
            'transactions' => []
        ];
        
        // Get breakfast data
        $query = "SELECT date FROM attendance1 WHERE id = ? AND attendance = 'Present' AND date BETWEEN ? AND ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bill_details['breakfast']['count'] = mysqli_num_rows($result);
        $bill_details['breakfast']['amount'] = $bill_details['breakfast']['count'] * $prices['Breakfast'];
        while($row = mysqli_fetch_assoc($result)) {
            if(!isset($bill_details['dates'][$row['date']])) {
                $bill_details['dates'][$row['date']] = [];
            }
            $bill_details['dates'][$row['date']]['breakfast'] = $prices['Breakfast'];
        }
        mysqli_stmt_close($stmt);
        
        // Get lunch data
        $query = "SELECT date FROM attendance2 WHERE id = ? AND attendance = 'Present' AND date BETWEEN ? AND ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bill_details['lunch']['count'] = mysqli_num_rows($result);
        $bill_details['lunch']['amount'] = $bill_details['lunch']['count'] * $prices['Lunch'];
        while($row = mysqli_fetch_assoc($result)) {
            if(!isset($bill_details['dates'][$row['date']])) {
                $bill_details['dates'][$row['date']] = [];
            }
            $bill_details['dates'][$row['date']]['lunch'] = $prices['Lunch'];
        }
        mysqli_stmt_close($stmt);
        
        // Get snacks data
        $query = "SELECT date FROM attendance3 WHERE id = ? AND attendance = 'Present' AND date BETWEEN ? AND ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bill_details['snacks']['count'] = mysqli_num_rows($result);
        $bill_details['snacks']['amount'] = $bill_details['snacks']['count'] * $prices['Snacks'];
        while($row = mysqli_fetch_assoc($result)) {
            if(!isset($bill_details['dates'][$row['date']])) {
                $bill_details['dates'][$row['date']] = [];
            }
            $bill_details['dates'][$row['date']]['snacks'] = $prices['Snacks'];
        }
        mysqli_stmt_close($stmt);
        
        // Get dinner data
        $query = "SELECT date FROM attendance4 WHERE id = ? AND attendance = 'Present' AND date BETWEEN ? AND ?";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $bill_details['dinner']['count'] = mysqli_num_rows($result);
        $bill_details['dinner']['amount'] = $bill_details['dinner']['count'] * $prices['Dinner'];
        while($row = mysqli_fetch_assoc($result)) {
            if(!isset($bill_details['dates'][$row['date']])) {
                $bill_details['dates'][$row['date']] = [];
            }
            $bill_details['dates'][$row['date']]['dinner'] = $prices['Dinner'];
        }
        mysqli_stmt_close($stmt);
        
        // Get fee transactions for food orders
        $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'fee_transactions'");
        if(mysqli_num_rows($check_table) > 0) {
            // First get all charges (food orders and other fees)
            $query = "SELECT * FROM fee_transactions 
                     WHERE user_id = ? AND type = 'add' 
                     AND transaction_date BETWEEN ? AND ? 
                     ORDER BY transaction_date ASC";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)) {
                // Add to transactions list
                $bill_details['transactions'][] = $row;
                
                // Categorize the transaction
                if(strpos($row['reason'], 'Food Order') !== false) {
                    $bill_details['food_orders']['count']++;
                    $bill_details['food_orders']['amount'] += $row['amount'];
                } else {
                    $bill_details['other_fees']['count']++;
                    $bill_details['other_fees']['amount'] += $row['amount'];
                }
                
                // Add to daily breakdown
                $date = date('Y-m-d', strtotime($row['transaction_date']));
                if(!isset($bill_details['dates'][$date])) {
                    $bill_details['dates'][$date] = [];
                }
                if(!isset($bill_details['dates'][$date]['orders'])) {
                    $bill_details['dates'][$date]['orders'] = 0;
                }
                $bill_details['dates'][$date]['orders'] += $row['amount'];
            }
            mysqli_stmt_close($stmt);
            
            // Then get all payments
            $query = "SELECT * FROM fee_transactions 
                     WHERE user_id = ? AND type = 'payment' 
                     AND transaction_date BETWEEN ? AND ? 
                     ORDER BY transaction_date ASC";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "iss", $user_id, $start_date, $end_date);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            
            while($row = mysqli_fetch_assoc($result)) {
                // Add to transactions list
                $bill_details['transactions'][] = $row;
                
                // Add payments to a separate category
                if(!isset($bill_details['payments'])) {
                    $bill_details['payments'] = ['count' => 0, 'amount' => 0];
                }
                $bill_details['payments']['count']++;
                $bill_details['payments']['amount'] += $row['amount'];
                
                // Add to daily breakdown
                $date = date('Y-m-d', strtotime($row['transaction_date']));
                if(!isset($bill_details['dates'][$date])) {
                    $bill_details['dates'][$date] = [];
                }
                if(!isset($bill_details['dates'][$date]['payments'])) {
                    $bill_details['dates'][$date]['payments'] = 0;
                }
                $bill_details['dates'][$date]['payments'] += $row['amount'];
            }
            mysqli_stmt_close($stmt);
        }
        
        // Calculate total (now including payments)
        $bill_details['total'] = $bill_details['breakfast']['amount'] + 
                               $bill_details['lunch']['amount'] + 
                               $bill_details['snacks']['amount'] + 
                               $bill_details['dinner']['amount'] +
                               $bill_details['food_orders']['amount'] +
                               $bill_details['other_fees']['amount'];
        
        // Subtract payments if any exist
        if(isset($bill_details['payments'])) {
            $bill_details['total'] -= $bill_details['payments']['amount'];
        }
        
        // Subtract additional payment from URL parameter (for immediately after payment)
        if($additional_payment > 0) {
            $bill_details['total'] -= $additional_payment;
            
            // If we go negative, reset to zero
            if($bill_details['total'] < 0) {
                $bill_details['total'] = 0;
            }
        }
        
        return $bill_details;
    }
    
    // Get all users
    $query = "SELECT sno, fname, lname, email FROM users ORDER BY fname, lname";
    $result = mysqli_query($connection, $query);
    $users = [];
    while($row = mysqli_fetch_assoc($result)) {
        $users[$row['sno']] = $row;
    }
    
    // Generate bills for all users or selected user
    $bills = [];
    if($selected_user > 0) {
        if(isset($users[$selected_user])) {
            $bills[$selected_user] = calculateUserBill($connection, $selected_user, $month, $year, $prices, $payment_success ? $payment_amount : 0);
        }
    } else {
        foreach($users as $user_id => $user) {
            $bills[$user_id] = calculateUserBill($connection, $user_id, $month, $year, $prices, ($payment_success && $user_id == $selected_user) ? $payment_amount : 0);
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Bills</title>
    <style>
        .bill-container {
            padding: 20px;
        }
        .bill-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .bill-header {
            background-color: #194350;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .bill-body {
            padding: 20px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
            border: 1px solid #ddd;
        }
        .btn-filter {
            background-color: #194350;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .btn-filter:hover {
            background-color: #0d2b36;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .bill-summary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .bill-summary-table th, .bill-summary-table td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .bill-summary-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .bill-summary-table tbody tr:hover {
            background-color: #f9f9f9;
        }
        .meal-count {
            font-size: 14px;
            color: #666;
        }
        .total-amount {
            font-weight: bold;
            color: #194350;
        }
        .bill-details-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        .bill-details-table th, .bill-details-table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .bill-details-table th {
            background-color: #f2f2f2;
            font-weight: 600;
        }
        .bill-details-table thead th {
            background-color: #194350;
            color: white;
        }
        .user-info {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #194350;
        }
        .total-row {
            background-color: #e9f7e9;
            font-weight: bold;
        }
        .present {
            color: #28a745;
        }
        .absent {
            color: #dc3545;
        }
        .date-cell {
            white-space: nowrap;
        }
        .print-btn {
            background-color: #28a745;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .print-btn:hover {
            background-color: #218838;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            .bill-card {
                box-shadow: none;
                margin-bottom: 10px;
            }
            .bill-header {
                background-color: #f8f9fa !important;
                color: #000 !important;
                border-bottom: 2px solid #194350;
            }
            .bill-details-table thead th {
                background-color: #f8f9fa !important;
                color: #000 !important;
                border-bottom: 2px solid #194350;
            }
        }
    </style>
</head>
<body>
    <div class="container bill-container">
        <div class="bill-card">
            <div class="bill-header no-print">
                <i class="fas fa-file-invoice-dollar mr-2"></i> User Bills
            </div>
            <div class="bill-body">
                <!-- Filter form -->
                <form action="" method="get" class="no-print">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="user_id">Select User:</label>
                                <select class="form-control" name="user_id" id="user_id">
                                    <option value="0">All Users</option>
                                    <?php foreach($users as $user_id => $user): ?>
                                        <option value="<?php echo $user_id; ?>" <?php echo ($selected_user == $user_id) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($user['fname'] . ' ' . $user['lname']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="month">Month:</label>
                                <select class="form-control" name="month" id="month">
                                    <?php
                                    for($i = 1; $i <= 12; $i++) {
                                        $month_name = date('F', mktime(0, 0, 0, $i, 1));
                                        echo '<option value="' . sprintf('%02d', $i) . '" ' . 
                                              ($month == sprintf('%02d', $i) ? 'selected' : '') . '>' . 
                                              $month_name . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="year">Year:</label>
                                <select class="form-control" name="year" id="year">
                                    <?php
                                    $start_year = $current_year - 1;
                                    $end_year = $current_year + 1;
                                    for($i = $start_year; $i <= $end_year; $i++) {
                                        echo '<option value="' . $i . '" ' . 
                                              ($year == $i ? 'selected' : '') . '>' . 
                                              $i . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-filter btn-block">
                                    <i class="fas fa-filter mr-2"></i> Filter
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
                
                <div class="text-right mb-3 no-print">
                    <button class="print-btn" onclick="window.print()">
                        <i class="fas fa-print mr-2"></i> Print Bills
                    </button>
                </div>
                
                <?php if(isset($_GET['payment']) && $_GET['payment'] == 'success'): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <strong><i class="fas fa-check-circle mr-2"></i>Payment Successful!</strong> The bill has been updated.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>
                
                <?php if($selected_user > 0 && isset($bills[$selected_user])): ?>
                    <!-- Detailed view for a single user -->
                    <div class="user-info">
                        <h4><?php echo htmlspecialchars($users[$selected_user]['fname'] . ' ' . $users[$selected_user]['lname']); ?></h4>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($users[$selected_user]['email']); ?></p>
                        <p><strong>Month:</strong> <?php echo date('F Y', strtotime("$year-$month-01")); ?></p>
                    </div>
                    
                    <h5>Bill Summary</h5>
                    <table class="bill-summary-table">
                        <thead>
                            <tr>
                                <th>Meal Type</th>
                                <th>Number of Meals</th>
                                <th>Rate</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>Breakfast</td>
                                <td><?php echo $bills[$selected_user]['breakfast']['count']; ?> meals</td>
                                <td>₹<?php echo number_format($prices['Breakfast'], 2); ?></td>
                                <td>₹<?php echo number_format($bills[$selected_user]['breakfast']['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Lunch</td>
                                <td><?php echo $bills[$selected_user]['lunch']['count']; ?> meals</td>
                                <td>₹<?php echo number_format($prices['Lunch'], 2); ?></td>
                                <td>₹<?php echo number_format($bills[$selected_user]['lunch']['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Snacks</td>
                                <td><?php echo $bills[$selected_user]['snacks']['count']; ?> meals</td>
                                <td>₹<?php echo number_format($prices['Snacks'], 2); ?></td>
                                <td>₹<?php echo number_format($bills[$selected_user]['snacks']['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Dinner</td>
                                <td><?php echo $bills[$selected_user]['dinner']['count']; ?> meals</td>
                                <td>₹<?php echo number_format($prices['Dinner'], 2); ?></td>
                                <td>₹<?php echo number_format($bills[$selected_user]['dinner']['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Food Orders</td>
                                <td><?php echo $bills[$selected_user]['food_orders']['count']; ?> orders</td>
                                <td>Varies</td>
                                <td>₹<?php echo number_format($bills[$selected_user]['food_orders']['amount'], 2); ?></td>
                            </tr>
                            <tr>
                                <td>Other Fees</td>
                                <td><?php echo $bills[$selected_user]['other_fees']['count']; ?> fees</td>
                                <td>Varies</td>
                                <td>₹<?php echo number_format($bills[$selected_user]['other_fees']['amount'], 2); ?></td>
                            </tr>
                            <?php if(isset($bills[$selected_user]['payments'])): ?>
                            <tr>
                                <td>Payments Made</td>
                                <td><?php echo $bills[$selected_user]['payments']['count']; ?> payments</td>
                                <td>Varies</td>
                                <td>-₹<?php echo number_format($bills[$selected_user]['payments']['amount'], 2); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr class="total-row">
                                <td colspan="3"><strong>Total Outstanding</strong></td>
                                <td>₹<?php echo number_format($bills[$selected_user]['total'], 2); ?></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <!-- Add Pay Now button with payment options -->
                    <div class="text-right mt-3 no-print">
                        <?php if($bills[$selected_user]['total'] > 0): ?>
                        <button type="button" class="btn btn-success" data-toggle="modal" data-target="#paymentModal" onclick="openPaymentModal()">
                            <i class="fas fa-money-bill-wave mr-2"></i> Pay Now
                        </button>
                        <?php else: ?>
                        <button class="btn btn-success" disabled>
                            <i class="fas fa-check-circle mr-2"></i> Paid
                        </button>
                        <?php endif; ?>
                        <a href="print_bill.php?user_id=<?php echo $selected_user; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-primary ml-2" target="_blank">
                            <i class="fas fa-print mr-2"></i> Print Bill
                        </a>
                    </div>

                    <!-- Payment Modal -->
                    <div class="modal" id="paymentModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="paymentModalLabel">Process Payment</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" onclick="closePaymentModal()">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form action="process_payment.php" method="POST">
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>User Name:</label>
                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($users[$selected_user]['fname'] . ' ' . $users[$selected_user]['lname']); ?>" readonly>
                                            <input type="hidden" name="user_id" value="<?php echo $selected_user; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Total Amount:</label>
                                            <input type="text" class="form-control" value="₹<?php echo number_format($bills[$selected_user]['total'], 2); ?>" readonly>
                                            <input type="hidden" name="amount" value="<?php echo $bills[$selected_user]['total']; ?>">
                                            <input type="hidden" name="month" value="<?php echo $month; ?>">
                                            <input type="hidden" name="year" value="<?php echo $year; ?>">
                                        </div>
                                        <div class="form-group">
                                            <label>Payment Method:</label>
                                            <select class="form-control" name="payment_method" required>
                                                <option value="">Select Payment Method</option>
                                                <option value="cash">Cash</option>
                                                <option value="upi">UPI</option>
                                                <option value="card">Card</option>
                                                <option value="bank_transfer">Bank Transfer</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label>Transaction Reference (if applicable):</label>
                                            <input type="text" class="form-control" name="transaction_ref" placeholder="Enter transaction reference number">
                                        </div>
                                        <div class="form-group">
                                            <label>Notes:</label>
                                            <textarea class="form-control" name="notes" rows="2" placeholder="Any additional notes"></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="closePaymentModal()">Cancel</button>
                                        <button type="submit" name="pay_bill" class="btn btn-success">
                                            <i class="fas fa-check mr-2"></i> Process Payment
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <?php if(count($bills[$selected_user]['dates']) > 0): ?>
                        <h5 class="mt-4">Daily Meal Details</h5>
                        <table class="bill-details-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Breakfast<br><small>₹<?php echo number_format($prices['Breakfast'], 2); ?></small></th>
                                    <th>Lunch<br><small>₹<?php echo number_format($prices['Lunch'], 2); ?></small></th>
                                    <th>Snacks<br><small>₹<?php echo number_format($prices['Snacks'], 2); ?></small></th>
                                    <th>Dinner<br><small>₹<?php echo number_format($prices['Dinner'], 2); ?></small></th>
                                    <th>Food Orders</th>
                                    <th>Other Fees</th>
                                    <th>Daily Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $start_date = "$year-$month-01";
                                $days_in_month = date('t', strtotime($start_date));
                                $total = 0;
                                
                                for($day = 1; $day <= $days_in_month; $day++) {
                                    $current_date = sprintf('%s-%s-%02d', $year, $month, $day);
                                    $daily_total = 0;
                                    echo "<tr>";
                                    echo "<td class='date-cell'>" . date('d M Y (D)', strtotime($current_date)) . "</td>";
                                    
                                    // Breakfast
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['breakfast'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['breakfast'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['breakfast'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Lunch
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['lunch'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['lunch'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['lunch'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Snacks
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['snacks'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['snacks'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['snacks'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Dinner
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['dinner'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['dinner'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['dinner'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Food Orders
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['orders'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['orders'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['orders'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Other Fees
                                    echo "<td>";
                                    if(isset($bills[$selected_user]['dates'][$current_date]['other_fees'])) {
                                        echo "<span class='present'>Present</span><br>";
                                        echo "<small>₹" . number_format($bills[$selected_user]['dates'][$current_date]['other_fees'], 2) . "</small>";
                                        $daily_total += $bills[$selected_user]['dates'][$current_date]['other_fees'];
                                    } else {
                                        echo "<span class='absent'>Absent</span>";
                                    }
                                    echo "</td>";
                                    
                                    // Daily total
                                    echo "<td>₹" . number_format($daily_total, 2) . "</td>";
                                    echo "</tr>";
                                    
                                    $total += $daily_total;
                                }
                                ?>
                                <tr class="total-row">
                                    <td colspan="8"><strong>Total</strong></td>
                                    <td>₹<?php echo number_format($total, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="alert alert-info mt-4">
                            No attendance records found for this user in the selected month.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Fee Transactions Section -->
                    <?php if(!empty($bills[$selected_user]['transactions'])): ?>
                    <div class="bill-card mt-4">
                        <div class="bill-header">
                            <i class="fas fa-history mr-2"></i> Fee Transaction History
                        </div>
                        <div class="bill-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Admin</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($bills[$selected_user]['transactions'] as $transaction): ?>
                                            <tr>
                                                <td><?php echo date('d M Y, h:i A', strtotime($transaction['transaction_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['reason']); ?></td>
                                                <td>₹<?php echo number_format($transaction['amount'], 2); ?></td>
                                                <td><?php echo htmlspecialchars($transaction['admin_email']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Summary view for all users -->
                    <h5>Monthly Bill Summary - <?php echo date('F Y', strtotime("$year-$month-01")); ?></h5>
                    <table class="bill-summary-table">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Breakfast</th>
                                <th>Lunch</th>
                                <th>Snacks</th>
                                <th>Dinner</th>
                                <th>Food Orders</th>
                                <th>Other Fees</th>
                                <th>Total Bill</th>
                                <th class="no-print">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $grand_total = 0;
                            foreach($bills as $user_id => $bill): 
                                $grand_total += $bill['total'];
                            ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($users[$user_id]['fname'] . ' ' . $users[$user_id]['lname']); ?></td>
                                    <td>
                                        ₹<?php echo number_format($bill['breakfast']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['breakfast']['count']; ?> meals</div>
                                    </td>
                                    <td>
                                        ₹<?php echo number_format($bill['lunch']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['lunch']['count']; ?> meals</div>
                                    </td>
                                    <td>
                                        ₹<?php echo number_format($bill['snacks']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['snacks']['count']; ?> meals</div>
                                    </td>
                                    <td>
                                        ₹<?php echo number_format($bill['dinner']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['dinner']['count']; ?> meals</div>
                                    </td>
                                    <td>
                                        ₹<?php echo number_format($bill['food_orders']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['food_orders']['count']; ?> orders</div>
                                    </td>
                                    <td>
                                        ₹<?php echo number_format($bill['other_fees']['amount'], 2); ?>
                                        <div class="meal-count"><?php echo $bill['other_fees']['count']; ?> fees</div>
                                    </td>
                                    <td class="total-amount">₹<?php echo number_format($bill['total'], 2); ?></td>
                                    <td class="no-print">
                                        <a href="?user_id=<?php echo $user_id; ?>&month=<?php echo $month; ?>&year=<?php echo $year; ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="total-row">
                                <td><strong>Grand Total</strong></td>
                                <td colspan="4"></td>
                                <td colspan="2"></td>
                                <td class="total-amount">₹<?php echo number_format($grand_total, 2); ?></td>
                                <td class="no-print"></td>
                            </tr>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add current date to printouts
            if (window.matchMedia) {
                var mediaQueryList = window.matchMedia('print');
                mediaQueryList.addListener(function(mql) {
                    if (mql.matches) {
                        // Add print timestamp
                        var now = new Date();
                        var dateStr = now.toLocaleDateString() + ' ' + now.toLocaleTimeString();
                        var printInfo = document.createElement('div');
                        printInfo.innerHTML = '<div style="text-align: right; font-size: 12px; margin-top: 20px; color: #777;">Printed on: ' + dateStr + '</div>';
                        document.querySelector('.bill-body').appendChild(printInfo);
                    }
                });
            }
            
            // Payment modal handling
            window.openPaymentModal = function() {
                const modal = document.getElementById('paymentModal');
                if (modal) {
                    modal.style.display = 'block';
                    setTimeout(() => {
                        modal.classList.add('show');
                        document.body.classList.add('modal-open');
                        
                        // Add modal backdrop
                        if (!document.querySelector('.modal-backdrop')) {
                            const backdrop = document.createElement('div');
                            backdrop.className = 'modal-backdrop fade show';
                            document.body.appendChild(backdrop);
                        }
                    }, 10);
                }
            };
            
            window.closePaymentModal = function() {
                const modal = document.getElementById('paymentModal');
                if (modal) {
                    modal.classList.remove('show');
                    setTimeout(() => {
                        modal.style.display = 'none';
                        document.body.classList.remove('modal-open');
                        
                        // Remove backdrop
                        const backdrop = document.querySelector('.modal-backdrop');
                        if (backdrop) {
                            backdrop.remove();
                        }
                    }, 150);
                }
            };
            
            // Close modal when clicking on backdrop
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
                    closePaymentModal();
                }
            });
            
            // Close modal when pressing Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    const modal = document.querySelector('.modal.show');
                    if (modal) {
                        closePaymentModal();
                    }
                }
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