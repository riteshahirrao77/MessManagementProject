<?php
// Include the error handler to check MySQL connectivity first
require_once 'error_handler.php';

// Start output buffering to prevent "headers already sent" errors
ob_start();

session_start();
  if(isset($_POST['login'])){
    include('includes/connection.php');
    
    // Sanitize inputs
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM users WHERE email = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
            $_SESSION['email'] = $user['email'];
            $_SESSION['fname'] = $user['fname'];
            $_SESSION['lname'] = $user['lname'];
            $_SESSION['uid'] = $user['sno'];
            
            echo "<script type='text/javascript'>
                window.location.href = 'user_dashboard.php';
            </script>";
        } else {
            echo "<script type='text/javascript'>
                alert('Invalid password.');
                window.location.href = 'index.php';
            </script>";
        }
    } else {
        echo "<script type='text/javascript'>
            alert('User not found.');
            window.location.href = 'index.php';
        </script>";
    }
    mysqli_stmt_close($stmt);
  }

  if(isset($_POST['register'])){
    include('includes/connection.php');
    
    // Sanitize inputs
    $fname = mysqli_real_escape_string($connection, $_POST['fname']);
    $lname = mysqli_real_escape_string($connection, $_POST['lname']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $mobile = mysqli_real_escape_string($connection, $_POST['mobile']);
    $address = mysqli_real_escape_string($connection, $_POST['address']);
    $fee_amount = 1000.00; // Default fee amount for new users
    
    // Check if email already exists
    $check_query = "SELECT email FROM users WHERE email = ?";
    $check_stmt = mysqli_prepare($connection, $check_query);
    mysqli_stmt_bind_param($check_stmt, "s", $email);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) > 0){
        echo "<script type='text/javascript'>
            alert('Email already exists.');
            window.location.href = 'index.php';
        </script>";
        exit();
    }
    
    // Check if fee_amount column exists in users table
    $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_amount'");
    if(mysqli_num_rows($check_column) == 0) {
      // If fee_amount column doesn't exist, add it
      mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 1000.00");
    }
    
    $query = "INSERT INTO users (fname, lname, email, password, mobile, address, fee_status, fee_amount) VALUES (?, ?, ?, ?, ?, ?, 0, ?)";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "ssssssd", $fname, $lname, $email, $password, $mobile, $address, $fee_amount);
    
    if(mysqli_stmt_execute($stmt)){
        echo "<script type='text/javascript'>
            alert('Registration successful.');
            window.location.href = 'index.php';
        </script>";
    } else {
        echo "<script type='text/javascript'>
            alert('Registration failed. Please try again.');
            window.location.href = 'index.php';
        </script>";
    }
    mysqli_stmt_close($stmt);
    mysqli_stmt_close($check_stmt);
  }

	if(isset($_POST['admin_login'])){
    include('includes/connection.php');
    
    // Sanitize inputs
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = $_POST['password'];
    
    $query = "SELECT * FROM admins WHERE email = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if(mysqli_num_rows($result) > 0){
        $admin = mysqli_fetch_assoc($result);
        if(password_verify($password, $admin['password'])){
            $_SESSION['email'] = $admin['email'];
            $_SESSION['fname'] = $admin['fname'];
            $_SESSION['lname'] = $admin['lname'];
            
            echo "<script type='text/javascript'>
                window.location.href = 'Admin pannel/admin_dashboard.php';
            </script>";
        } else {
            echo "<script type='text/javascript'>
                alert('Invalid password.');
                window.location.href = 'index.php';
            </script>";
        }
    } else {
        echo "<script type='text/javascript'>
            alert('Admin not found.');
            window.location.href = 'index.php';
        </script>";
    }
    mysqli_stmt_close($stmt);
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mess Management System</title>
    <!-- Custom CSS Framework -->
    <link rel="stylesheet" href="assets/css/custom.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script src="assets/js/custom.js"></script>
    <!-- CSS Files -->
    <link rel="stylesheet" href="css/styles.css">
    <style>
        body {
            background-image: url('images/food_background.jpg');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            height: 100vh;
        }
        .overlay {
            background-color: rgba(0, 0, 0, 0.7);
            height: 100%;
            width: 100%;
            position: fixed;
            top: 0;
            left: 0;
            z-index: -1;
        }
        .navbar {
            background-color: rgba(25, 67, 80, 0.9) !important;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        .navbar-brand h3 {
            margin-bottom: 0;
            font-weight: 600;
            color: white;
        }
        .ml-auto {
            display: flex;
            align-items: center;
        }
        .nav-links {
            display: flex;
        }
        .nav-links .nav-link {
            color: white;
            padding: 0.5rem 1rem;
            margin: 0 5px;
            font-weight: 500;
            transition: all 0.3s;
            border-radius: 4px;
        }
        .nav-links .nav-link:hover {
            background-color: rgba(255, 255, 255, 0.1);
            transform: translateY(-3px);
        }
        .hero-section {
            color: white;
            text-align: center;
            padding: 100px 0;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 20px;
            text-shadow: 2px 2px 5px rgba(0,0,0,0.5);
        }
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 40px;
            opacity: 0.9;
        }
        .modal-content {
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            border: none;
        }
        .modal-header {
            background-color: #194350;
            color: white;
            border-radius: 15px 15px 0 0;
            border-bottom: none;
        }
        .modal-title {
            font-weight: 600;
        }
        .modal-body {
            padding: 25px;
        }
        .form-group label {
            font-weight: 500;
            color: #194350;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px 15px;
            border: 1px solid #ddd;
            margin-bottom: 15px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 3px rgba(25, 67, 80, 0.2);
            border-color: #194350;
        }
        .btn-submit {
            background-color: #194350;
            color: white;
            border-radius: 5px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            width: 100%;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background-color: #0d2b36;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-reset {
            background-color: #dc3545;
            color: white;
            border-radius: 5px;
            padding: 10px 20px;
            font-weight: 500;
            transition: all 0.3s;
            border: none;
            width: 100%;
            margin-top: 10px;
        }
        .btn-reset:hover {
            background-color: #c82333;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .modal-footer {
            border-top: none;
        }
    </style>
    <script type="text/javascript">
        function resetData(){
            document.getElementById('fname').value = "";
            document.getElementById('lname').value = "";
            document.getElementById('email').value = "";
            document.getElementById('password').value = "";
            document.getElementById('mobile').value = "";
            document.getElementById('address').value = "";
        }
    </script>
</head>
<body>
    <div class="overlay"></div>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <h3><i class="fas fa-utensils mr-2"></i> Mess Management System</h3>
            </a>
            <div class="ml-auto nav-links">
                <a href="#" class="nav-link" data-toggle="modal" data-target="#login_modal">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </a>
                <a href="#" class="nav-link" data-toggle="modal" data-target="#register_modal">
                    <i class="fas fa-user-plus mr-2"></i> Register
                </a>
                <a href="#" class="nav-link" data-toggle="modal" data-target="#admin_login_modal">
                    <i class="fas fa-user-shield mr-2"></i> Admin Login
                </a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="container hero-section">
        <h1 class="hero-title">Welcome to Mess Management System</h1>
        <p class="hero-subtitle">Efficient, Transparent, and Delicious Meals Every Day</p>
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card bg-dark text-white" style="opacity: 0.9; border-radius: 15px;">
                    <div class="card-body">
                        <h4><i class="fas fa-info-circle mr-2"></i> About Our System</h4>
                        <p>Our Mess Management System provides a comprehensive solution for managing mess operations, including attendance tracking, menu planning, feedback collection, and fee management. Join us for a seamless dining experience!</p>
                        <div class="row text-center mt-4">
                            <div class="col-md-4">
                                <i class="fas fa-utensils fa-3x mb-3"></i>
                                <h5>Delicious Meals</h5>
                                <p>Enjoy nutritious and tasty meals prepared by our expert chefs.</p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-clipboard-check fa-3x mb-3"></i>
                                <h5>Attendance Tracking</h5>
                                <p>Efficient tracking of meal attendance for better planning.</p>
                            </div>
                            <div class="col-md-4">
                                <i class="fas fa-comment-dots fa-3x mb-3"></i>
                                <h5>Feedback System</h5>
                                <p>Share your thoughts to help us improve our services.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- LOGIN MODAL -->
    <div class="modal fade" id="login_modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-sign-in-alt mr-2"></i> Login</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope mr-2"></i> Email:</label>
                            <input class="form-control" type="email" name="email" placeholder="Your Email" required>
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock mr-2"></i> Password:</label>
                            <input class="form-control" type="password" name="password" placeholder="Your Password" required>
                        </div>
                        <button class="btn btn-submit" type="submit" name="login">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login
                        </button>
                    </form>
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal  -->
    <div class="modal fade" id="register_modal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-user-plus mr-2"></i> Registration Form</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="modal-body">
                    <form action="" method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="fname"><i class="fas fa-user mr-2"></i> First Name:</label>
                                    <input type="text" class="form-control" name="fname" placeholder="Enter First Name" id="fname" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="lname"><i class="fas fa-user mr-2"></i> Last Name:</label>
                                    <input type="text" class="form-control" name="lname" placeholder="Enter Last Name" id="lname" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="email"><i class="fas fa-envelope mr-2"></i> Email ID:</label>
                                    <input type="email" class="form-control" name="email" placeholder="Enter email ID" id="email" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="password"><i class="fas fa-lock mr-2"></i> Password:</label>
                                    <input type="password" class="form-control" name="password" placeholder="Enter Password" id="password" required>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="mobile"><i class="fas fa-mobile-alt mr-2"></i> Mobile No:</label>
                            <input type="number" class="form-control" name="mobile" placeholder="Enter Mobile No" id="mobile" required>
                        </div>
                        <div class="form-group">
                            <label for="address"><i class="fas fa-map-marker-alt mr-2"></i> Address:</label>
                            <textarea name="address" class="form-control" rows="3" placeholder="Enter Address Here..." id="address" required></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <button type="submit" class="btn btn-submit" name="register">
                                    <i class="fas fa-user-plus mr-2"></i> Register
                                </button>
                            </div>
                            <div class="col-md-6">
                                <button type="button" class="btn btn-reset" name="reset" onclick="resetData()">
                                    <i class="fas fa-redo mr-2"></i> Reset
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Admin LOGIN MODAL -->
    <div class="modal fade" id="admin_login_modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <!-- Modal Header -->
                <div class="modal-header">
                    <h4 class="modal-title"><i class="fas fa-user-shield mr-2"></i> Admin Login</h4>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <!-- Modal body -->
                <div class="modal-body">
                    <form action="" method="post">
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope mr-2"></i> Email:</label>
                            <input class="form-control" type="email" name="email" placeholder="Admin Email" required>
                        </div>
                        <div class="form-group">
                            <label for="password"><i class="fas fa-lock mr-2"></i> Password:</label>
                            <input class="form-control" type="password" name="password" placeholder="Admin Password" required>
                        </div>
                        <button class="btn btn-submit" type="submit" name="admin_login">
                            <i class="fas fa-sign-in-alt mr-2"></i> Login as Admin
                        </button>
                    </form>
                </div>
                <!-- Modal footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

// End output buffering
ob_end_flush();
?>
