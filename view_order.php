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

// Check if order ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "Order ID is required";
    header("Location: manage_orders.php");
    exit();
}

$order_id = intval($_GET['id']);

// Check table structure for users table
$check_users_columns = mysqli_query($connection, "SHOW COLUMNS FROM users");
$columns = [];
if ($check_users_columns) {
    while ($column = mysqli_fetch_assoc($check_users_columns)) {
        $columns[] = $column['Field'];
    }
}

// Determine user ID and name fields
$id_field = in_array('id', $columns) ? 'id' : (in_array('sno', $columns) ? 'sno' : 'id');
$has_name = in_array('name', $columns);
$has_fname = in_array('fname', $columns);
$has_lname = in_array('lname', $columns);
$has_phone = in_array('phone', $columns);
$has_mobile = in_array('mobile', $columns);
$phone_field = $has_phone ? 'phone' : ($has_mobile ? 'mobile' : null);

if ($has_name) {
    $name_select = "u.name as user_name";
} elseif ($has_fname && $has_lname) {
    $name_select = "CONCAT(u.fname, ' ', u.lname) as user_name";
} elseif ($has_fname) {
    $name_select = "u.fname as user_name";
} else {
    $name_select = "u.email as user_name";
}

// Check if food_orders table exists
$check_food_orders = mysqli_query($connection, "SHOW TABLES LIKE 'food_orders'");
$food_orders_exists = mysqli_num_rows($check_food_orders) > 0;

// Check if orders table exists
$check_orders = mysqli_query($connection, "SHOW TABLES LIKE 'orders'");
$orders_exists = mysqli_num_rows($check_orders) > 0;

// Determine which table to use
$orders_table = $food_orders_exists ? 'food_orders' : ($orders_exists ? 'orders' : 'food_orders');

// Create the table if it doesn't exist
if (!$food_orders_exists && !$orders_exists) {
    $create_table_sql = "CREATE TABLE IF NOT EXISTS `food_orders` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `user_id` int(11) NOT NULL,
      `items` text NOT NULL,
      `total_amount` decimal(10,2) NOT NULL,
      `status` enum('pending','approved','rejected','paid','cancelled','processing','completed') NOT NULL DEFAULT 'pending',
      `notes` text DEFAULT NULL,
      `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (!mysqli_query($connection, $create_table_sql)) {
        die("Error creating table: " . mysqli_error($connection));
    }
}

// Fetch order details
$phone_select = $phone_field ? ", u.$phone_field as user_phone" : ", NULL as user_phone";
$sql = "SELECT o.*, $name_select, u.email as user_email $phone_select
        FROM $orders_table o 
        LEFT JOIN users u ON o.user_id = u.$id_field 
        WHERE o.id = ?";

$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "i", $order_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) == 0) {
    // Try the other table if we didn't find the order
    if ($food_orders_exists && $orders_table === 'orders') {
        $orders_table = 'food_orders';
    } elseif ($orders_exists && $orders_table === 'food_orders') {
        $orders_table = 'orders';
    } else {
        $_SESSION['error_message'] = "Order not found";
        header("Location: manage_orders.php");
        exit();
    }
    
    $sql = "SELECT o.*, $name_select, u.email as user_email $phone_select
            FROM $orders_table o 
            LEFT JOIN users u ON o.user_id = u.$id_field 
            WHERE o.id = ?";
            
    $stmt = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) == 0) {
        $_SESSION['error_message'] = "Order not found";
        header("Location: manage_orders.php");
        exit();
    }
}

$order = mysqli_fetch_assoc($result);
$items_json = $order['items'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    $update_sql = "UPDATE $orders_table SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($connection, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Order status updated successfully!";
        // Reload the page to show updated status
        header("Location: view_order.php?id=" . $order_id);
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating order status: " . mysqli_error($connection);
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order Details #<?php echo $order_id; ?></h2>
                <a href="manage_orders.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Orders
                </a>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <!-- Order Summary -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4>Order Summary</h4>
                            <span class="badge bg-<?php 
                                $status = strtolower($order['status']);
                                if ($status == 'pending') echo 'warning';
                                else if ($status == 'approved' || $status == 'processing') echo 'info';
                                else if ($status == 'completed' || $status == 'paid') echo 'success';
                                else if ($status == 'rejected' || $status == 'cancelled') echo 'danger';
                                else echo 'secondary';
                            ?>">
                                <?php echo ucfirst(htmlspecialchars($order['status'])); ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity</th>
                                            <th>Price</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = 0;
                                        
                                        // Check if items data exists
                                        if (isset($items_json) && !empty($items_json)) {
                                            $items = @json_decode($items_json, true);
                                            
                                            if ($items && is_array($items)): 
                                                foreach ($items as $item): 
                                                    if (!is_array($item)) {
                                                        echo "<tr><td colspan='4'>" . htmlspecialchars((string)$item) . "</td></tr>";
                                                        continue;
                                                    }
                                                    
                                                    // Safely get item properties
                                                    $name = '';
                                                    if (isset($item['name'])) {
                                                        $name = $item['name'];
                                                    } elseif (isset($item['item_name'])) {
                                                        $name = $item['item_name'];
                                                    } else {
                                                        $name = 'Unknown Item';
                                                    }
                                                    
                                                    $quantity = 1;
                                                    if (isset($item['quantity'])) {
                                                        $quantity = $item['quantity'];
                                                    } elseif (isset($item['qty'])) {
                                                        $quantity = $item['qty'];
                                                    }
                                                    
                                                    $price = 0;
                                                    if (isset($item['price'])) {
                                                        $price = (float)$item['price'];
                                                    }
                                                    
                                                    $subtotal = $price * $quantity;
                                                    $total += $subtotal;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($name); ?></td>
                                                <td><?php echo htmlspecialchars($quantity); ?></td>
                                                <td>₹<?php echo htmlspecialchars(number_format($price, 2)); ?></td>
                                                <td>₹<?php echo htmlspecialchars(number_format($subtotal, 2)); ?></td>
                                            </tr>
                                        <?php 
                                                endforeach;
                                            elseif (is_string($items_json)):
                                                // If items is not a JSON array, display as text
                                                echo "<tr><td colspan='4'>" . htmlspecialchars($items_json) . "</td></tr>";
                                            endif;
                                        } else {
                                            echo "<tr><td colspan='4' class='text-center'><em>No items data available</em></td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th>
                                                <?php 
                                                $display_total = isset($order['total_amount']) ? (float)$order['total_amount'] : $total;
                                                echo "₹" . number_format($display_total, 2); 
                                                ?>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Notes -->
                    <?php if (!empty($order['notes'])): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Order Notes</h4>
                        </div>
                        <div class="card-body">
                            <p><?php echo nl2br(htmlspecialchars($order['notes'])); ?></p>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- Customer Information and Actions -->
                <div class="col-md-4">
                    <!-- Customer Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Customer Information</h4>
                        </div>
                        <div class="card-body">
                            <p><strong>Name:</strong> <?php echo htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?></p>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($order['user_email'] ?? 'No email'); ?></p>
                            <?php if (!empty($order['user_phone'])): ?>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['user_phone']); ?></p>
                            <?php endif; ?>
                            <p><strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></p>
                            <p><strong>Order ID:</strong> #<?php echo $order_id; ?></p>
                            <a href="user_bills.php?user_id=<?php echo $order['user_id']; ?>" class="btn btn-info btn-sm mt-2">
                                <i class="fas fa-user me-1"></i> View User Bills
                            </a>
                        </div>
                    </div>
                    
                    <!-- Order Actions -->
                    <div class="card">
                        <div class="card-header">
                            <h4>Order Actions</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Update Status</label>
                                    <select name="status" id="status" class="form-select">
                                        <option value="pending" <?php echo ($order['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo ($order['status'] == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                        <option value="processing" <?php echo ($order['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                        <option value="paid" <?php echo ($order['status'] == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                        <option value="completed" <?php echo ($order['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                        <option value="rejected" <?php echo ($order['status'] == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                        <option value="cancelled" <?php echo ($order['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="update_status" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Status
                                    </button>
                                    <a href="manage_orders.php?delete_order=<?php echo $order_id; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this order?');">
                                        <i class="fas fa-trash me-1"></i> Delete Order
                                    </a>
                                    <button type="button" class="btn btn-info" onclick="window.print();">
                                        <i class="fas fa-print me-1"></i> Print Order
                                    </button>
                                </div>
                            </form>
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