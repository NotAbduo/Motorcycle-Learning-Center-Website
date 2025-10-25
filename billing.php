<?php
/* ------------------------------------------------------------------
 * billing.php
 * Monthly instructor billing view with inline "Update paid hours" + Pay Slip
 * ------------------------------------------------------------------ */
session_start();
require_once 'db_pdo.php'; // Supplies $pdo (PDO connection)

/* ─────── Access control ─────── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

/* ─────── Helpers ─────── */
function ym_to_date(string $ym): string {
    return $ym . '-01';
}
function nice_month(string $ym): string {
    return date('F Y', strtotime($ym . '-01'));
}

/* ─────── Determine month to show (YYYY-MM) ─────── */
$selectedYM = $_GET['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    $selectedYM = date('Y-m');
}
[$year, $month] = explode('-', $selectedYM);

/* ─────── Handle "Update paid hours" submission ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['Instructor_ID'])) {
    $iid      = $_POST['Instructor_ID'];
    $addHours = max(0, (float)$_POST['add_hours']);
    $payMonth = ym_to_date($selectedYM);

    if (is_numeric($_POST['add_hours'])) {
        $stmt = $pdo->prepare("
            INSERT INTO instructor_payments (Instructor_ID, pay_month, paid_hours)
            VALUES (:iid, :pm, :hrs)
            ON DUPLICATE KEY UPDATE paid_hours = VALUES(paid_hours)
        ");
        $stmt->execute([
            ':iid' => $iid,
            ':pm'  => $payMonth,
            ':hrs' => $addHours
        ]);
    }
    header("Location: {$_SERVER['PHP_SELF']}?month={$selectedYM}");
    exit;
}

/* ─────── Handle "Update Rate" submission ─────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_rate'])) {
    $iid = $_POST['Instructor_ID_rate'];
    $newRate = max(0, (float)$_POST['default_rate']);
    
    if ($newRate > 0) {
        $stmt = $pdo->prepare("
            INSERT INTO instructor_rates (Instructor_ID, default_rate)
            VALUES (:iid, :rate)
            ON DUPLICATE KEY UPDATE default_rate = VALUES(default_rate)
        ");
        $stmt->execute([':iid' => $iid, ':rate' => $newRate]);
    }
    header("Location: {$_SERVER['PHP_SELF']}?month={$selectedYM}");
    exit;
}

/* ─────── 1. Total (approved) hours per instructor ─────── */
$totalStmt = $pdo->prepare("
    SELECT al.Instructor_ID,
           e.name AS instructor_name,
           SUM(al.Hours) AS total_hours
      FROM approved_billing_logs AS al
 LEFT JOIN employees AS e ON al.Instructor_ID = e.national_id
     WHERE YEAR(al.Date) = :yr
       AND MONTH(al.Date) = :mn
  GROUP BY al.Instructor_ID, e.name
");
$totalStmt->execute([':yr' => $year, ':mn' => $month]);
$totals = $totalStmt->fetchAll(PDO::FETCH_ASSOC);

/* ─────── 2. Paid hours already recorded for the same month ─────── */
$paidStmt = $pdo->prepare("
    SELECT Instructor_ID, paid_hours
      FROM instructor_payments
     WHERE pay_month = :pm
");
$paidStmt->execute([':pm' => ym_to_date($selectedYM)]);
$paidMap = $paidStmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* ─────── 3. Get default rates for all instructors ─────── */
$ratesStmt = $pdo->prepare("
    SELECT Instructor_ID, default_rate
      FROM instructor_rates
");
$ratesStmt->execute();
$ratesMap = $ratesStmt->fetchAll(PDO::FETCH_KEY_PAIR);

/* ─────── 4. Get custom rates totals for this month ─────── */
$customRatesStmt = $pdo->prepare("
    SELECT Instructor_ID, 
           SUM(custom_rate * hours) as custom_total,
           SUM(hours) as custom_hours
      FROM custom_hourly_rates
     WHERE pay_month = :pm
  GROUP BY Instructor_ID
");
$customRatesStmt->execute([':pm' => ym_to_date($selectedYM)]);
$customRatesData = $customRatesStmt->fetchAll(PDO::FETCH_ASSOC);
$customRatesMap = [];
foreach ($customRatesData as $cr) {
    $customRatesMap[$cr['Instructor_ID']] = [
        'total' => $cr['custom_total'],
        'hours' => $cr['custom_hours']
    ];
}

/* ─────── 5. Build rows for display ─────── */
$rows = [];
foreach ($totals as $t) {
    $iid        = $t['Instructor_ID'];
    $totalHours = (float)$t['total_hours'];
    $paidHours  = (float)($paidMap[$iid] ?? 0);
    $defaultRate = (float)($ratesMap[$iid] ?? 8.0);
    
    // Calculate payment considering custom rates
    $customHours = (float)($customRatesMap[$iid]['hours'] ?? 0);
    $customTotal = (float)($customRatesMap[$iid]['total'] ?? 0);
    $normalHours = max(0, $totalHours - $customHours);
    $totalPayment = ($normalHours * $defaultRate) + $customTotal;

    $status = 'pending';
    if ($paidHours >= $totalHours && $totalHours > 0) { 
        $status = 'paid'; 
    } elseif ($paidHours > 0 && $paidHours < $totalHours) { 
        $status = 'partial paid'; 
    }

    $rows[] = [
        'iid'           => $iid,
        'name'          => $t['instructor_name'] ?? 'Unknown',
        'total_hours'   => $totalHours,
        'paid_hours'    => $paidHours,
        'default_rate'  => $defaultRate,
        'custom_hours'  => $customHours,
        'total_payment' => $totalPayment,
        'status'        => $status
    ];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Instructor Billing – <?= htmlspecialchars(nice_month($selectedYM)) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="css/billing2.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body class="p-4">
<a href="supervisor_dashboard.php" class="back-button"><i class="fas fa-arrow-left"></i></a>

<div class="page-content">
  <div class="dashboard-container">

<h2 class="text-xl font-bold mb-4"><?= htmlspecialchars(nice_month($selectedYM)) ?> Billing</h2>

<!-- Month selector -->
<form method="get" class="mb-4">
    <label class="highlight-label">Select month:
        <input type="month" name="month" value="<?= htmlspecialchars($selectedYM) ?>">
        <button type="submit" class="btn update">Go</button>
    </label>
</form>

<?php if (!$rows): ?>
    <p>No approved hours for this month.</p>
<?php else: ?>
<?php
$totalTotalHours   = 0.0;
$totalPaidHours    = 0.0;
$totalPendingHours = 0.0;
$totalPayment      = 0.0;

foreach ($rows as $r) {
    $th = (float)$r['total_hours'];
    $ph = (float)$r['paid_hours'];
    $pending = max(0, $th - $ph);

    $totalTotalHours   += $th;
    $totalPaidHours    += $ph;
    $totalPendingHours += $pending;
    $totalPayment      += $r['total_payment'];
}
?>
<table class="table-auto w-full mb-6">
    <thead>
        <tr class="bg-gray-100">
            <th>Instructor</th>
            <th>Total Hours</th>
            <th>Paid Hours</th>
            <th>Pending Hours</th>
			<th>Status</th>
			<th>Update</th>
            <th>Rate</th>
            <th>Total Payment</th>
            <th>Pay Slip</th>
        </tr>
    </thead>
    <tbody>
<?php foreach ($rows as $r): ?>
        <tr>
            <td class="text-left"><?= htmlspecialchars($r['name']) ?><br><small><?= $r['iid'] ?></small></td>
            <td><?= number_format($r['total_hours'], 1) ?></td>
            <td><?= number_format($r['paid_hours'], 1) ?></td>
            <td><?= number_format(max(0, $r['total_hours'] - $r['paid_hours']), 1) ?></td>
			<td class="<?php
    echo $r['status'] === 'paid' ? 'status-paid' :
         ($r['status'] === 'pending' ? 'status-pending' : 'status-partial');
?>">
    <?= $r['status'] ?>
</td>
<td>
                <form method="post" class="update-hours-form">
                    <input type="hidden" name="Instructor_ID" value="<?= htmlspecialchars($r['iid']) ?>">
                    <input type="number" name="add_hours" value="0" min="0" step="0.5" class="hours-input">
                    <button class="btn update btn-small" onclick="return confirm('Add these hours as paid?')">Update</button>
                </form>
            </td>
<td class="payslip-cell">
                <div class="payslip-container-horizontal">
                    <div class="rate-section">
                        <div class="rate-display">
                            <div class="rate-item">
                                <label>Rate</label>
                                <div class="value"><?= number_format($r['default_rate'], 1) ?></div>
                            </div>
                            <form method="post" class="rate-update-form">
                                <input type="hidden" name="Instructor_ID_rate" value="<?= htmlspecialchars($r['iid']) ?>">
                                <input type="number" name="default_rate" 
                                       value="<?= $r['default_rate'] ?>" 
                                       min="0" step="0.5" 
                                       class="rate-input"
                                       title="Update default rate">
                                <button type="submit" name="update_rate" class="btn update btn-small">Set</button>
                            </form>
                        </div>
                        <?php if ($r['custom_hours'] > 0): ?>
                            <div class="rate-indicator custom-rate-active">
                                <i class="fas fa-star"></i> Custom
                            </div>
                        <?php else: ?>
                            <div class="rate-indicator normal-rate-active">
                                <i class="fas fa-check-circle"></i> Normal
                            </div>
                        <?php endif; ?>
                    </div>
                    <a href="custom_rates.php?instructor=<?= urlencode($r['iid']) ?>&month=<?= urlencode($selectedYM) ?>" 
                       class="btn-custom-rate">
                        <i class="fas fa-edit"></i> Edit
                        <?php if ($r['custom_hours'] > 0): ?>
                            <span class="custom-hours-badge"><?= number_format($r['custom_hours'], 1) ?>h</span>
                        <?php endif; ?>
                    </a>
                </div>
            </td>
            <td><strong><?= number_format($r['total_payment'], 1) ?></strong></td>
            

            <td>
                <a href="generate_payslip.php?instructor=<?= urlencode($r['iid']) ?>&month=<?= urlencode($selectedYM) ?>" 
                   class="btn btn-payslip" 
                   target="_blank"
                   title="Generate PDF Pay Slip">
                    <i class="fas fa-file-pdf"></i> Generate
                </a>
            </td>
        </tr>
<?php endforeach; ?>
<tr class="bg-gray-200 font-semibold">
    <td class="text-right pr-2">Total:</td>
    <td><?= number_format($totalTotalHours, 1) ?></td>
    <td><?= number_format($totalPaidHours, 1) ?></td>
    <td><?= number_format($totalPendingHours, 1) ?></td>
    <td>-</td>
    <td></td> <!-- Update column -->
    <td></td> <!-- Rate column -->
    <td><strong><?= number_format($totalPayment, 1) ?></strong></td> <!-- Total Payment now under correct header -->
    <td></td> <!-- Pay Slip column -->
</tr>

    </tbody>
</table>
<?php endif; ?>

  </div>
</div>
</body>
</html>