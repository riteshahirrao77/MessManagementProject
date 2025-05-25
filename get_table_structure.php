<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once '../includes/connection.php';

if(!isset($_POST['table']) || empty($_POST['table'])) {
    echo '<div class="alert alert-danger">No table specified</div>';
    exit;
}

$table = mysqli_real_escape_string($connection, $_POST['table']);

$columns_query = "SHOW COLUMNS FROM `$table`";
$columns_result = mysqli_query($connection, $columns_query);

if(!$columns_result) {
    echo '<div class="alert alert-danger">Error: ' . mysqli_error($connection) . '</div>';
    exit;
}

$count_query = "SELECT COUNT(*) as count FROM `$table`";
$count_result = mysqli_query($connection, $count_query);
$row_count = 0;

if($count_result) {
    $count_data = mysqli_fetch_assoc($count_result);
    $row_count = $count_data['count'];
}

echo '<div class="card mt-4">';
echo '<div class="card-header bg-dark text-white">';
echo '<h5 class="mb-0">Table Structure: ' . htmlspecialchars($table) . ' (' . $row_count . ' rows)</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="table-responsive">';
echo '<table class="table table-bordered table-striped">';
echo '<thead class="bg-secondary text-white">';
echo '<tr>';
echo '<th>Field</th>';
echo '<th>Type</th>';
echo '<th>Null</th>';
echo '<th>Key</th>';
echo '<th>Default</th>';
echo '<th>Extra</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

if(mysqli_num_rows($columns_result) > 0) {
    while($column = mysqli_fetch_assoc($columns_result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($column['Field']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Type']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Null']) . '</td>';
        echo '<td>' . htmlspecialchars($column['Key']) . '</td>';
        echo '<td>' . (is_null($column['Default']) ? '<em>NULL</em>' : htmlspecialchars($column['Default'])) . '</td>';
        echo '<td>' . htmlspecialchars($column['Extra']) . '</td>';
        echo '</tr>';
    }
} else {
    echo '<tr><td colspan="6" class="text-center">No columns found</td></tr>';
}

echo '</tbody>';
echo '</table>';
echo '</div>';

if($row_count > 0) {
    $sample_query = "SELECT * FROM `$table` LIMIT 5";
    $sample_result = mysqli_query($connection, $sample_query);
    
    if($sample_result && mysqli_num_rows($sample_result) > 0) {
        echo '<h6 class="mt-4">Sample Data (First 5 rows):</h6>';
        echo '<div class="table-responsive">';
        echo '<table class="table table-bordered table-striped">';
        echo '<thead class="bg-info text-white">';
        echo '<tr>';
        
        $field_info = mysqli_fetch_fields($sample_result);
        foreach($field_info as $field) {
            echo '<th>' . htmlspecialchars($field->name) . '</th>';
        }
        
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        while($row = mysqli_fetch_assoc($sample_result)) {
            echo '<tr>';
            foreach($row as $value) {
                echo '<td>' . (is_null($value) ? '<em>NULL</em>' : htmlspecialchars($value)) . '</td>';
            }
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
}

echo '</div>';
echo '</div>';
?> 