<?php
// page2.php - Finance document uploader using backend validation
session_start();
$upload_dir = 'uploads/';

// Ensure upload directory exists
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Allowed MIME types and size (for viewer and hints)
$allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
$max_file_size = 2 * 1024 * 1024; // 2MB

// Finance-friendly filename pattern (client hint and server-side viewer gate)
// Allows letters, numbers, spaces, dash, underscore, dot, parentheses, ampersand, %, $
// Also (intentionally) allows forward slashes to simulate directory references
$finance_pattern = '/^[A-Za-z0-9 _\-\.\(\)&%$\/]+$/';

// Helper: detect mime using finfo
function get_mime_type_safe($path) {
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $type = $finfo ? finfo_file($finfo, $path) : null;
    if ($finfo) finfo_close($finfo);
    return $type ?: 'application/octet-stream';
}

// Upload handling: forward to backend for validation
$upload_message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['file'])) {
    $file = $_FILES['file'];
    $backendUrl = 'backend_server.php/upload';

    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $backendUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $postData = [
            'file' => new CURLFile($file['tmp_name'], $file['type'], $file['name']),
            'source' => 'page2'
        ];
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        $backendResponse = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
    } else {
        // Fallback: use PHP streams to POST multipart/form-data (requires allow_url_fopen)
        $boundary = '----P2Boundary' . bin2hex(random_bytes(8));
        $eol = "\r\n";
        $multipartBody = '';
        // source field
        $multipartBody .= '--' . $boundary . $eol;
        $multipartBody .= 'Content-Disposition: form-data; name="source"' . $eol . $eol . 'page2' . $eol;
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
        $upload_message = 'File uploaded successfully (validated by backend).';
        header('Location: page2.php?doc=' . urlencode(basename($file['name'])));
        exit;
    } else {
        $msg = is_array($backendData) ? ($backendData['message'] ?? 'Backend error') : 'Backend unreachable';
        $upload_message = 'Upload failed: ' . htmlspecialchars($msg);
    }
}

// Weak document viewing logic (allows finance-style references)
$view_error = '';
$served_inline = false;
if (isset($_GET['doc'])) {
    $requested = $_GET['doc'];
    if (!preg_match($finance_pattern, $requested)) {
        $view_error = 'Requested file contains disallowed characters.';
    } else {
        // Candidate locations; include backend uploads
        $candidates = [
            'backend_uploads/' . basename($requested),
            $upload_dir . $requested,
            'Financial/' . basename($requested),
            '../Financial/' . basename($requested)
        ];
        foreach ($candidates as $candidate) {
            if (file_exists($candidate) && is_file($candidate)) {
                $mime = get_mime_type_safe($candidate);
                if (in_array($mime, $allowed_types)) {
                    header('Content-Type: ' . $mime);
                    header('Content-Length: ' . filesize($candidate));
                    header('Content-Disposition: inline; filename="' . basename($candidate) . '"');
                    readfile($candidate);
                    $served_inline = true;
                } else {
                    $view_error = 'Disallowed content type.';
                }
                break;
            }
        }
        if (!$served_inline && !$view_error) {
            $view_error = 'File not found.';
        }
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
    <title>Page 2 - Moderate File Upload (Finance)</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 900px; margin: 0 auto; padding: 20px; background: #f8f9fa; }
        .card { background: #fff; padding: 16px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.08); margin-bottom: 16px; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; border-radius: 6px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; padding: 10px; border-radius: 6px; }
        .hint { font-size: 13px; color: #6c757d; }
        label { display: block; margin-bottom: 6px; }
        input[type="file"] { display: block; margin-bottom: 10px; }
        button { padding: 8px 12px; }
        .example { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 6px; }
        .path { font-family: monospace; }
    </style>
</head>
<body>
    <h1>Finance Document Upload</h1>

    <?php if (!empty($upload_message)): ?>
        <div class="card <?php echo strpos($upload_message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($upload_message); ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($view_error)): ?>
        <div class="card error">
            <?php echo htmlspecialchars($view_error); ?>
        </div>
    <?php endif; ?>

    <div class="card">
        <h3>Upload Financial Documents (Images/PDFs only)</h3>
        <form action="page2.php" method="post" enctype="multipart/form-data" id="p2Form">
            <label for="file">Select file</label>
            <input type="file" name="file" id="p2File" required accept="image/*,application/pdf">
            <div class="hint">Allowed types: JPG, PNG, GIF, PDF. Max 2MB.</div>
            <div class="hint">Allowed filename characters: letters, numbers, space, - _ . ( ) & % $ and /</div>
            <button type="submit">Upload</button>
        </form>
    </div>

    <div class="card example">
        <h3>Viewer</h3>
        <form method="get" action="page2.php" style="margin-bottom:10px;">
            <label for="doc">View uploaded or referenced document</label>
            <input type="text" id="doc" name="doc" placeholder="Q4_Results(2024)/slides.pdf" style="width:100%; padding:8px;">
            <div class="hint">Tip: Use finance-friendly names. Example: <span class="path">Q1-2024/earnings.pdf</span></div>
            <button type="submit">View</button>
        </form>
        <div class="hint">Inline display for images/PDFs. Target file of interest: <span class="path">Financial/NASDAQ_RKLB_2024.pdf</span></div>
    </div>

    <script>
    // Minimal client-side validation: images/PDFs only, 2MB cap, finance-friendly name
    (function(){
        const form = document.getElementById('p2Form');
        if (!form) return;
        form.addEventListener('submit', function(e){
            const input = document.getElementById('p2File');
            if (!input || !input.files || !input.files[0]) {
                alert('Please select a file.');
                e.preventDefault();
                return;
            }
            const f = input.files[0];
            const name = f.name || '';
            const size = typeof f.size === 'number' ? f.size : 0;
            const ext = name.includes('.') ? name.split('.').pop().toLowerCase() : '';
            const allowedExt = ['jpg','jpeg','png','gif','pdf'];
            const financeRe = /^[A-Za-z0-9 _\-\.\(\)&%$\/]+$/;
            if (!allowedExt.includes(ext)) {
                alert('Only images and PDFs are allowed.');
                e.preventDefault();
                return;
            }
            if (size > (2 * 1024 * 1024)) {
                alert('File too large. Max 2MB.');
                e.preventDefault();
                return;
            }
            if (!financeRe.test(name)) {
                alert('Filename contains disallowed characters.');
                e.preventDefault();
                return;
            }
        });
    })();
    </script>
</body>
</html>