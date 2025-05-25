<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

$success_files = [];
$error_files = [];

function removePhpComments($content) {
    $patterns = [
        '/\/\*.*?\*\//s',           // Remove multi-line comments /* */
        '/\/\/.*?(?=\n|\r|$)/',     // Remove single-line comments //
        '/#.*?(?=\n|\r|$)/',        // Remove shell-style comments #
        '/\/\*\*.*?\*\//s'          // Remove doc-block comments /** */
    ];
    
    return preg_replace($patterns, '', $content);
}

function processDirectory($dir = '.') {
    global $success_files, $error_files;
    
    $items = scandir($dir);
    
    foreach ($items as $item) {
        if ($item == '.' || $item == '..' || $item == 'remove_comments.php') {
            continue;
        }
        
        $path = $dir . '/' . $item;
        
        if (is_dir($path)) {
            processDirectory($path);
        } elseif (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
            try {
                $content = file_get_contents($path);
                if ($content === false) {
                    $error_files[] = $path;
                    continue;
                }
                
                $newContent = removePhpComments($content);
                
                if (file_put_contents($path, $newContent)) {
                    $success_files[] = $path;
                } else {
                    $error_files[] = $path;
                }
            } catch (Exception $e) {
                $error_files[] = $path . ' (Error: ' . $e->getMessage() . ')';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_comments'])) {
    $directory = isset($_POST['directory']) ? $_POST['directory'] : '.';
    processDirectory($directory);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP Comment Remover</title>
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
        .file-list {
            max-height: 300px;
            overflow-y: auto;
            border: 1px solid #ddd;
            padding: 10px;
            border-radius: 5px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="mb-4">PHP Comment Remover</h1>
        
        <div class="alert alert-warning">
            <strong>Warning:</strong> This tool will remove all comments from PHP files. This operation cannot be undone. Make sure to backup your files before proceeding.
        </div>
        
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Remove Comments</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label for="directory">Directory to Process</label>
                        <input type="text" class="form-control" id="directory" name="directory" value="." placeholder="Enter directory path (default is current directory)">
                    </div>
                    <button type="submit" name="remove_comments" class="btn btn-danger" onclick="return confirm('Are you sure you want to remove comments from all PHP files? This cannot be undone!');">
                        Remove Comments
                    </button>
                </form>
            </div>
        </div>
        
        <?php if (!empty($success_files)): ?>
        <div class="card mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">Successfully Processed Files (<?= count($success_files) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="file-list">
                    <ul>
                        <?php foreach($success_files as $file): ?>
                            <li><?= htmlspecialchars($file) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_files)): ?>
        <div class="card mb-4">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Failed Files (<?= count($error_files) ?>)</h5>
            </div>
            <div class="card-body">
                <div class="file-list">
                    <ul>
                        <?php foreach($error_files as $file): ?>
                            <li><?= htmlspecialchars($file) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="text-center mt-4">
            <a href="index.php" class="btn btn-secondary">Back to Home</a>
        </div>
    </div>
</body>
</html> 