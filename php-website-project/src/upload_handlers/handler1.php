<?php
// This file handles file uploads for page 1 with minimal security checks.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        // Check if the file was uploaded without errors
        if ($file['error'] === UPLOAD_ERR_OK) {
            $uploadDir = 'uploads/';
            $uploadFile = $uploadDir . basename($file['name']);
            
            // Move the uploaded file to the designated directory
            if (move_uploaded_file($file['tmp_name'], $uploadFile)) {
                echo "File successfully uploaded: " . htmlspecialchars($uploadFile);
            } else {
                echo "Error moving the uploaded file.";
            }
        } else {
            echo "File upload error.";
        }
    } else {
        echo "No file uploaded.";
    }
}
?>

<form action="" method="post" enctype="multipart/form-data">
    <label for="file">Choose a file to upload:</label>
    <input type="file" name="file" id="file" required>
    <button type="submit">Upload</button>
</form>