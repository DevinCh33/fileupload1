<?php
// page1.php - Minimal safeguards against file upload vulnerabilities

$uploadDir = 'uploads/';
$uploadedFile = '';
$message = '';
$fileInfo = '';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Handle clear all images
if (isset($_POST['clear_all'])) {
    $files = scandir($uploadDir);
    $deletedCount = 0;
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            if (unlink($uploadDir . $file)) {
                $deletedCount++;
            }
        }
    }
    $message = "Successfully deleted $deletedCount files from uploads directory.";
}

// Handle test payload execution
if (isset($_GET['test_payload'])) {
    $testPayload = $_GET['test_payload'];
    $fileInfo = "<h3>Testing Payload Execution:</h3>";
    $fileInfo .= "<h4>Payload:</h4><pre>" . htmlspecialchars($testPayload) . "</pre>";
    
    // Create a temporary file with the payload
    $tempFile = $uploadDir . 'test_payload_' . time() . '.php';
    file_put_contents($tempFile, $testPayload);
    
    // Execute the payload
    ob_start();
    try {
        include($tempFile);
        $output = ob_get_clean();
        $fileInfo .= "<h4>Execution Result:</h4><pre>" . htmlspecialchars($output) . "</pre>";
        
        if (empty($output)) {
            $fileInfo .= "<p><strong>Note:</strong> No output generated. Payload may be executing silently.</p>";
        }
    } catch (Exception $e) {
        $fileInfo .= "<h4>Execution Error:</h4><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
    
    // Clean up temp file
    unlink($tempFile);
}

// Handle file execution (for vulnerability practice)
if (isset($_GET['execute']) && !empty($_GET['execute'])) {
    $fileToExecute = $uploadDir . basename($_GET['execute']);
    if (file_exists($fileToExecute)) {
        $fileType = strtolower(pathinfo($fileToExecute, PATHINFO_EXTENSION));
        if ($fileType == 'php') {
            // Execute PHP files (for vulnerability practice)
            $fileInfo = "<h3>Executing PHP File: " . htmlspecialchars(basename($fileToExecute)) . "</h3>";
            
            // Show file content first
            $content = file_get_contents($fileToExecute);
            $fileInfo .= "<h4>File Content:</h4><pre>" . htmlspecialchars($content) . "</pre>";
            
            // Execute the file and capture output
            ob_start();
            try {
                include($fileToExecute);
                $output = ob_get_clean();
                $fileInfo .= "<h4>Execution Output:</h4><pre>" . htmlspecialchars($output) . "</pre>";
                
                if (empty($output)) {
                    $fileInfo .= "<p><strong>Note:</strong> No output was generated. The file may be executing silently or waiting for input.</p>";
                }
            } catch (Exception $e) {
                $fileInfo .= "<h4>Execution Error:</h4><pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
            }
            
            // Add debugging information
            $fileInfo .= "<h4>Debug Info:</h4>";
            $fileInfo .= "<p><strong>File Permissions:</strong> " . substr(sprintf('%o', fileperms($fileToExecute)), -4) . "</p>";
            $fileInfo .= "<p><strong>File Size:</strong> " . filesize($fileToExecute) . " bytes</p>";
            $fileInfo .= "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
            
        } elseif (in_array($fileType, ['txt', 'log'])) {
            // Display text files
            $content = file_get_contents($fileToExecute);
            $fileInfo = "<h3>Text File Content:</h3><pre>" . htmlspecialchars($content) . "</pre>";
        }
    } else {
        $fileInfo = "<h3>Error:</h3><p>File not found: " . htmlspecialchars($fileToExecute) . "</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['clear_all'])) {
    $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);
    $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

    // Check file type (only allow certain types)
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'php', 'txt', 'log', 'html', 'htm'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile)) {
            $message = "File is valid, and was successfully uploaded.";
            $uploadedFile = $uploadFile;
            
            // Analyze uploaded file
            $fileInfo = analyzeFile($uploadFile);
        } else {
            $message = "Possible file upload attack!";
        }
    } else {
        $message = "File type not allowed.";
    }
}

// Function to analyze uploaded files
function analyzeFile($filePath) {
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $fileSize = filesize($filePath);
    $mimeType = mime_content_type($filePath);
    
    $analysis = "<h3>File Analysis:</h3>";
    $analysis .= "<p><strong>File:</strong> " . htmlspecialchars(basename($filePath)) . "</p>";
    $analysis .= "<p><strong>Type:</strong> " . htmlspecialchars($fileType) . "</p>";
    $analysis .= "<p><strong>MIME Type:</strong> " . htmlspecialchars($mimeType) . "</p>";
    $analysis .= "<p><strong>Size:</strong> " . number_format($fileSize) . " bytes</p>";
    
    // Content analysis based on file type
    if (in_array($fileType, ['txt', 'log', 'php', 'html', 'htm'])) {
        $content = file_get_contents($filePath);
        $analysis .= "<h4>File Content Preview (first 500 chars):</h4>";
        $analysis .= "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
        
        if ($fileType == 'php') {
            $analysis .= "<p><strong>⚠️ PHP File Detected!</strong> This file can be executed.</p>";
            $analysis .= "<a href='?execute=" . urlencode(basename($filePath)) . "' class='execute-btn'>Execute PHP File</a>";
        }
    }
    
    return $analysis;
}

// Get list of uploaded files
$uploadedFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadDir . $file;
            $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $uploadedFiles[] = [
                'path' => $filePath,
                'name' => $file,
                'type' => $fileType,
                'isImage' => in_array($fileType, ['jpg', 'jpeg', 'png', 'gif']),
                'isExecutable' => $fileType == 'php'
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 1 - File Upload Vulnerability Lab</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .upload-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .uploaded-image {
            max-width: 100%;
            max-height: 400px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .gallery-item {
            text-align: center;
            background: #fff;
            padding: 10px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .gallery-item img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .gallery-item p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
        .file-actions {
            margin-top: 10px;
        }
        .execute-btn {
            background: #dc3545;
            color: white;
            padding: 5px 10px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 12px;
            margin: 2px;
        }
        .execute-btn:hover {
            background: #c82333;
        }
        .clear-btn {
            background: #dc3545;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            margin: 10px 0;
        }
        .clear-btn:hover {
            background: #c82333;
        }
        .file-info {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .vulnerability-info {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .file-type-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: bold;
            margin: 2px;
        }
        .badge-php {
            background: #dc3545;
            color: white;
        }
        .badge-image {
            background: #28a745;
            color: white;
        }
        .badge-text {
            background: #17a2b8;
            color: white;
        }
    </style>
</head>
<body>
    <h1>File Upload Vulnerability Lab - Page 1</h1>
    
    <div class="vulnerability-info">
        <h3>⚠️ Vulnerability Practice Area</h3>
        <p>This page allows various file types including PHP files for vulnerability testing. 
        Be careful with uploaded files as they may contain malicious code!</p>
    </div>
    
    <div class="upload-form">
        <form enctype="multipart/form-data" action="page1_HR.php" method="POST">
            <input type="file" name="userfile" required>
            <input type="submit" value="Upload">
        </form>
        
        <form method="POST" style="display: inline;">
            <button type="submit" name="clear_all" class="clear-btn">Clear All Files</button>
        </form>
        
        <div style="margin-top: 15px; padding: 10px; background: #e9ecef; border-radius: 4px;">
            <h4>Quick Test Payloads:</h4>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Hello World!"; ?>'); ?>" class="execute-btn">Test: Hello World</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Current time: " . date("Y-m-d H:i:s"); ?>'); ?>" class="execute-btn">Test: Current Time</a>
            <a href="?test_payload=<?php echo urlencode('<?php system("whoami"); ?>'); ?>" class="execute-btn">Test: System Command</a>
            <a href="?test_payload=<?php echo urlencode('<?php phpinfo(); ?>'); ?>" class="execute-btn">Test: PHP Info</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($fileInfo): ?>
        <div class="file-info">
            <?php echo $fileInfo; ?>
        </div>
    <?php endif; ?>

    <?php if ($uploadedFile && file_exists($uploadedFile)): ?>
        <h2>Recently Uploaded File:</h2>
        <?php 
        $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));
        if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <img src="<?php echo htmlspecialchars($uploadedFile); ?>" alt="Uploaded Image" class="uploaded-image">
        <?php endif; ?>
    <?php endif; ?>

    <?php if (!empty($uploadedFiles)): ?>
        <h2>Uploaded Files Gallery:</h2>
        <div class="gallery">
            <?php foreach ($uploadedFiles as $file): ?>
                <div class="gallery-item">
                    <?php if ($file['isImage']): ?>
                        <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="Uploaded Image">
                    <?php else: ?>
                        <div style="height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px;">
                            <span style="font-size: 24px;">📄</span>
                        </div>
                    <?php endif; ?>
                    <p><?php echo htmlspecialchars($file['name']); ?></p>
                    <span class="file-type-badge badge-<?php echo $file['type'] == 'php' ? 'php' : ($file['isImage'] ? 'image' : 'text'); ?>">
                        <?php echo strtoupper($file['type']); ?>
                    </span>
                    <div class="file-actions">
                        <?php if ($file['isExecutable']): ?>
                            <a href="?execute=<?php echo urlencode($file['name']); ?>" class="execute-btn">Execute</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>