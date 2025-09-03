<?php
// This file handles file uploads with improved security measures.
// It checks for specific MIME types and implements basic content validation.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);
    $fileType = mime_content_type($_FILES['userfile']['tmp_name']);
    
    // Allowed MIME types
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];

    // Check if the file type is allowed
    if (in_array($fileType, $allowedTypes)) {
        // Move the uploaded file to the designated directory
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile)) {
            echo "File is valid, and was successfully uploaded.\n";
        } else {
            echo "Possible file upload attack!\n";
        }
    } else {
        echo "File type not allowed.\n";
    }
} else {
    echo "Invalid request method.\n";
}
?>