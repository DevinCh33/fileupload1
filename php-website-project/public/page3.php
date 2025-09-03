<?php
// page3.php - Improved safeguards for file uploads

// Start the session
session_start();

// Define allowed MIME types
$allowedMimeTypes = ['image/jpeg', 'image/png', 'application/pdf'];

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $file = $_FILES['fileToUpload'];
        
        // Check for upload errors
        if ($file['error'] === UPLOAD_ERR_OK) {
            // Validate MIME type
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if (in_array($mimeType, $allowedMimeTypes)) {
                // Basic content validation (e.g., check for specific content)
                $content = file_get_contents($file['tmp_name']);
                if (strpos($content, '<?php') === false) { // Example check to prevent PHP code
                    // Move the uploaded file to a secure directory
                    $uploadDir = 'uploads/';
                    $uploadFile = $uploadDir . basename($file['name']);
                    
                    if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                        echo "File uploaded successfully: " . htmlspecialchars($uploadFile);
                    } else {
                        echo "Error moving the uploaded file.";
                    }
                } else {
                    echo "Invalid file content.";
                }
            } else {
                echo "Invalid file type.";
            }
        } else {
            echo "File upload error.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 3 - File Upload</title>
</head>
<body>
    <h1>Upload a File</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>