<?php
// This script will add output buffering to all PHP files in the admin panel
// to prevent "headers already sent" errors

// Directory to scan
$directory = __DIR__;

// Get all PHP files in the directory
$files = glob($directory . '/*.php');

// Count files fixed
$fixed_count = 0;
$skipped_count = 0;

echo "<h2>Adding Output Buffering to Admin Panel Files</h2>";
echo "<ul>";

foreach ($files as $file) {
    // Skip the current file
    if (basename($file) === 'fix_all_headers.php') {
        continue;
    }
    
    // Read file content
    $content = file_get_contents($file);
    
    // Skip files that already have output buffering
    if (strpos($content, 'ob_start()') !== false) {
        echo "<li>Skipped " . basename($file) . " (already has output buffering)</li>";
        $skipped_count++;
        continue;
    }
    
    // Make a backup copy
    copy($file, $file . '.bak');
    
    // Add ob_start() at the beginning
    $content = preg_replace('/^<\?php/', "<?php\n// Start output buffering to prevent \"headers already sent\" errors\nob_start();\n", $content, 1);
    
    // Add ob_end_flush() at the end if it includes footer.php
    if (strpos($content, "include 'footer.php'") !== false || strpos($content, "include \"footer.php\"") !== false) {
        $content = str_replace("include 'footer.php';", "include 'footer.php';\n\n// End output buffering\nob_end_flush();", $content);
        $content = str_replace("include \"footer.php\";", "include \"footer.php\";\n\n// End output buffering\nob_end_flush();", $content);
    } else {
        // If no footer.php found, add ob_end_flush() at the end of the file
        $content .= "\n\n// End output buffering\nob_end_flush();\n?>";
    }
    
    // Save the modified content
    file_put_contents($file, $content);
    
    echo "<li>Fixed " . basename($file) . "</li>";
    $fixed_count++;
}

echo "</ul>";
echo "<p><strong>Summary:</strong> $fixed_count files fixed, $skipped_count files skipped.</p>";
echo "<p>Backup copies of the original files have been created with .bak extension.</p>";
echo "<p><a href='admin_dashboard.php'>Return to Dashboard</a></p>";
?> 