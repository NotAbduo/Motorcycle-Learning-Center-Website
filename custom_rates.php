<?php
/* ------------------------------------------------------------------
 * custom_rates.php
 * Set custom rates for specific hours/sessions of an instructor
 * ------------------------------------------------------------------ */
session_start();
require_once 'db_pdo.php';

/* ─────── Access control ─────── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

/* ─────── Get parameters ─────── */
$instructorID = $_GET['instructor'] ?? '';
$selectedYM = $_GET['month'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    $selectedYM = date('Y-m');
}
[$year, $month] = explode('-', $selectedYM);
$payMonth = $selectedYM . '-01';

/* ─────── Validate instructor ─────── */
if (empty($instructorID)) {
    die("Invalid instructor ID");
}

/* ─────── Get instructor info ─────── */
$instStmt = $pdo->prepare("SELECT name FROM employees WHERE national_id = ?");
$instStmt->execute([$instructorID]);
$instructor = $instStmt->fetch(PDO::FETCH_ASSOC);
if (!$instructor) {
    die("Instructor not found");
}

/* ─────── Handle custom rate addition ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_custom_rate'])) {
    $logId = (int)$_POST['log_id'];
    $customRate = max(0, (float)$_POST['custom_rate']);
    
    if ($logId > 0 && $customRate > 0) {
        // Get the hours for this log
        $hoursStmt = $pdo->prepare("SELECT Hours FROM approved_billing_logs WHERE ID = ?");
        $hoursStmt->execute([$logId]);
        $hours = $hoursStmt->fetchColumn();
        
        if ($hours) {
            $stmt = $pdo->prepare("
                INSERT INTO custom_hourly_rates 
                (Instructor_ID, pay_month, log_id, custom_rate, hours, description)
                VALUES (?, ?, ?, ?, ?, '')
                ON DUPLICATE KEY UPDATE custom_rate = VALUES(custom_rate), hours = VALUES(hours)
            ");
            $stmt->execute([$instructorID, $payMonth, $logId, $customRate, $hours]);
        }
        
        header("Location: {$_SERVER['PHP_SELF']}?instructor=" . urlencode($instructorID) . "&month=" . urlencode($selectedYM));
        exit;
    }
}

/* ─────── Handle custom rate deletion ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_custom_rate'])) {
    $customId = (int)$_POST['custom_id'];
    
    $stmt = $pdo->prepare("DELETE FROM custom_hourly_rates WHERE id = ? AND Instructor_ID = ?");
    $stmt->execute([$customId, $instructorID]);
    
    header("Location: {$_SERVER['PHP_SELF']}?instructor=" . urlencode($instructorID) . "&month=" . urlencode($selectedYM));
    exit;
}

/* ─────── Get approved billing logs for this instructor/month ─────── */
$logsStmt = $pdo->prepare("
    SELECT ID as id, Instructor_ID, Hours, Date
      FROM approved_billing_logs
     WHERE Instructor_ID = ?
       AND YEAR(Date) = ?
       AND MONTH(Date) = ?
  ORDER BY Date ASC
");
$logsStmt->execute([$instructorID, $year, $month]);
$logs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

/* ─────── Get existing custom rates ─────── */
$customStmt = $pdo->prepare("
    SELECT id, log_id, custom_rate, hours
      FROM custom_hourly_rates
     WHERE Instructor_ID = ?
       AND pay_month = ?
");
$customStmt->execute([$instructorID, $payMonth]);
$customRates = $customStmt->fetchAll(PDO::FETCH_ASSOC);

// Map custom rates by log_id (single rate per log)
$customByLog = [];
foreach ($customRates as $cr) {
    $customByLog[$cr['log_id']] = $cr;
}

/* ─────── Get default rate ─────── */
$rateStmt = $pdo->prepare("SELECT default_rate FROM instructor_rates WHERE Instructor_ID = ?");
$rateStmt->execute([$instructorID]);
$defaultRate = $rateStmt->fetchColumn() ?: 8.0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Custom Rates - <?= htmlspecialchars($instructor['name']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/custom_rate.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body class="p-4">
<a href="billing.php?month=<?= urlencode($selectedYM) ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="page-content">
  <div class="dashboard-container">

<h2 class="text-xl font-bold mb-4">
    Custom Rates: <?= htmlspecialchars($instructor['name']) ?>
</h2>
<p style="color: #FFFFFF; margin-bottom: 20px;">
    Month: <strong><?= date('F Y', strtotime($payMonth)) ?></strong> | 
    Default Rate: <strong><?= number_format($defaultRate, 1) ?></strong>
</p>

<?php
// Calculate summary
$totalHours = array_sum(array_column($logs, 'Hours'));
$totalCustomHours = 0;
$totalCustomAmount = 0;
foreach ($customRates as $cr) {
    $totalCustomHours += $cr['hours'];
    $totalCustomAmount += $cr['hours'] * $cr['custom_rate'];
}
$normalHours = max(0, $totalHours - $totalCustomHours);
$normalAmount = $normalHours * $defaultRate;
$grandTotal = $normalAmount + $totalCustomAmount;
?>

<div class="summary-box">
    <h3 style="margin-top: 0;">Payment Summary</h3>
    <div class="summary-item">
        <span>Total Hours:</span>
        <strong><?= number_format($totalHours, 1) ?> hours</strong>
    </div>
    <div class="summary-item">
        <span>Normal Rate Hours (<?= number_format($defaultRate, 1) ?>):</span>
        <strong><?= number_format($normalHours, 1) ?> × <?= number_format($defaultRate, 1) ?> = <?= number_format($normalAmount, 1) ?></strong>
    </div>
    <div class="summary-item">
        <span>Custom Rate Hours:</span>
        <strong><?= number_format($totalCustomHours, 1) ?> hours = <?= number_format($totalCustomAmount, 1) ?></strong>
    </div>
    <hr style="border-color: rgba(255,255,255,0.3); margin: 10px 0;">
    <div class="summary-item" style="font-size: 1.2rem;">
        <span>Total Payment:</span>
        <strong><?= number_format($grandTotal, 1) ?></strong>
    </div>
</div>

<?php if (!$logs): ?>
    <p>No approved hours found for this month.</p>
<?php else: ?>

<h3>Approved Hours Sessions</h3>
<p style="color: #FFFFFF; font-size: 0.9rem; margin-bottom: 15px;">
    Set a custom rate for entire sessions. By default, all hours use the normal rate (<?= number_format($defaultRate, 1) ?>).
</p>

<?php foreach ($logs as $log): ?>
<div class="log-card">
    <div class="log-header">
        <div>
            <strong>Session #<?= $log['id'] ?></strong><br>
            <small style="color: #666;">
                <?= date('M d, Y', strtotime($log['Date'])) ?> | 
                <?= number_format($log['Hours'], 1) ?> hours
            </small>
        </div>
        <div style="text-align: right;">
            <?php if (isset($customByLog[$log['id']])): ?>
                <span style="color: #28a745; font-weight: bold; margin-right: 10px;">
                    <i class="fas fa-check-circle"></i> Custom Rate: <?= number_format($customByLog[$log['id']]['custom_rate'], 1) ?>
                </span>
                <button onclick="toggleForm(<?= $log['id'] ?>)" class="btn update">
                    <i class="fas fa-edit"></i> Edit Rate
                </button>
            <?php else: ?>
                <span style="color: #666; margin-right: 10px;">
                    Normal Rate: <?= number_format($defaultRate, 1) ?>
                </span>
                <button onclick="toggleForm(<?= $log['id'] ?>)" class="btn update">
                    <i class="fas fa-plus"></i> Set Custom Rate
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Custom rate display -->
    <?php if (isset($customByLog[$log['id']])): ?>
    <div class="custom-rate-section">
        <div class="custom-rate-item">
            <div>
                <strong>Total Payment: <?= number_format($log['Hours'] * $customByLog[$log['id']]['custom_rate'], 1) ?></strong>
                <br>
                <small style="color: #666;">
                    <?= number_format($log['Hours'], 1) ?> hours × <?= number_format($customByLog[$log['id']]['custom_rate'], 1) ?>
                </small>
            </div>
            <form method="post" style="display: inline;">
                <input type="hidden" name="custom_id" value="<?= $customByLog[$log['id']]['id'] ?>">
                <button type="submit" name="delete_custom_rate" 
                        class="btn-delete"
                        onclick="return confirm('Remove custom rate and use normal rate instead?')">
                    <i class="fas fa-trash"></i> Remove
                </button>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Set/Edit custom rate form (hidden by default) -->
    <div id="form-<?= $log['id'] ?>" class="add-custom-form" style="display: none;">
        <form method="post">
            <input type="hidden" name="log_id" value="<?= $log['id'] ?>">
            <h4 style="margin-top: 0;">
                <?= isset($customByLog[$log['id']]) ? 'Edit' : 'Set' ?> Custom Rate for This Session
            </h4>
            <div class="form-row">
                <div class="form-group">
                    <label>Custom Rate *</label>
                    <input type="number" name="custom_rate" 
                           min="0" step="0.5" required
                           value="<?= isset($customByLog[$log['id']]) ? $customByLog[$log['id']]['custom_rate'] : '' ?>"
                           placeholder="e.g., 12.5">
                </div>
            </div>
            <div style="text-align: right;">
                <button type="button" onclick="toggleForm(<?= $log['id'] ?>)" 
                        style="background: #6c757d; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-right: 10px;">
                    Cancel
                </button>
                <button type="submit" name="add_custom_rate" class="btn-add">
                    <i class="fas fa-save"></i> Save Custom Rate
                </button>
            </div>
        </form>
    </div>
</div>
<?php endforeach; ?>

<?php endif; ?>

  </div>
</div>

<script>
function toggleForm(logId) {
    const form = document.getElementById('form-' + logId);
    if (form.style.display === 'none') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>

</body>
</html>