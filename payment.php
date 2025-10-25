<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['pay_national_id'])) {
    header("Location: login.php");
    exit();
}

$national_id = $_SESSION['pay_national_id'];
$trainee_name = '';
$payment_status = '';
$success_msg = '';
$error_msg = '';

// Fetch trainee info
$stmt = $conn->prepare("SELECT name, payment FROM trainees WHERE national_id = ?");
$stmt->bind_param("s", $national_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $trainee_name = $row['name'];
    $payment_status = $row['payment'] == 1 ? 'Yes' : 'No';
} else {
    $error_msg = "Trainee not found.";
}

// Handle payment update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['payment_status'])) {
        $new_payment = $_POST['payment_status'] == 'yes' ? 1 : 0;
        $update_stmt = $conn->prepare("UPDATE trainees SET payment = ? WHERE national_id = ?");
        $update_stmt->bind_param("is", $new_payment, $national_id);
        if ($update_stmt->execute()) {
            $payment_status = $new_payment == 1 ? 'Yes' : 'No';
            $success_msg = "Payment status updated successfully.";
        } else {
            $error_msg = "Failed to update payment status.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Status</title>
    <link rel="stylesheet" href="css/payment_start.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>


<div class="dashboard-container">
    <h1>Payment Status</h1>

    <?php if ($error_msg): ?>
        <p class="error"><?= htmlspecialchars($error_msg) ?></p>
    <?php elseif ($trainee_name): ?>
        <p><strong>Name:</strong> <?= htmlspecialchars($trainee_name) ?></p>
        <p><strong>Paid:</strong> <?= htmlspecialchars($payment_status) ?></p>

        <?php if ($success_msg): ?>
            <p class="success"><?= htmlspecialchars($success_msg) ?></p>
        <?php endif; ?>

        <form method="POST">
            <button type="submit" name="payment_status" value="yes">
                <i class="fa-solid fa-check"></i> Yes, He Paid
            </button>
            <button type="submit" name="payment_status" value="no">
                <i class="fa-solid fa-xmark"></i> No, He Didn't
            </button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
