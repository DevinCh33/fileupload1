<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $file = $_FILES['fileToUpload'];
        $uploadDirectory = 'uploads/';
        $uploadFilePath = $uploadDirectory . basename($file['name']);
        
        // Validate file type
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (in_array($file['type'], $allowedTypes) && $file['size'] <= 2000000) {
            // Move the uploaded file to the designated directory
            if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                // Log the upload activity
                file_put_contents('upload_log.txt', "File uploaded: " . $uploadFilePath . "\n", FILE_APPEND);
                echo "File uploaded successfully.";
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file type or size exceeded.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 5 - Secure File Upload</title>
</head>
<body>
    <h1>Upload a File</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" required>
        <button type="submit">Upload</button>
    </form>
    <a href="index.php">Back to Home</a>
</body>
</html>