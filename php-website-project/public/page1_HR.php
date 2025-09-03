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
        $originalName = $file['name'];
        
        // VULNERABILITY: Only client-side validation
        if (weakExtensionCheck($file['name'])) {
            // Simulate backend bypass by directly uploading to backend directory
            $backendUploadDir = 'backend_uploads/';
            if (!is_dir($backendUploadDir)) {
                mkdir($backendUploadDir, 0755, true);
            }
            
            $backendFilePath = $backendUploadDir . basename($file['name']);
            
            if (move_uploaded_file($file['tmp_name'], $backendFilePath)) {
                $backendData = [
                    'status' => 'success',
                    'message' => 'File uploaded successfully (PAGE1 BYPASS)',
                    'file_path' => $backendFilePath,
                    'file_size' => filesize($backendFilePath),
                    'upload_time' => date('Y-m-d H:i:s'),
                    'validation_bypassed' => true,
                    'source' => 'page1'
                ];
                
                // Auto-execute PHP files for page1
                $fileExtension = strtolower(pathinfo($backendFilePath, PATHINFO_EXTENSION));
                if ($fileExtension === 'php') {
                    ob_start();
                    try {
                        include($backendFilePath);
                        $executionOutput = ob_get_clean();
                        
                        $backendData['php_execution'] = [
                            'executed' => true,
                            'output' => $executionOutput,
                            'execution_time' => date('Y-m-d H:i:s'),
                            'bypass_mode' => true
                        ];
                    } catch (Exception $e) {
                        $backendData['php_execution'] = [
                            'executed' => false,
                            'error' => $e->getMessage(),
                            'bypass_mode' => true
                        ];
                    }
                }
            } else {
                $backendData = [
                    'status' => 'error',
                    'message' => 'Failed to move uploaded file',
                    'source' => 'page1'
                ];
            }
            
            if ($backendData && $backendData['status'] === 'success') {
                $message = "File uploaded successfully (PAGE1 BYPASS)";
                $uploadedFile = $backendData['file_path'];
                
                // Show backend response (render raw output for payload manifestation)
                $fileInfo = "<h3>Backend Response (PAGE1 BYPASS):</h3>";
                $fileInfo .= "<pre>" . htmlspecialchars(json_encode($backendData, JSON_PRETTY_PRINT)) . "</pre>";
                $fileInfo .= "<p><a href='" . htmlspecialchars($backendData['file_path']) . "' target='_blank'>Open uploaded file</a></p>";
                // UNSAFE: Render original filename in multiple sinks to allow XSS via filename
                $fileInfo .= '<h4>Unsafe filename render (for XSS via name)</h4>';
                // Attribute injection sink via onerror
                $fileInfo .= '<img src="x" alt="x" onerror="' . $originalName . '">';
                // Text/HTML sink
                $fileInfo .= '<div id="unsafe-name">' . $originalName . '</div>';
                // Attribute value without quotes (another vector)
                $fileInfo .= '<a id=unsafe-link href=' . $originalName . '>Open by name</a>';
                // Inline script using the name
                $fileInfo .= '<script>document.querySelector("#unsafe-name").setAttribute("data-name", "' . $originalName . '");</script>';
                
                if (isset($backendData['php_execution'])) {
                    // Intentionally not escaping to allow DOM-based payloads to manifest
                    $fileInfo .= "<h4>PHP Execution Result (Raw):</h4>";
                    $fileInfo .= $backendData['php_execution']['output'];
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
    
    $info .= "<strong>Server Software:</strong> " . $_SERVER['SERVER_SOFTWARE'] . "<br>";
    $info .= "<strong>PHP Version:</strong> " . phpversion() . "<br>";
    $info .= "<strong>Server OS:</strong> " . php_uname() . "<br>";
    $info .= "<strong>Current User:</strong> " . (function_exists('posix_getpwuid') && function_exists('posix_geteuid') ? posix_getpwuid(posix_geteuid())['name'] : 'Unknown') . "<br>";
    $info .= "<strong>Current Working Directory:</strong> " . getcwd() . "<br>";
    $info .= "<strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    $info .= "<strong>Script Path:</strong> " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
    
    $info .= "<br><strong>Environment Variables:</strong><br>";
    $envVars = ['PATH', 'HOME', 'USER', 'SHELL', 'PWD', 'HOSTNAME'];
    foreach ($envVars as $var) {
        if (isset($_ENV[$var])) {
            $info .= "  " . $var . " = " . htmlspecialchars($_ENV[$var]) . "<br>";
        }
    }
    
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
    
    $info .= "<br><strong>Network Information:</strong><br>";
    $info .= "  Server IP: " . $_SERVER['SERVER_ADDR'] . "<br>";
    $info .= "  Client IP: " . $_SERVER['REMOTE_ADDR'] . "<br>";
    $info .= "  User Agent: " . htmlspecialchars($_SERVER['HTTP_USER_AGENT']) . "<br>";
    
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
            max-width: 1100px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8f9fa;
        }
        .top-nav {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #343a40;
            color: #fff;
            padding: 10px 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .top-nav a {
            color: #fff;
            text-decoration: none;
            margin: 0 8px;
        }
        .layout {
            display: grid;
            grid-template-columns: 240px 1fr;
            gap: 15px;
        }
        .sidebar {
            background: #fff;
            padding: 12px;
            border-radius: 6px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
            height: fit-content;
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
        .toggle-bar {
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 10px 0 15px 0;
        }
        .toggle-bar button {
            padding: 6px 10px;
            border: 1px solid #ced4da;
            background: #fff;
            cursor: pointer;
            border-radius: 4px;
        }
        .collapsible {
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            background: #fff;
            margin: 10px 0;
        }
        .collapsible summary {
            list-style: none;
            cursor: pointer;
            padding: 10px 12px;
            font-weight: bold;
            background: #f1f3f5;
            border-bottom: 1px solid #e9ecef;
            border-radius: 6px 6px 0 0;
        }
        .collapsible .content {
            padding: 12px;
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
        .footer {
            margin-top: 20px;
            color: #6c757d;
            text-align: center;
        }
        .toast {
            position: fixed;
            right: 12px;
            bottom: 12px;
            background: #343a40;
            color: #fff;
            padding: 10px 14px;
            border-radius: 6px;
            display: none;
        }
        .modal-backdrop {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
        }
        .modal {
            background: #fff;
            border-radius: 8px;
            padding: 16px;
            width: 480px;
            max-width: 90vw;
        }
    </style>
</head>
<body>
    <div class="top-nav">
        <div>
            <a href="index.php">Home</a>
            <a href="#" onclick="showModal('about')">About</a>
            <a href="#" onclick="document.getElementById('help-controls').scrollIntoView()">Help</a>
        </div>
        <form onsubmit="siteSearch(event)">
            <input type="text" id="search" placeholder="Search..." style="padding:6px 10px;border-radius:4px;border:1px solid #ced4da;">
        </form>
    </div>

    <div class="layout">
        <div class="sidebar">
            <h3>Sidebar</h3>
            <ul>
                <li><a href="#upload">Upload</a></li>
                <li><a href="#gallery">Gallery</a></li>
                <li><a href="#info">Info</a></li>
            </ul>
            <div class="toggle-bar" id="help-controls">
                <button type="button" onclick="toggleAll(true)">Expand All</button>
                <button type="button" onclick="toggleAll(false)">Collapse All</button>
                <button type="button" onclick="toggleHelp()">Hide/Show Help</button>
            </div>
        </div>
        <div>
            <h1>🚨 Page 1 - CLIENT-SIDE Only Validation (Vulnerable)</h1>
            
            <details class="collapsible" id="help-vuln">
                <summary>Vulnerability Overview</summary>
                <div class="content vulnerability-info">
                    <h3>⚠️ CRITICAL VULNERABILITY: CLIENT-SIDE ONLY VALIDATION</h3>
                    <p><strong>This page uses ONLY client-side validation which can be easily bypassed!</strong></p>
                    <p>All uploads are sent to backend with "page1" source identifier to bypass server-side validation.</p>
                </div>
            </details>
            
            <details class="collapsible" id="help-bypass">
                <summary>Backend Bypass Mechanism</summary>
                <div class="content">
                    <h3>🔄 Backend Bypass Mechanism</h3>
                    <ol>
                        <li>Client-side validation checks file extension only</li>
                        <li>File is placed into backend directory directly</li>
                        <li>PHP files may auto-execute and output raw HTML/JS</li>
                    </ol>
                </div>
            </details>
            
            <details class="collapsible" id="help-tips">
                <summary>Bypass Techniques</summary>
                <div class="content">
                    <ul>
                        <li>Modify file extension in browser dev tools</li>
                        <li>Change Content-Type header</li>
                        <li>Use double extensions: <code>shell.jpg.php</code></li>
                        <li>Case manipulation: <code>shell.PHP</code></li>
                        <li>Null byte injection: <code>shell.php%00.jpg</code></li>
                    </ul>
                </div>
            </details>

            <div class="upload-form" id="upload">
                <form enctype="multipart/form-data" action="page1_HR.php" method="POST" id="uploadForm">
                    <input type="file" name="userfile" id="fileInput" required accept="image/*,.pdf,.php,.txt,.log,.html,.htm">
                    <input type="submit" value="Upload">
                </form>
                
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_all" class="clear-btn">Clear All Files</button>
                </form>
            </div>

            <?php if ($message): ?>
                <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($fileInfo): ?>
                <details class="collapsible" open>
                    <summary>Execution & Backend Response</summary>
                    <div class="content file-info">
                        <?php echo $fileInfo; ?>
                    </div>
                </details>
            <?php endif; ?>

            <?php if ($uploadedFile && file_exists($uploadedFile)): ?>
                <h2 id="info">Recently Uploaded File:</h2>
                <?php 
                $fileType = strtolower(pathinfo($uploadedFile, PATHINFO_EXTENSION));
                if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                    <img src="<?php echo htmlspecialchars($uploadedFile); ?>" alt="Uploaded Image" class="uploaded-image">
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($uploadedFiles)): ?>
                <h2 id="gallery">Uploaded Files Gallery:</h2>
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

            <details class="collapsible">
                <summary>Comments</summary>
                <div class="content">
                    <form onsubmit="addComment(event)">
                        <input type="text" id="comment" placeholder="Leave a comment..." style="width:100%;padding:8px;border:1px solid #ced4da;border-radius:4px;">
                        <button type="submit" style="margin-top:8px">Post</button>
                    </form>
                    <ul id="comments"></ul>
                </div>
            </details>

            <div class="footer">© <?php echo date('Y'); ?> Vulnerable Upload Lab</div>
        </div>
    </div>

    <div class="toast" id="toast">Action completed.</div>

    <div class="modal-backdrop" id="modal">
        <div class="modal">
            <h3 id="modal-title">About</h3>
            <p>This page intentionally includes common UI elements and collapsible help blocks to provide multiple vectors for payload manifestation (DOM events, innerHTML sinks, modals, toasts).</p>
            <button onclick="hideModal()">Close</button>
        </div>
    </div>

    <script>
    function toggleAll(expand) {
        document.querySelectorAll('details.collapsible').forEach(d => {
            d.open = !!expand;
        });
    }
    function toggleHelp() {
        ['help-vuln','help-bypass','help-tips'].forEach(id => {
            const el = document.getElementById(id);
            if (el) el.style.display = (el.style.display === 'none') ? '' : 'none';
        });
    }
    function siteSearch(e) {
        e.preventDefault();
        const q = (document.getElementById('search').value || '').toLowerCase();
        const toast = document.getElementById('toast');
        toast.textContent = 'Searched for: ' + q;
        toast.style.display = 'block';
        setTimeout(() => toast.style.display = 'none', 2000);
    }
    function addComment(e) {
        e.preventDefault();
        const val = document.getElementById('comment').value;
        const li = document.createElement('li');
        // Intentionally unsafe to allow DOM XSS demonstration
        li.innerHTML = val;
        document.getElementById('comments').appendChild(li);
        document.getElementById('comment').value = '';
    }
    function showModal(type) {
        document.getElementById('modal').style.display = 'flex';
        document.getElementById('modal-title').textContent = type === 'about' ? 'About' : 'Info';
    }
    function hideModal() {
        document.getElementById('modal').style.display = 'none';
    }
    // Very simple, client-side-only validation (easily bypassed)
    (function(){
        const form = document.getElementById('uploadForm');
        if (!form) return;
        form.addEventListener('submit', function(e){
            const input = document.getElementById('fileInput');
            if (!input || !input.files || !input.files[0]) {
                alert('Please select a file.');
                e.preventDefault();
                return;
            }
            const f = input.files[0];
            const name = f.name || '';
            const size = typeof f.size === 'number' ? f.size : 0;
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            const allowed = ['jpg','jpeg','png','gif','pdf','php','txt','log','html','htm'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowed.includes(ext)) {
                alert('Unsupported file type: ' + ext);
                e.preventDefault();
                return;
            }
            if (size > maxSize) {
                alert('File too large. Max 5MB.');
                e.preventDefault();
                return;
            }
            if (name.includes('..') || name.includes('/') || name.includes('\\')) {
                alert('Invalid file name.');
                e.preventDefault();
                return;
            }
            // Note: Client-side checks are for UX only and are trivially bypassed.
        });
    })();
    </script>
</body>
</html>