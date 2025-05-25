<?php
session_start();
if(isset($_SESSION['email'])){
    include('includes/connection.php');
    
    // Make sure user ID is available in session
    if(!isset($_SESSION['uid'])) {
        echo "<script>
            alert('User ID not found in session. Please log in again.');
            window.location.href = 'index.php';
        </script>";
        exit();
    }
    
    $user_id = $_SESSION['uid'];
    
    // Handle order cancellation
    if(isset($_GET['cancel'])) {
        $order_id = intval($_GET['cancel']);
        
        // Check if order exists and belongs to the user
        $check_query = "SELECT * FROM food_orders WHERE id = ? AND user_id = ? AND status = 'Pending'";
        $check_stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($check_stmt, "ii", $order_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if(mysqli_num_rows($check_result) > 0) {
            // Delete the order
            $delete_query = "DELETE FROM food_orders WHERE id = ? AND user_id = ?";
            $delete_stmt = mysqli_prepare($connection, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, "ii", $order_id, $user_id);
            
            if(mysqli_stmt_execute($delete_stmt)) {
                echo "<script>
                    alert('Order canceled successfully.');
                    window.location.href = 'view_orders.php';
                </script>";
            } else {
                echo "<script>
                    alert('Failed to cancel order. Please try again.');
                    window.location.href = 'view_orders.php';
                </script>";
            }
            mysqli_stmt_close($delete_stmt);
        } else {
            echo "<script>
                alert('Order not found or cannot be canceled (only pending orders can be canceled).');
                window.location.href = 'view_orders.php';
            </script>";
        }
        mysqli_stmt_close($check_stmt);
    }
    
    // Get user's orders
    $query = "SELECT * FROM food_orders WHERE user_id = ? ORDER BY order_date DESC, created_at DESC";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Get user's current fee amount
    $user_query = "SELECT fee_amount FROM users WHERE sno = ?";
    $user_stmt = mysqli_prepare($connection, $user_query);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    $current_fee = $user_data['fee_amount'];
    
    include('includes/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders</title>
    <style>
        .orders-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        .orders-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .orders-header {
            background-color: #194350;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .orders-body {
            padding: 20px;
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
        .badge {
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px; 
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-pending {
            background-color: #ffc107;
            color: #212529;
        }
        .badge-approved {
            background-color: #28a745;
            color: white;
        }
        .badge-rejected {
            background-color: #dc3545;
            color: white;
        }
        .badge-completed {
            background-color: #17a2b8;
            color: white;
        }
        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
            font-size: 12px;
        }
        .btn-danger {
            background-color: #dc3545;
            color: white;
        }
        .btn-primary {
            background-color: #194350;
            color: white;
        }
        .btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        .summary-box {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #194350;
        }
        .summary-title {
            font-weight: 600;
            color: #194350;
            margin-bottom: 10px;
        }
        .summary-stat {
            font-size: 24px;
            font-weight: bold;
        }
        .empty-orders {
            text-align: center;
            padding: 40px 0;
            color: #6c757d;
        }
        .empty-orders i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="container orders-container">
        <div class="orders-card">
            <div class="orders-header">
                <i class="fas fa-shopping-cart mr-2"></i> My Orders
            </div>
            <div class="orders-body">
                <!-- Summary Row -->
                <div class="row mb-4">
                    <?php
                    // Get order statistics
                    $stats_query = "SELECT 
                        COUNT(*) as total_orders,
                        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_orders,
                        SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) as approved_orders,
                        SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected_orders,
                        SUM(CASE WHEN status = 'Completed' THEN 1 ELSE 0 END) as completed_orders,
                        SUM(CASE WHEN status = 'Approved' OR status = 'Completed' THEN price ELSE 0 END) as total_spent
                    FROM food_orders WHERE user_id = ?";
                    $stats_stmt = mysqli_prepare($connection, $stats_query);
                    mysqli_stmt_bind_param($stats_stmt, "i", $user_id);
                    mysqli_stmt_execute($stats_stmt);
                    $stats_result = mysqli_stmt_get_result($stats_stmt);
                    $stats = mysqli_fetch_assoc($stats_result);
                    ?>
                    <div class="col-md-4">
                        <div class="summary-box">
                            <div class="summary-title">Current Fee Balance</div>
                            <div class="summary-stat">₹<?php echo number_format($current_fee, 2); ?></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box">
                            <div class="summary-title">Total Orders</div>
                            <div class="summary-stat"><?php echo $stats['total_orders']; ?></div>
                            <div class="mt-2">
                                <span class="badge badge-pending"><?php echo $stats['pending_orders']; ?> Pending</span>
                                <span class="badge badge-approved"><?php echo $stats['approved_orders']; ?> Approved</span>
                                <span class="badge badge-rejected"><?php echo $stats['rejected_orders']; ?> Rejected</span>
                                <span class="badge badge-completed"><?php echo $stats['completed_orders']; ?> Completed</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-box">
                            <div class="summary-title">Total Amount Spent</div>
                            <div class="summary-stat">₹<?php echo number_format($stats['total_spent'], 2); ?></div>
                            <div class="mt-2">
                                <a href="order_food.php" class="btn btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Place New Order
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Orders Table -->
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Meal Type</th>
                                <th>Food Item</th>
                                <th>Price</th>
                                <th>Order Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(mysqli_num_rows($result) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($result)): ?>
                                    <tr>
                                        <td>#<?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['meal_type']); ?></td>
                                        <td><?php echo htmlspecialchars($row['food_item']); ?></td>
                                        <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                        <td><?php echo date('d M Y', strtotime($row['order_date'])); ?></td>
                                        <td>
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <span class="badge badge-pending">Pending</span>
                                            <?php elseif($row['status'] == 'Approved'): ?>
                                                <span class="badge badge-approved">Approved</span>
                                            <?php elseif($row['status'] == 'Rejected'): ?>
                                                <span class="badge badge-rejected">Rejected</span>
                                            <?php elseif($row['status'] == 'Completed'): ?>
                                                <span class="badge badge-completed">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($row['status'] == 'Pending'): ?>
                                                <a href="view_orders.php?cancel=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to cancel this order?')">
                                                    <i class="fas fa-times mr-1"></i> Cancel
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted small">No actions available</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-orders">
                                            <i class="fas fa-shopping-cart"></i>
                                            <h4>No Orders Found</h4>
                                            <p>You haven't placed any orders yet.</p>
                                            <a href="order_food.php" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus mr-1"></i> Place Your First Order
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
} else {
    header('location:index.php');
    exit();
}
?> 