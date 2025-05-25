<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include 'header.php';
include '../includes/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize filter variables
$filter_item = isset($_GET['item']) ? $_GET['item'] : '';
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get all stock items for the filter dropdown
$items_sql = "SELECT DISTINCT item_name FROM mess_stock ORDER BY item_name";
$items_result = mysqli_query($connection, $items_sql);

// Build the query with filters
$sql = "SELECT * FROM stock_usage_history WHERE 1=1";
$params = [];
$types = "";

if (!empty($filter_item)) {
    $sql .= " AND item_name = ?";
    $params[] = $filter_item;
    $types .= "s";
}

if (!empty($filter_date_from)) {
    $sql .= " AND DATE(used_at) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}

if (!empty($filter_date_to)) {
    $sql .= " AND DATE(used_at) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

$sql .= " ORDER BY used_at DESC";

// Prepare and execute the query
$stmt = mysqli_prepare($connection, $sql);
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Stock Usage History</h2>
            
            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Filter Usage History</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="item" class="form-label">Item</label>
                                    <select name="item" id="item" class="form-select">
                                        <option value="">All Items</option>
                                        <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                                            <option value="<?php echo htmlspecialchars($item['item_name']); ?>" <?php echo ($filter_item == $item['item_name'] ? 'selected' : ''); ?>>
                                                <?php echo htmlspecialchars($item['item_name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="date_from" class="form-label">Date From</label>
                                    <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $filter_date_from; ?>">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="date_to" class="form-label">Date To</label>
                                    <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $filter_date_to; ?>">
                                </div>
                            </div>
                        </div>
                        <div class="d-flex">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="stock_usage_history.php" class="btn btn-secondary">Reset Filters</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Usage History Table -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4>Usage History</h4>
                    <button onclick="printTable()" class="btn btn-success">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive" id="printArea">
                        <h3 class="text-center d-none" id="print-title">Stock Usage History Report</h3>
                        <p class="text-center d-none" id="print-date"></p>
                        <table class="table table-bordered table-striped">
                            <thead class="table-dark">
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity Used</th>
                                    <th>Unit</th>
                                    <th>Purpose</th>
                                    <th>Used By</th>
                                    <th>Date & Time</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total_rows = mysqli_num_rows($result);
                                if ($total_rows > 0):
                                    while ($usage = mysqli_fetch_assoc($result)): 
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($usage['item_name']); ?></td>
                                    <td><?php echo $usage['quantity_used']; ?></td>
                                    <td><?php echo htmlspecialchars($usage['unit']); ?></td>
                                    <td><?php echo htmlspecialchars($usage['purpose']); ?></td>
                                    <td><?php echo htmlspecialchars($usage['used_by']); ?></td>
                                    <td><?php echo date('d-M-Y h:i A', strtotime($usage['used_at'])); ?></td>
                                </tr>
                                <?php 
                                    endwhile; 
                                else:
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center">No usage history found</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="6" class="text-end">
                                        <strong>Total Records: <?php echo $total_rows; ?></strong>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printTable() {
    // Add date to print view
    document.getElementById('print-date').innerHTML = 'Generated on: ' + new Date().toLocaleString();
    
    // Show print elements
    document.getElementById('print-title').classList.remove('d-none');
    document.getElementById('print-date').classList.remove('d-none');
    
    // Print
    const printContents = document.getElementById('printArea').innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = `
        <html>
            <head>
                <title>Stock Usage History</title>
                <link href="../assets/css/custom.css" rel="stylesheet">
                <style>
                    @media print {
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
                        th { background-color: #f2f2f2; }
                        h3 { margin-bottom: 10px; }
                    }
                </style>
            </head>
            <body>
                ${printContents}
            </body>
        </html>
    `;
    window.print();
    document.body.innerHTML = originalContents;
    
    // Hide print elements again
    document.getElementById('print-title').classList.add('d-none');
    document.getElementById('print-date').classList.add('d-none');
}
</script>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 