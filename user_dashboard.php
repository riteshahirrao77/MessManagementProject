<?php
  include('includes/header.php');
  if(isset($_SESSION['email']) && isset($_SESSION['uid'])){
    include('includes/connection.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Dashboard - Mess Management System</title>
    <link rel="stylesheet" href="assets/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .dashboard-container {
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.12);
            margin-bottom: 20px;
            background-color: white;
        }
        .card-header {
            background-color: white;
            border-bottom: 1px solid #eee;
            padding: 12px 15px;
            font-weight: 600;
            font-size: 15px;
        }
        .card-header i {
            color: #007bff;
            margin-right: 8px;
        }
        .card-body {
            padding: 15px;
        }
        .action-btn {
            border: none;
            border-radius: 5px;
            padding: 8px 10px;
            margin-bottom: 8px;
            margin-right: 5px;
            font-size: 14px;
            color: #fff;
            text-decoration: none;
            display: inline-block;
            white-space: nowrap;
        }
        .action-btn:hover {
            opacity: 0.9;
            text-decoration: none;
            color: white;
        }
        .action-btn i {
            margin-right: 5px;
            font-size: 12px;
        }
        .meal-item {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .meal-item:last-child {
            border-bottom: none;
        }
        .meal-label {
            font-weight: 500;
            color: #555;
        }
        .meal-time {
            color: #6c757d;
            font-size: 13px;
        }
        .notice-item {
            padding: 8px 0;
            border-bottom: 1px solid #f5f5f5;
            font-size: 14px;
        }
        .notice-item:last-child {
            border-bottom: none;
        }
        .notice-item i {
            color: #007bff;
            margin-right: 8px;
        }
        .btn-blue {
            background-color: #007bff;
        }
        .btn-green {
            background-color: #28a745;
        }
        .btn-teal {
            background-color: #17a2b8;
        }
        .btn-orange {
            background-color: #fd7e14;
        }
        .btn-red {
            background-color: #dc3545;
        }
        .menu-item {
            margin-bottom: 8px;
            line-height: 1.4;
        }
        .menu-type {
            font-weight: 500;
            color: #555;
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container dashboard-container">
        <div class="row">
            <div class="col-md-8">
                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-utensils"></i> Today's Menu (<?php echo date("l"); ?>)
                            </div>
                            <div class="card-body">
                                <?php
                                    $today = date("l");
                                    $meal_types = ['Breakfast', 'Lunch', 'Dinner'];
                                    
                                    // Check if any menu items exist for today
                                    $has_menu = false;
                                    foreach ($meal_types as $meal_type) {
                                        $query = "SELECT items FROM menu_items WHERE day = ? AND meal_type = ?";
                                        $stmt = mysqli_prepare($connection, $query);
                                        mysqli_stmt_bind_param($stmt, "ss", $today, $meal_type);
                                        mysqli_stmt_execute($stmt);
                                        $result = mysqli_stmt_get_result($stmt);
                                        if(mysqli_num_rows($result) > 0) {
                                            $has_menu = true;
                                            break;
                                        }
                                    }
                                    
                                    if($has_menu) {
                                        echo '<div class="row">';
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
                                            
                                            echo '<div class="col-md-4">';
                                            echo '<div class="menu-item">';
                                            echo '<span class="menu-type"><i class="' . $icon . '"></i> ' . $meal_type . ':</span>';
                                            echo '<div>' . (!empty($items) ? htmlspecialchars($items) : 'Not available') . '</div>';
                                            echo '</div>';
                                            echo '</div>';
                                        }
                                        echo '</div>';
                                        
                                        echo '<div class="mt-3">';
                                        echo '<a href="order_food.php" class="action-btn btn-green"><i class="fas fa-utensils"></i> Order Food</a>';
                                        echo '<a href="view_menu.php" class="action-btn btn-blue"><i class="fas fa-calendar-week"></i> View Full Menu</a>';
                                        echo '</div>';
                                    } else {
                                        echo '<div class="alert alert-info py-2">No menu available for today.</div>';
                                        echo '<div class="mt-2">';
                                        echo '<a href="view_menu.php" class="action-btn btn-blue"><i class="fas fa-calendar-week"></i> View Weekly Menu</a>';
                                        echo '</div>';
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-clock"></i> Mess Timings
                            </div>
                            <div class="card-body">
                                <div class="meal-item">
                                    <span class="meal-label"><i class="fas fa-coffee text-muted"></i> Breakfast:</span>
                                    <span class="meal-time">8:00 AM - 9:00 AM</span>
                                </div>
                                <div class="meal-item">
                                    <span class="meal-label"><i class="fas fa-utensils text-muted"></i> Lunch:</span>
                                    <span class="meal-time">12:00 PM - 2:00 PM</span>
                                </div>
                                <div class="meal-item">
                                    <span class="meal-label"><i class="fas fa-cookie text-muted"></i> Snacks:</span>
                                    <span class="meal-time">4:00 PM - 5:00 PM</span>
                                </div>
                                <div class="meal-item">
                                    <span class="meal-label"><i class="fas fa-moon text-muted"></i> Dinner:</span>
                                    <span class="meal-time">8:00 PM - 9:30 PM</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <i class="fas fa-bullhorn"></i> Important Notices
                            </div>
                            <div class="card-body">
                                <div class="notice-item">
                                    <i class="fas fa-shield-virus"></i>
                                    Please wear mask and sanitize your hands.
                                </div>
                                <div class="notice-item">
                                    <i class="fas fa-people-arrows"></i>
                                    Maintain social distance in mess.
                                </div>
                                <div class="notice-item">
                                    <i class="fas fa-utensils"></i>
                                    Please do not waste food.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-cogs"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px;">
                            <a href="order_food.php" class="action-btn btn-green">
                                <i class="fas fa-utensils"></i> Order Food
                            </a>
                            <a href="view_orders.php" class="action-btn btn-teal">
                                <i class="fas fa-shopping-cart"></i> My Orders
                            </a>
                            <a href="view_menu.php" class="action-btn btn-blue">
                                <i class="fas fa-calendar-week"></i> Weekly Menu
                            </a>
                            <a href="edit_profile.php" class="action-btn btn-orange">
                                <i class="fas fa-user-edit"></i> Edit Profile
                            </a>
                            <a href="feedback.php" class="action-btn btn-red">
                                <i class="fas fa-comment-alt"></i> Feedback
                            </a>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-calendar-day"></i> Tomorrow's Menu (<?php echo date("l", strtotime('+1 day')); ?>)
                    </div>
                    <div class="card-body">
                        <?php
                            $tomorrow = date("l", strtotime('+1 day'));
                            $meal_types = ['Breakfast', 'Lunch', 'Dinner'];
                            
                            // Check if any menu items exist for tomorrow
                            $has_menu = false;
                            foreach ($meal_types as $meal_type) {
                                $query = "SELECT items FROM menu_items WHERE day = ? AND meal_type = ?";
                                $stmt = mysqli_prepare($connection, $query);
                                mysqli_stmt_bind_param($stmt, "ss", $tomorrow, $meal_type);
                                mysqli_stmt_execute($stmt);
                                $result = mysqli_stmt_get_result($stmt);
                                if(mysqli_num_rows($result) > 0) {
                                    $has_menu = true;
                                    break;
                                }
                            }
                            
                            if($has_menu) {
                                foreach ($meal_types as $meal_type) {
                                    $query = "SELECT items FROM menu_items WHERE day = ? AND meal_type = ?";
                                    $stmt = mysqli_prepare($connection, $query);
                                    mysqli_stmt_bind_param($stmt, "ss", $tomorrow, $meal_type);
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
                        ?>
                            <div class="menu-item">
                                <span class="menu-type"><i class="<?php echo $icon; ?>"></i> <?php echo $meal_type; ?>:</span>
                                <div><?php echo !empty($items) ? htmlspecialchars($items) : 'Not available'; ?></div>
                            </div>
                        <?php 
                                }
                            } else {
                                echo '<div class="alert alert-info py-2">No menu available for tomorrow.</div>';
                            }
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
<?php } else {
  header('Location: index.php');
  exit();
}
