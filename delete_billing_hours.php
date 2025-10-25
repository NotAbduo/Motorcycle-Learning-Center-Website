<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

/* ───── Handle checkbox-based deletion ───── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logs'])) {
    $msgs = [];

    foreach ($_POST['logs'] as $logId => $actions) {
        $logId = intval($logId);
        $delete = isset($actions['delete']);

        if (!$delete) continue;

        try {
            $pdo->beginTransaction();

            $copyStmt = $pdo->prepare("
                INSERT INTO deleted_billing_hours (Instructor_ID, Hours, Date)
                SELECT Instructor_ID, Hours, Date
                  FROM approved_billing_logs
                 WHERE ID = :id
                LIMIT 1
            ");
            $copyStmt->execute([':id' => $logId]);

            $delStmt = $pdo->prepare("DELETE FROM approved_billing_logs WHERE ID = :id");
            $delStmt->execute([':id' => $logId]);

            $pdo->commit();
            $msgs[] = "🗑️ Deleted billing log #$logId";
        } catch (Exception $e) {
            $pdo->rollBack();
            $msgs[] = "❌ Error deleting log #$logId: " . $e->getMessage();
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?month=" . urlencode($_GET['month'] ?? '') . "&msg=" . urlencode(implode(" | ", $msgs)));
    exit;
}

/* ───── Month filtering ───── */
$selectedYM = $_GET['month'] ?? date('Y-m'); // default to current month
$whereClause = '';
$params = [];

if (preg_match('/^\d{4}-\d{2}$/', $selectedYM)) {
    $year = (int)substr($selectedYM, 0, 4);
    $month = (int)substr($selectedYM, 5, 2);

    $rangeStart = "$year-" . sprintf('%02d', $month) . "-01";
    $rangeEnd = date('Y-m-01', strtotime($rangeStart . ' +1 month'));

    $whereClause = "WHERE ab.Date >= :start AND ab.Date < :end";
    $params[':start'] = $rangeStart;
    $params[':end']   = $rangeEnd;
}

/* ───── Fetch approved billing logs ───── */
$stmt = $pdo->prepare("
    SELECT ab.ID,
           ab.Instructor_ID,
           ab.Hours,
           DATE_FORMAT(ab.Date, '%Y-%m-%d') AS req_date,
           e.name AS instructor_name
      FROM approved_billing_logs ab
 LEFT JOIN employees e ON ab.Instructor_ID = e.national_id
      $whereClause
  ORDER BY ab.Date DESC, ab.ID DESC
");
$stmt->execute($params);
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Delete Approved Billing Logs</title>
<link rel="stylesheet" href="css/hours.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body class="p-4">
<a href="delete_hours_page.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="page-content">
  <div class="dashboard-container">
    <h1 class="text-xl font-bold mb-4">Approved Billing Logs (Delete Tool)</h1>

    <!-- Month filter -->
    <form method="get" class="mb-4">
        <label class="highlight-label">Filter by month:
            <input type="month" name="month" value="<?= htmlspecialchars($selectedYM) ?>">
            <button class="btn accept">Go</button>
        </label>
    </form>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert success mb-3"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$logs): ?>
        <p>No approved billing logs <?= $selectedYM ? "for $selectedYM" : '' ?></p>
    <?php else: ?>
    <form method="post" onsubmit="return confirm('Are you sure you want to delete selected logs?');">
    <table class="table-auto w-full border mb-6">
        <thead>
            <tr class="bg-gray-100 text-center">
                <th>#</th>
                <th>Instructor</th>
                <th>Hours</th>
                <th>Date</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
        <?php $counter = 1; ?>
        <?php foreach ($logs as $row): ?>
            <tr class="border-t text-center">
                <td class="px-2 py-1"><?= $counter++ ?></td>
                <td class="text-left">
                    <?= htmlspecialchars($row['instructor_name'] ?? 'Unknown') ?><br>
                    <small><?= $row['Instructor_ID'] ?></small>
                </td>
                <td><?= $row['Hours'] ?></td>
                <td><?= date('j-n-Y', strtotime($row['req_date'])) ?></td>
                <td>
                    <input type="checkbox" name="logs[<?= $row['ID'] ?>][delete]">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-right mt-2">
        <button type="submit" class="btn danger">Delete Selected</button>
    </div>
    </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
