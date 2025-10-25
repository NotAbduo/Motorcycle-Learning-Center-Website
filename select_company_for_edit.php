<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all unique companies (sources)
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

<button onclick="goBack()" class="back-button">
    <i class="fas fa-arrow-left"></i>
</button>

<script>
function goBack() {
    // Check if there's a previous page in the browser history
    if (window.history.length > 1) {
        window.history.back();
    } else {
        // Fallback: redirect to a default page if no history
        window.location.href = '<?= ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'supervisor')
            ? 'admin_view_trainees.php'
            : 'registration_view_trainees.php' ?>';
    }
}
</script>

<div class="form-box">
    <h2>Select Company for Multi-Trainee Edit</h2>

    <form method="POST" action="select_trainees_for_edit.php">
        <label for="company">Company:</label>
        <select name="selected_company" id="company" required>
            <option value="" disabled selected>Select company</option>
            <?php foreach ($companies as $company): ?>
                <option value="<?= htmlspecialchars($company['source']) ?>">
                    <?= htmlspecialchars($company['source']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit"><i class="fas fa-arrow-right"></i> Continue</button>
    </form>
</div>

</body>
</html>
