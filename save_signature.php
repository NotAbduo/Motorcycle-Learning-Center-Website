<?php
session_start();
require_once 'db.php'; // ensure this connects to your database correctly

if (!isset($_SESSION['sign_national_id']) || !isset($_POST['sigImageData'])) {
    die("Invalid access.");
}

$nationalId = preg_replace('/[^a-zA-Z0-9]/', '', $_SESSION['sign_national_id']); // Sanitize input
$imageData = $_POST['sigImageData'];

$folder = 'signatures/';
if (!is_dir($folder)) {
    mkdir($folder, 0755, true);
}

// Clean and decode base64 image
$imageData = str_replace('data:image/png;base64,', '', $imageData);
$imageData = str_replace(' ', '+', $imageData);
$imageBinary = base64_decode($imageData);

if ($imageBinary === false) {
    die("Failed to decode image.");
}

$filePath = $folder . $nationalId . '.png';

if (file_put_contents($filePath, $imageBinary)) {
    // Update the database
    $stmt = $conn->prepare("UPDATE trainees SET sign = 1 WHERE national_id = ?");
    if ($stmt) {
        $stmt->bind_param("s", $nationalId);
        if ($stmt->execute()) {

            // Optionally redirect:
            header("Location: signed_successfully.php");
            exit();
        } else {
            echo "Failed to update database: " . $stmt->error;
        }
        $stmt->close();
    } else {
        echo "Failed to prepare statement: " . $conn->error;
    }
} else {
    echo "Failed to save signature image.";
}
?>
