<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

/* ───── Handle batch checkbox approval/denial ───── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logs'])) {
    $msgs = [];

    foreach ($_POST['logs'] as $logId => $actions) {
        $logId = intval($logId);
        $approve = isset($actions['approve']);
        $deny = isset($actions['deny']);

        if ($approve && $deny) {
            $msgs[] = "⚠️ Skipped log #$logId (both approve and deny checked)";
            continue;
        }

        if (!$approve && !$deny) {
            continue;
        }

        if ($approve) {
            try {
                $pdo->beginTransaction();

                $copyStmt = $pdo->prepare("
                    INSERT INTO approved_billing_logs (Instructor_ID, Hours, Date)
                    SELECT Instructor_ID, Hours, Date
                      FROM waiting_billing_logs
                     WHERE ID = :id
                    LIMIT 1
                ");
                $copyStmt->execute([':id' => $logId]);

                $delStmt = $pdo->prepare("DELETE FROM waiting_billing_logs WHERE ID = :id");
                $delStmt->execute([':id' => $logId]);

                $pdo->commit();
                $msgs[] = "✅ Approved log #$logId";
            } catch (Exception $e) {
                $pdo->rollBack();
                $msgs[] = "❌ Error approving log #$logId: " . $e->getMessage();
            }
        } elseif ($deny) {
            try {
                $stmt = $pdo->prepare("DELETE FROM waiting_billing_logs WHERE ID = :id");
                $stmt->execute([':id' => $logId]);
                $msgs[] = "🗑️ Denied log #$logId";
            } catch (Exception $e) {
                $msgs[] = "❌ Error denying log #$logId: " . $e->getMessage();
            }
        }
    }

    header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode(implode(" | ", $msgs)));
    exit;
}

/* ───── Fetch pending billing logs ───── */
$stmt = $pdo->query("
    SELECT wl.ID,
           wl.Instructor_ID,
           wl.Hours,
           DATE_FORMAT(wl.Date, '%Y-%m-%d') AS req_date,
           e.name AS instructor_name
      FROM waiting_billing_logs wl
 LEFT JOIN employees e ON wl.Instructor_ID = e.national_id
  ORDER BY wl.Date DESC, wl.ID DESC
");
$logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Pending Billing Requests</title>
<link rel="stylesheet" href="css/hours.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
    /* Make checkboxes slightly larger for better usability */
    input[type="checkbox"] {
        transform: scale(1.2);
        cursor: pointer;
    }
</style>
</head>
<body class="p-4">
<a href="approvals_page.php" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="page-content">
  <div class="dashboard-container">
    <h1 class="text-xl font-bold mb-4">Waiting Billing Logs</h1>

    <?php if (!empty($_GET['msg'])): ?>
        <div class="alert success mb-3"><?= htmlspecialchars($_GET['msg']) ?></div>
    <?php endif; ?>

    <?php if (!$logs): ?>
        <p>No pending requests 🎉</p>
    <?php else: ?>
    <form method="post" onsubmit="return confirm('Submit all selected actions?');">
    <table class="table-auto w-full border mb-6">
        <thead>
            <tr class="bg-gray-100 text-center">
                <th>#</th>
                <th>Instructor</th>
                <th>Hours</th>
                <th>Request Date</th>
                <th>
                    Approve <br>
                    <input type="checkbox" id="selectAllApprove">
                </th>
                <th>
                    Deny <br>
                    <input type="checkbox" id="selectAllDeny">
                </th>
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
                    <input type="checkbox" class="approve-checkbox" name="logs[<?= $row['ID'] ?>][approve]">
                </td>
                <td>
                    <input type="checkbox" class="deny-checkbox" name="logs[<?= $row['ID'] ?>][deny]">
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="text-right mt-2">
        <button type="submit" class="btn accept">Submit Selected</button>
    </div>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
    // Select/Deselect all Approve checkboxes
    document.getElementById('selectAllApprove').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.approve-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    // Select/Deselect all Deny checkboxes
    document.getElementById('selectAllDeny').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.deny-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
</script>
</body>
</html>
