<?php
// page2.php
session_start();
$upload_dir = 'uploads/';
$allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
$max_file_size = 2 * 1024 * 1024; // 2MB

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        // Check file type
        if (in_array($file['type'], $allowed_types) && $file['size'] <= $max_file_size) {
            $target_file = $upload_dir . basename($file['name']);
            if (move_uploaded_file($file['tmp_name'], $target_file)) {
                echo "File uploaded successfully.";
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file type or file too large.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 2 - Moderate File Upload</title>
</head>
<body>
    <h1>Upload a File</h1>
    <form action="page2.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file" required>
        <button type="submit">Upload</button>
    </form>
</body>
</html>