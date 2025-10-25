
<?php
// 1. First, update your login.php to include the "Forgot Password?" link

session_start();
require_once 'db.php'; // One line to include the database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare and bind query to check if the email exists
    $stmt = $conn->prepare("SELECT id, name, password, role, national_id, is_active FROM employees WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($id, $name, $hashed_password, $role, $national_id, $is_active);

    if ($stmt->num_rows > 0) {
        $stmt->fetch();

        // Check if account is active
        if ($is_active == 0) {
            $error = "Your account is deactivated. Please contact the administrator.";
        }
        // Check if the password matches
        elseif (password_verify($password, $hashed_password)) {
            $_SESSION['name'] = $name;
            $_SESSION['role'] = $role;
            $_SESSION['user_id'] = $national_id;
            $_SESSION['is_active'] = $is_active;
            $_SESSION['national_id'] = $national_id;

            // Redirect based on user role
            if ($role == 'admin') {
                header("Location: admin_dashboard.php");
            } elseif ($role == 'supervisor') {
                header("Location: supervisor_dashboard.php");
            } else {
                header("Location: user_dashboard.php");
            }

            exit();
        } else {
            $error = "Invalid password.";
        }
    } else {
        $error = "No user found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    <h2>Login to Your Account</h2>
    <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="password" name="password" placeholder="Password" required><br>
        <button type="submit">Login</button>
    </form>
	<br/>
    <p class="forgot-password">
        <a href="forgot_password.php">Forgot your password?</a>
    </p>
</div>

</body>
</html>