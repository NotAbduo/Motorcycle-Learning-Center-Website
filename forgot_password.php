
<?php

session_start();

// Set timezone - Choose one that matches your server or local timezone
// For Oman/Muscat timezone:
date_default_timezone_set('Asia/Muscat');

// Alternative timezones you might need:
// date_default_timezone_set('UTC'); // Universal time
// date_default_timezone_set('Asia/Dubai'); // UAE time (same as Oman)

require_once 'db.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    
    // Check if email exists in database
    $stmt = $conn->prepare("SELECT id, name FROM employees WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Generate a unique reset token
        $token = bin2hex(random_bytes(32));
        
        // TIMEZONE FIX: Use consistent datetime format and add more time buffer
        $expires = date('Y-m-d H:i:s', strtotime('+2 hours')); // Increased to 2 hours for safety
        
        // First, delete any existing tokens for this email
        $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        
        // Insert new token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("sss", $email, $token, $expires);
        
        if ($stmt->execute()) {
            // Send reset email
            $reset_link = "https://mlc-oman.com/reset_password.php?token=" . $token;
            $subject = "Password Reset Request - MLC Oman";
            $body = "
            <html>
            <head>
                <title>Password Reset Request</title>
            </head>
            <body>
                <h2>Password Reset Request</h2>
                <p>Dear " . htmlspecialchars($user['name']) . ",</p>
                <p>You have requested to reset your password for your MLC Oman account.</p>
                <p>Please click the link below to reset your password:</p>
                <p><a href='" . $reset_link . "' style='background-color: #4CAF50; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Reset Password</a></p>
                <p>Or copy and paste this link in your browser:</p>
                <p>" . $reset_link . "</p>
                <p><strong>This link will expire in 2 hours.</strong></p>
                <p>If you did not request this password reset, please ignore this email.</p>
                <br>
                <p>Best regards,<br>MLC Oman Team</p>
                <p><small>Link generated at: " . date('Y-m-d H:i:s T') . "</small></p>
            </body>
            </html>
            ";
            
            $headers = "MIME-Version: 1.0" . "\r\n";
            $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
            $headers .= "From: noreply@mlc-oman.com" . "\r\n";
            
            if (mail($email, $subject, $body, $headers)) {
                $message = "A password reset link has been sent to your email address. The link will expire in 2 hours.";
            } else {
                $error = "Failed to send email. Please try again later.";
            }
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        // Don't reveal if email exists or not for security
        $message = "If an account with that email exists, a password reset link has been sent.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="login-container">
    <h2>Forgot Password</h2>
    
    <?php if($message): ?>
        <p class="success"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <?php if($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <p>Enter your email address and we'll send you a link to reset your password.</p>
        <input type="email" name="email" placeholder="Enter your email" required><br>
        <button type="submit">Send Reset Link</button>
    </form>
    
    <p class="back-to-login">
        <a href="login.php">Back to Login</a>
    </p>
    
    <!-- Show current server time for debugging -->
    <p style="font-size: 12px; color: #666; margin-top: 20px;">
        Current server time: <?php echo date('Y-m-d H:i:s T'); ?>
    </p>
</div>

</body>
</html>