<?php
// Simple test to check if the backend is working
echo "Backend test file is working!";
echo "<br>Current directory: " . getcwd();
echo "<br>PHP version: " . phpversion();
echo "<br>Files in current directory:";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<br> - " . $file;
    }
}
?>
