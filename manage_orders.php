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

// Create food_orders table if it doesn't exist (renamed from 'orders' to be more specific and match potential existing schema)
$create_table_sql = "CREATE TABLE IF NOT EXISTS `food_orders` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `items` text NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `status` enum('pending','approved','rejected','paid','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `order_date` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($connection));
}

// Handle order status update
if (isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($connection, $_POST['status']);
    
    $update_sql = "UPDATE food_orders SET status = ? WHERE id = ?";
    $stmt = mysqli_prepare($connection, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $status, $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Order status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating order status: " . mysqli_error($connection);
    }
}

// Handle order deletion
if (isset($_GET['delete_order'])) {
    $order_id = intval($_GET['delete_order']);
    
    $delete_sql = "DELETE FROM food_orders WHERE id = ?";
    $stmt = mysqli_prepare($connection, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $order_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Order deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting order: " . mysqli_error($connection);
    }
}

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

if ($has_name) {
    $name_select = "u.name as user_name";
} elseif ($has_fname && $has_lname) {
    $name_select = "CONCAT(u.fname, ' ', u.lname) as user_name";
} elseif ($has_fname) {
    $name_select = "u.fname as user_name";
} else {
    $name_select = "u.email as user_name";
}

// Get filter values from GET parameters
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// Check if orders table exists
$check_orders_table = mysqli_query($connection, "SHOW TABLES LIKE 'orders'");
$orders_exists = mysqli_num_rows($check_orders_table) > 0;

// Check if food_orders table exists
$check_food_orders = mysqli_query($connection, "SHOW TABLES LIKE 'food_orders'");
$food_orders_exists = mysqli_num_rows($check_food_orders) > 0;

// Determine which table to use first
$active_table = $food_orders_exists ? 'food_orders' : ($orders_exists ? 'orders' : 'food_orders');

// Build the SQL query with filters for the active table
$sql = "SELECT o.*, $name_select, u.email as user_email 
        FROM $active_table o 
        LEFT JOIN users u ON o.user_id = u.$id_field 
        WHERE 1=1 ";

$params = [];
$param_types = "";

if (!empty($date_from)) {
    $sql .= "AND DATE(o.order_date) >= ? ";
    $params[] = $date_from;
    $param_types .= "s";
}

if (!empty($date_to)) {
    $sql .= "AND DATE(o.order_date) <= ? ";
    $params[] = $date_to;
    $param_types .= "s";
}

if (!empty($status_filter)) {
    // Check if the status is valid for the selected table
    $check_status_sql = "SHOW COLUMNS FROM $active_table LIKE 'status'";
    $check_status_result = mysqli_query($connection, $check_status_sql);
    if ($check_status_result && mysqli_num_rows($check_status_result) > 0) {
        $status_column = mysqli_fetch_assoc($check_status_result);
        $enum_values = [];
        
        if (isset($status_column['Type'])) {
            // Extract enum values from type definition
            preg_match("/^enum\(\'(.*)\'\)$/", $status_column['Type'], $matches);
            if (isset($matches[1])) {
                $enum_values = explode("','", $matches[1]);
            }
        }
        
        // Only add status filter if the status is valid for this table
        if (empty($enum_values) || in_array($status_filter, $enum_values)) {
            $sql .= "AND o.status = ? ";
            $params[] = $status_filter;
            $param_types .= "s";
        }
    }
} else {
    // No status filter
}

$sql .= "ORDER BY o.order_date DESC";

// If there's an id column, add secondary ordering by id
$check_id_column = mysqli_query($connection, "SHOW COLUMNS FROM $active_table LIKE 'id'");
if (mysqli_num_rows($check_id_column) > 0) {
    $sql .= ", o.id DESC";
}

// Prepare and execute the query
$stmt = mysqli_prepare($connection, $sql);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $param_types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$orders = [];

// Store results from first table
if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        // Debug the structure of items and total_amount
        if (!isset($row['items']) || !isset($row['total_amount'])) {
            // Fix the missing columns if needed
            if (!isset($row['items'])) {
                $row['items'] = '[]'; // Default empty JSON array
            }
            if (!isset($row['total_amount'])) {
                $row['total_amount'] = 0.00; // Default amount
            }
        }
        $orders[] = $row;
    }
}

// Check other table if both exist
if ($food_orders_exists && $orders_exists) {
    $other_table = ($active_table === 'food_orders') ? 'orders' : 'food_orders';
    
    // Get the column names from the other table
    $columns_query = "SHOW COLUMNS FROM $other_table";
    $columns_result = mysqli_query($connection, $columns_query);
    $other_table_columns = [];
    
    if ($columns_result) {
        while ($column = mysqli_fetch_assoc($columns_result)) {
            $other_table_columns[] = $column['Field'];
        }
    }
    
    // Only proceed if items and total_amount exist in the other table
    if (in_array('items', $other_table_columns) && in_array('total_amount', $other_table_columns)) {
        // Build the same query for the other table
        $sql = str_replace($active_table, $other_table, $sql);
        
        // Make sure the ORDER BY clause is correct for this table too
        if (strpos($sql, "ORDER BY") !== false) {
            // Reset the ORDER BY clause
            $sql = substr($sql, 0, strpos($sql, "ORDER BY"));
            $sql .= "ORDER BY o.order_date DESC";
            
            // Check if this table has an id column
            $check_id_column = mysqli_query($connection, "SHOW COLUMNS FROM $other_table LIKE 'id'");
            if (mysqli_num_rows($check_id_column) > 0) {
                $sql .= ", o.id DESC";
            }
        }
        
        $stmt = mysqli_prepare($connection, $sql);
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $param_types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        $other_result = mysqli_stmt_get_result($stmt);
        
        // Add results from other table
        if (mysqli_num_rows($other_result) > 0) {
            while ($row = mysqli_fetch_assoc($other_result)) {
                // Debug the structure of items and total_amount
                if (!isset($row['items']) || !isset($row['total_amount'])) {
                    // Fix the missing columns if needed
                    if (!isset($row['items'])) {
                        $row['items'] = '[]'; // Default empty JSON array
                    }
                    if (!isset($row['total_amount'])) {
                        $row['total_amount'] = 0.00; // Default amount
                    }
                }
                $orders[] = $row;
            }
        }
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Manage Orders</h2>
            
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
            
            <!-- Order Filtering -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Filter Orders</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="" class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo ($status_filter == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="approved" <?php echo ($status_filter == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                <option value="processing" <?php echo ($status_filter == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="paid" <?php echo ($status_filter == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="completed" <?php echo ($status_filter == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="rejected" <?php echo ($status_filter == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                <option value="cancelled" <?php echo ($status_filter == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-primary">Filter</button>
                            <a href="manage_orders.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Orders Table -->
            <div class="card">
                <div class="card-header">
                    <h4>All Orders</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead class="table-dark">
                                <tr>
                                    <th>Order ID</th>
                                    <th>User</th>
                                    <th>Order Date</th>
                                    <th>Items</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($orders) > 0): ?>
                                    <?php foreach ($orders as $order): ?>
                                        <tr>
                                            <td>#<?php echo htmlspecialchars($order['id']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['user_name'] ?? 'Unknown User'); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['user_email'] ?? 'No email'); ?></small>
                                            </td>
                                            <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <?php 
                                                // Safely handle items data
                                                if (isset($order['items']) && !empty($order['items'])) {
                                                    $items_data = $order['items'];
                                                    $items = @json_decode($items_data, true);
                                                    
                                                    if ($items && is_array($items)) {
                                                        foreach ($items as $item) {
                                                            if (is_array($item)) {
                                                                // Get item name safely
                                                                $name = '';
                                                                if (isset($item['name'])) {
                                                                    $name = $item['name'];
                                                                } elseif (isset($item['item_name'])) {
                                                                    $name = $item['item_name'];
                                                                } else {
                                                                    $name = 'Unknown Item';
                                                                }
                                                                
                                                                // Get quantity safely
                                                                $quantity = 1;
                                                                if (isset($item['quantity'])) {
                                                                    $quantity = $item['quantity'];
                                                                } elseif (isset($item['qty'])) {
                                                                    $quantity = $item['qty'];
                                                                }
                                                                
                                                                echo htmlspecialchars($name) . " x " . htmlspecialchars($quantity) . "<br>";
                                                            } else {
                                                                // Handle non-array items
                                                                if ($item !== null) {
                                                                    echo htmlspecialchars((string)$item) . "<br>";
                                                                }
                                                            }
                                                        }
                                                    } else {
                                                        // Not valid JSON, display as string
                                                        echo htmlspecialchars((string)$items_data);
                                                    }
                                                } else {
                                                    echo '<em>No items data</em>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Safely handle total amount
                                                $total_amount = 0.00;
                                                if (isset($order['total_amount'])) {
                                                    $total_amount = (float)$order['total_amount'];
                                                }
                                                ?>
                                                ₹<?php echo number_format($total_amount, 2); ?>
                                            </td>
                                            <td>
                                                <?php
                                                // Safely handle status
                                                $status = 'pending';
                                                if (isset($order['status'])) {
                                                    $status = strtolower($order['status']);
                                                }
                                                
                                                $badge_class = 'secondary';
                                                if ($status == 'pending') $badge_class = 'warning';
                                                else if ($status == 'approved' || $status == 'processing') $badge_class = 'info';
                                                else if ($status == 'completed' || $status == 'paid') $badge_class = 'success';
                                                else if ($status == 'rejected' || $status == 'cancelled') $badge_class = 'danger';
                                                ?>
                                                <span class="badge bg-<?php echo $badge_class; ?>">
                                                    <?php echo ucfirst(htmlspecialchars($status)); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <form method="POST" action="" class="mb-2">
                                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                                    <div class="input-group">
                                                        <select name="status" class="form-select form-select-sm">
                                                            <option value="pending" <?php echo ($status == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="approved" <?php echo ($status == 'approved') ? 'selected' : ''; ?>>Approved</option>
                                                            <option value="processing" <?php echo ($status == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                                            <option value="paid" <?php echo ($status == 'paid') ? 'selected' : ''; ?>>Paid</option>
                                                            <option value="completed" <?php echo ($status == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="rejected" <?php echo ($status == 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                                                            <option value="cancelled" <?php echo ($status == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                                                    </div>
                                                </form>
                                                
                                                <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-info">
                                                    <i class="fas fa-eye me-1"></i> View
                                                </a>
                                                
                                                <a href="manage_orders.php?delete_order=<?php echo $order['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this order?');">
                                                    <i class="fas fa-trash me-1"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No orders found.</td>
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

<!-- Add some JavaScript to initialize date fields with current date if empty -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    var dateFrom = document.getElementById('date_from');
    var dateTo = document.getElementById('date_to');
    
    if (!dateFrom.value) {
        // Set to first day of current month by default
        var today = new Date();
        var firstDay = new Date(today.getFullYear(), today.getMonth(), 1);
        dateFrom.valueAsDate = firstDay;
    }
    
    if (!dateTo.value) {
        // Set to today by default
        var today = new Date();
        dateTo.valueAsDate = today;
    }
});
</script>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 