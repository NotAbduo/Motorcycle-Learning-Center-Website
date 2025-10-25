<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
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

// Handle update
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $national_id = $_POST['national_id'];
    $phone_number = $_POST['phone_number'];
    $quiz = $_POST['quiz'];
    $sign = isset($_POST['sign']) ? 1 : 0;
    $payment = isset($_POST['payment']) ? 1 : 0;

    // ✅ FIX: Read the actual value (0 or 1)
    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;

    $source = $_POST['source'];
    $batch = trim($_POST['batch']); 
    $gender = $_POST['gender'];
    $number_of_trails = intval($_POST['number_of_trails']);
    $try_road = intval($_POST['try_road']);   // ✅ NEW FIELD

    $stmt = $conn->prepare("UPDATE trainees 
                            SET name=?, national_id=?, phone_number=?, quiz=?, sign=?, payment=?, is_active=?, source=?, batch=?, gender=?, number_of_trails=?, try_road=? 
                            WHERE id=?");
    $stmt->bind_param("sssisiisssiii", $name, $national_id, $phone_number, $quiz, $sign, $payment, $is_active, $source, $batch, $gender, $number_of_trails, $try_road, $trainee_id);

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

// Fetch source options
$sourceOptions = [];
$result = $conn->query("SELECT name FROM sources ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $sourceOptions[] = $row['name'];
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
        <label><i class="fas fa-database"></i> Source:</label><br>
        <select class="tail-dropdown" name="source" required>
            <?php foreach ($sourceOptions as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $trainee['source'] === $option ? 'selected' : '' ?>>
                    <?= htmlspecialchars($option) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br>

        <label><i class="fas fa-layer-group"></i> Batch:</label><br>
        <input type="text" name="batch" value="<?= htmlspecialchars($trainee['batch']) ?>" placeholder="e.g., Batch 2024-A"><br><br>

        <label><i class="fas fa-venus-mars"></i> Gender:</label><br>
        <select class="tail-dropdown" name="gender" required>
            <option value="male" <?= $trainee['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
            <option value="female" <?= $trainee['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
        </select><br><br>

        <label><i class="fas fa-user"></i> Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($trainee['name']) ?>" required><br><br>

        <label><i class="fas fa-id-card"></i> National ID:</label><br>
        <input type="text" name="national_id" value="<?= htmlspecialchars($trainee['national_id']) ?>" required><br><br>

        <label><i class="fas fa-phone"></i> Phone Number:</label><br>
        <input type="text" name="phone_number" value="<?= htmlspecialchars($trainee['phone_number']) ?>"><br><br>

        <label><i class="fas fa-clipboard-question"></i> Quiz Score:</label><br>
        <input type="number" name="quiz" value="<?= htmlspecialchars($trainee['quiz']) ?>" min="0" max="100"><br><br>

        <!-- Try 8 -->
        <label><i class="fas fa-repeat"></i> Try 8:</label><br>
        <select class="tail-dropdown" name="number_of_trails" required>
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= $trainee['number_of_trails'] == $i ? 'selected' : '' ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select><br><br>

        <!-- ✅ Try Road -->
        <label><i class="fas fa-road"></i> Try Road:</label><br>
        <select class="tail-dropdown" name="try_road" required>
            <?php for ($i = 0; $i <= 10; $i++): ?>
                <option value="<?= $i ?>" <?= $trainee['try_road'] == $i ? 'selected' : '' ?>>
                    <?= $i ?>
                </option>
            <?php endfor; ?>
        </select><br><br>

        <label><i class="fas fa-toggle-on"></i> Status:</label><br>
        <select name="is_active" class="tail-dropdown">
            <option value="1" <?= ((int)$trainee['is_active'] === 1) ? 'selected' : '' ?> style="color: orange;">Ongoing</option>
            <option value="0" <?= ((int)$trainee['is_active'] === 0) ? 'selected' : '' ?> style="color: green;">Completed</option>
        </select><br><br>

        <label><i class="fas fa-file-signature"></i> Signed Contract:</label><br>
        <input type="checkbox" name="sign" <?= $trainee['sign'] ? 'checked' : '' ?>><br><br>

        <label><i class="fa-solid fa-credit-card"></i> Payment Contract:</label><br>
        <input type="checkbox" name="payment" <?= $trainee['payment'] ? 'checked' : '' ?>><br><br>

        <button type="submit" name="update"><i class="fas fa-save"></i> Update</button>
    </form>

    <br>
    <a href="admin_view_trainees.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
</div>

</body>
</html>
