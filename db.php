<?php
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'motorcycle'; // Replace with your DB name

$conn = new mysqli($host, $user, $password, $database);

// Check for connection error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
