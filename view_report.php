<?php
// Start output buffering to prevent "headers already sent" errors
ob_start();

include('header.php');
if(isset($_SESSION['email'])){
    include('../includes/connection.php');

    // Fetch attendance records and user details
    $query1 = "SELECT u.fname, u.lname, a.date, a.attendance 
            FROM attendance1 a
            JOIN users u ON a.id = u.sno
            ORDER BY a.date DESC";
    $result1 = mysqli_query($connection, $query1);
    if (!$result1) {
        die("Query failed: " . mysqli_error($connection));
    }

    $query2 = "SELECT u.fname, u.lname, a.date, a.attendance 
            FROM attendance2 a
            JOIN users u ON a.id = u.sno
            ORDER BY a.date DESC";
    $result2 = mysqli_query($connection, $query2);
    if (!$result2) {
        die("Query failed: " . mysqli_error($connection));
    }

    $query3 = "SELECT u.fname, u.lname, a.date, a.attendance 
            FROM attendance3 a
            JOIN users u ON a.id = u.sno
            ORDER BY a.date DESC";
    $result3 = mysqli_query($connection, $query3);
    if (!$result3) {
        die("Query failed: " . mysqli_error($connection));
    }

    $query4 = "SELECT u.fname, u.lname, a.date, a.attendance 
            FROM attendance4 a
            JOIN users u ON a.id = u.sno
            ORDER BY a.date DESC";
    $result4 = mysqli_query($connection, $query4);
    if (!$result4) {
        die("Query failed: " . mysqli_error($connection));
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Attendance Report - Mess Management System</title>
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .report-container {
            padding: 30px;
        }
        .report-header {
            background-color: #194350;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .report-title {
            margin: 0;
            font-weight: 600;
        }
        .report-subtitle {
            margin-top: 5px;
            opacity: 0.8;
        }
        .report-section {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            overflow: hidden;
        }
        .section-header {
            background-color: #f1f1f1;
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
        }
        .section-title {
            margin: 0;
            color: #194350;
            font-weight: 600;
        }
        .table {
            width: 100%;
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        .badge-present {
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .badge-absent {
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
        }
        .print-btn {
            background-color: #194350;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s;
            margin-bottom: 20px;
        }
        .print-btn:hover {
            background-color: #0d2b36;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        @media print {
            .print-btn, .navbar {
                display: none;
            }
            .report-container {
                padding: 0;
            }
            .report-section {
                box-shadow: none;
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            .report-header {
                background-color: #f1f1f1 !important;
                color: #000 !important;
                box-shadow: none;
            }
        }
    </style>
</head>
<body>
    <div class="container report-container">
        <div class="report-header text-center">
            <h2 class="report-title">Mess Attendance Report</h2>
            <p class="report-subtitle">Generated on <?php echo date('F j, Y'); ?></p>
        </div>
        
        <button class="print-btn" onclick="window.print()">
            <i class="fas fa-print mr-2"></i> Print Report
        </button>
        
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-coffee mr-2"></i> Breakfast Attendance</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($result1) > 0) {
                            while($row = mysqli_fetch_assoc($result1)) { 
                                // Make sure date is properly formatted
                                $formatted_date = !empty($row['date']) ? date('F j, Y', strtotime($row['date'])) : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
                                <td>
                                    <?php if($row['attendance'] == 'Present') { ?>
                                        <span class="badge-present">Present</span>
                                    <?php } else { ?>
                                        <span class="badge-absent">Absent</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No records found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-utensils mr-2"></i> Lunch Attendance</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($result2) > 0) {
                            while($row = mysqli_fetch_assoc($result2)) { 
                                // Make sure date is properly formatted
                                $formatted_date = !empty($row['date']) ? date('F j, Y', strtotime($row['date'])) : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
                                <td>
                                    <?php if($row['attendance'] == 'Present') { ?>
                                        <span class="badge-present">Present</span>
                                    <?php } else { ?>
                                        <span class="badge-absent">Absent</span>
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No records found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-cookie mr-2"></i> Snacks Attendance</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
        <tr>
            <th>User Name</th>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($result3) > 0) {
                            while($row = mysqli_fetch_assoc($result3)) { 
                                // Make sure date is properly formatted
                                $formatted_date = !empty($row['date']) ? date('F j, Y', strtotime($row['date'])) : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
                                <td>
                                    <?php if($row['attendance'] == 'Present') { ?>
                                        <span class="badge-present">Present</span>
                                    <?php } else { ?>
                                        <span class="badge-absent">Absent</span>
                                    <?php } ?>
                                </td>
        </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No records found</td></tr>';
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="report-section">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-moon mr-2"></i> Dinner Attendance</h3>
            </div>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>User Name</th>
                            <th>Date</th>
                            <th>Status</th>
            </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if(mysqli_num_rows($result4) > 0) {
                            while($row = mysqli_fetch_assoc($result4)) { 
                                // Make sure date is properly formatted
                                $formatted_date = !empty($row['date']) ? date('F j, Y', strtotime($row['date'])) : 'N/A';
                        ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['fname'] . ' ' . $row['lname']); ?></td>
                                <td><?php echo $formatted_date; ?></td>
                                <td>
                                    <?php if($row['attendance'] == 'Present') { ?>
                                        <span class="badge-present">Present</span>
                                    <?php } else { ?>
                                        <span class="badge-absent">Absent</span>
        <?php } ?>
                                </td>
                            </tr>
                        <?php 
                            }
                        } else {
                            echo '<tr><td colspan="3" class="text-center">No records found</td></tr>';
                        }
                        ?>
                    </tbody>
    </table>
            </div>
        </div>
    </div>
</body>
</html>
<?php 
} else {
    header('Location: ../index.php');
    exit();
}
?>


// End output buffering
ob_end_flush();
?>