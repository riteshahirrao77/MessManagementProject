<?php
session_start();
if(isset($_SESSION['email'])){
    include('includes/connection.php');
    
    if(!isset($_SESSION['uid'])) {
        echo "<script>
            alert('User ID not found in session. Please log in again.');
            window.location.href = 'index.php';
        </script>";
        exit();
    }
    
    $check_price_table = mysqli_query($connection, "SHOW TABLES LIKE 'food_prices'");
    if(mysqli_num_rows($check_price_table) == 0) {
        echo "<script>
            alert('Food prices are not set up yet. Please contact the administrator.');
            window.location.href = 'user_dashboard.php';
        </script>";
        exit();
    }
    
    $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'food_orders'");
    if(mysqli_num_rows($check_table) == 0) {
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
        if(!mysqli_query($connection, $create_table)) {
            echo "<script>
                alert('Error creating food_orders table: " . mysqli_error($connection) . "');
            </script>";
        }
    }
    
    $current_day = date('l');
    
    $query = "SELECT meal1 as breakfast, meal2 as lunch, meal3 as snacks, meal4 as dinner FROM menu WHERE day = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $current_day);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) == 0) {
        echo "<script>
            alert('Menu for today is not available. Please try again later.');
            window.location.href = 'user_dashboard.php';
        </script>";
        exit();
    }
    
    $menu = mysqli_fetch_assoc($result);
    
    $price_query = "SELECT * FROM food_prices";
    $price_result = mysqli_query($connection, $price_query);
    $prices = [];
    while($row = mysqli_fetch_assoc($price_result)) {
        $prices[$row['meal_type']] = $row['price'];
    }
    
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order_food'])) {
        $meal_type = mysqli_real_escape_string($connection, $_POST['meal_type']);
        $food_items = mysqli_real_escape_string($connection, $_POST['food_items']);
        $order_date = mysqli_real_escape_string($connection, $_POST['order_date']);
        
        $today = date('Y-m-d');
        if ($order_date < $today) {
            $error_msg = "You cannot order for past dates.";
        } else {
            $price = 0;
            // Get price from food_prices table
            $price_query = "SELECT price FROM food_prices WHERE meal_type = ?";
            $stmt = mysqli_prepare($connection, $price_query);
            mysqli_stmt_bind_param($stmt, "s", $meal_type);
            mysqli_stmt_execute($stmt);
            $price_result = mysqli_stmt_get_result($stmt);
            
            if ($row = mysqli_fetch_assoc($price_result)) {
                $price = $row['price'];
            } else {
                // Default prices if not found in the table
                if ($meal_type == "Breakfast") {
                    $price = 60.00;
                } else if ($meal_type == "Lunch") {
                    $price = 100.00;
                } else if ($meal_type == "Dinner") {
                    $price = 90.00;
                }
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
    
    $user_id = $_SESSION['uid'];
    $user_query = "SELECT fee_amount FROM users WHERE sno = ?";
    $user_stmt = mysqli_prepare($connection, $user_query);
    mysqli_stmt_bind_param($user_stmt, "i", $user_id);
    mysqli_stmt_execute($user_stmt);
    $user_result = mysqli_stmt_get_result($user_stmt);
    $user_data = mysqli_fetch_assoc($user_result);
    $current_fee = $user_data['fee_amount'];
    
    include('includes/header.php');
?>
<style>
    body {
        background-color: #f8f9fa;
    }
    .order-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 20px;
    }
    .order-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.12);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .order-header {
        padding: 15px;
        border-bottom: 1px solid #eee;
        font-weight: 600;
    }
    .order-header i {
        color: #007bff;
        margin-right: 8px;
    }
    .order-body {
        padding: 20px;
    }
    .form-control {
        border: 1px solid #ced4da;
        border-radius: 4px;
        padding: 8px 12px;
        width: 100%;
        font-size: 14px;
    }
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        font-size: 14px;
        color: #555;
    }
    .btn {
        padding: 8px 16px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        border: none;
    }
    .btn-primary {
        background-color: #007bff;
        color: white;
    }
    .btn-danger {
        background-color: #dc3545;
        color: white;
    }
    .btn:hover {
        opacity: 0.9;
    }
    .menu-card {
        background-color: #f8f9fa;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 15px;
        border-left: 3px solid #007bff;
    }
    .menu-title {
        font-weight: 500;
        margin-bottom: 8px;
        color: #555;
    }
    .menu-title i {
        margin-right: 5px;
        color: #007bff;
    }
    .menu-items {
        font-size: 14px;
        padding-left: 10px;
    }
    .price-tag {
        display: inline-block;
        padding: 2px 6px;
        background-color: #28a745;
        color: white;
        border-radius: 3px;
        font-size: 12px;
        margin-left: 5px;
    }
    .fee-balance {
        background-color: #e9f7fe;
        border-radius: 4px;
        padding: 10px;
        margin-bottom: 15px;
        font-size: 14px;
    }
    .fee-balance i {
        color: #007bff;
        margin-right: 5px;
    }
    .form-text {
        font-size: 12px;
        color: #6c757d;
        margin-top: 4px;
    }
    .alert {
        padding: 8px 12px;
        border-radius: 4px;
        font-size: 14px;
        margin-bottom: 15px;
    }
    .alert-info {
        background-color: #f8f9fa;
        border-left: 3px solid #17a2b8;
        color: #555;
    }
    .back-link {
        display: inline-block;
        margin-bottom: 15px;
        color: #6c757d;
        text-decoration: none;
        font-size: 14px;
    }
    .back-link:hover {
        color: #007bff;
        text-decoration: none;
    }
    .back-link i {
        margin-right: 5px;
    }
</style>

<div class="container order-container">
    <a href="user_dashboard.php" class="back-link">
        <i class="fas fa-arrow-left"></i> Back to Dashboard
    </a>
    
    <div class="order-card">
        <div class="order-header">
            <i class="fas fa-utensils"></i> Order Food
        </div>
        <div class="order-body">
            <div class="row">
                <div class="col-md-5">
                    <h5>Place Your Order</h5>
                    
                    <div class="fee-balance">
                        <i class="fas fa-money-bill-wave"></i> Your Current Fee Balance: <strong>₹<?php echo number_format($current_fee, 2); ?></strong>
                    </div>
                    
                    <?php if(isset($error_msg)): ?>
                        <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($success_msg)): ?>
                        <div class="alert alert-success"><?php echo $success_msg; ?></div>
                    <?php endif; ?>
                    
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="meal_type">Meal Type</label>
                            <select class="form-control" id="meal_type" name="meal_type" required>
                                <option value="">Select Meal Type</option>
                                <?php
                                // Get available meal types from the menu_items table
                                $meal_types = ['Breakfast', 'Lunch', 'Dinner'];
                                
                                // Get prices from food_prices table
                                $prices_query = "SELECT meal_type, price FROM food_prices";
                                $prices_result = mysqli_query($connection, $prices_query);
                                $prices = [];
                                if ($prices_result) {
                                    while ($price_row = mysqli_fetch_assoc($prices_result)) {
                                        $prices[$price_row['meal_type']] = $price_row['price'];
                                    }
                                }
                                
                                foreach ($meal_types as $type) {
                                    $price = isset($prices[$type]) ? $prices[$type] : 0;
                                    echo "<option value=\"$type\">$type - ₹" . number_format($price, 2) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="food_items">Food Items (Optional)</label>
                            <textarea class="form-control" id="food_items" name="food_items" rows="2" placeholder="Enter any special requests..."></textarea>
                            <small class="form-text">Leave blank for standard meal items.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="order_date">Order Date:</label>
                            <input type="date" name="order_date" id="order_date" class="form-control" min="<?php echo date('Y-m-d'); ?>" max="<?php echo date('Y-m-d', strtotime('+7 days')); ?>" value="<?php echo date('Y-m-d'); ?>" required>
                            <small class="form-text">You can order for today or up to 7 days in advance.</small>
                        </div>
                        
                        <div class="text-right">
                            <button type="submit" name="order_food" class="btn btn-primary">
                                <i class="fas fa-check"></i> Place Order
                            </button>
                            <a href="user_dashboard.php" class="btn btn-danger">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
                
                <div class="col-md-7">
                    <h5>Today's Menu (<?php echo date('d M Y'); ?> - <?php echo $current_day; ?>)</h5>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Orders placed will be reviewed by the admin before approval. The fee amount will be added to your account after approval.
                    </div>
                    
                    <?php
                    $today = date("l");
                    $meal_types = ['Breakfast', 'Lunch', 'Dinner'];
                    
                    foreach ($meal_types as $meal_type) {
                        $query = "SELECT items FROM menu_items WHERE day = ? AND meal_type = ?";
                        $stmt = mysqli_prepare($connection, $query);
                        mysqli_stmt_bind_param($stmt, "ss", $today, $meal_type);
                        mysqli_stmt_execute($stmt);
                        $result = mysqli_stmt_get_result($stmt);
                        $items = '';
                        if(mysqli_num_rows($result) > 0) {
                            $row = mysqli_fetch_assoc($result);
                            $items = $row['items'];
                        }
                        
                        // Icon based on meal type
                        $icon = 'fas fa-utensils';
                        if($meal_type == 'Breakfast') {
                            $icon = 'fas fa-coffee';
                        } else if($meal_type == 'Dinner') {
                            $icon = 'fas fa-moon';
                        }
                        
                        echo '<div class="menu-card">';
                        echo '<div class="menu-title">';
                        echo '<i class="' . $icon . '"></i> ' . $meal_type;
                        echo '<span class="price-tag">₹' . number_format($prices[$meal_type] ?? 0, 2) . '</span>';
                        echo '</div>';
                        
                        if(!empty($items)) {
                            $items_array = explode(',', $items);
                            echo '<div class="menu-items">';
                            foreach($items_array as $item) {
                                echo '<div>' . htmlspecialchars(trim($item)) . '</div>';
                            }
                            echo '</div>';
                        } else {
                            echo '<div class="menu-items text-muted">Not available</div>';
                        }
                        
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Update food items placeholder based on meal type
        $('#meal_type').change(function() {
            const mealType = $(this).val();
            const foodItemsField = $('#food_items');
            
            if (mealType) {
                // Find the menu items for the selected meal type
                const menuText = $('.menu-title:contains("' + mealType + '")').closest('.menu-card').find('.menu-items').text().trim();
                if (menuText && menuText !== 'Not available') {
                    foodItemsField.attr('placeholder', 'Standard menu: ' + menuText + '\nEnter any special requests...');
                } else {
                    foodItemsField.attr('placeholder', 'Enter any specific food items or preferences...');
                }
            } else {
                foodItemsField.attr('placeholder', 'Enter any specific food items or preferences...');
            }
        });
    });
</script>

<?php 
} else {
    header('location:index.php');
    exit();
}
?> 