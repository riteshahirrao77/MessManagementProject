<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

  session_start();
  if(isset($_SESSION['email'])){
  include('../includes/connection.php');
  
  // Check if fee_amount column exists in users table
  $check_column = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_amount'");
  if(mysqli_num_rows($check_column) == 0) {
    // If fee_amount column doesn't exist, add it
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_amount DECIMAL(10,2) DEFAULT 1000.00");
  }
  
  // Check if fee_status column exists
  $check_status = mysqli_query($connection, "SHOW COLUMNS FROM users LIKE 'fee_status'");
  if(mysqli_num_rows($check_status) == 0) {
    // Add fee_status column if it doesn't exist
    mysqli_query($connection, "ALTER TABLE users ADD COLUMN fee_status TINYINT(1) DEFAULT 0");
  }
  
  // Calculate total fees
  $query = "SELECT 
            SUM(CASE WHEN fee_status = 0 THEN fee_amount ELSE 0 END) as total_fees_due,
            SUM(CASE WHEN fee_status = 1 THEN fee_amount ELSE 0 END) as total_fees_collected,
            SUM(fee_amount) as overall_total,
            COUNT(CASE WHEN fee_status = 0 THEN 1 END) as unpaid_count,
            COUNT(CASE WHEN fee_status = 1 THEN 1 END) as paid_count,
            COUNT(*) as total_users
            FROM users";
  $result = mysqli_query($connection, $query);
  $stats = mysqli_fetch_assoc($result);
  
  $total_fees_due = $stats['total_fees_due'] ?? 0;
  $total_fees_collected = $stats['total_fees_collected'] ?? 0;
  $overall_total = $stats['overall_total'] ?? 0;
  $collection_rate = ($overall_total > 0) ? ($total_fees_collected / $overall_total) * 100 : 0;
  $user_payment_rate = ($stats['total_users'] > 0) ? ($stats['paid_count'] / $stats['total_users']) * 100 : 0;
  
  // Get all users with fee information
  $query = "SELECT * FROM users ORDER BY fee_status ASC, fname ASC";
  $query_run = mysqli_query($connection, $query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fee Status</title>
  <link href='../includes/fontawesome/css/all.css' rel='stylesheet'>
  <link rel='stylesheet' href='../assets/css/custom.css'>
  <style>
    .stats-card {
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      margin-bottom: 20px;
      overflow: hidden;
    }
    .stats-header {
      padding: 15px;
      color: white;
      font-weight: bold;
    }
    .stats-body {
      padding: 15px;
      background-color: white;
    }
    .bg-fees-due {
      background-color: #dc3545;
    }
    .bg-fees-collected {
      background-color: #28a745;
    }
    .bg-fees-total {
      background-color: #007bff;
    }
    .stats-value {
      font-size: 1.8rem;
      font-weight: 600;
    }
    .stats-label {
      color: #6c757d;
      font-size: 0.9rem;
    }
    .progress {
      height: 10px;
      margin-top: 10px;
    }
    .badge {
      padding: 8px 12px;
      border-radius: 4px;
      font-size: 12px;
    }
    @media print {
      .btn, .no-print { 
        display: none !important; 
      }
      .card { 
        border: none !important; 
        box-shadow: none !important;
      }
      .card-header { 
        background-color: #f8f9fa !important; 
        color: #000 !important; 
      }
      .thead-dark th { 
        background-color: #f8f9fa !important; 
        color: #000 !important; 
        border: 1px solid #dee2e6 !important;
      }
      .stats-header {
        background-color: #f8f9fa !important;
        color: #000 !important;
      }
      @page {
        size: landscape;
      }
    }
  </style>
</head>
<body>
  <div class="container mt-4 mb-4">
    <div class="card">
      <div class="card-header bg-primary text-white">
        <h4><i class="fas fa-money-bill-wave mr-2"></i> Fee Status Report</h4>
      </div>
      <div class="card-body">
        <!-- Fee Statistics Overview -->
        <div class="row mb-4">
          <div class="col-md-4">
            <div class="stats-card">
              <div class="stats-header bg-fees-due">
                <i class="fas fa-exclamation-circle mr-2"></i> Fees Due
              </div>
              <div class="stats-body">
                <div class="stats-value text-danger">₹<?php echo number_format($total_fees_due, 2); ?></div>
                <div class="stats-label">From <?php echo $stats['unpaid_count']; ?> users</div>
                <div class="progress">
                  <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo 100 - $collection_rate; ?>%" 
                       aria-valuenow="<?php echo 100 - $collection_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="stats-card">
              <div class="stats-header bg-fees-collected">
                <i class="fas fa-check-circle mr-2"></i> Fees Collected
              </div>
              <div class="stats-body">
                <div class="stats-value text-success">₹<?php echo number_format($total_fees_collected, 2); ?></div>
                <div class="stats-label">From <?php echo $stats['paid_count']; ?> users</div>
                <div class="progress">
                  <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $collection_rate; ?>%" 
                       aria-valuenow="<?php echo $collection_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-4">
            <div class="stats-card">
              <div class="stats-header bg-fees-total">
                <i class="fas fa-calculator mr-2"></i> Total Fees
              </div>
              <div class="stats-body">
                <div class="stats-value text-primary">₹<?php echo number_format($overall_total, 2); ?></div>
                <div class="stats-label">From <?php echo $stats['total_users']; ?> users</div>
                <div class="progress">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: 100%" 
                       aria-valuenow="100" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Collection Rate Statistics -->
        <div class="row mb-4">
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-percentage mr-2"></i> Collection Rate by Amount</h5>
              </div>
              <div class="card-body">
                <div class="text-center">
                  <h1 class="display-4 font-weight-bold <?php echo $collection_rate >= 70 ? 'text-success' : ($collection_rate >= 40 ? 'text-warning' : 'text-danger'); ?>">
                    <?php echo number_format($collection_rate, 1); ?>%
                  </h1>
                  <p class="text-muted">of the total fees have been collected</p>
                  <div class="progress" style="height: 20px;">
                    <div class="progress-bar <?php echo $collection_rate >= 70 ? 'bg-success' : ($collection_rate >= 40 ? 'bg-warning' : 'bg-danger'); ?>" 
                         role="progressbar" style="width: <?php echo $collection_rate; ?>%" 
                         aria-valuenow="<?php echo $collection_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo number_format($collection_rate, 1); ?>%
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          
          <div class="col-md-6">
            <div class="card">
              <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="fas fa-users mr-2"></i> Collection Rate by Users</h5>
              </div>
              <div class="card-body">
                <div class="text-center">
                  <h1 class="display-4 font-weight-bold <?php echo $user_payment_rate >= 70 ? 'text-success' : ($user_payment_rate >= 40 ? 'text-warning' : 'text-danger'); ?>">
                    <?php echo number_format($user_payment_rate, 1); ?>%
                  </h1>
                  <p class="text-muted">of users have paid their fees</p>
                  <div class="progress" style="height: 20px;">
                    <div class="progress-bar <?php echo $user_payment_rate >= 70 ? 'bg-success' : ($user_payment_rate >= 40 ? 'bg-warning' : 'bg-danger'); ?>" 
                         role="progressbar" style="width: <?php echo $user_payment_rate; ?>%" 
                         aria-valuenow="<?php echo $user_payment_rate; ?>" aria-valuemin="0" aria-valuemax="100">
                      <?php echo number_format($user_payment_rate, 1); ?>%
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- User Fee Details Table -->
        <div class="table-responsive">
          <table class="table table-striped table-bordered">
            <thead class="thead-dark">
              <tr>
                <th>User ID</th>
                <th>Name</th>
      <th>Email</th>
      <th>Mobile</th>
                <th>Fee Amount</th>
      <th>Fee Status</th>
              </tr>
            </thead>
            <tbody>
              <?php
              if(mysqli_num_rows($query_run) > 0) {
  while($row = mysqli_fetch_assoc($query_run)){
                  echo "<tr>
                    <td>{$row['sno']}</td>
                    <td>" . htmlspecialchars($row['fname'] . ' ' . $row['lname']) . "</td>
                    <td>" . htmlspecialchars($row['email']) . "</td>
                    <td>" . htmlspecialchars($row['mobile']) . "</td>
                    <td>₹" . number_format($row['fee_amount'], 2) . "</td>
        <td>";
                    if($row['fee_status'] == 1){
                      echo '<span class="badge badge-success">Fee Paid</span>';
                    } else {
                      echo '<span class="badge badge-danger">Not Paid</span>';
                    } 
                    echo "</td>
                  </tr>";
                }
              } else {
                echo "<tr><td colspan='6' class='text-center'>No users found</td></tr>";
              }
              ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    
    <!-- Print Button -->
    <div class="text-center my-4 no-print">
      <button class="btn btn-primary" onclick="window.print()">
        <i class="fas fa-print mr-2"></i> Print Fee Status Report
      </button>
      <button class="btn btn-success ml-2" onclick="exportTableToExcel('fee-table', 'fee_status_report')">
        <i class="fas fa-file-excel mr-2"></i> Export to Excel
      </button>
    </div>
  </div>
  
  <script>
    function exportTableToExcel(tableID, filename = '') {
      var downloadLink;
      var dataType = 'application/vnd.ms-excel';
      var tableSelect = document.querySelector('table');
      var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
      
      // Specify filename
      filename = filename?filename+'.xls':'excel_data.xls';
      
      // Create download link element
      downloadLink = document.createElement("a");
      
      document.body.appendChild(downloadLink);
      
      if(navigator.msSaveOrOpenBlob) {
        var blob = new Blob(['\ufeff', tableHTML], {
          type: dataType
        });
        navigator.msSaveOrOpenBlob(blob, filename);
      } else {
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
      
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
      }
    }
  </script>
</body>
</html>
<?php 
} else {
  header('location:../index.php');
  exit();
}
?>


// End output buffering
ob_end_flush();
?>