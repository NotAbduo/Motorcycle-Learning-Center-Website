<?php
session_start();

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor'){
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
				<a href="submit_hours_start.php" class="tab"> 
			<i class="fa-solid fa-motorcycle"></i>
			<span>Submit Hours</span>
		</a>
<a href="billing_hours.php" class="tab"> 
  <i class="fa-solid fa-dollar-sign"></i>
  <span>Billed Hours</span>
</a>
<a href="submit_multiple_instructors_late.php" class="tab"> 
<i class="fa-solid fa-exclamation-circle"></i>
  <span>Submit late Hours</span>
</a>
<a href="submit_multiple_instructors_billing_late.php" class="tab"> 
<i class="fa-solid fa-exclamation-circle"></i>
  <span>Submit late Billing Hours</span>
</a>
<a href="system_controls.php" class="tab">
  <i class="fa-solid fa-sliders"></i>
  <span>Hours Settings</span>
</a>


    </div>
</div>

</body>
</html>




