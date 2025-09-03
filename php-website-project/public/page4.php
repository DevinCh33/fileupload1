<?php
// page4.php - Strong safeguards for file uploads

session_start();

// Define allowed file types and maximum file size
$allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
$maxFileSize = 2 * 1024 * 1024; // 2MB
$uploadDir = 'uploads/';

// Ensure the upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['fileToUpload'])) {
        $file = $_FILES['fileToUpload'];
        
        // Validate file type
        if (!in_array($file['type'], $allowedTypes)) {
            echo "Invalid file type.";
            exit;
        }

        // Validate file size
        if ($file['size'] > $maxFileSize) {
            echo "File size exceeds the limit.";
            exit;
        }

        // Validate file content (basic check)
        $fileContent = file_get_contents($file['tmp_name']);
        if (strpos($fileContent, '<?php') !== false) {
            echo "File contains invalid content.";
            exit;
        }

        // Move the uploaded file to the secure directory
        $targetFile = $uploadDir . basename($file['name']);
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            echo "File uploaded successfully.";
        } else {
            echo "Error uploading file.";
        }
    } else {
        echo "No file uploaded.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Page 4</title>
</head>
<body>
    <h1>Upload File - Page 4</h1>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="file" name="fileToUpload" required>
        <input type="submit" value="Upload">
    </form>
</body>
</html>