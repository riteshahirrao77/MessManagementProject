<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include '../includes/connection.php';
session_start();

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: user_bills.php");
    exit();
}

$user_id = $_GET['id'];

// Fetch user details
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    header("Location: user_bills.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Fetch user fees
$fees_sql = "SELECT * FROM fees WHERE user_id = ? ORDER BY date_added ASC";
$stmt = mysqli_prepare($connection, $fees_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$fees_result = mysqli_stmt_get_result($stmt);

// Fetch user payments
$payments_sql = "SELECT * FROM payments WHERE user_id = ? ORDER BY payment_date ASC";
$stmt = mysqli_prepare($connection, $payments_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$payments_result = mysqli_stmt_get_result($stmt);

// Calculate totals
$fees_sql = "SELECT 
    SUM(CASE WHEN status = 'unpaid' THEN amount ELSE 0 END) as total_unpaid,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_paid
    FROM fees WHERE user_id = ?";
$stmt = mysqli_prepare($connection, $fees_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$totals_result = mysqli_stmt_get_result($stmt);
$totals = mysqli_fetch_assoc($totals_result);

$total_unpaid = $totals['total_unpaid'] ?: 0;
$total_paid = $totals['total_paid'] ?: 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill Statement - <?php echo htmlspecialchars($user['name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 30px;
            border: 1px solid #eee;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.15);
        }
        .invoice-header {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            margin: 0;
            color: #194350;
        }
        .invoice-details {
            display: flex;
            justify-content: space-between;
        }
        .invoice-details-col {
            flex: 1;
        }
        .invoice-date {
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 20px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .amount {
            text-align: right;
            font-family: monospace;
        }
        .total-row {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .total-amount {
            font-size: 18px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
        .payment-info {
            margin-top: 20px;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        @media print {
            body {
                padding: 0;
            }
            .invoice-container {
                box-shadow: none;
                border: none;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-success {
            background-color: #28a745;
            color: white;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #333;
        }
        .organization-info {
            margin-bottom: 30px;
            text-align: center;
        }
        .organization-name {
            font-size: 24px;
            font-weight: bold;
            color: #194350;
            margin-bottom: 5px;
        }
        .organization-address {
            font-size: 14px;
            color: #666;
        }
        .section-title {
            font-size: 18px;
            font-weight: bold;
            margin-top: 30px;
            margin-bottom: 10px;
            color: #194350;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="organization-info">
            <div class="organization-name">Mess Management System</div>
            <div class="organization-address">
                123 Campus Street, Education City<br>
                Hyderabad, Telangana, 500032<br>
                Phone: +91 9876543210 | Email: mess@example.edu.in
            </div>
        </div>
        
        <div class="invoice-header">
            <h1 class="invoice-title">BILL STATEMENT</h1>
            <p class="invoice-id">Statement #: <?php echo date('Ymd') . '-' . $user_id; ?></p>
        </div>
        
        <div class="invoice-details">
            <div class="invoice-details-col">
                <h3>Billed To:</h3>
                <p>
                    <strong><?php echo htmlspecialchars($user['name']); ?></strong><br>
                    <?php echo htmlspecialchars($user['email']); ?><br>
                    <?php if (!empty($user['phone'])): ?>
                        Phone: <?php echo htmlspecialchars($user['phone']); ?><br>
                    <?php endif; ?>
                </p>
            </div>
            <div class="invoice-details-col invoice-date">
                <h3>Statement Details:</h3>
                <p>
                    Date: <?php echo date('F d, Y'); ?><br>
                    Status: <span class="badge <?php echo $total_unpaid > 0 ? 'badge-warning' : 'badge-success'; ?>">
                        <?php echo $total_unpaid > 0 ? 'Outstanding' : 'Paid'; ?>
                    </span>
                </p>
            </div>
        </div>
        
        <h4 class="section-title">Fee Summary</h4>
        <table>
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Total Charged Fees</td>
                    <td class="amount">₹<?php echo number_format($total_unpaid + $total_paid, 2); ?></td>
                </tr>
                <tr>
                    <td>Total Payments Received</td>
                    <td class="amount">₹<?php echo number_format($total_paid, 2); ?></td>
                </tr>
                <tr class="total-row">
                    <td>Outstanding Balance</td>
                    <td class="amount total-amount">₹<?php echo number_format($total_unpaid, 2); ?></td>
                </tr>
            </tbody>
        </table>
        
        <?php if (mysqli_num_rows($fees_result) > 0): ?>
            <h4 class="section-title">Fee Details</h4>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($fees_result, 0);
                    while ($fee = mysqli_fetch_assoc($fees_result)): 
                    ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($fee['date_added'])); ?></td>
                            <td><?php echo htmlspecialchars($fee['reason']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $fee['status'] == 'paid' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($fee['status']); ?>
                                </span>
                            </td>
                            <td class="amount">₹<?php echo number_format($fee['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <?php if (mysqli_num_rows($payments_result) > 0): ?>
            <h4 class="section-title">Payment History</h4>
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Method</th>
                        <th>Reference</th>
                        <th class="text-right">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($payments_result, 0);
                    while ($payment = mysqli_fetch_assoc($payments_result)): 
                    ?>
                        <tr>
                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            <td><?php echo !empty($payment['reference']) ? htmlspecialchars($payment['reference']) : '-'; ?></td>
                            <td class="amount">₹<?php echo number_format($payment['amount'], 2); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div class="payment-info">
            <h4>Payment Information</h4>
            <p>
                Please make payment to the following account:<br>
                Bank Name: Example Bank<br>
                Account Name: Mess Management System<br>
                Account Number: 1234567890<br>
                IFSC Code: EXAM0001234<br>
                <strong>Please include your name and email as reference.</strong>
            </p>
        </div>
        
        <div class="footer">
            <p>This is a computer-generated document. No signature is required.</p>
            <p>If you have any questions about this statement, please contact mess@example.edu.in</p>
            <p>Thank you for your business!</p>
        </div>
        
        <div class="no-print" style="text-align: center; margin-top: 20px;">
            <button onclick="window.print();" style="padding: 10px 20px; background-color: #194350; color: white; border: none; cursor: pointer; border-radius: 4px;">
                Print Bill
            </button>
            <button onclick="window.close();" style="padding: 10px 20px; background-color: #6c757d; color: white; border: none; cursor: pointer; border-radius: 4px; margin-left: 10px;">
                Close
            </button>
        </div>
    </div>
</body>
</html>


// End output buffering
ob_end_flush();
?>