<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");  // Redirect to login if not logged in or not an admin
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Supervisor Dashboard</title>
    <link rel="stylesheet" href="css/approvals_page.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome via public CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>


<div class="dashboard-container">
    <div class="tab-grid">
			<a href="manage_ola_columns.php" class="tab">
		<i class="fa-solid fa-clipboard-check"></i>
		<span>OLA</span>
		</a>
			<a href="share_database.php" class="tab">
		<i class="fa-solid fa-clipboard-check"></i>
		<span>Other Companies</span>
		</a>
    </div>
</div>
</body>
</html>




