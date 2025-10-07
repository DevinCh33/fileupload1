<?php
// page6_CSV.php - CLIENT-SIDE ONLY validation with CSV/XLSX cell extraction
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
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'php', 'txt', 'log', 'html', 'htm', 'csv', 'xlsx'];
    return in_array($ext, $allowed);
}

// NEW FUNCTION: Display specific cells from CSV or XLSX
function displayCSVCells($filePath) {
    $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    
    if ($fileType !== 'xlsx' && $fileType !== 'csv') {
        return '';
    }
    
    $output = '<div class="csv-display" style="background: #e3f2fd; padding: 15px; border-radius: 8px; margin: 15px 0; border: 2px solid #2196f3;">';
    $output .= '<h3 style="color: #1565c0; margin-top: 0;">Spreadsheet Data Extract:</h3>';
    
    $c19_value = 'N/A';
    $d19_value = 'N/A';
    
    try {
        if ($fileType === 'csv') {
            // Read CSV file
            if (($handle = fopen($filePath, 'r')) !== FALSE) {
                $rowIndex = 0;
                
                while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    $rowIndex++;
                    
                    // Row 19 (index starts at 1 for row numbering)
                    if ($rowIndex == 19) {
                        // Column C is index 2 (A=0, B=1, C=2), Column D is index 3
                        $c19_value = isset($data[2]) ? trim($data[2]) : 'N/A';
                        $d19_value = isset($data[3]) ? trim($data[3]) : 'N/A';
                        break;
                    }
                }
                fclose($handle);
            } else {
                throw new Exception('Could not read CSV file');
            }
        } elseif ($fileType === 'xlsx') {
            // Check if PhpSpreadsheet is available
            $phpSpreadsheetClass = 'PhpOffice\\PhpSpreadsheet\\IOFactory';
            if (file_exists('vendor/autoload.php') && class_exists($phpSpreadsheetClass)) {
                require_once 'vendor/autoload.php';
                
                // Use dynamic class loading to avoid IDE errors when library not installed
                $ioFactoryClass = $phpSpreadsheetClass;
                $reader = $ioFactoryClass::createReader('Xlsx');
                $spreadsheet = $reader->load($filePath);
                $worksheet = $spreadsheet->getActiveSheet();
                
                // Get cells C19 and D19
                $c19_value = $worksheet->getCell('C19')->getValue();
                $d19_value = $worksheet->getCell('D19')->getValue();
                
                // Convert to string if needed
                $c19_value = ($c19_value !== null) ? (string)$c19_value : 'N/A';
                $d19_value = ($d19_value !== null) ? (string)$d19_value : 'N/A';
                
            } else {
                // Fallback: Simple ZIP extraction for XLSX without PhpSpreadsheet
                $zip = new ZipArchive;
                if ($zip->open($filePath) === TRUE) {
                    $xmlData = $zip->getFromName('xl/worksheets/sheet1.xml');
                    $zip->close();
                    
                    if ($xmlData !== false) {
                        $xml = simplexml_load_string($xmlData);
                        
                        // Register namespace
                        $xml->registerXPathNamespace('ns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
                        
                        // Find cells C19 and D19
                        $c19Cells = $xml->xpath('//ns:c[@r="C19"]/ns:v');
                        $d19Cells = $xml->xpath('//ns:c[@r="D19"]/ns:v');
                        
                        if (!empty($c19Cells)) {
                            $c19_value = (string)$c19Cells[0];
                        }
                        if (!empty($d19Cells)) {
                            $d19_value = (string)$d19Cells[0];
                        }
                    }
                } else {
                    throw new Exception('Could not open XLSX file');
                }
            }
        }
        
        $output .= '<p style="font-size: 18px; margin: 15px 0; padding: 10px; background: white; border-radius: 4px;">';
        $output .= '<strong>Cash and cash equivalents for Q1 = ' . htmlspecialchars($c19_value) . ', Q2 = ' . htmlspecialchars($d19_value) . '</strong>';
        $output .= '</p>';
        
        // Optional: Show cell locations for clarity
        $output .= '<div style="font-size: 12px; color: #666; margin-top: 10px; padding: 8px; background: #f5f5f5; border-radius: 4px;">';
        $output .= '<strong>Cell Details:</strong><br>';
        $output .= 'File Type: ' . strtoupper($fileType) . '<br>';
        $output .= 'Cell C19 (Row 19, Column C): ' . htmlspecialchars($c19_value) . '<br>';
        $output .= 'Cell D19 (Row 19, Column D): ' . htmlspecialchars($d19_value);
        $output .= '</div>';
        
    } catch (Exception $e) {
        $output .= '<p style="color: #d32f2f;">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    
    $output .= '</div>';
    return $output;
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
                    'message' => 'File uploaded successfully (PAGE6 CSV/XLSX)',
                    'file_path' => $backendFilePath,
                    'file_size' => filesize($backendFilePath),
                    'upload_time' => date('Y-m-d H:i:s'),
                    'validation_bypassed' => true,
                    'source' => 'page6_csv'
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
                    'source' => 'page6_csv'
                ];
            }
            
            if ($backendData && $backendData['status'] === 'success') {
                $message = "File uploaded successfully (PAGE6 CSV/XLSX)";
                $uploadedFile = $backendData['file_path'];
                
                // Show backend response (render raw output for payload manifestation)
                $fileInfo = "<h3>Backend Response (PAGE6 CSV/XLSX):</h3>";
                $fileInfo .= "<pre>" . htmlspecialchars(json_encode($backendData, JSON_PRETTY_PRINT)) . "</pre>";
                $fileInfo .= "<p><a href='" . htmlspecialchars($backendData['file_path']) . "' target='_blank'>Open uploaded file</a></p>";
                
                // NEW: Display CSV/XLSX cell data if it's a spreadsheet file
                if (in_array($fileExtension, ['csv', 'xlsx'])) {
                    $fileInfo .= displayCSVCells($backendFilePath);
                }
                
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
    
    if (in_array($fileType, ['txt', 'log', 'php', 'html', 'htm', 'csv'])) {
        $content = file_get_contents($filePath);
        $analysis .= "<h4>File Content Preview (first 500 chars):</h4>";
        $analysis .= "<pre>" . htmlspecialchars(substr($content, 0, 500)) . "</pre>";
        
        if ($fileType == 'php') {
            $analysis .= "<p><strong>Warning: PHP File Detected!</strong> This file can be executed.</p>";
            $analysis .= "<a href='?execute=" . urlencode(basename($filePath)) . "' class='execute-btn'>Execute PHP File</a>";
            $analysis .= "<a href='?include_file=" . urlencode(basename($filePath)) . "' class='execute-btn'>Include File</a>";
        }
    }
    
    if (in_array($fileType, ['csv', 'xlsx'])) {
        $analysis .= displayCSVCells($filePath);
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
                'isExecutable' => $fileType == 'php',
                'isSpreadsheet' => in_array($fileType, ['csv', 'xlsx'])
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
    <title>Page 6 - CSV/XLSX Cell Extraction Demo</title>
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
            display: inline-block;
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
        .badge-csv, .badge-xlsx {
            background: #ffc107;
            color: #000;
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
            <h1>Page 6 - CSV/XLSX Cell Extraction Demo</h1>
            
            <details class="collapsible" id="help-vuln">
                <summary>Vulnerability Overview</summary>
                <div class="content vulnerability-info">
                    <h3>CRITICAL VULNERABILITY: CLIENT-SIDE ONLY VALIDATION</h3>
                    <p><strong>This page uses ONLY client-side validation which can be easily bypassed!</strong></p>
                    <p>All uploads are sent to backend with "page6_csv" source identifier to bypass server-side validation.</p>
                    <p><strong>NEW: CSV and XLSX files are parsed and specific cells (C19, D19) are extracted and displayed.</strong></p>
                </div>
            </details>
            
            <details class="collapsible" id="help-csv">
                <summary>Spreadsheet Functionality</summary>
                <div class="content" style="background: #e8f5e9; padding: 15px; border-radius: 4px;">
                    <h3>CSV/XLSX Cell Extraction</h3>
                    <p>When you upload a CSV or XLSX file, the system will:</p>
                    <ol>
                        <li>Read the spreadsheet file</li>
                        <li>Navigate to row 19</li>
                        <li>Extract values from columns C and D</li>
                        <li>Display: "Cash and cash equivalents for Q1 = C19, Q2 = D19"</li>
                    </ol>
                    <p><strong>Supported formats:</strong> CSV (comma-delimited) and XLSX (Excel 2007+)</p>
                    <p><strong>Requirements:</strong> Your file should have at least 19 rows and 4 columns (A, B, C, D).</p>
                    <p><strong>Note:</strong> For XLSX files, PhpSpreadsheet library is recommended but a fallback parser is available.</p>
                </div>
            </details>

            <div class="upload-form" id="upload">
                <h3>Upload File (CSV, XLSX, Images, PDF, PHP, etc.)</h3>
                <form enctype="multipart/form-data" action="page6_CSV.php" method="POST" id="uploadForm">
                    <input type="file" name="userfile" id="fileInput" required accept="image/*,.pdf,.csv,.xlsx">
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
                <?php elseif (in_array($fileType, ['csv', 'xlsx'])): ?>
                    <?php echo displayCSVCells($uploadedFile); ?>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!empty($uploadedFiles)): ?>
                <h2 id="gallery">Uploaded Files Gallery:</h2>
                <div class="gallery">
                    <?php foreach ($uploadedFiles as $file): ?>
                        <div class="gallery-item">
                            <?php if ($file['isImage']): ?>
                                <img src="<?php echo htmlspecialchars($file['path']); ?>" alt="Uploaded Image">
                            <?php elseif ($file['isSpreadsheet']): ?>
                                <div style="height: 150px; background: #e3f2fd; display: flex; align-items: center; justify-content: center; border: 1px solid #2196f3; border-radius: 4px;">
                                    <span style="font-size: 48px;">📊</span>
                                </div>
                            <?php else: ?>
                                <div style="height: 150px; background: #f8f9fa; display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px;">
                                    <span style="font-size: 24px;">📄</span>
                                </div>
                            <?php endif; ?>
                            <p><?php echo htmlspecialchars($file['name']); ?></p>
                            <span class="file-type-badge badge-<?php echo $file['isSpreadsheet'] ? $file['type'] : ($file['type'] == 'php' ? 'php' : ($file['isImage'] ? 'image' : 'text')); ?>">
                                <?php echo strtoupper($file['type']); ?>
                            </span>
                            <div class="file-actions">
                                <?php if ($file['isExecutable']): ?>
                                    <a href="?execute=<?php echo urlencode($file['name']); ?>" class="execute-btn">Execute</a>
                                    <a href="?include_file=<?php echo urlencode($file['name']); ?>" class="execute-btn">Include</a>
                                <?php endif; ?>
                                <?php if ($file['isSpreadsheet']): ?>
                                    <a href="<?php echo htmlspecialchars($file['path']); ?>" class="execute-btn" style="background: #2196f3;" download>Download</a>
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

            <div class="footer">© <?php echo date('Y'); ?> Vulnerable Upload Lab - CSV/XLSX Demo</div>
        </div>
    </div>

    <div class="toast" id="toast">Action completed.</div>

    <div class="modal-backdrop" id="modal">
        <div class="modal">
            <h3 id="modal-title">About</h3>
            <p>This page demonstrates file upload vulnerabilities with CSV and XLSX parsing. It extracts cells C19 and D19 from uploaded spreadsheet files.</p>
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
        ['help-vuln','help-csv'].forEach(id => {
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
            const allowed = ['jpg','jpeg','png','gif','pdf','csv','xlsx'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            if (!allowed.includes(ext)) {
                alert('Unsupported file type: ' + ext + '. Only images, PDFs, CSV and XLSX files are allowed.');
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