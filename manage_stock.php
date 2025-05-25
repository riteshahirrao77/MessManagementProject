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

// Create mess_stock table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `mess_stock` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `item_name` varchar(100) NOT NULL,
  `quantity` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `price_per_unit` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($connection));
}

// Create stock usage history table
$create_usage_table = "CREATE TABLE IF NOT EXISTS `stock_usage_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stock_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `quantity_used` decimal(10,2) NOT NULL,
  `unit` varchar(20) NOT NULL,
  `used_by` varchar(100) NOT NULL,
  `purpose` varchar(255) DEFAULT 'Regular kitchen usage',
  `used_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `stock_id` (`stock_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_usage_table)) {
    die("Error creating usage history table: " . mysqli_error($connection));
}

// Add sample data if the table is empty
$check_data = mysqli_query($connection, "SELECT COUNT(*) as count FROM mess_stock");
$row = mysqli_fetch_assoc($check_data);
if ($row['count'] == 0) {
    $sample_data = "INSERT INTO `mess_stock` (`item_name`, `quantity`, `unit`, `price_per_unit`) VALUES
    ('Rice', 100.00, 'kg', 40.00),
    ('Wheat Flour', 50.00, 'kg', 35.00),
    ('Cooking Oil', 20.00, 'l', 120.00),
    ('Onions', 30.00, 'kg', 25.00),
    ('Tomatoes', 20.00, 'kg', 30.00),
    ('Potatoes', 40.00, 'kg', 20.00),
    ('Salt', 10.00, 'kg', 15.00),
    ('Sugar', 15.00, 'kg', 45.00),
    ('Tea Leaves', 5.00, 'kg', 200.00),
    ('Milk', 20.00, 'l', 60.00)";
    
    if (!mysqli_query($connection, $sample_data)) {
        $_SESSION['error_message'] = "Error adding sample data: " . mysqli_error($connection);
    } else {
        $_SESSION['success_message'] = "Sample stock data added successfully!";
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_stock'])) {
        $item_name = $_POST['item_name'];
        $quantity = $_POST['quantity'];
        $unit = $_POST['unit'];
        $price_per_unit = $_POST['price_per_unit'];
        
        $sql = "INSERT INTO mess_stock (item_name, quantity, unit, price_per_unit) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "sdsd", $item_name, $quantity, $unit, $price_per_unit);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Stock item added successfully!";
            // Redirect to prevent form resubmission
            echo "<script>window.location.href = 'manage_stock.php';</script>";
            exit();
        } else {
            $_SESSION['error_message'] = "Error adding stock item: " . mysqli_error($connection);
        }
    }
    
    if (isset($_POST['update_stock'])) {
        $stock_id = $_POST['stock_id'];
        $quantity = $_POST['quantity'];
        
        $sql = "UPDATE mess_stock SET quantity = ? WHERE id = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "di", $quantity, $stock_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Stock updated successfully!";
            // Redirect to prevent form resubmission
            echo "<script>window.location.href = 'manage_stock.php';</script>";
            exit();
        } else {
            $_SESSION['error_message'] = "Error updating stock: " . mysqli_error($connection);
        }
    }

    if (isset($_POST['use_stock'])) {
        $stock_id = $_POST['stock_id'];
        $used_quantity = $_POST['used_quantity'];
        $purpose = isset($_POST['purpose']) ? $_POST['purpose'] : 'Regular kitchen usage';
        
        // Get current quantity and item details
        $get_current = "SELECT quantity, item_name, unit FROM mess_stock WHERE id = ?";
        $stmt = mysqli_prepare($connection, $get_current);
        mysqli_stmt_bind_param($stmt, "i", $stock_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $current_quantity, $item_name, $unit);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
        
        // Calculate new quantity
        $new_quantity = $current_quantity - $used_quantity;
        
        // Update the quantity if not negative
        if ($new_quantity >= 0) {
            // Begin transaction
            mysqli_begin_transaction($connection);
            
            try {
                // Update stock quantity
                $sql = "UPDATE mess_stock SET quantity = ? WHERE id = ?";
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "di", $new_quantity, $stock_id);
                mysqli_stmt_execute($stmt);
                
                // Record usage in history
                $admin_email = $_SESSION['email'];
                $sql = "INSERT INTO stock_usage_history (stock_id, item_name, quantity_used, unit, used_by, purpose, used_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "isdsss", $stock_id, $item_name, $used_quantity, $unit, $admin_email, $purpose);
                mysqli_stmt_execute($stmt);
                
                // Commit transaction
                mysqli_commit($connection);
                
                $_SESSION['success_message'] = "Stock usage of $used_quantity $unit of $item_name recorded successfully!";
                echo "<script>window.location.href = 'manage_stock.php';</script>";
                exit();
            } catch (Exception $e) {
                // Rollback transaction on error
                mysqli_rollback($connection);
                $_SESSION['error_message'] = "Error: " . $e->getMessage();
                echo "<script>window.location.href = 'manage_stock.php';</script>";
                exit();
            }
        } else {
            $_SESSION['error_message'] = "Error: Not enough stock available. Current stock: " . $current_quantity . " " . $unit;
            echo "<script>window.location.href = 'manage_stock.php';</script>";
            exit();
        }
    }
}

// Fetch current stock
$sql = "SELECT * FROM mess_stock ORDER BY item_name";
$result = mysqli_query($connection, $sql);
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">Manage Mess Stock</h2>
                <a href="stock_usage_history.php" class="btn btn-warning">
                    <i class="fas fa-history me-2"></i> View Complete Usage History
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
            
            <!-- Add New Stock Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Add New Stock Item</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="item_name" class="form-label">Item Name</label>
                                    <input type="text" name="item_name" id="item_name" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity</label>
                                    <input type="number" name="quantity" id="quantity" class="form-control" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="unit" class="form-label">Unit</label>
                                    <select name="unit" id="unit" class="form-select" required>
                                        <option value="kg">Kilograms (kg)</option>
                                        <option value="g">Grams (g)</option>
                                        <option value="l">Liters (L)</option>
                                        <option value="ml">Milliliters (ml)</option>
                                        <option value="pcs">Pieces (pcs)</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-3">
                                    <label for="price_per_unit" class="form-label">Price per Unit</label>
                                    <input type="number" name="price_per_unit" id="price_per_unit" class="form-control" step="0.01" required>
                                </div>
                            </div>
                        </div>
                        <button type="submit" name="add_stock" class="btn btn-primary mt-3">Add Stock Item</button>
                    </form>
                </div>
            </div>
            
            <!-- Current Stock Table -->
            <div class="card">
                <div class="card-header">
                    <h4>Current Stock</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Quantity</th>
                                    <th>Unit</th>
                                    <th>Price per Unit</th>
                                    <th>Total Value</th>
                                    <th>Record Usage</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['item_name']); ?></td>
                                        <td>
                                            <form method="POST" action="">
                                                <div class="input-group">
                                                    <input type="hidden" name="stock_id" value="<?php echo $row['id']; ?>">
                                                    <input type="number" name="quantity" value="<?php echo $row['quantity']; ?>" class="form-control form-control-sm" step="0.01" min="0">
                                                    <button type="submit" name="update_stock" class="btn btn-sm btn-success">Update</button>
                                                </div>
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['unit']); ?></td>
                                        <td>₹<?php echo number_format($row['price_per_unit'], 2); ?></td>
                                        <td>₹<?php echo number_format($row['quantity'] * $row['price_per_unit'], 2); ?></td>
                                        <td>
                                            <form method="POST" action="">
                                                <div class="mb-1">
                                                    <input type="hidden" name="stock_id" value="<?php echo $row['id']; ?>">
                                                    <div class="input-group mb-1">
                                                        <input type="number" name="used_quantity" placeholder="Qty" class="form-control form-control-sm" step="0.01" min="0.01" max="<?php echo $row['quantity']; ?>" required>
                                                        <button type="submit" name="use_stock" class="btn btn-sm btn-warning">Use</button>
                                                    </div>
                                                    <input type="text" name="purpose" placeholder="Purpose (optional)" class="form-control form-control-sm">
                                                </div>
                                            </form>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-danger" onclick="deleteStock(<?php echo $row['id']; ?>)">Delete</button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Stock Usage History -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h4>Stock Usage History</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Item</th>
                                            <th>Quantity Used</th>
                                            <th>Unit</th>
                                            <th>Used By</th>
                                            <th>Date & Time</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $usage_sql = "SELECT * FROM stock_usage_history ORDER BY used_at DESC LIMIT 15";
                                        $usage_result = mysqli_query($connection, $usage_sql);
                                        while ($usage = mysqli_fetch_assoc($usage_result)): 
                                        ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($usage['item_name']); ?></td>
                                            <td><?php echo $usage['quantity_used']; ?></td>
                                            <td><?php echo htmlspecialchars($usage['unit']); ?></td>
                                            <td><?php echo htmlspecialchars($usage['used_by']); ?></td>
                                            <td><?php echo date('d-M-Y h:i A', strtotime($usage['used_at'])); ?></td>
                                        </tr>
                                        <?php endwhile; ?>
                                        <?php if (mysqli_num_rows($usage_result) == 0): ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No usage history available</td>
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

<script>
function deleteStock(id) {
    if (confirm('Are you sure you want to delete this stock item?')) {
        window.location.href = 'delete_stock.php?id=' + id;
    }
}
</script>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 