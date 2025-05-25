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

// Check if user ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error_message'] = "User ID is required";
    header("Location: manage_users.php");
    exit();
}

$user_id = (int) $_GET['id'];

// Determine primary key field (id or sno)
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

// Get user data
$user_sql = "SELECT * FROM users WHERE $id_field = ?";
$stmt = mysqli_prepare($connection, $user_sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "User not found";
    header("Location: manage_users.php");
    exit();
}

$user = mysqli_fetch_assoc($result);

// Determine user display name
if (isset($user['name'])) {
    $display_name = $user['name'];
} elseif (isset($user['fname'])) {
    $display_name = $user['fname'] . (isset($user['lname']) ? ' ' . $user['lname'] : '');
} else {
    $display_name = 'Unknown';
}

// Determine phone/mobile
if (isset($user['phone'])) {
    $phone = $user['phone'];
} elseif (isset($user['mobile'])) {
    $phone = $user['mobile'];
} else {
    $phone = '';
}

// Determine room number
$room = isset($user['room_number']) ? $user['room_number'] : '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $room_number = mysqli_real_escape_string($connection, $_POST['room_number']);
    
    // Check if email already exists for another user
    if ($email !== $user['email']) {
        $check_sql = "SELECT $id_field FROM users WHERE email = ? AND $id_field != ?";
        $stmt = mysqli_prepare($connection, $check_sql);
        mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $_SESSION['error_message'] = "Email already exists. Please use a different email.";
            header("Location: edit_users.php?id=$user_id");
            exit();
        }
    }
    
    // Check which structure the table has (name vs fname/lname)
    $check_name_sql = "SHOW COLUMNS FROM users LIKE 'name'";
    $name_exists = mysqli_query($connection, $check_name_sql);
    
    if (mysqli_num_rows($name_exists) > 0) {
        // Table has name column
        $update_sql = "UPDATE users SET name = ?, email = ?, phone = ?, room_number = ? WHERE $id_field = ?";
        $stmt = mysqli_prepare($connection, $update_sql);
        mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $phone, $room_number, $user_id);
    } else {
        // Check if table has fname/lname structure
        $check_fname_sql = "SHOW COLUMNS FROM users LIKE 'fname'";
        $fname_exists = mysqli_query($connection, $check_fname_sql);
        
        if (mysqli_num_rows($fname_exists) > 0) {
            // Split name into fname and lname
            $name_parts = explode(" ", $name, 2);
            $fname = $name_parts[0];
            $lname = isset($name_parts[1]) ? $name_parts[1] : '';
            
            // Check if mobile column exists instead of phone
            $check_mobile_sql = "SHOW COLUMNS FROM users LIKE 'mobile'";
            $mobile_exists = mysqli_query($connection, $check_mobile_sql);
            
            if (mysqli_num_rows($mobile_exists) > 0) {
                $update_sql = "UPDATE users SET fname = ?, lname = ?, email = ?, mobile = ? WHERE $id_field = ?";
                $stmt = mysqli_prepare($connection, $update_sql);
                mysqli_stmt_bind_param($stmt, "ssssi", $fname, $lname, $email, $phone, $user_id);
            } else {
                $update_sql = "UPDATE users SET fname = ?, lname = ?, email = ? WHERE $id_field = ?";
                $stmt = mysqli_prepare($connection, $update_sql);
                mysqli_stmt_bind_param($stmt, "sssi", $fname, $lname, $email, $user_id);
            }
        } else {
            // Just update email as a fallback
            $update_sql = "UPDATE users SET email = ? WHERE $id_field = ?";
            $stmt = mysqli_prepare($connection, $update_sql);
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
        }
    }
    
    if (mysqli_stmt_execute($stmt)) {
        // If updating the current admin user, update session details
        if ($email === $_SESSION['email']) {
            $_SESSION['name'] = $name;
        }
        
        $_SESSION['success_message'] = "User updated successfully!";
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating user: " . mysqli_error($connection);
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    
    $update_sql = "UPDATE users SET password = ? WHERE $id_field = ?";
    $stmt = mysqli_prepare($connection, $update_sql);
    mysqli_stmt_bind_param($stmt, "si", $new_password, $user_id);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Password changed successfully!";
        header("Location: manage_users.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Error changing password: " . mysqli_error($connection);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Edit User</h2>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Edit User Information</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($display_name); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="phone" class="form-label">Phone</label>
                                    <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="room_number" class="form-label">Room Number</label>
                                    <input type="text" class="form-control" id="room_number" name="room_number" value="<?php echo htmlspecialchars($room); ?>">
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" name="update_user" class="btn btn-primary">Update User</button>
                            <a href="manage_users.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h4>Change Password</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="" onsubmit="return validatePasswords()">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <button type="submit" name="change_password" class="btn btn-warning">Change Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function validatePasswords() {
    const newPassword = document.getElementById('new_password').value;
    const confirmPassword = document.getElementById('confirm_password').value;
    
    if (newPassword !== confirmPassword) {
        alert('Passwords do not match');
        return false;
    }
    
    return true;
}
</script>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?>
