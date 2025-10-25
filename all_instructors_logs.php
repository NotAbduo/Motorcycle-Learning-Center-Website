<?php
// all_instructors_logs.php
session_start();
require_once 'db_pdo.php'; // provides $pdo

// ── 1. Verify role ───────────────────────────────────────────────
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: login.php");
    exit();
}

// ── 2. Optional month filter ─────────────────────────────────────
$selectedYM = $_GET['month'] ?? '';
if (preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    $year = (int)substr($selectedYM, 0, 4);
    $month = (int)substr($selectedYM, 5, 2);
} else {
    $year = (int)date('Y');
    $month = (int)date('m');
    $selectedYM = sprintf('%04d-%02d', $year, $month);
}

$rangeStart = "$year-" . sprintf('%02d', $month) . "-01";
$rangeEnd   = date('Y-m-01', strtotime($rangeStart . ' +1 month'));

// ── 3. Fetch logs grouped by instructor ──────────────────────────
$stmt = $pdo->prepare("
    SELECT i.national_id AS instructor_id,
           i.name AS instructor_name,
           al.log_id,
           al.trainee_id,
           t.name AS trainee_name,
           al.training_hours,
           al.start_time,
           al.end_time,
           DATE_FORMAT(al.request_date,'%Y-%m-%d') AS req_date
      FROM approval_logs al
 LEFT JOIN trainees t     ON al.trainee_id = t.national_id
 LEFT JOIN employees  i     ON al.instructor_id = i.national_id
     WHERE al.request_date >= :start AND al.request_date < :end
  ORDER BY i.name, al.request_date DESC, al.log_id DESC
");
$stmt->execute([
    ':start' => $rangeStart,
    ':end'   => $rangeEnd
]);

$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ── 4. Group logs by instructor ──────────────────────────────────
$groupedLogs = [];
foreach ($logs as $log) {
    $iid = $log['instructor_id'];
    $iname = $log['instructor_name'] ?? 'Unknown';
    $groupedLogs[$iid]['name'] = $iname;
    $groupedLogs[$iid]['logs'][] = $log;
}

// ── ✅ Sort each instructor's logs by req_date ASC ───────────────
foreach ($groupedLogs as &$instructor) {
    usort($instructor['logs'], function($a, $b) {
        return strtotime($a['req_date']) <=> strtotime($b['req_date']);
    });
}
unset($instructor); // avoid accidental reference

// ── 5. Fetch total hours per instructor for this month ───────────
$totalStmt = $pdo->prepare("
    SELECT Instructor_ID, SUM(Hours) AS total_hours
      FROM approved_billing_logs
     WHERE YEAR(Date) = :yr AND MONTH(Date) = :mn
  GROUP BY Instructor_ID
");
$totalStmt->execute([':yr' => $year, ':mn' => $month]);
$totalMap = [];
foreach ($totalStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $totalMap[$row['Instructor_ID']] = $row['total_hours'];
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>All Instructors – Approved Training Logs</title>
<link rel="stylesheet" href="css/hours3.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<style>
table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
th, td { padding: 6px 8px; border: 1px solid #ccc; text-align: center; }
h2.instructor-name { margin-top: 40px; margin-bottom: 10px; font-size: 20px; }
.instructor-section { margin-bottom: 20px; }
.toggle-button {
  width: 100%;
  text-align: left;
  padding: 12px;
  font-size: 16px;
  font-weight: bold;
  background: #fff;
  color: #ff4b2b;
  border: 2px solid #ff4b2b;
  border-radius: 10px;
  cursor: pointer;
  display: flex;
  justify-content: space-between;
  align-items: center;
  margin-top: 20px;
}
.toggle-button i { transition: transform 0.2s ease; }
</style>
</head>
<body class="p-4">
<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="page-content">
  <div class="dashboard-container">
    <h1 class="text-xl font-bold mb-4">All Instructors – Approved Training Logs</h1>

    <!-- Month Filter -->
    <form method="get" class="mb-4">
        <label class="highlight-label">Filter by month:
            <input type="month" name="month" value="<?= htmlspecialchars($selectedYM) ?>">
            <button class="btn accept">Go</button>
        </label>
    </form>

<?php if (empty($groupedLogs)): ?>
    <p>No approved logs found <?= $selectedYM ? 'for this month.' : 'yet.' ?></p>
<?php else: ?>
    <?php foreach ($groupedLogs as $instructorId => $data): ?>
       <div class="instructor-section">
    <button class="toggle-button" onclick="toggleLogs('logs-<?= $instructorId ?>')">
        <?= htmlspecialchars($data['name']) ?> – Total Payable Hours: <?= $totalMap[$instructorId] ?? 0 ?> 
        <i class="fas fa-chevron-down" id="icon-<?= $instructorId ?>"></i>
    </button>
    <div class="log-table" id="logs-<?= $instructorId ?>" style="display: none;">
        <table>
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
                <?php $counter = 1; foreach ($data['logs'] as $row): ?>
                <tr>
                    <td data-label="#"> <?= $counter++ ?> </td>
                    <td data-label="Date"> <?= date('j-n-Y', strtotime($row['req_date'])) ?> </td>
                    <td data-label="Trainee">
                        <?= htmlspecialchars($row['trainee_name']) ?><br><small><?= $row['trainee_id'] ?></small>
                    </td>
                    <td data-label="Hours"> <?= $row['training_hours'] ?> </td>
                    <td data-label="Start → End"><?= date('H:i', strtotime($row['start_time'])) ?> → <?= date('H:i', strtotime($row['end_time'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
    <?php endforeach; ?>
<?php endif; ?>
  </div>
</div>
<script>
function toggleLogs(id) {
    const section = document.getElementById(id);
    const icon = document.getElementById("icon-" + id.split('-')[1]);
    if (section.style.display === "none") {
        section.style.display = "block";
        icon.classList.remove("fa-chevron-down");
        icon.classList.add("fa-chevron-up");
    } else {
        section.style.display = "none";
        icon.classList.remove("fa-chevron-up");
        icon.classList.add("fa-chevron-down");
    }
}
</script>
</body>
</html>
