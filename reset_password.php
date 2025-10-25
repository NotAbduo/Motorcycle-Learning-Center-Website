<?php
session_start();

// Set the same timezone as in forgot_password.php
date_default_timezone_set('Asia/Muscat');

require_once 'db.php';

$error = '';
$success = '';
$token = '';

// Check if token is provided
if (isset($_GET['token'])) {
    $token = $_GET['token'];
    
    // TIMEZONE FIX: Use consistent timezone for comparison
    $current_time = date('Y-m-d H:i:s');
    
    // Verify token with timezone-aware comparison
    $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $error = "Invalid reset link.";
    } else {
        $reset_data = $result->fetch_assoc();
        
        // TIMEZONE FIX: Compare times properly
        $expires_timestamp = strtotime($reset_data['expires_at']);
        $current_timestamp = time();
        
        if ($expires_timestamp < $current_timestamp) {
            $error = "This reset link has expired. Please request a new one.";
            // Delete expired token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->bind_param("s", $token);
            $stmt->execute();
        }
    }
} else {
    $error = "No reset token provided.";
}

// Process password reset
if ($_SERVER["REQUEST_METHOD"] == "POST" && !$error) {
    $new_password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    
    // Validate passwords
    if (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Get email from token (with timezone-aware check)
        $current_time = date('Y-m-d H:i:s');
        $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > ?");
        $stmt->bind_param("ss", $token, $current_time);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $reset_data = $result->fetch_assoc();
            $email = $reset_data['email'];
            
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password in database
            $stmt = $conn->prepare("UPDATE employees SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            
            if ($stmt->execute()) {
                // Delete used token
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                $stmt->bind_param("s", $token);
                $stmt->execute();
                
                $success = "Your password has been reset successfully. You can now login with your new password.";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "Invalid or expired reset link.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    <h2>Reset Password</h2>
    
    <?php if($success): ?>
        <p class="success"><?php echo $success; ?></p>
        <p><a href="login.php" class="button">Go to Login</a></p>
    <?php elseif($error): ?>
        <p class="error"><?php echo $error; ?></p>
        <?php if(strpos($error, 'expired') !== false): ?>
            <p>The link has expired. <a href="forgot_password.php">Request a new reset link</a></p>
        <?php else: ?>
            <p><a href="forgot_password.php">Request a new reset link</a></p>
        <?php endif; ?>
    <?php else: ?>
        <form method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="password" name="password" placeholder="New Password" required minlength="6"><br>
            <input type="password" name="confirm_password" placeholder="Confirm New Password" required minlength="6"><br>
            <button type="submit">Reset Password</button>
        </form>
        
        <!-- Show token info for debugging -->
        <?php if(isset($reset_data)): ?>
        <p style="font-size: 12px; color: #666; margin-top: 15px;">
            Link expires at: <?php echo date('Y-m-d H:i:s T', strtotime($reset_data['expires_at'])); ?><br>
            Current time: <?php echo date('Y-m-d H:i:s T'); ?>
        </p>
        <?php endif; ?>
    <?php endif; ?>
    
    <p class="back-to-login">
        <a href="login.php">Back to Login</a>
    </p>
</div>

</body>
</html>