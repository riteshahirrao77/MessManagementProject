<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/html; charset=utf-8');

$is_admin = false;
if(isset($_SESSION['email']) && isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
    $is_admin = true;
}

if (!$is_admin) {
    echo "<div class='alert alert-danger'>Access denied: Admin privileges required.</div>";
    exit;
}

require_once '../includes/connection.php';

if (!isset($_POST['table']) || empty($_POST['table'])) {
    echo "<div class='alert alert-danger'>No table name provided.</div>";
    exit;
}

$table = mysqli_real_escape_string($connection, $_POST['table']);

$describe_query = "DESCRIBE `$table`";
$columns_result = mysqli_query($connection, $describe_query);

if (!$columns_result) {
    echo "<div class='alert alert-danger'>Error fetching table structure: " . mysqli_error($connection) . "</div>";
    exit;
}

$sample_query = "SELECT * FROM `$table` LIMIT 5";
$sample_result = mysqli_query($connection, $sample_query);

echo "<div class='tool-section mt-4'>";
echo "<h2>Table Structure: " . htmlspecialchars($table) . "</h2>";

echo "<div class='card mb-4'>";
echo "<div class='card-header bg-dark text-white'><h5 class='mb-0'>Columns</h5></div>";
echo "<div class='card-body p-0'>";
echo "<div class='table-responsive'>";
echo "<table class='table table-bordered table-striped mb-0'>";
echo "<thead class='bg-light'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr></thead>";
echo "<tbody>";

while ($column = mysqli_fetch_assoc($columns_result)) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "<td>" . (is_null($column['Default']) ? "<em>NULL</em>" : htmlspecialchars($column['Default'])) . "</td>";
    echo "<td>" . htmlspecialchars($column['Extra']) . "</td>";
    echo "</tr>";
}

echo "</tbody></table>";
echo "</div>";
echo "</div>";
echo "</div>";

if ($sample_result && mysqli_num_rows($sample_result) > 0) {
    $fields = mysqli_fetch_fields($sample_result);
    
    echo "<div class='card'>";
    echo "<div class='card-header bg-primary text-white'><h5 class='mb-0'>Sample Data (5 rows)</h5></div>";
    echo "<div class='card-body p-0'>";
    echo "<div class='table-responsive'>";
    echo "<table class='table table-bordered table-striped mb-0'>";
    
    echo "<thead class='bg-light'><tr>";
    foreach ($fields as $field) {
        echo "<th>" . htmlspecialchars($field->name) . "</th>";
    }
    echo "</tr></thead>";
    
    echo "<tbody>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        foreach ($row as $value) {
            if (is_null($value)) {
                echo "<td><em>NULL</em></td>";
            } else {
                echo "<td>" . htmlspecialchars($value) . "</td>";
            }
        }
        echo "</tr>";
    }
    echo "</tbody></table>";
    echo "</div>";
    echo "</div>";
    echo "</div>";
} else {
    echo "<div class='alert alert-info'>No data in this table.</div>";
}

echo "</div>";
?> 