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

// Check if users table exists
$table_exists = mysqli_query($connection, "SHOW TABLES LIKE 'users'");
if (mysqli_num_rows($table_exists) > 0) {
    // Table exists, check for structure and see what columns it has
    $result = mysqli_query($connection, "DESCRIBE users");
    $columns = [];
    $has_primary_key = false;
    $has_id = false;
    
    while ($row = mysqli_fetch_assoc($result)) {
        $columns[] = $row['Field'];
        if ($row['Key'] == 'PRI') {
            $has_primary_key = true;
        }
        if ($row['Field'] == 'id') {
            $has_id = true;
        }
    }
    
    if (!$has_id && !$has_primary_key) {
        // Only add id column if there's no primary key
        $alter_table = "ALTER TABLE users ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY FIRST";
        if (!mysqli_query($connection, $alter_table)) {
            // If failed, try with a different approach
            $alter_table = "ALTER TABLE users ADD COLUMN id INT(11) NOT NULL AUTO_INCREMENT FIRST, ADD PRIMARY KEY (id)";
            if (!mysqli_query($connection, $alter_table)) {
                die("Error adding id column to users table: " . mysqli_error($connection));
            }
        }
    }
} else {
    // Create users table if it doesn't exist with all required fields
    $create_users_table_sql = "CREATE TABLE IF NOT EXISTS `users` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `name` varchar(100) NOT NULL,
      `email` varchar(100) NOT NULL,
      `password` varchar(255) NOT NULL,
      `phone` varchar(20) DEFAULT NULL,
      `room_number` varchar(20) DEFAULT NULL,
      `registration_date` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`id`),
      UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    if (!mysqli_query($connection, $create_users_table_sql)) {
        die("Error creating users table: " . mysqli_error($connection));
    }
}

// Check if we need to insert a sample student for testing
$check_users_sql = "SELECT COUNT(*) as count FROM users";
$result = mysqli_query($connection, $check_users_sql);
$row = mysqli_fetch_assoc($result);

if ($row['count'] == 0) {
    // Insert a sample student for testing
    $insert_sample_user = "INSERT INTO users (name, email, password, room_number) 
                           VALUES ('Sample Student', 'student@example.com', '" . password_hash('password', PASSWORD_DEFAULT) . "', '101')";
    mysqli_query($connection, $insert_sample_user);
}

// Create attendance table if it doesn't exist
$create_table_sql = "CREATE TABLE IF NOT EXISTS `attendance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `meal_type` enum('Breakfast','Lunch','Dinner') NOT NULL,
  `attendance_date` date NOT NULL DEFAULT current_timestamp(),
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_attendance` (`user_id`,`meal_type`,`attendance_date`),
  KEY `user_id` (`user_id`),
  KEY `attendance_date` (`attendance_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

if (!mysqli_query($connection, $create_table_sql)) {
    die("Error creating table: " . mysqli_error($connection));
}

// Process attendance form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_attendance'])) {
    $user_ids = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
    $meal_type = $_POST['meal_type'];
    $attendance_date = $_POST['attendance_date'];
    
    // Begin transaction
    mysqli_begin_transaction($connection);
    
    try {
        // Clear existing attendance for the selected date and meal
        $clear_sql = "DELETE FROM attendance WHERE meal_type = ? AND attendance_date = ?";
        $stmt = mysqli_prepare($connection, $clear_sql);
        mysqli_stmt_bind_param($stmt, "ss", $meal_type, $attendance_date);
        mysqli_stmt_execute($stmt);
        
        // Insert new attendance records
        if (!empty($user_ids)) {
            $insert_sql = "INSERT INTO attendance (user_id, meal_type, attendance_date) VALUES (?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insert_sql);
            
            foreach ($user_ids as $user_id) {
                mysqli_stmt_bind_param($stmt, "iss", $user_id, $meal_type, $attendance_date);
                mysqli_stmt_execute($stmt);
            }
        }
        
        // Commit transaction
        mysqli_commit($connection);
        $_SESSION['success_message'] = "Attendance marked successfully!";
    } catch (Exception $e) {
        // Rollback on error
        mysqli_rollback($connection);
        $_SESSION['error_message'] = "Error marking attendance: " . $e->getMessage();
    }
    
    // Redirect to prevent form resubmission
    echo "<script>window.location.href = 'manage_attendance.php?date=" . $attendance_date . "&meal=" . $meal_type . "';</script>";
    exit();
}

// Get date and meal type from URL parameters or use defaults
$current_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$selected_meal = isset($_GET['meal']) ? $_GET['meal'] : 'Breakfast';

// Get all students
$users_sql = "SELECT * FROM users";
$users_result = mysqli_query($connection, $users_sql);
$users = [];
if (!$users_result) {
    $_SESSION['error_message'] = "Error loading users: " . mysqli_error($connection);
} else {
    while ($row = mysqli_fetch_assoc($users_result)) {
        // Use the first numeric key as the ID if 'id' doesn't exist
        $id = isset($row['id']) ? $row['id'] : (isset($row['sno']) ? $row['sno'] : 0);
        $name = isset($row['name']) ? $row['name'] : (isset($row['fname']) ? $row['fname'] . ' ' . $row['lname'] : 'Unknown');
        $room = isset($row['room_number']) ? $row['room_number'] : 'N/A';
        
        $users[] = [
            'id' => $id,
            'name' => $name,
            'room_number' => $room
        ];
    }
}

// Get attendance for the selected date and meal
$attendance_sql = "SELECT user_id FROM attendance WHERE meal_type = ? AND attendance_date = ?";
$stmt = mysqli_prepare($connection, $attendance_sql);
if (!$stmt) {
    $_SESSION['error_message'] = "Error preparing attendance query: " . mysqli_error($connection);
    $attended_users = [];
} else {
    mysqli_stmt_bind_param($stmt, "ss", $selected_meal, $current_date);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_id);

    $attended_users = [];
    while (mysqli_stmt_fetch($stmt)) {
        $attended_users[] = $user_id;
    }
    mysqli_stmt_close($stmt);
}

// Get attendance counts
$meal_types = ['Breakfast', 'Lunch', 'Dinner'];
$attendance_counts = [];

foreach ($meal_types as $meal) {
    $count_sql = "SELECT COUNT(*) FROM attendance WHERE meal_type = ? AND attendance_date = ?";
    $stmt = mysqli_prepare($connection, $count_sql);
    mysqli_stmt_bind_param($stmt, "ss", $meal, $current_date);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $count);
    mysqli_stmt_fetch($stmt);
    $attendance_counts[$meal] = $count;
    mysqli_stmt_close($stmt);
}

// Get date navigation
$prev_date = date('Y-m-d', strtotime($current_date . ' -1 day'));
$next_date = date('Y-m-d', strtotime($current_date . ' +1 day'));
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Manage Attendance</h2>
            
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
            
            <!-- Date and Meal Navigation -->
            <div class="card mb-4">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4>Attendance for <?php echo date('d F Y', strtotime($current_date)); ?></h4>
                        <div>
                            <a href="?date=<?php echo $prev_date; ?>&meal=<?php echo $selected_meal; ?>" class="btn btn-secondary me-2">
                                <i class="fas fa-chevron-left"></i> Previous Day
                            </a>
                            <a href="?date=<?php echo $next_date; ?>&meal=<?php echo $selected_meal; ?>" class="btn btn-secondary">
                                Next Day <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="date" class="form-label">Change Date</label>
                                            <input type="date" name="date" id="date" class="form-control" value="<?php echo $current_date; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="meal" class="form-label">Meal Type</label>
                                            <select name="meal" id="meal" class="form-select">
                                                <?php foreach ($meal_types as $meal): ?>
                                                    <option value="<?php echo $meal; ?>" <?php echo ($selected_meal == $meal) ? 'selected' : ''; ?>>
                                                        <?php echo $meal; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">View Attendance</button>
                            </form>
                        </div>
                        <div class="col-md-6">
                            <div class="card h-100">
                                <div class="card-header">
                                    <h5>Today's Attendance Summary</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <?php foreach ($meal_types as $meal): ?>
                                            <div class="col-md-4">
                                                <div class="text-center">
                                                    <h6><?php echo $meal; ?></h6>
                                                    <div class="fs-4"><?php echo $attendance_counts[$meal]; ?></div>
                                                    <small>students</small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Mark Attendance Form -->
                    <form method="POST" action="">
                        <input type="hidden" name="attendance_date" value="<?php echo $current_date; ?>">
                        <input type="hidden" name="meal_type" value="<?php echo $selected_meal; ?>">
                        
                        <div class="d-flex justify-content-between mb-3">
                            <h5>Mark Attendance for <?php echo $selected_meal; ?></h5>
                            <div>
                                <button type="button" class="btn btn-sm btn-secondary me-2" onclick="selectAll()">Select All</button>
                                <button type="button" class="btn btn-sm btn-secondary" onclick="deselectAll()">Deselect All</button>
                            </div>
                        </div>
                        
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Present</th>
                                        <th>Student ID</th>
                                        <th>Name</th>
                                        <th>Room Number</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (count($users) > 0): ?>
                                        <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input type="checkbox" 
                                                               name="user_ids[]" 
                                                               value="<?php echo $user['id']; ?>" 
                                                               class="form-check-input" 
                                                               id="user_<?php echo $user['id']; ?>"
                                                               <?php echo in_array($user['id'], $attended_users) ? 'checked' : ''; ?>>
                                                    </div>
                                                </td>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                                <td><?php echo htmlspecialchars($user['room_number'] ?: 'N/A'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No students found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <?php if (count($users) > 0): ?>
                            <button type="submit" name="mark_attendance" class="btn btn-primary mt-3">Save Attendance</button>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function selectAll() {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}

function deselectAll() {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}
</script>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 