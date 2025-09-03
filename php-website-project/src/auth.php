<?php
session_start();

function login($username, $password) {
    include 'db.php';
    
    // Vulnerable SQL query
    $query = "SELECT * FROM users WHERE username = '$username' AND password = '$password'";
    $result = mysqli_query($conn, $query);
    
    if (mysqli_num_rows($result) > 0) {
        $_SESSION['user'] = $username;
        return true;
    } else {
        return false;
    }
}

function isLoggedIn() {
    return isset($_SESSION['user']);
}

function logout() {
    session_destroy();
    header("Location: index.php");
}
?>