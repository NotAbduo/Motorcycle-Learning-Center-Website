<?php
// db.php - PDO version of your MySQL connection
$host     = 'localhost';
$dbname   = 'motorcycle';  // your database name
$username = 'root';        // or your actual MySQL username
$password = '';            // leave empty if no password
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,         // fetch as associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                    // use native prepares
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
