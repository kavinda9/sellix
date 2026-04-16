<?php
// includes/db.php

$host     = 'localhost';
$dbname   = 'marketplace_db';
$username = 'root';        // Default XAMPP user
$password = '';            // Default XAMPP has empty password

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    // Show clear error during development
    die("Database Connection Failed: " . $e->getMessage());
}
?>