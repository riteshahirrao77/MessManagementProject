<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include('header.php');
if(isset($_SESSION['email'])){
    include('../includes/connection.php');
    
    // Check if the food_prices table exists, create it if it doesn't
    $check_table = mysqli_query($connection, "SHOW TABLES LIKE 'food_prices'");
    if(mysqli_num_rows($check_table) == 0) {
        // Create the table
        $create_table = "CREATE TABLE food_prices (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            meal_type VARCHAR(20) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        if(mysqli_query($connection, $create_table)) {
            // Insert default prices
            $insert_defaults = "INSERT INTO food_prices (meal_type, price) VALUES 
                ('Breakfast', 50.00),
                ('Lunch', 80.00),
                ('Snacks', 30.00),
                ('Dinner', 80.00)";
            mysqli_query($connection, $insert_defaults);
            echo "<script>alert('Food prices table created with default prices.');</script>";
        } else {
            echo "<script>alert('Error creating food prices table: " . mysqli_error($connection) . "');</script>";
        }
    }
    
    // Handle form submission to update prices
    if(isset($_POST['update_prices'])) {
        $breakfast_price = floatval($_POST['breakfast_price']);
        $lunch_price = floatval($_POST['lunch_price']);
        $snacks_price = floatval($_POST['snacks_price']);
        $dinner_price = floatval($_POST['dinner_price']);
        
        // Update breakfast price
        $update_breakfast = "UPDATE food_prices SET price = ? WHERE meal_type = 'Breakfast'";
        $stmt = mysqli_prepare($connection, $update_breakfast);
        mysqli_stmt_bind_param($stmt, "d", $breakfast_price);
        mysqli_stmt_execute($stmt);
        
        // Update lunch price
        $update_lunch = "UPDATE food_prices SET price = ? WHERE meal_type = 'Lunch'";
        $stmt = mysqli_prepare($connection, $update_lunch);
        mysqli_stmt_bind_param($stmt, "d", $lunch_price);
        mysqli_stmt_execute($stmt);
        
        // Update snacks price
        $update_snacks = "UPDATE food_prices SET price = ? WHERE meal_type = 'Snacks'";
        $stmt = mysqli_prepare($connection, $update_snacks);
        mysqli_stmt_bind_param($stmt, "d", $snacks_price);
        mysqli_stmt_execute($stmt);
        
        // Update dinner price
        $update_dinner = "UPDATE food_prices SET price = ? WHERE meal_type = 'Dinner'";
        $stmt = mysqli_prepare($connection, $update_dinner);
        mysqli_stmt_bind_param($stmt, "d", $dinner_price);
        mysqli_stmt_execute($stmt);
        
        echo "<script>
            alert('Food prices updated successfully.');
            window.location.href = 'manage_food_prices.php';
        </script>";
    }
    
    // Get current prices
    $price_query = "SELECT * FROM food_prices ORDER BY meal_type";
    $price_result = mysqli_query($connection, $price_query);
    $prices = [];
    while($row = mysqli_fetch_assoc($price_result)) {
        $prices[$row['meal_type']] = $row['price'];
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Food Prices</title>
    <style>
        .price-container {
            padding: 20px;
        }
        .price-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .price-header {
            background-color: #194350;
            color: white;
            padding: 15px 20px;
            font-weight: bold;
        }
        .price-body {
            padding: 20px;
        }
        .form-control {
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            width: 100%;
            margin-bottom: 15px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .btn {
            padding: 10px 20px;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: all 0.3s;
        }
        .btn-primary {
            background-color: #194350;
            color: white;
        }
        .btn:hover {
            opacity: 0.85;
            transform: translateY(-2px);
        }
        .price-history {
            margin-top: 30px;
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
        .meal-icon {
            width: 40px;
            height: 40px;
            margin-right: 10px;
            background-color: #f8f9fa;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #194350;
        }
        .meal-item {
            display: flex;
            align-items: center;
        }
    </style>
</head>
<body>
    <div class="container price-container">
        <div class="price-card">
            <div class="price-header">
                <i class="fas fa-utensils mr-2"></i> Manage Food Prices
            </div>
            <div class="price-body">
                <div class="row">
                    <div class="col-md-6">
                        <h4>Update Food Prices</h4>
                        <p class="text-muted">Set the price for each meal type. These prices will be used when users order food.</p>
                        
                        <form action="" method="post">
                            <div class="form-group">
                                <label for="breakfast_price">
                                    <div class="meal-item">
                                        <div class="meal-icon"><i class="fas fa-coffee"></i></div>
                                        Breakfast Price (₹):
                                    </div>
                                </label>
                                <input type="number" name="breakfast_price" id="breakfast_price" class="form-control" min="0" step="0.01" value="<?php echo $prices['Breakfast'] ?? 50.00; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="lunch_price">
                                    <div class="meal-item">
                                        <div class="meal-icon"><i class="fas fa-utensils"></i></div>
                                        Lunch Price (₹):
                                    </div>
                                </label>
                                <input type="number" name="lunch_price" id="lunch_price" class="form-control" min="0" step="0.01" value="<?php echo $prices['Lunch'] ?? 80.00; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="snacks_price">
                                    <div class="meal-item">
                                        <div class="meal-icon"><i class="fas fa-cookie"></i></div>
                                        Snacks Price (₹):
                                    </div>
                                </label>
                                <input type="number" name="snacks_price" id="snacks_price" class="form-control" min="0" step="0.01" value="<?php echo $prices['Snacks'] ?? 30.00; ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="dinner_price">
                                    <div class="meal-item">
                                        <div class="meal-icon"><i class="fas fa-moon"></i></div>
                                        Dinner Price (₹):
                                    </div>
                                </label>
                                <input type="number" name="dinner_price" id="dinner_price" class="form-control" min="0" step="0.01" value="<?php echo $prices['Dinner'] ?? 80.00; ?>" required>
                            </div>
                            
                            <div class="text-center">
                                <button type="submit" name="update_prices" class="btn btn-primary">
                                    <i class="fas fa-save mr-2"></i> Update Prices
                                </button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="col-md-6">
                        <h4>Current Prices</h4>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Meal Type</th>
                                        <th>Price</th>
                                        <th>Last Updated</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Reset the result pointer
                                    mysqli_data_seek($price_result, 0);
                                    while($row = mysqli_fetch_assoc($price_result)): 
                                    ?>
                                        <tr>
                                            <td>
                                                <div class="meal-item">
                                                    <div class="meal-icon">
                                                        <?php if($row['meal_type'] == 'Breakfast'): ?>
                                                            <i class="fas fa-coffee"></i>
                                                        <?php elseif($row['meal_type'] == 'Lunch'): ?>
                                                            <i class="fas fa-utensils"></i>
                                                        <?php elseif($row['meal_type'] == 'Snacks'): ?>
                                                            <i class="fas fa-cookie"></i>
                                                        <?php else: ?>
                                                            <i class="fas fa-moon"></i>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php echo $row['meal_type']; ?>
                                                </div>
                                            </td>
                                            <td>₹<?php echo number_format($row['price'], 2); ?></td>
                                            <td><?php echo isset($row['last_updated']) ? date('d M Y H:i', strtotime($row['last_updated'])) : 'N/A'; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <div class="alert alert-info mt-4">
                            <i class="fas fa-info-circle mr-2"></i> These prices are used to calculate the fee amount when users order food. When an order is approved, the corresponding price is added to the user's fee amount.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
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