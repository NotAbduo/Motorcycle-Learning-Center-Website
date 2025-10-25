<?php
session_start();
require_once 'db.php';

// Only allow supervisors
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

// ── Handle form submission ───────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Reset all columns first
    if (isset($_POST['visible'])) {
        $conn->query("UPDATE trainee_columns SET is_visible = 0");
        foreach ($_POST['visible'] as $col) {
            $stmt = $conn->prepare("UPDATE trainee_columns SET is_visible = 1 WHERE column_name = ?");
            $stmt->bind_param("s", $col);
            $stmt->execute();
        }
    }

    // Update password if provided
    if (!empty($_POST['new_password'])) {
        $passwordHash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $conn->query("DELETE FROM view_passwords"); // keep only 1
        $stmt = $conn->prepare("INSERT INTO view_passwords (password_hash) VALUES (?)");
        $stmt->bind_param("s", $passwordHash);
        $stmt->execute();
    }

    // Update company filters
    $conn->query("DELETE FROM trainee_company_filters");
    if (!empty($_POST['companies'])) {
        foreach ($_POST['companies'] as $company) {
            $stmt = $conn->prepare("INSERT INTO trainee_company_filters (company_name) VALUES (?)");
            $stmt->bind_param("s", $company);
            $stmt->execute();
        }
    }

    header("Location: manage_ola_columns.php?saved=1");
    exit();
}

// ── Fetch config ───────────────────────────
$result = $conn->query("SELECT * FROM trainee_columns ORDER BY id");
$columns = $result->fetch_all(MYSQLI_ASSOC);

// ── Check if password exists ───────────────────────────
$pwdResult = $conn->query("SELECT * FROM view_passwords LIMIT 1");
$passwordSet = $pwdResult->num_rows > 0;

// ── Fetch distinct companies from trainees ───────────────────────────
$companyResult = $conn->query("SELECT DISTINCT source FROM trainees ORDER BY source ASC");
$allCompanies = [];
while ($row = $companyResult->fetch_assoc()) {
    if (!empty($row['source'])) {
        $allCompanies[] = $row['source'];
    }
}

// ── Fetch allowed companies ───────────────────────────
$allowedCompaniesRes = $conn->query("SELECT company_name FROM trainee_company_filters");
$allowedCompanies = [];
while ($r = $allowedCompaniesRes->fetch_assoc()) {
    $allowedCompanies[] = $r['company_name'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Columns, Password & Companies</title>
    <link rel="stylesheet" href="css/manage_ola_columns.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <a href="shares_page.php" class="back-button">
        <i class="fas fa-arrow-left"></i>
    </a>

    <div class="dashboard-container">
        <h1>Manage Visible Columns, Password & Companies</h1>

        <?php if (isset($_GET['saved'])): ?>
            <div class="msg success">✅ Settings saved successfully</div>
        <?php endif; ?>

        <form method="post">
		<p>Link: https://mlc-oman.com/ola_view_trainees.php</p>
            <fieldset>
                <legend>Select Columns to Display</legend>
                <?php foreach ($columns as $col): ?>
                    <div class="checkbox-inline">
                        <input type="checkbox" 
                               name="visible[]" 
                               value="<?= htmlspecialchars($col['column_name']) ?>" 
                               <?= $col['is_visible'] ? 'checked' : '' ?>>
                        <label><?= htmlspecialchars($col['label']) ?></label>
                    </div>
                <?php endforeach; ?>
            </fieldset>

            <fieldset>
                <legend>Set Password for Trainee Viewer</legend>
                <input type="password" name="new_password" placeholder="Enter new password" style="width:100%; padding:10px; border:2px solid #ff4b2b; border-radius:8px; margin-top:8px;">
                <?php if ($passwordSet): ?>
                    <p style="margin-top:10px; color:green; font-weight:bold;">✅ A password is already set</p>
                <?php else: ?>
                    <p style="margin-top:10px; color:red; font-weight:bold;">❌ No password set yet</p>
                <?php endif; ?>
            </fieldset>

            <fieldset>
                <legend>Choose Allowed Companies</legend>
                <?php if (empty($allCompanies)): ?>
                    <p>No companies found in trainees data.</p>
                <?php else: ?>
                    <?php foreach ($allCompanies as $company): ?>
                        <div class="checkbox-inline">
                            <input type="checkbox" 
                                   name="companies[]" 
                                   value="<?= htmlspecialchars($company) ?>" 
                                   <?= in_array($company, $allowedCompanies) ? 'checked' : '' ?>>
                            <label><?= htmlspecialchars($company) ?></label>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </fieldset>

            <button type="submit"><i class="fas fa-save"></i> Save</button>
        </form>
    </div>
</body>
</html>
