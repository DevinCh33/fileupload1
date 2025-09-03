<?php
// page1.php - CLIENT-SIDE ONLY validation (easily bypassed)
// This page bypasses all server-side validation for educational purposes

$uploadDir = 'uploads/';
$uploadedFile = '';
$message = '';
$fileInfo = '';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// VULNERABILITY 1: Weak file extension validation (client-side only)
function weakExtensionCheck($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    // Only checks basic extensions, easily bypassed
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'php', 'txt', 'log', 'html', 'htm'];
    return in_array($ext, $allowed);
}

// VULNERABILITY 2: No MIME type validation
// VULNERABILITY 3: No file content validation
// VULNERABILITY 4: No file size limits

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

// Handle file uploads - SEND TO BACKEND WITH PAGE1 BYPASS
if ($_SERVER['REQUEST_METHOD'] == 'POST' && !isset($_POST['clear_all'])) {
    if (isset($_FILES['userfile'])) {
        $file = $_FILES['userfile'];
        
        // VULNERABILITY: Only client-side validation
        if (weakExtensionCheck($file['name'])) {
            // Send to backend with page1 source to bypass validation
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, 'backend_server.php/upload');
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $postData = [
                'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
                'source' => 'page1' // This triggers the bypass
            ];
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
            
            $backendResponse = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $backendData = json_decode($backendResponse, true);
            
            if ($backendData && $backendData['status'] === 'success') {
                $message = "File uploaded successfully (PAGE1 BYPASS)";
                $uploadedFile = $backendData['file_path'];
                
                // Show backend response
                $fileInfo = "<h3>Backend Response (PAGE1 BYPASS):</h3>";
                $fileInfo .= "<pre>" . htmlspecialchars(json_encode($backendData, JSON_PRETTY_PRINT)) . "</pre>";
                
                // If PHP file was executed, show the output
                if (isset($backendData['php_execution'])) {
                    $fileInfo .= "<h4>PHP Execution Result:</h4>";
                    $fileInfo .= "<div style='background: #000; color: #0f0; padding: 10px; border-radius: 4px; font-family: monospace;'>";
                    $fileInfo .= "<pre>" . htmlspecialchars($backendData['php_execution']['output']) . "</pre>";
                    $fileInfo .= "</div>";
                }
                
            } else {
                $message = "Upload failed: " . ($backendData['message'] ?? 'Unknown error');
            }
        } else {
            $message = "File type not allowed (client-side check)";
        }
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
            $analysis .= "<a href='?include_file=" . urlencode(basename($filePath)) . "' class='execute-btn'>Include File</a>";
        }
    }
    
    return $analysis;
}

// Function to get detailed system information
function getSystemInfo() {
    $info = "<h4>System Information:</h4>";
    $info .= "<div style='background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; font-size: 12px;'>";
    
    // Basic system info
    $info .= "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
    $info .= "<strong>PHP Version:</strong> " . phpversion() . "<br>";
    $info .= "<strong>Server OS:</strong> " . php_uname() . "<br>";
    $info .= "<strong>Current User:</strong> " . (function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "<br>";
    $info .= "<strong>Current Working Directory:</strong> " . getcwd() . "<br>";
    $info .= "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    $info .= "<strong>Script Path:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
    
    // Environment variables
    $info .= "<br><strong>Environment Variables:</strong><br>";
    $envVars = ['PATH', 'HOME', 'USER', 'SHELL', 'PWD', 'HOSTNAME'];
    foreach ($envVars as $var) {
        if (isset($_ENV[$var])) {
            $info .= "  " . $var . " = " . htmlspecialchars($_ENV[$var]) . "<br>";
        }
    }
    
    // PHP configuration
    $info .= "<br><strong>PHP Configuration:</strong><br>";
    $phpSettings = [
        'allow_url_fopen' => ini_get('allow_url_fopen'),
        'allow_url_include' => ini_get('allow_url_include'),
        'file_uploads' => ini_get('file_uploads'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'max_execution_time' => ini_get('max_execution_time'),
        'memory_limit' => ini_get('memory_limit'),
        'disable_functions' => ini_get('disable_functions'),
        'open_basedir' => ini_get('open_basedir')
    ];
    
    foreach ($phpSettings as $setting => $value) {
        $info .= "  " . $setting . " = " . htmlspecialchars($value) . "<br>";
    }
    
    // Network information
    $info .= "<br><strong>Network Information:</strong><br>";
    $info .= "  Server IP: " . $_SERVER['SERVER_ADDR'] . "<br>";
    $info .= "  Client IP: " . $_SERVER['REMOTE_ADDR'] . "<br>";
    $info .= "  User Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "<br>";
    
    // File system information
    $info .= "<br><strong>File System:</strong><br>";
    $info .= "  Upload Directory: " . realpath('uploads/') . "<br>";
    $info .= "  Upload Directory Writable: " . (is_writable('uploads/') ? 'Yes' : 'No') . "<br>";
    $info .= "  Temp Directory: " . sys_get_temp_dir() . "<br>";
    
    $info .= "</div>";
    return $info;
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
    <title>Page 1 - CLIENT-SIDE Only Validation (Vulnerable)</title>
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
        .danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            font-weight: bold;
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
            background: #f8d7da;
            border: 1px solid #f5c6cb;
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
        .vuln-list {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        .bypass-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <h1>🚨 Page 1 - CLIENT-SIDE Only Validation (Vulnerable)</h1>
    
    <div class="vulnerability-info">
        <h3>⚠️ CRITICAL VULNERABILITY: CLIENT-SIDE ONLY VALIDATION</h3>
        <p><strong>This page uses ONLY client-side validation which can be easily bypassed!</strong></p>
        <p>All uploads are sent to backend with "page1" source identifier to bypass server-side validation.</p>
    </div>
    
    <div class="bypass-info">
        <h3>🔄 Backend Bypass Mechanism</h3>
        <p><strong>How the bypass works:</strong></p>
        <ol>
            <li>Client-side validation checks file extension only</li>
            <li>File is sent to backend with <code>source=page1</code> parameter</li>
            <li>Backend detects "page1" source and skips ALL validation</li>
            <li>File is uploaded and executed without any security checks</li>
        </ol>
    </div>
    
    <div class="vuln-list">
        <h4>🔍 Vulnerabilities Implemented:</h4>
        <ul>
            <li><strong>Client-Side Only Validation:</strong> Easily bypassed with browser dev tools</li>
            <li><strong>Backend Bypass:</strong> Special "page1" source bypasses all server validation</li>
            <li><strong>No MIME Type Validation:</strong> Accepts any Content-Type</li>
            <li><strong>No File Content Validation:</strong> No magic number checking</li>
            <li><strong>No File Size Limits:</strong> Unlimited upload size</li>
            <li><strong>Auto-Execution:</strong> PHP files execute immediately upon upload</li>
            <li><strong>File Inclusion:</strong> Direct include() of uploaded files</li>
        </ul>
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
            <a href="?test_payload=<?php echo urlencode('<?php echo "Hello World! - Payload executed at " . date("Y-m-d H:i:s"); ?>'); ?>" class="execute-btn">Test: Hello World</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Current time: " . date("Y-m-d H:i:s") . "\nServer timezone: " . date_default_timezone_get(); ?>'); ?>" class="execute-btn">Test: Time Info</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Current user: " . (function_exists("posix_getpwuid") ? posix_getpwuid(posix_geteuid())["name"] : "Unknown") . "\nWorking directory: " . getcwd(); ?>'); ?>" class="execute-btn">Test: User Info</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "System info:\nOS: " . php_uname() . "\nPHP: " . phpversion() . "\nServer: " . $_SERVER["SERVER_SOFTWARE"]; ?>'); ?>" class="execute-btn">Test: System Info</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Environment:\nPATH: " . (isset($_ENV["PATH"]) ? $_ENV["PATH"] : "Not set") . "\nHOME: " . (isset($_ENV["HOME"]) ? $_ENV["HOME"] : "Not set"); ?>'); ?>" class="execute-btn">Test: Environment</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "File system access:\nUpload dir: " . realpath("uploads/") . "\nWritable: " . (is_writable("uploads/") ? "Yes" : "No") . "\nTemp dir: " . sys_get_temp_dir(); ?>'); ?>" class="execute-btn">Test: File System</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Network info:\nServer IP: " . $_SERVER["SERVER_ADDR"] . "\nClient IP: " . $_SERVER["REMOTE_ADDR"] . "\nUser Agent: " . $_SERVER["HTTP_USER_AGENT"]; ?>'); ?>" class="execute-btn">Test: Network</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "PHP Configuration:\nallow_url_fopen: " . ini_get("allow_url_fopen") . "\nallow_url_include: " . ini_get("allow_url_include") . "\nfile_uploads: " . ini_get("file_uploads") . "\ndisable_functions: " . ini_get("disable_functions"); ?>'); ?>" class="execute-btn">Test: PHP Config</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Directory listing:\n"; $files = scandir("uploads/"); foreach($files as $file) { if($file != "." && $file != "..") { echo "- " . $file . " (" . filesize("uploads/" . $file) . " bytes)\n"; } } ?>'); ?>" class="execute-btn">Test: Directory List</a>
            <a href="?test_payload=<?php echo urlencode('<?php echo "Process list:\n"; if(function_exists("shell_exec")) { echo shell_exec("ps aux | head -10"); } else { echo "shell_exec disabled"; } ?>'); ?>" class="execute-btn">Test: Process List</a>
        </div>
        
        <div style="margin-top: 10px; padding: 10px; background: #fff3cd; border-radius: 4px;">
            <h4>🔧 Bypass Techniques:</h4>
            <p><strong>Client-side bypass methods:</strong></p>
            <ul style="font-size: 12px;">
                <li>Modify file extension in browser dev tools</li>
                <li>Change Content-Type header</li>
                <li>Use double extensions: <code>shell.jpg.php</code></li>
                <li>Case manipulation: <code>shell.PHP</code></li>
                <li>Null byte injection: <code>shell.php%00.jpg</code></li>
            </ul>
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
                            <a href="?include_file=<?php echo urlencode($file['name']); ?>" class="execute-btn">Include</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>