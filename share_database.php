<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch all unique companies
$stmt = $conn->prepare("SELECT DISTINCT source FROM trainees WHERE source IS NOT NULL AND source != '' ORDER BY source ASC");
$stmt->execute();
$result = $stmt->get_result();
$companies = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Companies</title>
	<link rel="stylesheet" href="css/select_company.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .form-box {max-width:500px;margin:30px auto;background:#fff;padding:25px;border-radius:10px;box-shadow:0 5px 15px rgba(0,0,0,0.2);}
        .company-list {max-height:300px;overflow-y:auto;border:1px solid #ccc;padding:10px;border-radius:8px;margin-bottom:15px;}
        .company-item {margin-bottom:8px;display:flex;align-items:center;}
        .company-item input {margin-right:10px;transform:scale(1.2);cursor:pointer;}
        .company-item label {cursor:pointer;font-size:15px;}
        button {width:100%;padding:12px;background:#0078D7;color:#fff;border:none;border-radius:6px;font-size:16px;cursor:pointer;}
        button:hover {background:#005bb5;}
    </style>
</head>
<body>
<a href="shares_page.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="form-box">
    <h2>Select Companies to Share</h2>

    <form method="POST" action="generate_share_link.php">
        <div class="company-list">
            <?php foreach ($companies as $company): ?>
                <div class="company-item">
                    <input type="checkbox" name="selected_companies[]" value="<?= htmlspecialchars($company['source']) ?>" id="<?= htmlspecialchars($company['source']) ?>">
                    <label for="<?= htmlspecialchars($company['source']) ?>"><?= htmlspecialchars($company['source']) ?></label>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit"><i class="fas fa-arrow-right"></i> Continue</button>
    </form>
</div>

</body>
</html>


