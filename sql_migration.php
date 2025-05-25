<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if admin is logged in
if(!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'admin') {
    echo "Access denied. Admin login required.";
    exit();
}

require_once 'includes/connection.php';

// Create menu_items table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `menu_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `day` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `items` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `day_meal` (`day`,`meal_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating menu_items table: " . mysqli_error($connection));
}

// Check if the old menu table exists
$check_old_table = mysqli_query($connection, "SHOW TABLES LIKE 'menu'");
$old_table_exists = mysqli_num_rows($check_old_table) > 0;

// Process the migration if the old table exists
$migration_messages = [];
$error_messages = [];

if ($old_table_exists) {
    // Get data from old menu table
    $get_old_data = "SELECT * FROM menu ORDER BY FIELD(day, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $old_data = mysqli_query($connection, $get_old_data);
    
    if ($old_data) {
        while ($row = mysqli_fetch_assoc($old_data)) {
            $day = $row['day'];
            
            // Migrate Breakfast
            if (!empty($row['meal1'])) {
                $insert_breakfast = "INSERT INTO menu_items (day, meal_type, items) VALUES (?, 'Breakfast', ?)
                                    ON DUPLICATE KEY UPDATE items = VALUES(items)";
                $stmt = mysqli_prepare($connection, $insert_breakfast);
                mysqli_stmt_bind_param($stmt, "ss", $day, $row['meal1']);
                if (mysqli_stmt_execute($stmt)) {
                    $migration_messages[] = "Migrated Breakfast menu for $day";
                } else {
                    $error_messages[] = "Error migrating Breakfast menu for $day: " . mysqli_error($connection);
                }
            }
            
            // Migrate Lunch
            if (!empty($row['meal2'])) {
                $insert_lunch = "INSERT INTO menu_items (day, meal_type, items) VALUES (?, 'Lunch', ?)
                                ON DUPLICATE KEY UPDATE items = VALUES(items)";
                $stmt = mysqli_prepare($connection, $insert_lunch);
                mysqli_stmt_bind_param($stmt, "ss", $day, $row['meal2']);
                if (mysqli_stmt_execute($stmt)) {
                    $migration_messages[] = "Migrated Lunch menu for $day";
                } else {
                    $error_messages[] = "Error migrating Lunch menu for $day: " . mysqli_error($connection);
                }
            }
            
            // Migrate Dinner (from meal4)
            if (!empty($row['meal4'])) {
                $insert_dinner = "INSERT INTO menu_items (day, meal_type, items) VALUES (?, 'Dinner', ?)
                                ON DUPLICATE KEY UPDATE items = VALUES(items)";
                $stmt = mysqli_prepare($connection, $insert_dinner);
                mysqli_stmt_bind_param($stmt, "ss", $day, $row['meal4']);
                if (mysqli_stmt_execute($stmt)) {
                    $migration_messages[] = "Migrated Dinner menu for $day";
                } else {
                    $error_messages[] = "Error migrating Dinner menu for $day: " . mysqli_error($connection);
                }
            }
        }
    } else {
        $error_messages[] = "Error fetching data from old menu table: " . mysqli_error($connection);
    }
} else {
    $migration_messages[] = "Old menu table doesn't exist. No migration needed.";
}

// Create food_prices table if it doesn't exist
$create_prices_table = "CREATE TABLE IF NOT EXISTS `food_prices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `price` decimal(10,2) NOT NULL DEFAULT 0.00,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `meal_type` (`meal_type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_prices_table)) {
    $error_messages[] = "Error creating food_prices table: " . mysqli_error($connection);
}

// Insert default prices if they don't exist
$default_prices = [
    ['Breakfast', 60.00],
    ['Lunch', 100.00],
    ['Dinner', 90.00]
];

foreach ($default_prices as $price) {
    $insert_price = "INSERT INTO food_prices (meal_type, price) 
                     VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE price = VALUES(price)";
    $stmt = mysqli_prepare($connection, $insert_price);
    mysqli_stmt_bind_param($stmt, "sd", $price[0], $price[1]);
    if (mysqli_stmt_execute($stmt)) {
        $migration_messages[] = "Set default price for {$price[0]}: ₹{$price[1]}";
    } else {
        $error_messages[] = "Error setting price for {$price[0]}: " . mysqli_error($connection);
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Migration Tool</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body {
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .message-list {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">Menu Migration Tool</h1>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Migration Results</h4>
            </div>
            <div class="card-body">
                <?php if (empty($error_messages)): ?>
                    <div class="alert alert-success">
                        <h5>Migration Completed Successfully!</h5>
                        <p>The menu data has been migrated to the new structure.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger">
                        <h5>Migration Completed with Errors</h5>
                        <p>There were some issues during migration. See details below.</p>
                    </div>
                <?php endif; ?>
                
                <h5>Migration Messages:</h5>
                <div class="message-list border p-3 mb-3 bg-light">
                    <?php if (!empty($migration_messages)): ?>
                        <ul>
                            <?php foreach ($migration_messages as $message): ?>
                                <li><?php echo htmlspecialchars($message); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p class="text-muted">No migration messages.</p>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($error_messages)): ?>
                    <h5>Error Messages:</h5>
                    <div class="message-list border p-3 bg-light">
                        <ul class="text-danger">
                            <?php foreach ($error_messages as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="text-center">
            <a href="Admin pannel/manage_menu.php" class="btn btn-primary">Go to Menu Management</a>
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html> 