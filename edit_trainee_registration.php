<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registration') {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("No trainee ID provided.");
}

$trainee_id = intval($_GET['id']);

// Handle deletion
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare("DELETE FROM trainees WHERE id = ?");
    $stmt->bind_param("i", $trainee_id);
    if ($stmt->execute()) {
        header("Location: admin_view_trainees.php?deleted=true");
        exit();
    } else {
        $error = "Failed to delete trainee.";
    }
}

// Handle update - now 5 fields (added batch)
if (isset($_POST['update'])) {
    $payment = isset($_POST['payment']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
    $number_of_trails = intval($_POST['number_of_trails']);
    $try_road = intval($_POST['try_road']);
    $batch = !empty($_POST['batch']) ? $_POST['batch'] : null;

    $stmt = $conn->prepare("UPDATE trainees 
                            SET payment=?, is_active=?, number_of_trails=?, try_road=?, batch=? 
                            WHERE id=?");
    $stmt->bind_param("iiiisi", $payment, $is_active, $number_of_trails, $try_road, $batch, $trainee_id);

    if ($stmt->execute()) {
        $success = "Trainee updated successfully.";
        // Refresh trainee data after update
        $stmt = $conn->prepare("SELECT * FROM trainees WHERE id = ?");
        $stmt->bind_param("i", $trainee_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $trainee = $result->fetch_assoc();
    } else {
        $error = "Failed to update trainee.";
    }
}

// Fetch trainee data (if not already refreshed after update)
if (!isset($trainee)) {
    $stmt = $conn->prepare("SELECT * FROM trainees WHERE id = ?");
    $stmt->bind_param("i", $trainee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $trainee = $result->fetch_assoc();
}

if (!$trainee) {
    die("Trainee not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Trainee</title>
    <link rel="stylesheet" href="css/edit_trainee2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<div class="dashboard-container">
    <h1><i class="fas fa-user-edit"></i> Edit Trainee</h1>

    <?php if (isset($success)) echo "<p style='color: green;'><i class='fas fa-check-circle'></i> $success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color: red;'><i class='fas fa-exclamation-triangle'></i> $error</p>"; ?>

    <form method="POST">
        <label><i class="fas fa-toggle-on"></i> Status:</label><br>
        <select name="is_active" class="tail-dropdown">
            <option value="1" <?= ((int)$trainee['is_active'] === 1) ? 'selected' : '' ?> style="color: orange;">Ongoing</option>
            <option value="0" <?= ((int)$trainee['is_active'] === 0) ? 'selected' : '' ?> style="color: green;">Completed</option>
        </select><br><br>

        <label><i class="fa-solid fa-credit-card"></i> Payment Contract:</label><br>
        <input type="checkbox" name="payment" <?= $trainee['payment'] ? 'checked' : '' ?>><br><br>

        <label><i class="fas fa-repeat"></i> Try 8:</label><br>
        <select class="tail-dropdown" name="number_of_trails" required>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= $trainee['number_of_trails'] == $i ? 'selected' : '' ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select><br><br>

        <label><i class="fas fa-road"></i> Try Road:</label><br>
        <select class="tail-dropdown" name="try_road" required>
            <?php for ($i = 0; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= $trainee['try_road'] == $i ? 'selected' : '' ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select><br><br>

        <label><i class="fas fa-layer-group"></i> Batch:</label><br>
        <input type="text" name="batch" value="<?= htmlspecialchars($trainee['batch'] ?? '') ?>" placeholder="Batch (Optional)"><br><br>

        <button type="submit" name="update"><i class="fas fa-save"></i> Update</button>
    </form>

    <br>
    <a href="registration_view_trainees.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
</div>

</body>
</html>
