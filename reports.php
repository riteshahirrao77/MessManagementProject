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
?>

<div class="container">
    <div class="row">
        <div class="col-md-12">
            <h2 class="text-center mb-4">Reports</h2>
            
            <div class="card mb-4">
                <div class="card-header">
                    <h4>Available Reports</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clipboard-check fa-3x mb-3 text-primary"></i>
                                    <h5>Attendance Reports</h5>
                                    <p>View and export attendance data by date range</p>
                                    <a href="attendance_reports.php" class="btn btn-primary">View Report</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body text-center">
                                    <i class="fas fa-boxes fa-3x mb-3 text-warning"></i>
                                    <h5>Stock Reports</h5>
                                    <p>Track inventory usage and stock levels</p>
                                    <a href="stock_usage_history.php" class="btn btn-warning">View Report</a>
                                </div>
                            </div>
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