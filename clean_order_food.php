<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit();
}

require_once 'includes/connection.php';
require_once 'includes/functions.php';

if (!mysqli_query($connection, "DESCRIBE food_orders")) {
    $create_table = "CREATE TABLE food_orders (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        user_id INT(11) NOT NULL,
        meal_type VARCHAR(20) NOT NULL,
        food_item VARCHAR(255) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        order_date DATE NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(sno)
    )";
    mysqli_query($connection, $create_table);
}

$user_id = $_SESSION['user_id'];
$success_msg = "";
$error_msg = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_food'])) {
    $meal_type = mysqli_real_escape_string($connection, $_POST['meal_type']);
    $food_items = mysqli_real_escape_string($connection, $_POST['food_items']);
    $order_date = mysqli_real_escape_string($connection, $_POST['order_date']);
    
    $today = date('Y-m-d');
    if ($order_date < $today) {
        $error_msg = "You cannot order for past dates.";
    } else {
        $price = 0;
        if ($meal_type == "Breakfast") {
            $price = 60.00;
        } else if ($meal_type == "Lunch") {
            $price = 100.00;
        } else if ($meal_type == "Dinner") {
            $price = 90.00;
        }
        
        $check_query = "SELECT * FROM food_orders WHERE user_id = ? AND meal_type = ? AND order_date = ?";
        $stmt = mysqli_prepare($connection, $check_query);
        mysqli_stmt_bind_param($stmt, "iss", $user_id, $meal_type, $order_date);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($result) > 0) {
            $error_msg = "You have already ordered $meal_type for this date.";
        } else {
            $query = "INSERT INTO food_orders (user_id, meal_type, food_item, price, order_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')";
            $stmt = mysqli_prepare($connection, $query);
            mysqli_stmt_bind_param($stmt, "issds", $user_id, $meal_type, $food_items, $price, $order_date);
            
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "Your food order has been placed successfully!";
            } else {
                $error_msg = "Error placing order: " . mysqli_error($connection);
            }
        }
    }
}

$user_query = "SELECT * FROM users WHERE sno = ?";
$stmt = mysqli_prepare($connection, $user_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$user_result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($user_result);

$existing_orders_query = "SELECT * FROM food_orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($connection, $existing_orders_query);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$existing_orders = mysqli_stmt_get_result($stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Food</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="css/styles.css">
    <style>
        .order-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .form-group label {
            font-weight: 600;
        }
        .meal-price {
            font-weight: bold;
            color: #28a745;
        }
        .order-history {
            margin-top: 30px;
        }
        .order-card {
            margin-bottom: 15px;
            transition: transform 0.3s;
        }
        .order-card:hover {
            transform: translateY(-5px);
        }
        .status-pending {
            color: #ffc107;
        }
        .status-approved {
            color: #28a745;
        }
        .status-rejected {
            color: #dc3545;
        }
        .status-completed {
            color: #17a2b8;
        }
    </style>
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container mt-4 mb-4">
        <div class="order-container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order Food</h2>
                <a href="user_dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
            </div>
            
            <?php if (!empty($success_msg)): ?>
                <div class="alert alert-success"><?= $success_msg ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger"><?= $error_msg ?></div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Place New Order</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="order_food.php">
                                <div class="form-group">
                                    <label for="meal_type">Meal Type</label>
                                    <select class="form-control" id="meal_type" name="meal_type" required>
                                        <option value="">Select Meal Type</option>
                                        <option value="Breakfast">Breakfast - ₹60.00</option>
                                        <option value="Lunch">Lunch - ₹100.00</option>
                                        <option value="Dinner">Dinner - ₹90.00</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label for="food_items">Food Items (Optional)</label>
                                    <textarea class="form-control" id="food_items" name="food_items" rows="3" placeholder="Enter any specific food items or preferences..."></textarea>
                                    <small class="form-text text-muted">Leave blank for standard meal menu.</small>
                                </div>
                                
                                <div class="form-group">
                                    <label for="order_date">Order Date</label>
                                    <input type="date" class="form-control" id="order_date" name="order_date" min="<?= date('Y-m-d') ?>" required>
                                    <small class="form-text text-muted">You can order up to 7 days in advance.</small>
                                </div>
                                
                                <button type="submit" name="order_food" class="btn btn-primary btn-block">Place Order</button>
                            </form>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Meal Information</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Breakfast (₹60.00):</strong> Served from 7:30 AM to 9:30 AM</p>
                            <p><strong>Lunch (₹100.00):</strong> Served from 12:30 PM to 2:30 PM</p>
                            <p><strong>Dinner (₹90.00):</strong> Served from 7:30 PM to 9:30 PM</p>
                            <p class="mb-0 text-muted small">Please note: Orders must be placed at least 1 hour before meal service time.</p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card order-history">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Your Recent Orders</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($existing_orders) > 0): ?>
                                <div class="list-group">
                                    <?php while ($order = mysqli_fetch_assoc($existing_orders)): ?>
                                        <div class="list-group-item">
                                            <div class="d-flex w-100 justify-content-between">
                                                <h5 class="mb-1"><?= htmlspecialchars($order['meal_type']) ?></h5>
                                                <small class="text-muted"><?= date('M d, Y', strtotime($order['order_date'])) ?></small>
                                            </div>
                                            <?php if(!empty($order['food_item'])): ?>
                                                <p class="mb-1"><strong>Items:</strong> <?= htmlspecialchars($order['food_item']) ?></p>
                                            <?php else: ?>
                                                <p class="mb-1"><strong>Items:</strong> Standard meal</p>
                                            <?php endif; ?>
                                            <p class="mb-1"><strong>Price:</strong> ₹<?= htmlspecialchars($order['price']) ?></p>
                                            <div class="d-flex justify-content-between">
                                                <small class="
                                                    <?php
                                                    if ($order['status'] == 'Pending') echo 'status-pending';
                                                    else if ($order['status'] == 'Approved') echo 'status-approved';
                                                    else if ($order['status'] == 'Rejected') echo 'status-rejected';
                                                    else if ($order['status'] == 'Completed') echo 'status-completed';
                                                    ?>">
                                                    <strong>Status:</strong> <?= htmlspecialchars($order['status']) ?>
                                                </small>
                                                
                                                <?php if ($order['status'] == 'Pending' && $order['order_date'] >= date('Y-m-d')): ?>
                                                    <a href="cancel_order.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to cancel this order?')">Cancel</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center p-4">
                                    <p>You haven't placed any orders yet.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const today = new Date();
        const maxDate = new Date();
        maxDate.setDate(today.getDate() + 7);
        
        document.getElementById('order_date').setAttribute('max', maxDate.toISOString().split('T')[0]);
    </script>
</body>
</html> 