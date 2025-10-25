<?php
session_start();
require_once 'db.php';

// Ensure that the user is signed in by checking session variables
if (!isset($_SESSION['sign_name']) || !isset($_SESSION['sign_national_id'])) {
    header("Location: login.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Signed Successfully</title>
    <link rel="stylesheet" href="css/sign2.css">
	 <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	 <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="success-container">
    <h1>Signed Successfully</h1>
    <p>Thank you! Your signature has been successfully recorded.</p>
    <p>Your information has been updated, and you can now proceed with the next steps.</p>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

</div>

</body>
</html>
