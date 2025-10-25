<?php
session_start();
require_once 'db.php';

// If already logged in, skip
if (isset($_SESSION['trainee_viewer']) && $_SESSION['trainee_viewer'] === true) {
    header("Location: ola_view_trainees.php");
    exit();
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputPassword = $_POST['password'] ?? '';

    $result = $conn->query("SELECT * FROM view_passwords LIMIT 1");
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($inputPassword, $row['password_hash'])) {
            $_SESSION['trainee_viewer'] = true;
            header("Location: ola_view_trainees.php");
            exit();
        } else {
            $error = "❌ Incorrect password";
        }
    } else {
        $error = "❌ No password is set by supervisor";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Enter Password</title>
    <link rel="stylesheet" href="css/manage_ola_columns.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <div class="dashboard-container">
        <h1>🔒 Enter Password</h1>

        <?php if ($error): ?>
            <div class="msg warning"><?= $error ?></div>
        <?php endif; ?>

        <form method="post">
            <fieldset>
                <legend>Access Trainee Data</legend>
                <input type="password" name="password" placeholder="Enter password" required style="width:100%; padding:10px; border:2px solid #ff4b2b; border-radius:8px;">
            </fieldset>
            <button type="submit"><i class="fas fa-sign-in-alt"></i> Enter</button>
        </form>
    </div>
</body>
</html>

