<?php
// Enable output buffering to prevent "headers already sent" errors
ob_start();

// Start session
session_start();

// Include connection file
include '../includes/connection.php';

// Check if admin is logged in
if (!isset($_SESSION['email'])) {
    header("Location: ../index.php");
    exit();
}

// Handle user addition
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
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
        // Determine if we're using 'name' or 'fname/lname' structure
        $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'name'");
        if (mysqli_num_rows($check_column) > 0) {
            // Using name column
            $insert_sql = "INSERT INTO users (name, email, password, phone) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insert_sql);
            mysqli_stmt_bind_param($stmt, "ssss", $name, $email, $password, $phone);
        } else {
            // Using fname/lname structure
            $name_parts = explode(" ", $name, 2);
            $fname = $name_parts[0];
            $lname = isset($name_parts[1]) ? $name_parts[1] : '';
            
            $insert_sql = "INSERT INTO users (fname, lname, email, password, mobile) VALUES (?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($connection, $insert_sql);
            mysqli_stmt_bind_param($stmt, "sssss", $fname, $lname, $email, $password, $phone);
        }
        
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "User added successfully!";
        } else {
            $_SESSION['error_message'] = "Error adding user: " . mysqli_error($connection);
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: direct_manage_users.php");
    exit();
}

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];
    
    // Determine primary key field (id or sno)
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'id'");
    $id_field = mysqli_num_rows($check_column) > 0 ? 'id' : 'sno';
    
    $delete_sql = "DELETE FROM users WHERE $id_field = ?";
    $stmt = mysqli_prepare($connection, $delete_sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "User deleted successfully!";
    } else {
        $_SESSION['error_message'] = "Error deleting user: " . mysqli_error($connection);
    }
    
    // Redirect to prevent resubmission
    header("Location: direct_manage_users.php");
    exit();
}

// Get all users
$users_sql = "SELECT * FROM users";
$users_result = mysqli_query($connection, $users_sql);

// Check for errors in the query
if (!$users_result) {
    die("Error fetching users: " . mysqli_error($connection));
}

// Function to get user display name
function getUserName($user) {
    if (isset($user['name'])) {
        return $user['name'];
    } elseif (isset($user['fname'])) {
        return $user['fname'] . (isset($user['lname']) ? ' ' . $user['lname'] : '');
    } else {
        return 'Unknown';
    }
}

// Function to get user ID
function getUserId($user) {
    if (isset($user['id'])) {
        return $user['id'];
    } elseif (isset($user['sno'])) {
        return $user['sno'];
    } else {
        return 0;
    }
}

// Function to get user phone
function getUserPhone($user) {
    if (isset($user['phone'])) {
        return $user['phone'];
    } elseif (isset($user['mobile'])) {
        return $user['mobile'];
    } else {
        return 'N/A';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Mess Management System</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        .navbar {
            background-color: #194350;
        }
        .navbar-brand, .nav-link {
            color: white;
        }
        .container {
            margin-top: 30px;
        }
        .card-header {
            background-color: #194350;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-utensils"></i> Mess Management System
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php"><i class="fas fa-home"></i> Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="direct_manage_users.php"><i class="fas fa-users"></i> Users</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_menu.php"><i class="fas fa-utensils"></i> Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_attendance.php"><i class="fas fa-clipboard-check"></i> Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="manage_fees.php"><i class="fas fa-money-bill-wave"></i> Fees</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> Admin
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-id-card"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row mb-4">
            <div class="col">
                <h2 class="text-center"><i class="fas fa-users"></i> Manage Users</h2>
            </div>
        </div>
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php 
                echo $_SESSION['success_message'];
                unset($_SESSION['success_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php 
                echo $_SESSION['error_message'];
                unset($_SESSION['error_message']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4><i class="fas fa-user-plus"></i> Add New User</h4>
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
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" name="add_user" class="btn btn-primary">
                                <i class="fas fa-plus-circle"></i> Add User
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-info-circle"></i> Information</h4>
                    </div>
                    <div class="card-body">
                        <p>This page allows you to manage user accounts in the system.</p>
                        <p>If you're experiencing errors with the regular manage_users.php page, this is a temporary alternative that resolves header issues.</p>
                        <a href="manage_users.php" class="btn btn-secondary">
                            <i class="fas fa-undo"></i> Back to Regular Page
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="fas fa-list"></i> User List</h4>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Phone</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (mysqli_num_rows($users_result) > 0): ?>
                                        <?php while($user = mysqli_fetch_assoc($users_result)): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars(getUserName($user)); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo htmlspecialchars(getUserPhone($user)); ?></td>
                                                <td>
                                                    <a href="direct_manage_users.php?delete=<?php echo getUserId($user); ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center">No users found</td>
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
    
    <!-- Bootstrap JS and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
// End output buffering
ob_end_flush();
?> 