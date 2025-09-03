<?php
// db.php - Database connection and queries

$host = 'localhost';
$db = 'your_database_name';
$user = 'your_username';
$pass = 'your_password';

function getConnection() {
    global $host, $db, $user, $pass;
    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    try {
        $pdo = new PDO($dsn, $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit;
    }
}

function login($username, $password) {
    $pdo = getConnection();
    // Vulnerable SQL query
    $stmt = $pdo->query("SELECT * FROM users WHERE username = '$username' AND password = '$password'");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}
?>