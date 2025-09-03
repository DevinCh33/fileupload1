<?php
// This file contains tests for the application, ensuring that the login and upload functionalities work as intended.

// Include necessary files for testing
require_once '../src/db.php';
require_once '../src/auth.php';

// Function to test the login functionality
function test_login() {
    // Simulate a login request
    $username = "admin' OR '1'='1"; // SQL injection example
    $password = "password";
    
    // Attempt to log in
    $result = login($username, $password); // Assuming login function exists in auth.php
    
    // Check if login was successful (vulnerable to SQL injection)
    if ($result) {
        echo "Login successful!";
    } else {
        echo "Login failed.";
    }
}

// Function to test file upload functionality
function test_file_upload($page) {
    // Simulate a file upload request
    $file = 'test_file.txt'; // Example file
    
    // Call the appropriate upload handler based on the page
    switch ($page) {
        case 1:
            include '../src/upload_handlers/handler1.php';
            break;
        case 2:
            include '../src/upload_handlers/handler2.php';
            break;
        case 3:
            include '../src/upload_handlers/handler3.php';
            break;
        case 4:
            include '../src/upload_handlers/handler4.php';
            break;
        case 5:
            include '../src/upload_handlers/handler5.php';
            break;
        default:
            echo "Invalid page.";
            return;
    }
    
    // Check if the upload was successful
    if (file_exists($file)) {
        echo "File upload successful!";
    } else {
        echo "File upload failed.";
    }
}

// Run tests
test_login();
test_file_upload(1);
test_file_upload(2);
test_file_upload(3);
test_file_upload(4);
test_file_upload(5);
?>