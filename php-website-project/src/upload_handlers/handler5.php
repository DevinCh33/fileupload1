<?php
session_start();

// Check if the user is authenticated
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

// Handle file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['file'])) {
        $file = $_FILES['file'];
        
        // Validate file type and size
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        $maxFileSize = 2 * 1024 * 1024; // 2MB

        if (in_array($file['type'], $allowedTypes) && $file['size'] <= $maxFileSize) {
            // Validate file content (basic check)
            $fileContent = file_get_contents($file['tmp_name']);
            if (strpos($fileContent, '<?php') === false) {
                // Move the uploaded file to a secure directory
                $uploadDir = __DIR__ . '/uploads/';
                $uploadFilePath = $uploadDir . basename($file['name']);

                if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
                    // Log the upload activity
                    file_put_contents('upload_log.txt', "File uploaded: " . $file['name'] . " by user ID: " . $_SESSION['user_id'] . "\n", FILE_APPEND);
                    echo "File uploaded successfully.";
                } else {
                    echo "Failed to move uploaded file.";
                }
            } else {
                echo "Invalid file content.";
            }
        } else {
            echo "Invalid file type or size exceeded.";
        }
    } else {
        echo "No file uploaded.";
    }
}
?>