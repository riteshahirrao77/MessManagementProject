<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$is_admin = false;
if(isset($_SESSION['email']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    $is_admin = true;
}

require_once 'includes/connection.php';

if(isset($_POST['fix_food_orders'])) {
    if (mysqli_query($connection, "DROP TABLE IF EXISTS food_orders")) {
        $success_msg = "Table 'food_orders' dropped successfully.";
        
        $create_table = "CREATE TABLE food_orders (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL,
            meal_type VARCHAR(20) NOT NULL,
            food_item VARCHAR(255) NOT NULL,
            price DECIMAL(10,2) NOT NULL,
            order_date DATE NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(sno)
        )";
        
        if (mysqli_query($connection, $create_table)) {
            $success_msg .= " Table recreated successfully with the correct structure.";
        } else {
            $error_msg = "Failed to recreate table: " . mysqli_error($connection);
        }
    } else {
        $error_msg = "Failed to drop table: " . mysqli_error($connection);
    }
}

if(isset($_POST['check_database'])) {
    $tables = [];
    $result = mysqli_query($connection, "SHOW TABLES");
    if($result) {
        while($row = mysqli_fetch_array($result)) {
            $tables[] = $row[0];
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Management Tools</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { 
            padding: 20px;
            background-color: #f8f9fa;
        }
        .container {
            max-width: 900px;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
            margin-top: 30px;
        }
        .card {
            margin-bottom: 20px;
        }
        .tool-section {
            margin-bottom: 30px;
            border-bottom: 1px solid #eee;
            padding-bottom: 20px;
        }
        .btn-tool {
            background-color: #194350;
            color: white;
        }
        .btn-tool:hover {
            background-color: #0d2b36;
            color: white;
        }
        h1, h2 {
            color: #194350;
        }
        .alert {
            margin-top: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Database Management Tools</h1>
            <div>
                <a href="index.php" class="btn btn-secondary">Back to Home</a>
                <?php if($is_admin): ?>
                <a href="admin/dashboard.php" class="btn btn-primary">Admin Dashboard</a>
                <?php endif; ?>
            </div>
        </div>
        
        <?php if(isset($success_msg)): ?>
        <div class="alert alert-success"><?= $success_msg ?></div>
        <?php endif; ?>
        
        <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
        <?php endif; ?>
        
        <div class="tool-section">
            <h2>Database Structure Tools</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Fix Food Orders Table</h5>
                        </div>
                        <div class="card-body">
                            <p>This tool will drop and recreate the food_orders table with the correct structure.</p>
                            <form method="post">
                                <button type="submit" name="fix_food_orders" class="btn btn-tool" onclick="return confirm('Are you sure? This will delete all existing food orders!');">
                                    Fix Food Orders Table
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">Check Database Structure</h5>
                        </div>
                        <div class="card-body">
                            <p>This tool will check the database structure and display all tables.</p>
                            <form method="post">
                                <button type="submit" name="check_database" class="btn btn-tool">
                                    Check Database
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if(isset($_POST['check_database'])): ?>
        <div class="tool-section">
            <h2>Database Tables</h2>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>Table Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($tables)): ?>
                        <tr>
                            <td colspan="2" class="text-center">No tables found in database</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach($tables as $table): ?>
                            <tr>
                                <td><?= htmlspecialchars($table) ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-table" data-table="<?= htmlspecialchars($table) ?>">
                                        View Structure
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="table-structure"></div>
        <?php endif; ?>
        
        <div class="mt-4 text-center text-muted">
            <small>Use these tools with caution. Improper use may result in data loss.</small>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.view-table').click(function() {
                var table = $(this).data('table');
                $.ajax({
                    url: 'ajax/get_table_structure.php',
                    type: 'POST',
                    data: {table: table},
                    success: function(response) {
                        $('#table-structure').html(response);
                    },
                    error: function() {
                        alert('Error fetching table structure');
                    }
                });
            });
        });
    </script>
</body>
</html> 