<?php
// Display all errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mess_db';

// HTML header
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Import Tool</title>
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
        button, .button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: inline-block;
            text-decoration: none;
        }
        button:hover, .button:hover {
            background-color: #45a049;
        }
        .secondary {
            background-color: #6c757d;
        }
        .secondary:hover {
            background-color: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Database Import Tool</h1>';

// Check if MySQL is running
$conn = @mysqli_connect($host, $username, $password);
if (!$conn) {
    echo '<div class="error">
        <strong>MySQL Connection Error:</strong> ' . mysqli_connect_error() . '<br>
        Please make sure MySQL is running in XAMPP Control Panel.
    </div>';
    echo '<p><a href="db_diagnostics.php" class="button secondary">Run Diagnostics</a></p>';
    echo '</div></body></html>';
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import'])) {
    // Create database if it doesn't exist
    if (mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS $database")) {
        echo '<div class="success">Database created or already exists.</div>';
    } else {
        echo '<div class="error">Error creating database: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    // Select the database
    if (!mysqli_select_db($conn, $database)) {
        echo '<div class="error">Error selecting database: ' . mysqli_error($conn) . '</div>';
        exit;
    }
    
    // Find and import SQL files
    $sql_dir = __DIR__ . '/database file/';
    if (is_dir($sql_dir)) {
        $sql_files = glob($sql_dir . '*.sql');
        
        if (empty($sql_files)) {
            echo '<div class="warning">No SQL files found in the "database file" directory.</div>';
        } else {
            echo '<div class="info"><strong>Found SQL files:</strong><ul>';
            foreach ($sql_files as $sql_file) {
                echo '<li>' . basename($sql_file) . '</li>';
            }
            echo '</ul></div>';
            
            // Import each SQL file
            $success_count = 0;
            $error_count = 0;
            
            foreach ($sql_files as $sql_file) {
                $sql_content = file_get_contents($sql_file);
                
                // Split SQL content into separate queries
                $queries = explode(';', $sql_content);
                
                echo '<div class="info"><strong>Importing ' . basename($sql_file) . ':</strong><ul>';
                
                foreach ($queries as $query) {
                    $query = trim($query);
                    if (empty($query)) continue;
                    
                    if (mysqli_query($conn, $query)) {
                        echo '<li style="color: green;">Success: ' . substr($query, 0, 50) . '...</li>';
                        $success_count++;
                    } else {
                        echo '<li style="color: red;">Error: ' . mysqli_error($conn) . ' in query: ' . substr($query, 0, 50) . '...</li>';
                        $error_count++;
                    }
                }
                
                echo '</ul></div>';
            }
            
            echo '<div class="' . ($error_count > 0 ? 'warning' : 'success') . '">
                <strong>Import Summary:</strong><br>
                Successfully executed ' . $success_count . ' queries.<br>
                Failed to execute ' . $error_count . ' queries.
            </div>';
        }
    } else {
        echo '<div class="error">The "database file" directory does not exist.</div>';
    }
}

// Show the form
echo '<form method="POST" action="">
    <div class="warning">
        <p><strong>WARNING:</strong> This will import the database structure from the SQL files in the "database file" directory.</p>
        <p>Any existing data in the database might be overwritten.</p>
    </div>
    <p>
        <button type="submit" name="import">Import Database</button>
        <a href="db_diagnostics.php" class="button secondary">Run Diagnostics</a>
        <a href="index.php" class="button secondary">Go to Homepage</a>
    </p>
</form>';

echo '</div>
</body>
</html>';

// Close the connection
mysqli_close($conn);
?> 