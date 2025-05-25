<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database diagnostic tool
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Connection Diagnostics</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            color: #333;
            margin-top: 0;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Connection Diagnostics</h1>';

// Check if MySQL service is running
function is_service_running($service_name) {
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows
        $output = [];
        exec('sc query MySQL', $output);
        return strpos(implode('', $output), 'RUNNING') !== false;
    } else {
        // Linux/Unix
        $output = [];
        exec('ps aux | grep -v grep | grep mysql', $output);
        return !empty($output);
    }
}

// Check MySQL service
$mysql_running = is_service_running('MySQL');
if ($mysql_running) {
    echo '<div class="success">MySQL service appears to be running.</div>';
} else {
    echo '<div class="error">
        <strong>MySQL service does not appear to be running!</strong><br>
        Please start the MySQL service from XAMPP Control Panel before continuing.
    </div>';
}

// Try connecting to MySQL
echo '<h2>Connection Tests</h2>';
echo '<table>';
echo '<tr><th>Test</th><th>Result</th></tr>';

// Test 1: Connect to MySQL with default credentials
echo '<tr><td>Connect to MySQL with username "root" and empty password</td><td>';
$conn1 = @mysqli_connect('localhost', 'root', '');
if ($conn1) {
    echo '<span style="color: green;">SUCCESS</span>';
    mysqli_close($conn1);
} else {
    echo '<span style="color: red;">FAILED: ' . mysqli_connect_error() . '</span>';
}
echo '</td></tr>';

// Test 2: Connect to MySQL with default credentials and select database
echo '<tr><td>Connect to MySQL and select database "mess_db"</td><td>';
$conn2 = @mysqli_connect('localhost', 'root', '', 'mess_db');
if ($conn2) {
    echo '<span style="color: green;">SUCCESS</span>';
    
    // Test 3: Check if tables exist
    echo '</td></tr>';
    echo '<tr><td>Check if tables exist in "mess_db"</td><td>';
    
    $result = mysqli_query($conn2, "SHOW TABLES");
    if ($result) {
        if (mysqli_num_rows($result) > 0) {
            echo '<span style="color: green;">SUCCESS - Found ' . mysqli_num_rows($result) . ' tables</span>';
            
            echo '<ul>';
            while ($row = mysqli_fetch_row($result)) {
                echo '<li>' . $row[0] . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<span style="color: orange;">WARNING - No tables found in database</span>';
        }
    } else {
        echo '<span style="color: red;">FAILED: ' . mysqli_error($conn2) . '</span>';
    }
    
    mysqli_close($conn2);
} else {
    echo '<span style="color: red;">FAILED: ' . mysqli_connect_error() . '</span>';
    
    // Database doesn't exist, try to create it
    echo '</td></tr>';
    echo '<tr><td>Attempt to create database "mess_db"</td><td>';
    
    $conn3 = @mysqli_connect('localhost', 'root', '');
    if ($conn3) {
        if (mysqli_query($conn3, 'CREATE DATABASE IF NOT EXISTS mess_db')) {
            echo '<span style="color: green;">SUCCESS - Database created</span>';
        } else {
            echo '<span style="color: red;">FAILED: ' . mysqli_error($conn3) . '</span>';
        }
        mysqli_close($conn3);
    } else {
        echo '<span style="color: red;">FAILED: Cannot connect to create database</span>';
    }
}
echo '</td></tr>';
echo '</table>';

// Diagnostic information
echo '<h2>System Information</h2>';
echo '<div class="info">';
echo '<p><strong>PHP Version:</strong> ' . phpversion() . '</p>';
echo '<p><strong>MySQL Client Info:</strong> ' . (function_exists('mysqli_get_client_info') ? mysqli_get_client_info() : 'Not available') . '</p>';
echo '<p><strong>Server Software:</strong> ' . $_SERVER['SERVER_SOFTWARE'] . '</p>';
echo '<p><strong>Operating System:</strong> ' . PHP_OS . '</p>';
echo '</div>';

// Recommendations
echo '<h2>Recommendations</h2>';
echo '<div class="warning">';
echo '<p>Based on the diagnostic results, here are some recommendations:</p>';
echo '<ul>';

if (!$mysql_running) {
    echo '<li><strong>Start MySQL Service:</strong> Open XAMPP Control Panel and start the MySQL service.</li>';
}

if (!isset($conn2) || !$conn2) {
    echo '<li><strong>Database Connection Issues:</strong> Make sure your database credentials are correct in the connection.php file.</li>';
    echo '<li><strong>Create Database:</strong> If the database doesn\'t exist, you can import the SQL file from your database folder.</li>';
}

echo '<li><strong>Check File Permissions:</strong> Make sure the web server has proper permissions to read/write to your project files.</li>';
echo '<li><strong>Verify Database Structure:</strong> Import the database structure from the SQL file if tables are missing.</li>';
echo '</ul>';
echo '</div>';

// Action buttons
echo '<h2>Actions</h2>';
echo '<p><a href="index.php"><button type="button">Go to Homepage</button></a>&nbsp;';
echo '<a href="db_diagnostics.php"><button type="button">Run Diagnostics Again</button></a></p>';

echo '</div>
</body>
</html>';
?> 