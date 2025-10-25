<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['comment_name']) || !isset($_SESSION['comment_national_id'])) {
    header("Location: comment_start.php");
    exit();
}

$trainee_name = $_SESSION['comment_name'];
$trainee_id = $_SESSION['comment_national_id'];
$employee_id =  $_SESSION['national_id'];

$message = '';

// ---- 3. Handle POST request -------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $comment = trim($_POST['comment'] ?? '');

    if ($comment === '') {
        $message = 'Comment cannot be empty.';
    } else {
        // Insert if new, otherwise update the existing comment for this pair.
        $sql = "INSERT INTO `comment` (employee_national_id, trainee_national_id, comments)
                VALUES (:emp_id, :trainee_id, :comment)
                ON DUPLICATE KEY UPDATE comments = VALUES(comments)";

        $stmt = $pdo->prepare($sql);

        try {
            $stmt->execute([
                ':emp_id'     => $employee_id,
                ':trainee_id' => $trainee_id,
                ':comment'    => $comment
            ]);
            $message = 'Comment saved successfully.';
        } catch (PDOException $e) {
            // Log the exact error for debugging but show generic message to user.
            error_log($e->getMessage());
            $message = 'An error occurred while saving the comment. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Comment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="css/submit_hours2.css"> <!-- Your existing CSS file -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
    <h1>Add Comment for <?= htmlspecialchars($trainee_name) ?></h1>

    <?php if ($message): ?>
        <div class="<?= strpos($message, 'successfully') !== false ? 'success' : 'error' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="post">
        <div class="form-group">
            <label for="comment"><i class="fas fa-comments"></i> Your Comment</label>
            <textarea id="comment" name="comment" rows="6" placeholder="Write your comment here…" required style="padding: 12px; border-radius: 10px; border: 2px solid #ff4b2b; font-size: 1rem; color: #333; min-height: 120px; resize: vertical;"><?= isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : '' ?></textarea>
        </div>

        <div class="form-group" style="margin-top: 20px;">
            <button type="submit"><i class="fas fa-save"></i> Save Comment</button>
        </div>
    </form>
</div>

</body>
</html>
