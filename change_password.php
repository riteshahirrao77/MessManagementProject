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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $email = $_SESSION['email'];
    
    // Verify passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['error_message'] = "New passwords do not match!";
    } else {
        // Get current password from database
        $sql = "SELECT password FROM users WHERE email = ? AND user_type = 'admin'";
        $stmt = mysqli_prepare($connection, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        
        // Verify current password
        if (password_verify($current_password, $user['password'])) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $update_sql = "UPDATE users SET password = ? WHERE email = ?";
            $stmt = mysqli_prepare($connection, $update_sql);
            mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Password changed successfully!";
                // Redirect to prevent form resubmission
                echo "<script>window.location.href = 'profile.php';</script>";
                exit();
            } else {
                $_SESSION['error_message'] = "Error changing password: " . mysqli_error($connection);
            }
        } else {
            $_SESSION['error_message'] = "Current password is incorrect!";
        }
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Change Password</h2>
            
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
                <div class="col-md-6 mx-auto">
                    <div class="card">
                        <div class="card-header">
                            <h4>Change Your Password</h4>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" class="form-control" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="form-control" required>
                                    <small class="text-muted">Password should be at least 8 characters long</small>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="form-control" required>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                    <a href="profile.php" class="btn btn-secondary">Back to Profile</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'footer.php';

// End output buffering
ob_end_flush(); ?> 