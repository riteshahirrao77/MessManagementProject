<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

function checkMySQLRunning() {
    $conn = @mysqli_connect('localhost', 'root', '');
    if ($conn) {
        mysqli_close($conn);
        return true;
    }
    return false;
}

function checkDatabaseExists($dbname) {
    $conn = @mysqli_connect('localhost', 'root', '');
    if ($conn) {
        $result = mysqli_query($conn, "SHOW DATABASES LIKE '$dbname'");
        if (mysqli_num_rows($result) > 0) {
            mysqli_close($conn);
            return true;
        }
        mysqli_close($conn);
    }
    return false;
}

function displayDatabaseErrorPage($errorType) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Database Connection Error</title>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
        <style>
            body {
                background-color: #f8f9fa;
                padding: 20px;
            }
            .error-container {
                max-width: 800px;
                margin: 50px auto;
                background: white;
                padding: 30px;
                border-radius: 10px;
                box-shadow: 0 0 15px rgba(0,0,0,0.1);
            }
            .error-icon {
                font-size: 60px;
                color: #dc3545;
            }
            .solution-list li {
                margin-bottom: 10px;
            }
        </style>
    </head>
    <body>
        <div class="container error-container">
            <div class="text-center mb-4">
                <div class="error-icon">⚠️</div>
                <h1 class="text-danger">Database Connection Error</h1>
            </div>
            
            <div class="alert alert-danger">
                <?php if ($errorType == 'mysql_not_running'): ?>
                    <strong>Error:</strong> MySQL server is not running.
                <?php elseif ($errorType == 'database_not_exists'): ?>
                    <strong>Error:</strong> The database 'mess_db' does not exist.
                <?php else: ?>
                    <strong>Error:</strong> Unknown database error occurred.
                <?php endif; ?>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Possible Solutions</h5>
                </div>
                <div class="card-body">
                    <ul class="solution-list">
                        <?php if ($errorType == 'mysql_not_running'): ?>
                            <li>Start your XAMPP/WAMP/MAMP control panel and start the MySQL service.</li>
                            <li>Check if MySQL service is running in your system services.</li>
                            <li>Restart your web server software (XAMPP/WAMP/MAMP).</li>
                        <?php elseif ($errorType == 'database_not_exists'): ?>
                            <li>Import the database from the SQL file provided with this project.</li>
                            <li>Create a new database named 'mess_db' manually using phpMyAdmin or MySQL command line.</li>
                            <li>Check the database name in the connection settings.</li>
                        <?php endif; ?>
                        <li>If you're a developer, check the database connection parameters in includes/connection.php.</li>
                    </ul>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Useful Tools & Resources</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <ul>
                                <li><a href="http://localhost/phpmyadmin/" target="_blank">phpMyAdmin</a> - Database management tool</li>
                                <li><a href="https://www.apachefriends.org/index.html" target="_blank">XAMPP</a> - Development environment</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <ul>
                                <li><a href="https://dev.mysql.com/doc/" target="_blank">MySQL Documentation</a></li>
                                <li><a href="https://www.php.net/manual/en/book.mysqli.php" target="_blank">PHP MySQLi Manual</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="javascript:location.reload()" class="btn btn-primary">Try Again</a>
                <a href="index.php" class="btn btn-secondary ml-2">Back to Home</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if (!checkMySQLRunning()) {
    displayDatabaseErrorPage('mysql_not_running');
}

if (!checkDatabaseExists('mess_db')) {
    displayDatabaseErrorPage('database_not_exists');
}
?> 