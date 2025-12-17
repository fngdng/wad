<?php
// lib/db.php
$host = 'localhost';
$dbname = 'studentNO'; // IMPORTANT: Database name must match the one you created in PHPMyAdmin
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection error: " . $e->getMessage());
}
?>