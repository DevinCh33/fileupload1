<?php
// page1.php - Minimal safeguards against file upload vulnerabilities

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadDir = 'uploads/';
    
    // Ensure upload directory exists
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);
    $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

    // Check file type (only allow certain types)
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile)) {
            echo "File is valid, and was successfully uploaded.\n";
        } else {
            echo "Possible file upload attack!\n";
        }
    } else {
        echo "File type not allowed.\n";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 1 - File Upload</title>
</head>
<body>
    <h1>Upload a File</h1>
    <form enctype="multipart/form-data" action="page1_HR.php" method="POST">
        <input type="file" name="userfile" required>
        <input type="submit" value="Upload">
    </form>
</body>
</html>