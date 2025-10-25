<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['selected_instructors'])) {
    header("Location: login.php");
    exit();
}

$instructors = json_decode($_POST['selected_instructors'], true);

// Get all unique companies from trainees.source
$stmt = $conn->prepare("SELECT DISTINCT source FROM trainees WHERE source IS NOT NULL AND source != '' ORDER BY source ASC");
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Company</title>
    <link rel="stylesheet" href="css/select_company.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'submit_hours_start.php' : ($_SESSION['role'] == 'supervisor' ? 'submit_hours_start.php' : 'submit_hours_start.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="form-box">
    <h2>Select Company (From Trainee Sources)</h2>

    <form method="POST" action="select_trainees_by_company.php">
        <input type="hidden" name="selected_instructors" value='<?= htmlspecialchars(json_encode($instructors)) ?>'>

        <label for="company">Company:</label>
        <select name="selected_company" id="company" required>
            <option value="" disabled selected>Select company</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= htmlspecialchars($company['source']) ?>">
                    <?= htmlspecialchars($company['source']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="instructor-preview">
            <strong>Selected Instructors:</strong>
            <ul>
                <?php foreach ($instructors as $inst): ?>
                    <li><?= htmlspecialchars($inst['name']) ?> (<?= htmlspecialchars($inst['national_id']) ?>)</li>
                <?php endforeach; ?>
            </ul>
        </div>

        <button type="submit"><i class="fas fa-arrow-right"></i> Continue to Trainee Selection</button>
    </form>
</div>

</body>
</html>
