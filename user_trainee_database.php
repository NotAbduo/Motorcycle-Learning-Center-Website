<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['staff', 'registration'])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_trainee'])) {
    $name = trim($_POST['name']);
    $national_id = trim($_POST['national_id']);
    $phone_number = trim($_POST['phone_number']);
    $added_by = $_SESSION['name'];

    // optional batch field
    $batch = !empty($_POST['batch']) ? $_POST['batch'] : null;

    // ✅ Server-side numeric validation for national_id
    if (!ctype_digit($national_id)) {
        $error_message = "National ID must contain only numbers.";
    } else {
        // Check if national_id already exists
        $check_stmt = $conn->prepare("SELECT national_id FROM trainees WHERE national_id = ?");
        $check_stmt->bind_param("s", $national_id);
        $check_stmt->execute();
        $check_stmt->store_result();

        if ($check_stmt->num_rows > 0) {
            $error_message = "A trainee with this National ID already exists.";
        } else {
            $source = $_POST['source'] ?? 'Individual';
            $gender = $_POST['gender'] ?? 'male';

            $stmt = $conn->prepare("INSERT INTO trainees (national_id, name, phone_number, added_by, is_active, source, gender, batch) VALUES (?, ?, ?, ?, 1, ?, ?, ?)");
            $stmt->bind_param("sssssss", $national_id, $name, $phone_number, $added_by, $source, $gender, $batch);

            if ($stmt->execute()) {
                $success_message = "Trainee added successfully!";
            } else {
                $error_message = "Failed to add trainee.";
            }
            $stmt->close();
        }

        $check_stmt->close();
    }
}

$sourceOptions = [];
$result = $conn->query("SELECT name FROM sources ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $sourceOptions[] = $row['name'];
}

// Ensure "Individual" is included as default if no data yet
if (empty($sourceOptions)) {
    $sourceOptions[] = 'Individual';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainee Database</title>
    <link rel="stylesheet" href="css/trainee2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<!-- Fixed Back Button with Icon -->
<a href="user_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>

<div class="dashboard-container">
    <h1><i class="fas fa-user-plus"></i> Add New Trainee</h1>

    <?php if (isset($success_message)) echo "<p class='success'><i class='fas fa-check-circle'></i> $success_message</p>"; ?>
    <?php if (isset($error_message)) echo "<p class='error'><i class='fas fa-times-circle'></i> $error_message</p>"; ?>

    <form method="POST" autocomplete="off">
        <input type="text" name="name" placeholder="Trainee Name" required autocomplete="new-password">

        <!-- ✅ Client-side numeric validation -->
        <input type="text" 
               name="national_id" 
               placeholder="National ID" 
               required 
               autocomplete="new-password"
               pattern="[0-9]+"
               inputmode="numeric"
               title="National ID must contain only numbers">

        <input type="text" name="phone_number" placeholder="Phone Number" autocomplete="new-password">

        <!-- New Batch Field (Optional) -->
        <input type="text" name="batch" placeholder="Batch (Optional)" autocomplete="new-password"><br><br/>

        <select class="tail-dropdown" name="source" required>
            <?php foreach ($sourceOptions as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= $option === 'Individual' ? 'selected' : '' ?>>
                    <?= htmlspecialchars($option) ?>
                </option>
            <?php endforeach; ?>
        </select><br><br/>

        <!-- Gender Dropdown -->
        <select class="tail-dropdown" name="gender" required>
            <option value="male" selected>Male</option>
            <option value="female">Female</option>
        </select><br><br/>

        <button type="submit" name="add_trainee"><i class="fas fa-user-plus"></i> Add Trainee</button>
    </form>

    <br>
    <?php
    $targetPage = ($_SESSION['role'] === 'staff') 
        ? "user_view_trainees.php" 
        : "registration_view_trainees.php";
    ?>

    <a href="<?= $targetPage ?>">
        <button><i class="fas fa-list-ul"></i> View All Trainees</button>
    </a>
</div>

</body>
</html>
