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
    <link rel="stylesheet" href="css/dashboard2.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Font Awesome via public CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<!-- Logout Button (Left corner) -->
<a href="logout.php" class="logout-button">
    <i class="fas fa-sign-out-alt fa-flip-horizontal"></i> Logout
</a>


<div class="dashboard-container">
    <h1>Welcome Supervisor, <?php echo $_SESSION['name']; ?>!</h1>

    <div class="tab-grid">
			<a href="approvals_page.php" class="tab">
		<i class="fa-solid fa-clipboard-check"></i>
		<span>Approvals</span>

				<a href="hours_page_supervisor.php" class="tab"> 
			<i class="fa-solid fa-motorcycle"></i>
			<span>Submit Hours</span>
		</a>
		<a href="all_instructors_logs.php" class="tab">
			<i class="fa-solid fa-user-clock"></i>
			<span>Instructors History</span>
		</a>
				<a href="history.php" class="tab">
			<i class="fa-solid fa-clock-rotate-left"></i>
			<span>History</span>
		</a>
		        <a href="admin_trainee_database.php" class="tab">
            <i class="fa-solid fa-database"></i>
            <span>Trainee Database</span>
        </a>
		<a href="shares_page.php" class="tab">
            <i class="fa-solid fa-share-from-square"></i>
            <span>Share Database</span>
        </a>
		<a href="companies.php" class="tab">
			<i class="fa-solid fa-building"></i>
			<span>Companies</span>
		</a>
		<a href="delete_hours_page.php" class="tab">
			<i class="fa-solid fa-trash"></i>
			<span>Delete Hours</span>
		</a>

        <a href="staff.php" class="tab">
            <i class="fa-solid fa-users"></i>
            <span>Staff</span>
        </a>
		<a href="edit_quiz.php" class="tab">
            <i class="fa-solid fa-pen-to-square"></i>
            <span>Edit Quiz</span>
        </a>

        <a href="quiz_start.php" class="tab">
            <i class="fa-solid fa-question-circle"></i>
            <span>Quizzes</span>
        </a>
        <a href="sign_start.php" class="tab">
            <i class="fa-solid fa-file-signature"></i>
            <span>Contract Sign</span>
        </a>
		<a href="payment_start.php" class="tab">
            <i class="fa-solid fa-credit-card"></i>
            <span>Payment</span>
        </a>



		<a href="billing.php" class="tab">
			<i class="fa-solid fa-file-invoice"></i>
			<span>Billing</span>
		</a>
		<a href="comment_start.php" class="tab">
			<i class="fa-solid fa-comment"></i>
			<span>Comment</span>
		</a>
    </div>
</div>

</body>
</html>




