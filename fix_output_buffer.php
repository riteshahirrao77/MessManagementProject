<?php
// This script fixes the "headers already sent" error by updating the header.php file

// Path to the header file
$header_file = __DIR__ . '/header.php';

// Check if the file exists
if (!file_exists($header_file)) {
    die("Error: Header file not found.");
}

// Read the header file
$header_content = file_get_contents($header_file);

// Create a backup of the original file
file_put_contents($header_file . '.bak', $header_content);

// Apply fix: Add output buffering at the beginning
$fixed_content = "<?php
// Start output buffering to prevent 'headers already sent' errors
ob_start();
session_start();
if(isset(\$_SESSION['email'])){
?>";

// Replace the original session start code
$original_pattern = "<?php
	session_start();
	if(isset(\$_SESSION['email'])){
?>";

$header_content = str_replace($original_pattern, $fixed_content, $header_content);

// Add buffer flush at the end
$end_pattern = "<?php }
else{
  header('location:../index.php');
}
?>";

$fixed_end = "<?php }
else{
  header('location:../index.php');
}
// Flush the output buffer
ob_end_flush();
?>";

$header_content = str_replace($end_pattern, $fixed_end, $header_content);

// Write the fixed content back to the file
if (file_put_contents($header_file, $header_content)) {
    echo "Success! The header file has been updated to fix the 'headers already sent' error.<br>";
    echo "A backup of the original file has been saved as header.php.bak<br>";
    echo "<p>Now you can navigate back to the <a href='manage_users.php'>Manage Users</a> page.</p>";
} else {
    echo "Error: Could not write to the header file. Please check permissions.";
}
?> 