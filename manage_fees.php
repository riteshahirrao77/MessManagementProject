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

// Create fees table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `fees` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `frequency` enum('one-time','monthly','quarterly','yearly') NOT NULL DEFAULT 'one-time',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating fees table: " . mysqli_error($connection));
}

// Handle fee addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_fee'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $description = mysqli_real_escape_string($connection, $_POST['description']);
    $amount = (float) $_POST['amount'];
    $frequency = mysqli_real_escape_string($connection, $_POST['frequency']);
    
    $insert_sql = "INSERT INTO fees (name, description, amount, frequency) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($connection, $insert_sql);
    mysqli_stmt_bind_param($stmt, "ssds", $name, $description, $amount, $frequency);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Fee added successfully!";
    } else {
        $_SESSION['error_message'] = "Error adding fee: " . mysqli_error($connection);
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage_fees.php");
    exit();
}

// Handle fee deletion
if (isset($_GET['delete'])) {
    $fee_id = (int) $_GET['delete'];
    
    $delete_sql = "DELETE FROM fees WHERE id = ?";
    $stmt = mysqli_prepare($connection, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $fee_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Fee deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting fee: " . mysqli_error($connection);
    }
    
    // Redirect to prevent resubmission
    header("Location: manage_fees.php");
    exit();
}

// Get all fees
$fees_sql = "SELECT * FROM fees ORDER BY name";
$fees_result = mysqli_query($connection, $fees_sql);
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Manage Fees</h2>
            
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
                <div class="col-md-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Add New Fee</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Fee Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label for="amount" class="form-label">Amount</label>
                                    <div class="input-group">
                                        <span class="input-group-text">₹</span>
                                        <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0" required>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="frequency" class="form-label">Frequency</label>
                                    <select class="form-select" id="frequency" name="frequency" required>
                                        <option value="one-time">One-time</option>
                                        <option value="monthly">Monthly</option>
                                        <option value="quarterly">Quarterly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <button type="submit" name="add_fee" class="btn btn-primary">Add Fee</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-7">
                    <div class="card">
                        <div class="card-header">
                            <h4>Fee List</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Amount</th>
                                            <th>Frequency</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($fees_result) > 0): ?>
                                            <?php while($fee = mysqli_fetch_assoc($fees_result)): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($fee['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($fee['description']); ?></td>
                                                    <td>₹<?php echo number_format($fee['amount'], 2); ?></td>
                                                    <td><?php echo ucfirst($fee['frequency']); ?></td>
                                                    <td>
                                                        <a href="manage_fees.php?delete=<?php echo $fee['id']; ?>" 
                                                           class="btn btn-sm btn-danger"
                                                           onclick="return confirm('Are you sure you want to delete this fee?');">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No fees defined yet</td>
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