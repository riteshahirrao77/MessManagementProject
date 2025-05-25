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

// Get admin details
$email = $_SESSION['email'];
$sql = "SELECT * FROM users WHERE email = ? AND user_type = 'admin'";
$stmt = mysqli_prepare($connection, $sql);
mysqli_stmt_bind_param($stmt, "s", $email);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$admin = mysqli_fetch_assoc($result);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    
    $update_sql = "UPDATE users SET name = ?, phone = ? WHERE email = ?";
    $stmt = mysqli_prepare($connection, $update_sql);
    mysqli_stmt_bind_param($stmt, "sss", $name, $phone, $email);
    
    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['success_message'] = "Profile updated successfully!";
        // Update session name
        $_SESSION['name'] = $name;
        // Redirect to prevent form resubmission
        echo "<script>window.location.href = 'profile.php';</script>";
        exit();
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . mysqli_error($connection);
    }
}
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Admin Profile</h2>
            
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
                            <h4>Profile Information</h4>
                        </div>
                        <div class="card-body">
                            <?php if ($admin): ?>
                                <form method="POST" action="">
                                    <div class="text-center mb-4">
                                        <div style="width: 100px; height: 100px; background-color: #194350; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 2.5rem;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <h4 class="mt-3"><?php echo htmlspecialchars($admin['name']); ?></h4>
                                        <p class="text-muted">Administrator</p>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name</label>
                                        <input type="text" name="name" id="name" class="form-control" value="<?php echo htmlspecialchars($admin['name']); ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" name="email" id="email" class="form-control" value="<?php echo htmlspecialchars($admin['email']); ?>" readonly>
                                        <small class="text-muted">Email cannot be changed</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="text" name="phone" id="phone" class="form-control" value="<?php echo htmlspecialchars($admin['phone'] ?: ''); ?>">
                                    </div>
                                    
                                    <div class="d-grid gap-2">
                                        <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                        <a href="change_password.php" class="btn btn-secondary">Change Password</a>
                                    </div>
                                </form>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    Admin profile not found. Please contact the system administrator.
                                </div>
                            <?php endif; ?>
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