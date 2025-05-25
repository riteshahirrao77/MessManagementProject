<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

if(isset($_SESSION['email'])){
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_stock.php">
                        <i class="fas fa-boxes"></i> Manage Stock
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_orders.php">
                        <i class="fas fa-clipboard-list"></i> Manage Orders
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="attendance_reports.php">
                        <i class="fas fa-clipboard-check"></i> Attendance Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="manage_users.php">
                        <i class="fas fa-users"></i> Users
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_report.php">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="view_fee_status.php">
                        <i class="fas fa-money-bill-wave"></i> Fee Status
                    </a>
                </li>
            </ul>
        </div>
    </div>
</nav>
<?php } ?> 

// End output buffering
ob_end_flush();
?>