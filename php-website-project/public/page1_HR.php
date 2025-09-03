<?php
// page1.php - Minimal safeguards against file upload vulnerabilities

$uploadDir = 'uploads/';
$uploadedFile = '';
$message = '';

// Ensure upload directory exists
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $uploadFile = $uploadDir . basename($_FILES['userfile']['name']);
    $fileType = strtolower(pathinfo($uploadFile, PATHINFO_EXTENSION));

    // Check file type (only allow certain types)
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    if (in_array($fileType, $allowedTypes)) {
        if (move_uploaded_file($_FILES['userfile']['tmp_name'], $uploadFile)) {
            $message = "File is valid, and was successfully uploaded.";
            $uploadedFile = $uploadFile;
        } else {
            $message = "Possible file upload attack!";
        }
    } else {
        $message = "File type not allowed.";
    }
}

// Get list of uploaded files
$uploadedFiles = [];
if (is_dir($uploadDir)) {
    $files = scandir($uploadDir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $uploadDir . $file;
            $fileType = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            if (in_array($fileType, ['jpg', 'jpeg', 'png', 'gif'])) {
                $uploadedFiles[] = $filePath;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Page 1 - File Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        .upload-form {
            background: #f5f5f5;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .uploaded-image {
            max-width: 100%;
            max-height: 400px;
            border: 2px solid #ddd;
            border-radius: 8px;
            margin: 10px 0;
        }
        .gallery {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .gallery-item {
            text-align: center;
        }
        .gallery-item img {
            max-width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 4px;
            border: 1px solid #ddd;
        }
        .gallery-item p {
            margin: 5px 0;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <h1>Upload a File</h1>
    
    <div class="upload-form">
        <form enctype="multipart/form-data" action="page1_HR.php" method="POST">
            <input type="file" name="userfile" required>
            <input type="submit" value="Upload">
        </form>
    </div>

    <?php if ($message): ?>
        <div class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($uploadedFile && file_exists($uploadedFile)): ?>
        <h2>Recently Uploaded Image:</h2>
        <img src="<?php echo htmlspecialchars($uploadedFile); ?>" alt="Uploaded Image" class="uploaded-image">
    <?php endif; ?>

    <?php if (!empty($uploadedFiles)): ?>
        <h2>Uploaded Images Gallery:</h2>
        <div class="gallery">
            <?php foreach ($uploadedFiles as $file): ?>
                <div class="gallery-item">
                    <img src="<?php echo htmlspecialchars($file); ?>" alt="Uploaded Image">
                    <p><?php echo htmlspecialchars(basename($file)); ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</body>
</html>