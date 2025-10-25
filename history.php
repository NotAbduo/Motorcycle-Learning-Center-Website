<?php
/* ------------------------------------------------------------------
 * history.php
 * Shows the logged‑in instructor their own approved training logs.
 * ------------------------------------------------------------------
 */
session_start();
require_once 'db_pdo.php'; // provides $pdo (PDO connection)

// ── 1. Verify role ───────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_SESSION['national_id'])) {
    die("Session missing national_id. Please log in again.");
}
$instructorId = $_SESSION['national_id'];
$instructorName = $_SESSION['name'] ?? ''; // optional – if stored in session

// ── 2. Optional month filter (YYYY-MM) ───────────────────────────
$selectedYM = $_GET['month'] ?? '';          // blank = all months
$whereExtra = '';
$params     = [':iid' => $instructorId];
$totalHoursThisMonth = null;

// Use selected month or fallback to current month
if (preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    $year = (int)substr($selectedYM, 0, 4);
    $month = (int)substr($selectedYM, 5, 2);
} else {
    $year = (int)date('Y');
    $month = (int)date('m');
    $selectedYM = sprintf('%04d-%02d', $year, $month); // So input field gets pre-filled
}

// First day of the chosen month (00:00:00)
$rangeStart = "$year-" . sprintf('%02d', $month) . "-01";
// First day of the next month (00:00:00)
$rangeEnd   = date('Y-m-01', strtotime($rangeStart . ' +1 month'));

// Compare raw values – avoids the DATE_FORMAT() pit-fall
$whereExtra = "AND request_date >= :start AND request_date < :end";
$params[':start'] = $rangeStart;
$params[':end']   = $rangeEnd;

// ── Always get total approved billing hours for this instructor/month ─
$totalStmt = $pdo->prepare("
    SELECT SUM(al.Hours) AS total_hours
      FROM approved_billing_logs AS al
     WHERE al.Instructor_ID = :iid
       AND YEAR(al.Date) = :yr
       AND MONTH(al.Date) = :mn
");
$totalStmt->execute([
    ':iid' => $instructorId,
    ':yr' => $year,
    ':mn' => $month
]);
$totalResult = $totalStmt->fetch(PDO::FETCH_ASSOC);
$totalHoursThisMonth = $totalResult['total_hours'] ?? 0;

// ── 3. Fetch approved logs for this instructor ──────────────────
$stmt = $pdo->prepare("
    SELECT al.log_id,
           al.trainee_id,
           t.name AS trainee_name,
           al.training_hours,
           al.start_time,
           al.end_time,
           DATE_FORMAT(al.request_date,'%Y-%m-%d') AS req_date
      FROM approval_logs al
 LEFT JOIN trainees t ON al.trainee_id = t.national_id
     WHERE al.instructor_id = :iid
           $whereExtra
  ORDER BY al.request_date DESC, al.log_id DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>My Approved Training Logs</title>
<link rel="stylesheet" href="css/hours.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
table{border-collapse:collapse;width:100%;}
th,td{padding:6px 8px;border:1px solid #ccc;text-align:center;}
</style>
</head>
<body class="p-4">
<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="page-content">
  <div class="dashboard-container">
<h2 class="text-xl font-bold mb-4">
    My Approved Training Logs <?= $instructorName ? '– '.htmlspecialchars($instructorName) : '' ?>
</h2>

<!-- Month selector (optional) -->
<form method="get" class="mb-4">
    <label class="highlight-label">Filter by month:
        <input type="month" name="month" value="<?= htmlspecialchars($selectedYM) ?>">
        <button class="btn accept">Go</button>
    </label>

</form>

<?php if (!$logs): ?>
    <p>No approved logs found <?= $selectedYM ? 'for this month.' : 'yet.' ?></p>
<?php else: ?>

<h3 class="mb-2 font-semibold text-lg">
    Total Billing Hours in <?= date('F Y', strtotime($selectedYM . '-01')) ?>: <?= htmlspecialchars($totalHoursThisMonth) ?>
</h3>


<table class="mb-6">
    <thead>
        <tr class="bg-gray-100">
            <th>#</th>
            <th>Date</th>
            <th>Trainee</th>
            <th>Hours</th>
            <th>Start → End</th>
        </tr>
    </thead>
    <tbody>
<?php $counter = 1; foreach ($logs as $row): ?>
    <tr>
        <td><?= $counter++ ?></td>
        <td><?= date('j-n-Y', strtotime($row['req_date'])) ?></td>
        <td><?= htmlspecialchars($row['trainee_name']) ?><br><small><?= $row['trainee_id'] ?></small></td>
        <td><?= $row['training_hours'] ?></td>
        <td><?= $row['start_time'] ?> → <?= $row['end_time'] ?></td>
    </tr>
<?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
  </div>
</div>
</body>
</html>
