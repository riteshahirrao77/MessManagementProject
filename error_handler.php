<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function is_mysql_running() {
    $conn = @mysqli_connect('localhost', 'root', '');
    if ($conn) {
        mysqli_close($conn);
        return true;
    }
    return false;
}

function database_exists($database_name) {
    $conn = @mysqli_connect('localhost', 'root', '');
    if ($conn) {
        $result = mysqli_query($conn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '$database_name'");
        $exists = mysqli_num_rows($result) > 0;
        mysqli_close($conn);
        return $exists;
    }
    return false;
}

function show_database_error($type = 'mysql') {
    header("HTTP/1.1 503 Service Unavailable");
    echo '<div style="font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; background-color: #f8f9fa; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1);">';
    echo '<h1 style="color: #dc3545;">Database Connection Error</h1>';
    echo '<div style="background-color: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    
    if($type == 'mysql') {
        echo '<p><strong>MySQL Server Connection Failed</strong></p>';
        echo '<p>The application could not connect to the MySQL database server. This could be due to:</p>';
        echo '<ul>';
        echo '<li>MySQL service is not running</li>';
        echo '<li>Database credentials are incorrect</li>';
        echo '<li>Server is experiencing high load</li>';
        echo '</ul>';
    } else {
        echo '<p><strong>Database Not Found</strong></p>';
        echo '<p>The application could not find the required database. This could be due to:</p>';
        echo '<ul>';
        echo '<li>Database has not been created</li>';
        echo '<li>Database name is incorrect</li>';
        echo '<li>Database user does not have access permissions</li>';
        echo '</ul>';
    }
    
    echo '</div>';
    
    echo '<h2 style="color: #0d6efd;">Possible Solutions</h2>';
    echo '<div style="background-color: #cfe2ff; color: #084298; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    echo '<ol>';
    echo '<li>Check if MySQL service is running on your server</li>';
    echo '<li>Verify database credentials in the connection file</li>';
    echo '<li>Ensure the database exists and is properly configured</li>';
    echo '<li>Try restarting the MySQL service</li>';
    echo '</ol>';
    echo '</div>';
    
    echo '<h2 style="color: #198754;">Tools & Resources</h2>';
    echo '<div style="background-color: #d1e7dd; color: #0f5132; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
    echo '<ul>';
    echo '<li><a href="db_import.php" style="color: #0f5132; font-weight: bold;">Import Database</a> - Use this tool to import the database structure</li>';
    echo '<li><a href="database_tools.php" style="color: #0f5132; font-weight: bold;">Database Tools</a> - Use these tools to fix common database issues</li>';
    echo '<li><a href="https://dev.mysql.com/doc/" style="color: #0f5132; font-weight: bold;" target="_blank">MySQL Documentation</a> - Official MySQL documentation</li>';
    echo '</ul>';
    echo '</div>';
    
    echo '<div style="margin-top: 30px; text-align: center;">';
    echo '<a href="index.php" style="display: inline-block; background-color: #0d6efd; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">Retry Connection</a>';
    echo '<a href="mailto:admin@example.com" style="display: inline-block; background-color: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Contact Support</a>';
    echo '</div>';
    
    echo '<div style="margin-top: 30px; font-size: 12px; color: #6c757d; text-align: center;">';
    echo 'Mess Management System &copy; ' . date('Y') . ' - System Error Page';
    echo '</div>';
    
    echo '</div>';
    exit;
}

if (!is_mysql_running()) {
    show_database_error('mysql');
}

if (!database_exists('mess_db')) {
    show_database_error('database');
}
?> 