<?php
// This file handles file uploads for page 2 with moderate security checks.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);
    $fileType = pathinfo($uploadFile, PATHINFO_EXTENSION);
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];

    // Check if the file type is allowed
    if (in_array($fileType, $allowedTypes)) {
        // Check file size (limit to 2MB)
        if ($_FILES['userfile']['size'] <= 2000000) {
            // Attempt to move the uploaded file
            if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile)) {
                echo "File is valid, and was successfully uploaded.\n";
            } else {
                echo "Possible file upload attack!\n";
            }
        } else {
            echo "File is too large. Maximum size is 2MB.\n";
        }
    } else {
        echo "Invalid file type. Only JPG, JPEG, PNG, GIF, and PDF files are allowed.\n";
    }
}
?>