<?php
session_start();
include('includes/connection.php');

// Check if user is logged in
if(!isset($_SESSION['email'])) {
    header("Location: index.php");
    exit();
}

// Get days of the week
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$meal_types = ['Breakfast', 'Lunch', 'Dinner'];

// Fetch menu from menu_items table (used by admin panel)
$menu_data = [];
foreach ($days as $day) {
    $menu_data[$day] = [];
    foreach ($meal_types as $meal_type) {
        $sql = "SELECT items FROM menu_items WHERE day = ? AND meal_type = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $day, $meal_type);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $items);
        
        if (mysqli_stmt_fetch($stmt)) {
            $menu_data[$day][$meal_type] = $items;
        } else {
            $menu_data[$day][$meal_type] = '';
        }
        
        mysqli_stmt_close($stmt);
    }
}

// Get food prices
$prices = [];
$price_query = "SELECT meal_type, price FROM food_prices";
$price_result = mysqli_query($connection, $price_query);
if ($price_result) {
    while($row = mysqli_fetch_assoc($price_result)) {
        $prices[$row['meal_type']] = $row['price'];
    }
}

// Get current day of week
$current_day = date('l');

// Include header
include('includes/header.php');
?>

<style>
    .menu-container {
        max-width: 900px;
        margin: 0 auto;
        padding: 15px;
    }
    .header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .day-card {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        margin-bottom: 15px;
        overflow: hidden;
    }
    .day-header {
        padding: 10px 15px;
        background-color: #f8f9fa;
        border-bottom: 1px solid #eee;
        font-weight: 600;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .day-header.today {
        background-color: #e8f4ff;
        border-left: 4px solid #007bff;
    }
    .day-body {
        padding: 0;
    }
    .meal-row {
        padding: 10px 15px;
        border-bottom: 1px solid #f1f1f1;
        display: flex;
    }
    .meal-row:last-child {
        border-bottom: none;
    }
    .meal-type {
        min-width: 100px;
        font-weight: 500;
    }
    .meal-price {
        font-size: 14px;
        color: #28a745;
        margin-left: 5px;
    }
    .meal-items {
        flex: 1;
    }
    .meal-list {
        margin: 0;
        padding-left: 20px;
    }
    .meal-list li {
        margin-bottom: 2px;
    }
    .empty-text {
        color: #6c757d;
        font-style: italic;
    }
    .today-badge {
        font-size: 12px;
        background-color: #007bff;
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .back-btn {
        padding: 5px 10px;
        color: #6c757d;
        border: 1px solid #ccc;
        border-radius: 4px;
        text-decoration: none;
        font-size: 14px;
    }
    .back-btn:hover {
        background-color: #f8f9fa;
        text-decoration: none;
    }
    .order-link {
        display: inline-block;
        margin-top: 5px;
        font-size: 14px;
        color: #007bff;
        text-decoration: none;
    }
    .order-link:hover {
        text-decoration: underline;
    }
</style>

<div class="container menu-container">
    <div class="header-actions">
        <h4>Weekly Menu</h4>
        <a href="user_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>
    
    <?php foreach ($days as $day): ?>
        <div class="day-card">
            <div class="day-header <?php echo ($day == $current_day) ? 'today' : ''; ?>">
                <?php echo $day; ?>
                <?php if($day == $current_day): ?>
                    <span class="today-badge">Today</span>
                <?php endif; ?>
            </div>
            <div class="day-body">
                <?php foreach ($meal_types as $meal_type): ?>
                    <div class="meal-row">
                        <div class="meal-type">
                            <?php echo $meal_type; ?>
                            <span class="meal-price">₹<?php echo number_format($prices[$meal_type] ?? 0, 2); ?></span>
                        </div>
                        <div class="meal-items">
                            <?php 
                            if(!empty($menu_data[$day][$meal_type])) {
                                $items = explode(',', $menu_data[$day][$meal_type]);
                                echo '<ul class="meal-list">';
                                foreach($items as $item) {
                                    echo '<li>' . htmlspecialchars(trim($item)) . '</li>';
                                }
                                echo '</ul>';
                            } else {
                                echo '<p class="empty-text">No items available</p>';
                            }
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?php if($day == $current_day): ?>
                    <div style="text-align: right; padding: 0 15px 10px;">
                        <a href="order_food.php" class="order-link">
                            <i class="fas fa-utensils"></i> Order Today's Meals
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
    
    <?php if(count($menu_data) == 0): ?>
        <div class="alert alert-info">
            <p>The mess menu hasn't been set up yet. Please check back later.</p>
        </div>
    <?php endif; ?>
</div>

<?php include('includes/footer.php'); ?> 