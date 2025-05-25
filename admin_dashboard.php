<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

  include('header.php');
  if(isset($_SESSION['email'])){
  include('../includes/connection.php');
  if(isset($_POST['submit_attendance'])){
    $id = mysqli_real_escape_string($connection, $_POST['id']);
    $attendance = mysqli_real_escape_string($connection, $_POST['attendance']);
    $food_type = mysqli_real_escape_string($connection, $_POST['food_type']);
    $date = date('Y-m-d'); // Use proper date format YYYY-MM-DD
    
    // Check if user exists
    $check_user = "SELECT * FROM users WHERE sno = ?";
    $check_stmt = mysqli_prepare($connection, $check_user);
    mysqli_stmt_bind_param($check_stmt, "i", $id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);
    
    if(mysqli_num_rows($check_result) == 0) {
      echo "<script type='text/javascript'>
        alert('User ID not found. Please enter a valid ID.');
        window.location.href = 'admin_dashboard.php';
      </script>";
      exit();
    }
    
    // Check if attendance already marked for today
    $table_name = "";
    if($food_type == 'Breakfast') {
      $table_name = "attendance1";
    } elseif($food_type == 'Lunch') {
      $table_name = "attendance2";
    } elseif($food_type == 'Snacks') {
      $table_name = "attendance3";
    } else {
      $table_name = "attendance4";
    }
    
    $check_attendance = "SELECT * FROM $table_name WHERE id = ? AND date = ?";
    $check_att_stmt = mysqli_prepare($connection, $check_attendance);
    mysqli_stmt_bind_param($check_att_stmt, "is", $id, $date);
    mysqli_stmt_execute($check_att_stmt);
    $check_att_result = mysqli_stmt_get_result($check_att_stmt);
    
    if(mysqli_num_rows($check_att_result) > 0) {
      // Update existing attendance
      $update_query = "UPDATE $table_name SET attendance = ? WHERE id = ? AND date = ?";
      $update_stmt = mysqli_prepare($connection, $update_query);
      mysqli_stmt_bind_param($update_stmt, "sis", $attendance, $id, $date);
      
      if(mysqli_stmt_execute($update_stmt)){
        // Update to new attendance table as well for consistency
        if($attendance == 'Present') {
          // Add to unified attendance table
          $add_query = "INSERT IGNORE INTO attendance (user_id, meal_type, attendance_date) VALUES (?, ?, ?)";
          $add_stmt = mysqli_prepare($connection, $add_query);
          mysqli_stmt_bind_param($add_stmt, "iss", $id, $food_type, $date);
          mysqli_stmt_execute($add_stmt);
          mysqli_stmt_close($add_stmt);
        } else {
          // Remove from unified attendance table
          $del_query = "DELETE FROM attendance WHERE user_id = ? AND meal_type = ? AND attendance_date = ?";
          $del_stmt = mysqli_prepare($connection, $del_query);
          mysqli_stmt_bind_param($del_stmt, "iss", $id, $food_type, $date);
          mysqli_stmt_execute($del_stmt);
          mysqli_stmt_close($del_stmt);
        }
        
        echo "<script type='text/javascript'>
          alert('Attendance updated successfully...');
          window.location.href = 'admin_dashboard.php';
        </script>";
      } else {
        echo "<script type='text/javascript'>
          alert('Failed to update attendance. Please try again.');
          window.location.href = 'admin_dashboard.php';
        </script>";
      }
      mysqli_stmt_close($update_stmt);
    } else {
      // Insert new attendance
      if($food_type == 'Breakfast'){
        $query = "INSERT INTO attendance1(id, attendance, date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $id, $attendance, $date);
      }
      elseif($food_type == 'Lunch'){
        $query = "INSERT INTO attendance2(id, attendance, date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $id, $attendance, $date);
      }
      elseif($food_type == 'Snacks'){
        $query = "INSERT INTO attendance3(id, attendance, date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $id, $attendance, $date);
      }
      else{
        $query = "INSERT INTO attendance4(id, attendance, date) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($connection, $query);
        mysqli_stmt_bind_param($stmt, "iss", $id, $attendance, $date);
      }
      
      if(mysqli_stmt_execute($stmt)){
        // Also add to the unified attendance table for consistency
        if($attendance == 'Present') {
          $add_query = "INSERT IGNORE INTO attendance (user_id, meal_type, attendance_date) VALUES (?, ?, ?)";
          $add_stmt = mysqli_prepare($connection, $add_query);
          mysqli_stmt_bind_param($add_stmt, "iss", $id, $food_type, $date);
          mysqli_stmt_execute($add_stmt);
          mysqli_stmt_close($add_stmt);
        }
        
        echo "<script type='text/javascript'>
          alert('Attendance submitted successfully...');
          window.location.href = 'admin_dashboard.php';
        </script>";
      }
      else{
        echo "<script type='text/javascript'>
          alert('Failed...Plz try again.');
          window.location.href = 'admin_dashboard.php';
        </script>";
      }
      mysqli_stmt_close($stmt);
    }
    
    mysqli_stmt_close($check_att_stmt);
    mysqli_stmt_close($check_stmt);
  }
  // Find total no of users
  $query = "select * from users";
  $query_run = mysqli_query($connection,$query);
  $total_users = mysqli_num_rows($query_run);

  // Breakfast Attendance percentage
  $date = date('Y-m-d'); // Use proper date format YYYY-MM-DD
  $query = "select * from attendance1 where attendance = 'Present' and date = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "s", $date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $breakfast_present = mysqli_num_rows($result);
  $breakfast_percentage = ($total_users > 0) ? round(($breakfast_present / $total_users) * 100) : 0;
  mysqli_stmt_close($stmt);

  // Lunch Attendance percentage
  $query = "select * from attendance2 where attendance = 'Present' and date = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "s", $date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $lunch_present = mysqli_num_rows($result);
  $lunch_percentage = ($total_users > 0) ? round(($lunch_present / $total_users) * 100) : 0;
  mysqli_stmt_close($stmt);

  // Snacks Attendance percentage
  $query = "select * from attendance3 where attendance = 'Present' and date = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "s", $date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $snacks_present = mysqli_num_rows($result);
  $snacks_percentage = ($total_users > 0) ? round(($snacks_present / $total_users) * 100) : 0;
  mysqli_stmt_close($stmt);

  // Dinner Attendance percentage
  $query = "select * from attendance4 where attendance = 'Present' and date = ?";
  $stmt = mysqli_prepare($connection, $query);
  mysqli_stmt_bind_param($stmt, "s", $date);
  mysqli_stmt_execute($stmt);
  $result = mysqli_stmt_get_result($stmt);
  $dinner_present = mysqli_num_rows($result);
  $dinner_percentage = ($total_users > 0) ? round(($dinner_present / $total_users) * 100) : 0;
  mysqli_stmt_close($stmt);

  // Calculate total attendance information
  $total_present = $breakfast_present + $lunch_present + $snacks_present + $dinner_present;
  $total_possible = $total_users * 4; // 4 meals per day
  $overall_percentage = ($total_possible > 0) ? round(($total_present / $total_possible) * 100) : 0;

  // Find total feedbacks
  $query = "select * from feedback";
  $query_run = mysqli_query($connection,$query);
  $total_feedback = mysqli_num_rows($query_run);

  // Poor Feedback percentage
  $query = "select * from feedback where rating = 'Poor'";
  $query_run = mysqli_query($connection,$query);
  $poor_feedback = mysqli_num_rows($query_run);
  $poor_feedback_percentage = ($total_feedback > 0) ? round(($poor_feedback / $total_feedback) * 100, 2) : 0;

  // Good Feedback percentage
  $query = "select * from feedback where rating = 'Good'";
  $query_run = mysqli_query($connection,$query);
  $good_feedback = mysqli_num_rows($query_run);
  $good_feedback_percentage = ($total_feedback > 0) ? round(($good_feedback / $total_feedback) * 100, 2) : 0;

  // Excellent Feedback percentage
  $query = "select * from feedback where rating = 'Excellent'";
  $query_run = mysqli_query($connection,$query);
  $Excellent_feedback = mysqli_num_rows($query_run);
  $Excellent_feedback_percentage = ($total_feedback > 0) ? round(($Excellent_feedback / $total_feedback) * 100, 2) : 0;

  // Check if fee_amount column exists in users table
  $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_amount'");
  if(mysqli_num_rows($check_column) == 0) {
    // Create fee_amount column with default 0
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 0.00");
  } else {
    // Update any existing default 1000 fees to 0
    mysqli_query($connection, "UPDATE users SET fee_amount = 0.00 WHERE fee_amount = 1000.00");
  }

  // Fee status percentage (only for users who have pending fees)
  $query = "select * from users where fee_status = 0 AND fee_amount > 0";
  $query_run = mysqli_query($connection,$query);
  $fee_status = mysqli_num_rows($query_run);
  
  // Get total users with pending fees
  $query = "select COUNT(*) as total from users where fee_amount > 0";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);
  $total_users_with_fees = $row['total'];
  
  $fee_status_percentage = ($total_users_with_fees > 0) ? round(($fee_status / $total_users_with_fees) * 100, 2) : 0;
  
  // Calculate total fees
  $query = "SELECT SUM(fee_amount) as total_fees FROM users WHERE fee_status = 0";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);
  $total_fees_due = $row['total_fees'] ? $row['total_fees'] : 0;
  
  // Calculate collected fees
  $query = "SELECT SUM(fee_amount) as total_collected FROM users WHERE fee_status = 1";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);
  $total_fees_collected = $row['total_collected'] ? $row['total_collected'] : 0;
  
  // Calculate overall total fees
  $query = "SELECT SUM(fee_amount) as overall_total FROM users";
  $result = mysqli_query($connection, $query);
  $row = mysqli_fetch_assoc($result);
  $overall_total_fees = $row['overall_total'] ? $row['overall_total'] : 0;

  if(isset($_POST['pay_fee'])){
    $id = mysqli_real_escape_string($connection, $_POST['id']);
    $query = "UPDATE users SET fee_status = 1 WHERE sno = ?";
    $stmt = mysqli_prepare($connection, $query);
    mysqli_stmt_bind_param($stmt, "i", $id);
    
    if(mysqli_stmt_execute($stmt)){
      echo "<script type='text/javascript'>
        alert('Fee status updated successfully...');
        window.location.href = 'admin_dashboard.php';
      </script>";
    }
    else{
      echo "<script type='text/javascript'>
        alert('Failed...Plz try again.');
        window.location.href = 'admin_dashboard.php';
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
    <title>Admin Dashboard</title>
    <!-- Custom CSS -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background-color: #194350;
            color: white;
            border-radius: 10px 10px 0 0;
            font-weight: bold;
            padding: 12px 15px;
        }
        .btn {
            border-radius: 5px;
            font-weight: 500;
            padding: 8px 16px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .action-div {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            padding: 20px;
            min-height: 400px;
        }
        .progress {
            height: 25px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .progress-bar {
            font-weight: bold;
            line-height: 25px;
        }
        .dashboard-container {
            padding: 20px;
        }
        .form-control {
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 15px;
        }
        .form-group label {
            font-weight: 500;
            margin-bottom: 5px;
        }
        h3 {
            color: #194350;
            font-weight: 600;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid dashboard-container">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-user me-2"></i> Total Users
                    </div>
                    <div class="card-body text-center">
                        <h2><?php echo $total_users; ?></h2>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-boxes me-2"></i> Stock Items
                    </div>
                    <div class="card-body text-center">
                        <?php
                        $query = "SELECT COUNT(*) as count FROM mess_stock";
                        $result = mysqli_query($connection, $query);
                        $row = mysqli_fetch_assoc($result);
                        $stock_count = $row['count'];
                        ?>
                        <h2><?php echo $stock_count; ?></h2>
                        <a href="manage_stock.php" class="btn btn-sm btn-primary">Manage Stock</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-check me-2"></i> Today's Attendance
                    </div>
                    <div class="card-body text-center">
                        <?php
                        // Check if there's any attendance data for today
                        if ($total_users > 0) {
                            echo "<h2>{$overall_percentage}%</h2>";
                            echo "<p class='text-muted mb-2'>{$total_present} present out of {$total_possible} possible</p>";
                        } else {
                            echo "<h2>0%</h2>";
                            echo "<p class='text-muted mb-2'>No attendance data</p>";
                        }
                        ?>
                        <a href="attendance_reports.php" class="btn btn-sm btn-primary">View Reports</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-money-bill-wave me-2"></i> Total Fees Due
                    </div>
                    <div class="card-body text-center">
                        <h2>₹<?php echo number_format($total_fees_due, 2); ?></h2>
                        <a href="view_fee_status.php" class="btn btn-sm btn-primary">View Details</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Attendance Statistics -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-chart-bar me-2"></i> Today's Meal Attendance
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Breakfast Attendance <span class="badge bg-primary"><?php echo $breakfast_present; ?>/<?php echo $total_users; ?></span></h5>
                                <div class="progress">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $breakfast_percentage; ?>%" aria-valuenow="<?php echo $breakfast_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $breakfast_percentage; ?>%
                                    </div>
                                </div>
                                
                                <h5>Lunch Attendance <span class="badge bg-primary"><?php echo $lunch_present; ?>/<?php echo $total_users; ?></span></h5>
                                <div class="progress">
                                    <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo $lunch_percentage; ?>%" aria-valuenow="<?php echo $lunch_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $lunch_percentage; ?>%
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <h5>Snacks Attendance <span class="badge bg-primary"><?php echo $snacks_present; ?>/<?php echo $total_users; ?></span></h5>
                                <div class="progress">
                                    <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $snacks_percentage; ?>%" aria-valuenow="<?php echo $snacks_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $snacks_percentage; ?>%
                                    </div>
                                </div>
                                
                                <h5>Dinner Attendance <span class="badge bg-primary"><?php echo $dinner_present; ?>/<?php echo $total_users; ?></span></h5>
                                <div class="progress">
                                    <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $dinner_percentage; ?>%" aria-valuenow="<?php echo $dinner_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $dinner_percentage; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mt-3">
                            <div class="col-12">
                                <h5 class="text-center">Overall Attendance <span class="badge bg-primary"><?php echo $total_present; ?>/<?php echo $total_possible; ?></span></h5>
                                <div class="progress">
                                    <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $overall_percentage; ?>%" aria-valuenow="<?php echo $overall_percentage; ?>" aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $overall_percentage; ?>%
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="attendance_reports.php" class="btn btn-primary">
                                <i class="fas fa-file-alt me-2"></i> Generate Detailed Reports
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-clipboard-check me-2"></i> Mark Attendance
                    </div>
                    <div class="card-body">
                        <form action="" method="post">
                            <div class="mb-3">
                                <label for="user_id" class="form-label"><i class="fas fa-id-card me-2"></i> Enter User ID:</label>
                                <input type="text" class="form-control" id="user_id" name="id" placeholder="Enter User ID" required>
                                <div class="form-text">Enter the User ID number from the user list (not the sequential number).</div>
                            </div>
                            <div class="mb-3">
                                <label for="food_type" class="form-label"><i class="fas fa-utensils me-2"></i> Select Type:</label>
                                <select class="form-select" id="food_type" name="food_type" required>
                                    <option value="">-Select-</option>
                                    <option value="Breakfast">Breakfast</option>
                                    <option value="Lunch">Lunch</option>
                                    <option value="Snacks">Snacks</option>
                                    <option value="Dinner">Dinner</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="attendance" class="form-label"><i class="fas fa-check-circle me-2"></i> Attendance:</label>
                                <select class="form-select" id="attendance" name="attendance" required>
                                    <option value="">-Select-</option>
                                    <option value="Present">Present</option>
                                    <option value="Absent">Absent</option>
                                </select>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" name="submit_attendance">
                                    <i class="fas fa-save me-2"></i> Submit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-bolt me-2"></i> Quick Actions
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="manage_users.php" class="btn btn-primary">
                                <i class="fas fa-users me-2"></i> View Users
                            </a>
                            <a href="manage_users.php" class="btn btn-danger">
                                <i class="fas fa-user-minus me-2"></i> Delete User
                            </a>
                            <a href="manage_menu.php" class="btn btn-success">
                                <i class="fas fa-edit me-2"></i> Edit Menu
                            </a>
                            <a href="add_fees.php" class="btn btn-success">
                                <i class="fas fa-plus-circle me-2"></i> Add Fees
                            </a>
                            <a href="user_bills.php" class="btn btn-success">
                                <i class="fas fa-file-invoice-dollar me-2"></i> User Bills
                            </a>
                            <a href="manage_orders.php" class="btn btn-primary">
                                <i class="fas fa-shopping-basket me-2"></i> Manage Orders
                            </a>
                            <a href="stock_usage_history.php" class="btn btn-warning">
                                <i class="fas fa-history me-2"></i> Stock Usage History
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-7">
                <div class="action-div" id="action_div">
                    <!-- User ID Lookup Section -->
                    <div class="card">
                        <div class="card-header">
                            <i class="fas fa-id-card me-2"></i> User ID Lookup
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <input type="text" class="form-control" id="userSearchInput" placeholder="Search by name or email" onkeyup="filterUsers()">
                            </div>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Email</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        // Simplified query without room_number
                                        $query = "SELECT sno, fname, lname, email FROM users ORDER BY fname, lname";
                                        $result = mysqli_query($connection, $query);
                                        
                                        if ($result && mysqli_num_rows($result) > 0) {
                                            while ($row = mysqli_fetch_assoc($result)) {
                                                echo "<tr>";
                                                echo "<td><strong>" . $row['sno'] . "</strong></td>";
                                                echo "<td>" . htmlspecialchars($row['fname'] . ' ' . $row['lname']) . "</td>";
                                                echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                                echo "</tr>";
                                            }
                                        } else {
                                            echo "<tr><td colspan='3' class='text-center'>No users found</td></tr>";
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    
    <script type="text/javascript">
      // User search filter function
      function filterUsers() {
        // Get input value
        var input = document.getElementById("userSearchInput");
        var filter = input.value.toUpperCase();
        var table = document.querySelector("#action_div table");
        var tr = table.getElementsByTagName("tr");
        
        // Loop through all table rows and hide those that don't match
        for (var i = 1; i < tr.length; i++) { // Start from 1 to skip header
          var tdName = tr[i].getElementsByTagName("td")[1]; // Name is in the second column
          var tdEmail = tr[i].getElementsByTagName("td")[2]; // Email is in the third column
          
          if (tdName || tdEmail) {
            var nameValue = tdName ? tdName.textContent || tdName.innerText : "";
            var emailValue = tdEmail ? tdEmail.textContent || tdEmail.innerText : "";
            
            if (
              nameValue.toUpperCase().indexOf(filter) > -1 || 
              emailValue.toUpperCase().indexOf(filter) > -1
            ) {
              tr[i].style.display = "";
            } else {
              tr[i].style.display = "none";
            }
          }
        }
      }
    </script>
  </body>
</html>
<?php }
else{
  header('location:../index.php');
}
?>


// End output buffering
ob_end_flush();
?>