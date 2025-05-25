<?php
// Display database connection errors for troubleshooting purposes
// Comment these lines in production
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection parameters
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mess_db';

// Connection function with improved error handling
function connectToDatabase($host, $user, $pass, $db = null) {
    // Try to connect
    if ($db !== null) {
        $conn = mysqli_connect($host, $user, $pass, $db);
    } else {
        $conn = mysqli_connect($host, $user, $pass);
    }
    
    // Check connection
    if (!$conn) {
        return [false, "Connection failed: " . mysqli_connect_error()];
    }
    
    return [true, $conn];
}

// Try connecting with database specified
list($success, $connection_or_error) = connectToDatabase($host, $username, $password, $database);

// If failed, try connecting without database
if (!$success) {
    // Try connecting to just the server without specifying a database
    list($server_success, $connection_or_server_error) = connectToDatabase($host, $username, $password);
    
    if (!$server_success) {
        // Cannot connect to MySQL server at all
        echo "<div style='margin: 50px auto; max-width: 800px; padding: 20px; border: 1px solid #dc3545; border-radius: 5px; background-color: #f8d7da; color: #721c24;'>";
        echo "<h3>MySQL Connection Error</h3>";
        echo "<p><strong>Error:</strong> " . $connection_or_error . "</p>";
        echo "<p><strong>Possible solutions:</strong></p>";
        echo "<ol>";
        echo "<li>Make sure MySQL server is running in XAMPP Control Panel</li>";
        echo "<li>Check if the username and password are correct</li>";
        echo "<li>Verify that the MySQL server is configured to accept connections from 'localhost'</li>";
        echo "</ol>";
        echo "<p><a href='javascript:location.reload()' style='color: #721c24; text-decoration: underline;'>Try Again</a></p>";
        echo "</div>";
        die();
    }
    
    // Connected to server but not to database
    $connection = $connection_or_server_error;
    
    // Create the database if it doesn't exist
    $create_db_query = "CREATE DATABASE IF NOT EXISTS $database";
    if (mysqli_query($connection, $create_db_query)) {
        // Select the database
        if (mysqli_select_db($connection, $database)) {
            // Successfully created and selected database
        } else {
            die("Error selecting database: " . mysqli_error($connection));
        }
    } else {
        die("Error creating database: " . mysqli_error($connection));
    }
} else {
    // Connection successful on first attempt
    $connection = $connection_or_error;
}

// Set utf8 character set
mysqli_set_charset($connection, "utf8mb4");
?>
