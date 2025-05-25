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

// Create menu table if it doesn't exist
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
    die("Error creating table: " . mysqli_error($connection));
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_menu'])) {
        $day = mysqli_real_escape_string($connection, $_POST['day']);
        $meal_type = mysqli_real_escape_string($connection, $_POST['meal_type']);
        $items = mysqli_real_escape_string($connection, $_POST['items']);
        
        if (empty($items)) {
            $_SESSION['error_message'] = "Menu items cannot be empty!";
        } else {
            // Check if menu already exists
            $check_sql = "SELECT id FROM menu_items WHERE day = ? AND meal_type = ?";
            $stmt = mysqli_prepare($connection, $check_sql);
            mysqli_stmt_bind_param($stmt, "ss", $day, $meal_type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);
            
            if (mysqli_stmt_num_rows($stmt) > 0) {
                // Update existing menu
                $sql = "UPDATE menu_items SET items = ? WHERE day = ? AND meal_type = ?";
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $items, $day, $meal_type);
            } else {
                // Insert new menu
                $sql = "INSERT INTO menu_items (day, meal_type, items) VALUES (?, ?, ?)";
                $stmt = mysqli_prepare($connection, $sql);
                mysqli_stmt_bind_param($stmt, "sss", $day, $meal_type, $items);
            }
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Menu for " . $day . " " . $meal_type . " updated successfully!";
                // Redirect to prevent form resubmission
                header("Location: manage_menu.php");
                exit();
            } else {
                $_SESSION['error_message'] = "Error updating menu: " . mysqli_error($connection);
            }
        }
    }
    
    if (isset($_POST['delete_menu'])) {
        $day = mysqli_real_escape_string($connection, $_POST['day']);
        $meal_type = mysqli_real_escape_string($connection, $_POST['meal_type']);
        
        $sql = "DELETE FROM menu_items WHERE day = ? AND meal_type = ?";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $day, $meal_type);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Menu for " . $day . " " . $meal_type . " deleted successfully!";
            header("Location: manage_menu.php");
            exit();
        } else {
            $_SESSION['error_message'] = "Error deleting menu: " . mysqli_error($connection);
        }
    }
}

// Fetch menu for each day and meal type
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
$meal_types = ['Breakfast', 'Lunch', 'Dinner'];

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
$check_food_prices = mysqli_query($connection, "SHOW TABLES LIKE 'food_prices'");
if (mysqli_num_rows($check_food_prices) > 0) {
    $price_query = "SELECT meal_type, price FROM food_prices";
    $price_result = mysqli_query($connection, $price_query);
    while ($row = mysqli_fetch_assoc($price_result)) {
        $prices[$row['meal_type']] = $row['price'];
    }
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Manage Mess Menu</h2>
            
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
            
            <div class="row">
                <!-- Menu Editor Form -->
                <div class="col-md-4">
                    <div class="card mb-4 h-100">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Menu Editor</h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Update the menu for each day and meal type. Separate food items with commas.</p>
                            
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="day" class="form-label">Day</label>
                                    <select name="day" id="day" class="form-select" required>
                                        <?php foreach ($days as $day): ?>
                                            <option value="<?php echo $day; ?>"><?php echo $day; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="meal_type" class="form-label">Meal Type</label>
                                    <select name="meal_type" id="meal_type" class="form-select" required onchange="loadMenuItems()">
                                        <?php foreach ($meal_types as $meal_type): ?>
                                            <option value="<?php echo $meal_type; ?>"><?php echo $meal_type; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="items" class="form-label">Menu Items</label>
                                    <textarea name="items" id="items" class="form-control" rows="4" required placeholder="Enter food items separated by commas"></textarea>
                                </div>
                                
                                <div class="d-flex justify-content-between">
                                    <button type="submit" name="update_menu" class="btn btn-primary">
                                        <i class="fas fa-save me-1"></i> Update Menu
                                    </button>
                                    <button type="submit" name="delete_menu" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this menu?');">
                                        <i class="fas fa-trash me-1"></i> Delete Menu
                                    </button>
                                </div>
                            </form>
                            
                            <?php if (!empty($prices)): ?>
                            <div class="mt-4">
                                <h5>Current Meal Prices</h5>
                                <ul class="list-group">
                                    <?php foreach ($prices as $meal => $price): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <?php echo $meal; ?>
                                            <span class="badge bg-primary rounded-pill">₹<?php echo number_format($price, 2); ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <div class="mt-2">
                                    <a href="manage_food_prices.php" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-edit me-1"></i> Edit Prices
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Display Weekly Menu -->
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h4 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Weekly Menu</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Day</th>
                                            <th>Breakfast</th>
                                            <th>Lunch</th>
                                            <th>Dinner</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($days as $day): ?>
                                        <tr>
                                            <td class="fw-bold">
                                                <span class="badge bg-secondary me-1">
                                                    <?php 
                                                    $day_num = array_search($day, $days) + 1;
                                                    echo $day_num;
                                                    ?>
                                                </span>
                                                <?php echo $day; ?>
                                            </td>
                                            <?php foreach ($meal_types as $meal_type): ?>
                                            <td>
                                                <?php
                                                if (!empty($menu_data[$day][$meal_type])) {
                                                    $items = explode(',', $menu_data[$day][$meal_type]);
                                                    echo '<ul class="list-unstyled mb-0">';
                                                    foreach ($items as $item) {
                                                        echo '<li><i class="fas fa-utensil-spoon me-1 text-muted"></i> ' . htmlspecialchars(trim($item)) . '</li>';
                                                    }
                                                    echo '</ul>';
                                                } else {
                                                    echo '<em class="text-muted">Not set</em>';
                                                }
                                                ?>
                                                <div class="mt-1">
                                                    <button type="button" class="btn btn-sm btn-outline-primary edit-menu-btn" 
                                                        data-day="<?php echo $day; ?>" 
                                                        data-meal="<?php echo $meal_type; ?>" 
                                                        data-items="<?php echo htmlspecialchars($menu_data[$day][$meal_type]); ?>">
                                                        <i class="fas fa-edit"></i> Edit
                                                    </button>
                                                </div>
                                            </td>
                                            <?php endforeach; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-end mt-3">
                                <a href="../view_menu.php" target="_blank" class="btn btn-outline-primary">
                                    <i class="fas fa-external-link-alt me-1"></i> View Public Menu
                                </a>
                                <button type="button" class="btn btn-outline-success" onclick="printMenu()">
                                    <i class="fas fa-print me-1"></i> Print Menu
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function loadMenuItems() {
    const day = document.getElementById('day').value;
    const mealType = document.getElementById('meal_type').value;
    const menuData = <?php echo json_encode($menu_data); ?>;
    
    if (menuData[day] && menuData[day][mealType]) {
        document.getElementById('items').value = menuData[day][mealType];
    } else {
        document.getElementById('items').value = '';
    }
}

// Quick edit functionality
document.addEventListener('DOMContentLoaded', function() {
    loadMenuItems();
    
    // Update menu items when day changes
    document.getElementById('day').addEventListener('change', loadMenuItems);
    
    // Set up edit buttons
    const editButtons = document.querySelectorAll('.edit-menu-btn');
    editButtons.forEach(button => {
        button.addEventListener('click', function() {
            const day = this.getAttribute('data-day');
            const meal = this.getAttribute('data-meal');
            const items = this.getAttribute('data-items');
            
            document.getElementById('day').value = day;
            document.getElementById('meal_type').value = meal;
            document.getElementById('items').value = items;
            
            // Scroll to form
            document.querySelector('.card-header').scrollIntoView({ behavior: 'smooth' });
        });
    });
});

function printMenu() {
    const printContents = document.querySelector('.table-responsive').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h2 style="text-align: center; margin-bottom: 20px;">Weekly Mess Menu</h2>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    location.reload();
}
</script>

<?php include 'footer.php'; ?>

// End output buffering
ob_end_flush();
?> 