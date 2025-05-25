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

// Check if the users table exists and has expected structure
$check_table_sql = "SHOW TABLES LIKE 'users'";
$table_exists = mysqli_query($connection, $check_table_sql);

if (mysqli_num_rows($table_exists) == 0) {
    // Table doesn't exist, create it
    $create_table_sql = "CREATE TABLE `users` (
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
    
    if (!mysqli_query($connection, $create_table_sql)) {
        die("Error creating users table: " . mysqli_error($connection));
    }
} else {
    // Table exists, check if the structure matches what we need
    // First, let's see if the 'name' column exists
    $check_name_sql = "SHOW COLUMNS FROM users LIKE 'name'";
    $name_exists = mysqli_query($connection, $check_name_sql);
    
    if (mysqli_num_rows($name_exists) == 0) {
        // 'name' column doesn't exist, check if 'fname' and 'lname' exist instead
        $check_fname_sql = "SHOW COLUMNS FROM users LIKE 'fname'";
        $fname_exists = mysqli_query($connection, $check_fname_sql);
        
        if (mysqli_num_rows($fname_exists) > 0) {
            // The table has fname but not name, so we'll need to handle this in our queries
            // Continue with the existing structure
        } else {
            // Neither name nor fname exists, try to add name column
            $add_name_sql = "ALTER TABLE users ADD COLUMN name VARCHAR(100) NOT NULL DEFAULT 'User'";
            mysqli_query($connection, $add_name_sql);
        }
    }
}

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $room_number = mysqli_real_escape_string($connection, $_POST['room_number']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    // Check if email already exists
    $check_sql = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connection, $check_sql);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $_SESSION['error_message'] = "Email already exists. Please use a different email.";
    } else {
        // Check which columns exist in the table
        $check_name_sql = "SHOW COLUMNS FROM users LIKE 'name'";
        $name_exists = mysqli_query($connection, $check_name_sql);
        
        if (mysqli_num_rows($name_exists) > 0) {
            // Table has name column
            $insert_sql = "INSERT INTO users (name, email, password, phone, room_number) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insert_sql);
            mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $password, $phone, $room_number);
        } else {
            // Table has fname/lname structure
            $check_fname_sql = "SHOW COLUMNS FROM users LIKE 'fname'";
            $fname_exists = mysqli_query($connection, $check_fname_sql);
            
            if (mysqli_num_rows($fname_exists) > 0) {
                // Split name into fname and lname
                $name_parts = explode(" ", $name, 2);
                $fname = $name_parts[0];
                $lname = isset($name_parts[1]) ? $name_parts[1] : '';
                
                $insert_sql = "INSERT INTO users (fname, lname, email, password, mobile) VALUES (?, ?, ?, ?, ?)";
                $stmt = mysqli_prepare($connection, $insert_sql);
                mysqli_stmt_bind_param($stmt, "sssss", $fname, $lname, $email, $password, $phone);
            } else {
                // Unknown structure, just try with available fields
                $insert_sql = "INSERT INTO users (email, password) VALUES (?, ?)";
                $stmt = mysqli_prepare($connection, $insert_sql);
                mysqli_stmt_bind_param($stmt, "ss", $email, $password);
            }
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding user: " . mysqli_error($connection);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: manage_users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];
    
    // Check if id or sno is the primary key
    $check_id_sql = "SHOW COLUMNS FROM users LIKE 'id'";
    $id_exists = mysqli_query($connection, $check_id_sql);
    
    if (mysqli_num_rows($id_exists) > 0) {
        $id_field = 'id';
    } else {
        $check_sno_sql = "SHOW COLUMNS FROM users LIKE 'sno'";
        $sno_exists = mysqli_query($connection, $check_sno_sql);
        
        if (mysqli_num_rows($sno_exists) > 0) {
            $id_field = 'sno';
        } else {
            $_SESSION['error_message'] = "Could not determine primary key for users table.";
            header("Location: manage_users.php");
            exit();
        }
    }
    
    // Check if trying to delete own account
    $check_sql = "SELECT email FROM users WHERE $id_field = ?";
    $stmt = mysqli_prepare($connection, $check_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $email);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);
    
    if ($email === $_SESSION['email']) {
        $_SESSION['error_message'] = "You cannot delete your own account.";
    } else {
        $delete_sql = "DELETE FROM users WHERE $id_field = ?";
        $stmt = mysqli_prepare($connection, $delete_sql);
        mysqli_stmt_bind_param($stmt, "i", $user_id);
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($connection);
        }
    }
    
    // Redirect to prevent resubmission
    header("Location: manage_users.php");
    exit();
}

// Get all users
$users_sql = "SELECT * FROM users";
$users_result = mysqli_query($connection, $users_sql);

// Check for errors in the query
if (!$users_result) {
    die("Error fetching users: " . mysqli_error($connection));
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Manage Users</h2>
            
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
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4>Add New User</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" required>
                                </div>
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" required>
                                </div>
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone">
                                </div>
                                <div class="mb-3">
                                    <label for="room_number" class="form-label">Room Number</label>
                                    <input type="text" class="form-control" id="room_number" name="room_number">
                                </div>
                                <div class="mb-3">
                                    <label for="password" class="form-label">Password</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <button type="submit" name="add_user" class="btn btn-primary">Add User</button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h4>User List</h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Room</th>
                                            <th>Registration Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (mysqli_num_rows($users_result) > 0): ?>
                                            <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                                <?php 
                                                // Determine user display name
                                                if (isset($user['name'])) {
                                                    $display_name = $user['name'];
                                                } elseif (isset($user['fname'])) {
                                                    $display_name = $user['fname'] . (isset($user['lname']) ? ' ' . $user['lname'] : '');
                                                } else {
                                                    $display_name = 'Unknown';
                                                }
                                                
                                                // Determine user ID
                                                if (isset($user['id'])) {
                                                    $user_id = $user['id'];
                                                } elseif (isset($user['sno'])) {
                                                    $user_id = $user['sno'];
                                                } else {
                                                    $user_id = 0;
                                                }
                                                
                                                // Determine phone/mobile
                                                if (isset($user['phone'])) {
                                                    $phone = $user['phone'];
                                                } elseif (isset($user['mobile'])) {
                                                    $phone = $user['mobile'];
                                                } else {
                                                    $phone = 'N/A';
                                                }
                                                
                                                // Determine room number
                                                $room = isset($user['room_number']) ? $user['room_number'] : 'N/A';
                                                
                                                // Determine registration date
                                                if (isset($user['registration_date'])) {
                                                    $reg_date = date('d M Y', strtotime($user['registration_date']));
                                                } else {
                                                    $reg_date = 'N/A';
                                                }
                                                ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($display_name); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($phone); ?></td>
                                                    <td><?php echo htmlspecialchars($room); ?></td>
                                                    <td><?php echo $reg_date; ?></td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <a href="edit_users.php?id=<?php echo $user_id; ?>" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i> Edit
                                                            </a>
                                                            <a href="manage_users.php?delete=<?php echo $user_id; ?>" 
                                                               class="btn btn-sm btn-danger"
                                                               onclick="return confirm('Are you sure you want to delete this user?');">
                                                                <i class="fas fa-trash"></i> Delete
                                                            </a>
                                                            <a href="view_user_bill.php?user_id=<?php echo $user_id; ?>" class="btn btn-sm btn-info">
                                                                <i class="fas fa-file-invoice-dollar"></i> Bills
                                                            </a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="6" class="text-center">No users found</td>
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

<?php 
include 'footer.php'; 

// End output buffering
ob_end_flush();
?> 