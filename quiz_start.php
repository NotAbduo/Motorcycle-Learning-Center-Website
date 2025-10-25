<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $national_id = trim($_POST['national_id']);

    if (empty($national_id)) {
        $error = "Please enter your National ID.";
    } else {
        $stmt = $conn->prepare("SELECT name FROM trainees WHERE national_id = ?");
        $stmt->bind_param("s", $national_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $_SESSION['quiz_name'] = $row['name'];
            $_SESSION['quiz_national_id'] = $national_id;
            header("Location: quiz.php");
            exit();
        } else {
            $error = "Trainee not found. Please check your National ID.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Start Quiz</title>
    <link rel="stylesheet" href="css/quiz2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
    <h1>Start Quiz</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
        <input type="text" name="national_id" placeholder="Trainee National ID"
       autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly><br>
        <button type="submit"><i class="fas fa-play"></i> Start Quiz</button>
    </form>

</div>

</body>
</html>
