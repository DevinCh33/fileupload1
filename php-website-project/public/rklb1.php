<?php
// rklb1.php - File upload vulnerability lab for accessing NASDAQ_RKLB_2024.pdf
session_start();
$upload_dir = 'rklb1_uploads/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Allowed MIME types (client-side validation only)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

// Function to process financial document name
function processFinancialName($fileName) {
    if (stripos($fileName, 'FINANCIAL_') !== false) {
        // Extract the part after "FINANCIAL_"
        $parts = explode('FINANCIAL_', $fileName, 2);
        if (count($parts) > 1) {
            $financialPart = $parts[1];
            // Remove file extension if present
            $financialPart = pathinfo($financialPart, PATHINFO_FILENAME);
            // Replace underscores with spaces
            $processedName = str_replace('_', ' ', $financialPart);
            return $processedName;
        }
    }
    return null;
}

// Upload handling: forward to specialized backend with NO validation
$upload_message = '';
$financial_document_name = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $backendUrl = 'rklb1_backend.php';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backendUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $postData = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'action' => 'upload'
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $backendResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // Fallback: use PHP streams to POST multipart/form-data
        $boundary = '----RKLB1Boundary' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $multipartBody = '';
        // action field
        $multipartBody .= '--' . $boundary . $eol;
        $multipartBody .= 'Content-Disposition: form-data; name="action"' . $eol . $eol . 'upload' . $eol;
        // file field
        $filename = $file['name'];
        $mime = $file['type'] ?: 'application/octet-stream';
        $multipartBody .= '--' . $boundary . $eol;
        $multipartBody .= 'Content-Disposition: form-data; name="file"; filename="' . str_replace('"', '"', $filename) . '"' . $eol;
        $multipartBody .= 'Content-Type: ' . $mime . $eol . $eol;
        $multipartBody .= file_get_contents($file['tmp_name']) . $eol;
        $multipartBody .= '--' . $boundary . '--' . $eol;

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: multipart/form-data; boundary=' . $boundary . $eol . 'Content-Length: ' . strlen($multipartBody),
                'content' => $multipartBody,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $backendResponse = @file_get_contents($backendUrl, false, $context);
        $httpCode = 0;
        if (isset($http_response_header) && preg_match('#\s(\d{3})\s#', $http_response_header[0] ?? '', $m)) {
            $httpCode = (int)$m[1];
        }
    }

    $backendData = json_decode($backendResponse, true);
    if (is_array($backendData) && ($backendData['status'] ?? '') === 'success') {
        $upload_message = 'File uploaded successfully!';
        
        // Process financial document name
        $processedName = processFinancialName($file['name']);
        if ($processedName !== null) {
            $financial_document_name = $processedName;
        }
        
        header('Location: rklb1.php?doc=' . urlencode(basename($file['name'])) . '&financial_name=' . urlencode($financial_document_name));
        exit;
    } else {
        $msg = is_array($backendData) ? ($backendData['message'] ?? 'Backend error') : 'Backend unreachable';
        $upload_message = 'Upload failed: ' . htmlspecialchars($msg);
    }
}

// Clear files functionality
if (isset($_POST['clear_files'])) {
    $backendUrl = 'rklb1_backend.php';
    
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backendUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $postData = ['action' => 'clear'];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $backendResponse = curl_exec($ch);
        curl_close($ch);
    } else {
        $postData = http_build_query(['action' => 'clear']);
        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $postData,
                'ignore_errors' => true
            ]
        ];
        $context = stream_context_create($opts);
        $backendResponse = @file_get_contents($backendUrl, false, $context);
    }
    
    $backendData = json_decode($backendResponse, true);
    if (is_array($backendData) && ($backendData['status'] ?? '') === 'success') {
        $upload_message = 'All uploaded files cleared successfully!';
    } else {
        $upload_message = 'Failed to clear files.';
    }
}

// File viewing logic (NO directory traversal protection)
$view_error = '';
$served_inline = false;
if (isset($_GET['doc'])) {
    $requested = $_GET['doc'];
    
    // Multiple candidate paths to check (including directory traversal)
    $candidates = [
        $upload_dir . $requested,
        $requested, // Direct path access
        '../Financial/' . basename($requested),
        'Financial/' . basename($requested),
        '../' . $requested,
        '../../' . $requested
    ];
    
    foreach ($candidates as $filePath) {
        if (file_exists($filePath) && is_file($filePath)) {
            $mime = mime_content_type($filePath);
            if (in_array($mime, $allowed_types)) {
                header('Content-Type: ' . $mime);
                header('Content-Length: ' . filesize($filePath));
                header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
                readfile($filePath);
                $served_inline = true;
                break;
            } else {
                $view_error = 'Disallowed content type.';
                break;
            }
        }
    }
    
    if (!$served_inline && !$view_error) {
        $view_error = 'File not found in any candidate location.';
    }
    
    if ($served_inline) {
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RKLB1 - File Upload Vulnerability Lab</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: #fff; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 6px; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; padding: 10px; border-radius: 6px; }
        .hint { font-size: 13px; color: #6c757d; }
        label { display: block; margin-bottom: 6px; }
        input[type="file"] { display: block; margin-bottom: 10px; }
        button { padding: 8px 12px; margin-right: 10px; }
        .example { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 6px; }
        .path { font-family: monospace; }
        .important { font-weight: bold; color: #dc3545; }
    </style>
</head>
<body>
    <h1>RKLB1 - File Upload Vulnerability Lab</h1>
    
    <div class="card warning">
        <h3>⚠️ Lab Objective</h3>
        <p>Your goal is to access the file: <span class="path">Financial/NASDAQ_RKLB_2024.pdf</span></p>
        <p class="important">IMPORTANT: File names MUST contain "FINANCIAL_" to be processed by the backend!</p>
    </div>

    <?php if (!empty($upload_message)): ?>
        <div class="card <?php echo strpos($upload_message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($upload_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_GET['financial_name'])): ?>
        <div class="card success">
            <h4>📄 Financial Document Uploaded</h4>
            <p>The following Financial document has been uploaded: <strong><?php echo htmlspecialchars($_GET['financial_name']); ?></strong></p>
        </div>
    <?php endif; ?>

    <?php if (!empty($view_error)): ?>
        <div class="card error">
            <?php echo htmlspecialchars($view_error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Upload File (Client-side validation only)</h3>
        <form action="rklb1.php" method="post" enctype="multipart/form-data" id="rklb1Form">
            <label for="file">Select file</label>
            <input type="file" name="file" id="rklb1File" required accept="image/*,application/pdf">
            <div class="hint">Allowed types: JPG, PNG, GIF, PDF only</div>
            <div class="hint important">Filename MUST contain "FINANCIAL_" to be processed!</div>
            <button type="submit">Upload</button>
        </form>
    </div>

    <div class="card">
        <h3>Clear All Uploaded Files</h3>
        <form action="rklb1.php" method="post" style="display: inline;">
            <button type="submit" name="clear_files">Clear All Files</button>
        </form>
    </div>

    <div class="card example">
        <h3>File Viewer (No Path Restrictions)</h3>
        <form method="get" action="rklb1.php" style="margin-bottom:10px;">
            <label for="doc">View uploaded file</label>
            <input type="text" id="doc" name="doc" placeholder="FINANCIAL_test.pdf" style="width:100%; padding:8px;">
            <div class="hint">Enter the filename to view (no path restrictions)</div>
            <div class="hint">Try: <span class="path">../Financial/NASDAQ_RKLB_2024.pdf</span></div>
            <button type="submit">View</button>
        </form>
        <div class="hint">Target file: <span class="path">Financial/NASDAQ_RKLB_2024.pdf</span></div>
        <div class="hint">Hint: The viewer checks multiple paths including directory traversal attempts</div>
    </div>

    <script>
    // Client-side validation: images/PDFs only, must contain "FINANCIAL_"
    (function(){
        const form = document.getElementById('rklb1Form');
        if (!form) return;
        form.addEventListener('submit', function(e){
            const input = document.getElementById('rklb1File');
            if (!input || !input.files || !input.files[0]) {
                alert('Please select a file.');
                e.preventDefault();
                return;
            }
            const f = input.files[0];
            const name = f.name || '';
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            const allowedExt = ['jpg','jpeg','png','gif','pdf'];
            
            if (!allowedExt.includes(ext)) {
                alert('Only images and PDFs are allowed.');
                e.preventDefault();
                return;
            }
            
            if (!name.includes('FINANCIAL_')) {
                alert('Filename MUST contain "FINANCIAL_" to be processed!');
                e.preventDefault();
                return;
            }
        });
    })();
    </script>
</body>
</html>
