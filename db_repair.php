<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include 'header.php';
include '../includes/connection.php';

// Check if user is logged in as admin
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Initialize status messages
$success_messages = [];
$error_messages = [];

// Database repair functionality
if (isset($_POST['repair_db'])) {
    // 1. Fix admins table structure
    $check_admins_table = mysqli_query($connection, "SHOW TABLES LIKE 'admins'");
    if (mysqli_num_rows($check_admins_table) == 0) {
        // Create admins table if it doesn't exist
        $create_admins = "CREATE TABLE `admins` (
            `sno` int(11) NOT NULL AUTO_INCREMENT,
            `fname` varchar(100) NOT NULL,
            `lname` varchar(100) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `mobile` bigint(12) NOT NULL,
            PRIMARY KEY (`sno`),
            UNIQUE KEY `email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        
        if (mysqli_query($connection, $create_admins)) {
            $success_messages[] = "Admins table created successfully.";
        } else {
            $error_messages[] = "Error creating admins table: " . mysqli_error($connection);
        }
    } else {
        // Check if password field needs to be enlarged
        $check_password_field = mysqli_query($connection, "SHOW COLUMNS FROM admins LIKE 'password'");
        $password_field = mysqli_fetch_assoc($check_password_field);
        
        if ($password_field && strpos($password_field['Type'], 'varchar(100)') !== false) {
            $alter_admins = "ALTER TABLE admins MODIFY password VARCHAR(255) NOT NULL";
            if (mysqli_query($connection, $alter_admins)) {
                $success_messages[] = "Admins password field enlarged to support hashed passwords.";
            } else {
                $error_messages[] = "Error modifying admins password field: " . mysqli_error($connection);
            }
        } else {
            $success_messages[] = "Admins table structure is already correct.";
        }
    }
    
    // 2. Fix users table structure
    $check_users_table = mysqli_query($connection, "SHOW TABLES LIKE 'users'");
    if (mysqli_num_rows($check_users_table) > 0) {
        // Check if password field needs to be enlarged
        $check_password_field = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'password'");
        $password_field = mysqli_fetch_assoc($check_password_field);
        
        if ($password_field && strpos($password_field['Type'], 'varchar(100)') !== false) {
            $alter_users = "ALTER TABLE users MODIFY password VARCHAR(255) NOT NULL";
            if (mysqli_query($connection, $alter_users)) {
                $success_messages[] = "Users password field enlarged to support hashed passwords.";
            } else {
                $error_messages[] = "Error modifying users password field: " . mysqli_error($connection);
            }
        }
    }
    
    // 3. Update admin password with properly hashed version
    $admin_email = 'admin@gmail.com';
    $admin_password = 'admin@123';
    $hashed_password = password_hash($admin_password, PASSWORD_DEFAULT);
    
    // Check if admin exists
    $check_admin = mysqli_query($connection, "SELECT * FROM admins WHERE email = '$admin_email'");
    if (mysqli_num_rows($check_admin) > 0) {
        $update_admin = "UPDATE admins SET password = '$hashed_password' WHERE email = '$admin_email'";
        if (mysqli_query($connection, $update_admin)) {
            $success_messages[] = "Admin password updated with proper hashing.";
        } else {
            $error_messages[] = "Error updating admin password: " . mysqli_error($connection);
        }
    } else {
        $insert_admin = "INSERT INTO admins (fname, lname, email, password, mobile) 
                         VALUES ('Admin', 'Admin', '$admin_email', '$hashed_password', '9988776655')";
        if (mysqli_query($connection, $insert_admin)) {
            $success_messages[] = "Admin account created with proper password hashing.";
        } else {
            $error_messages[] = "Error creating admin account: " . mysqli_error($connection);
        }
    }
    
    // 4. Fix password hashing for all users
    $get_users = mysqli_query($connection, "SELECT sno, email, password FROM users");
    $users_fixed = 0;
    
    while ($user = mysqli_fetch_assoc($get_users)) {
        // Check if password is not hashed (less than 40 chars usually means not hashed)
        if (strlen($user['password']) < 40) {
            $plain_password = $user['password'];
            $hashed_password = password_hash($plain_password, PASSWORD_DEFAULT);
            
            $update_user = "UPDATE users SET password = '$hashed_password' WHERE sno = " . $user['sno'];
            if (mysqli_query($connection, $update_user)) {
                $users_fixed++;
            }
        }
    }
    
    if ($users_fixed > 0) {
        $success_messages[] = "$users_fixed user passwords updated with proper hashing.";
    }
    
    // 5. Restore index.php if it was modified
    if (file_exists('../index.php.bak')) {
        if (copy('../index.php.bak', '../index.php')) {
            $success_messages[] = "Restored original index.php from backup.";
            // Delete the backup file
            unlink('../index.php.bak');
        } else {
            $error_messages[] = "Failed to restore index.php from backup.";
        }
    }
    
    // 6. Remove emergency login file if it exists
    if (file_exists('../emergency_login.php')) {
        if (unlink('../emergency_login.php')) {
            $success_messages[] = "Removed emergency login file for security.";
        }
    }
}

// Get database tables info
$tables_query = mysqli_query($connection, "SHOW TABLES");
$tables = [];
while ($table = mysqli_fetch_array($tables_query)) {
    $tables[] = $table[0];
}

// Get admin users
$admin_users_query = mysqli_query($connection, "SELECT * FROM admins");
$admin_users = [];
while ($admin = mysqli_fetch_assoc($admin_users_query)) {
    $admin_users[] = $admin;
}
?>

<div class="container-fluid pt-4 px-4">
    <div class="row g-4">
        <div class="col-12">
            <div class="bg-light rounded h-100 p-4">
                <h4 class="mb-4"><i class="fas fa-database me-2"></i> Database Repair Utility</h4>
                
                <?php if (!empty($success_messages)): ?>
                    <div class="alert alert-success">
                        <ul class="mb-0">
                            <?php foreach($success_messages as $message): ?>
                                <li><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($error_messages)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach($error_messages as $message): ?>
                                <li><?php echo $message; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-tools me-2"></i> Database Repair Options</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <p>This utility will fix the following issues:</p>
                                    <ul>
                                        <li>Fix the admin table structure</li>
                                        <li>Enlarge password fields to properly store hashed passwords</li>
                                        <li>Update admin password with proper hashing</li>
                                        <li>Fix password hashing for all users</li>
                                        <li>Restore any modified login files</li>
                                        <li>Remove temporary emergency login files</li>
                                    </ul>
                                    <div class="mt-3">
                                        <button type="submit" name="repair_db" class="btn btn-primary">
                                            <i class="fas fa-wrench me-2"></i> Repair Database
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Database Information</h5>
                            </div>
                            <div class="card-body">
                                <h6>Database Tables</h6>
                                <ul>
                                    <?php foreach($tables as $table): ?>
                                        <li><?php echo $table; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                
                                <h6 class="mt-4">Admin Users</h6>
                                <div class="table-responsive">
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Password Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($admin_users)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No admin users found.</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach($admin_users as $admin): ?>
                                                <tr>
                                                    <td><?php echo $admin['sno']; ?></td>
                                                    <td><?php echo $admin['fname'] . ' ' . $admin['lname']; ?></td>
                                                    <td><?php echo $admin['email']; ?></td>
                                                    <td>
                                                        <?php if (strlen($admin['password']) > 40): ?>
                                                            <span class="badge bg-success">Hashed</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Not Hashed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info">
                    <i class="fas fa-lightbulb me-2"></i> <strong>Tip:</strong> After repairing the database, try logging in with:
                    <ul class="mb-0">
                        <li>Email: admin@gmail.com</li>
                        <li>Password: admin@123</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 