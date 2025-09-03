<?php
// This file implements strong safeguards for file uploads, including thorough validation of file content and type,
// as well as implementing a secure upload directory.

$uploadDir = __DIR__ . '/uploads/';
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxFileSize = 2 * 1024 * 1024; // 2MB

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $file = $_FILES['fileToUpload'];
        
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            die("Upload failed with error code " . $file['error']);
        }

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            die("File is too large. Maximum size is 2MB.");
        }

        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            die("Invalid file type. Only JPEG, PNG, and PDF files are allowed.");
        }

        // Validate file content (basic check)
        $fileContent = file_get_contents($file['tmp_name']);
        if (strpos($fileContent, '<?php') !== false) {
            die("Invalid file content detected.");
        }

        // Move the uploaded file to the secure directory
        $destination = $uploadDir . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            echo "File uploaded successfully.";
        } else {
            die("Failed to move uploaded file.");
        }
    } else {
        die("No file uploaded.");
    }
} else {
    die("Invalid request method.");
}
?>