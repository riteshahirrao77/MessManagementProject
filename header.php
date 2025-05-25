<?php
session_start();
if (!isset($_SESSION['email']) || !isset($_SESSION['fname']) || !isset($_SESSION['lname'])) {
    header('Location: index.php');
    exit();
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
	<!-- Custom CSS -->
	<link rel="stylesheet" href="css/styles.css">
	<style>
		.navbar {
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
		}
		.navbar-brand h3 {
			margin-bottom: 0;
		}
		.user-info {
			display: flex;
			align-items: center;
			color: white;
			margin-right: 15px;
		}
		.user-avatar {
			background-color: #fff;
			color: #194350;
			width: 35px;
			height: 35px;
			border-radius: 50%;
			display: flex;
			align-items: center;
			justify-content: center;
			margin-right: 10px;
			font-weight: bold;
		}
		.logout-btn {
			border-radius: 20px;
			padding: 8px 20px;
			font-weight: 500;
			transition: all 0.3s;
		}
		.logout-btn:hover {
			transform: translateY(-2px);
			box-shadow: 0 4px 8px rgba(0,0,0,0.1);
		}
	</style>
</head>
<body>
	<nav class="navbar navbar-expand-lg" style="background-color:#194350;">
		<div class="container-fluid">
			<a class="navbar-brand" href="user_dashboard.php">
				<h3 style="color:white;"><i class="fas fa-utensils mr-2"></i> Mess Management System</h3>
			</a>
			<div class="ml-auto d-flex align-items-center">
				<div class="user-info">
					<div class="user-avatar">
						<i class="fas fa-user"></i>
					</div>
					<div>
						<span><?php echo htmlspecialchars($_SESSION['fname'] . " " . $_SESSION['lname']); ?></span>
						<small class="d-block">User</small>
					</div>
				</div>
				<a href="logout.php" class="btn btn-warning logout-btn">
					<i class="fas fa-sign-out-alt mr-2"></i> Logout
				</a>
			</div>
		</div>
	</nav>
</body>
</html>
