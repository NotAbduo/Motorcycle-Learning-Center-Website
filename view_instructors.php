<?php
session_start();
require_once 'db.php';   //  ==>  $conn  (mysqli connection)



if (!isset($_GET['trainee_id'])) {
    echo "Invalid request.";
    exit();
}

$trainee_id = intval($_GET['trainee_id']);

/* ── 1.  Get trainee info ─────────────────────────────────────── */
$traineeStmt = $conn->prepare(
    "SELECT name, national_id FROM trainees WHERE national_id = ?"
);
$traineeStmt->bind_param("i", $trainee_id);
$traineeStmt->execute();
$traineeResult = $traineeStmt->get_result();

if ($traineeResult->num_rows === 0) {
    echo "Trainee not found.";
    exit();
}

$trainee = $traineeResult->fetch_assoc();

/* ── 2.  Get unique sessions grouped by date ──────────────────── */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT); // throw exceptions

try {
    $sql = "
        SELECT al.request_date,
               al.start_time,
               al.end_time,
               MIN(al.training_hours) AS training_hours,
               GROUP_CONCAT(DISTINCT e.name ORDER BY e.name SEPARATOR ', ') AS instructors
          FROM approval_logs AS al
          LEFT JOIN employees e ON al.instructor_id = e.national_id
         WHERE al.trainee_id = ?
      GROUP BY al.request_date, al.start_time, al.end_time
      ORDER BY al.request_date DESC
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trainee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $dataByDate = [];
    $totalAllHours = 0;

    while ($row = $result->fetch_assoc()) {
        $date = $row['request_date'];
        if (!isset($dataByDate[$date])) {
            $dataByDate[$date] = ['total_hours' => 0, 'sessions' => []];
        }

        $hours = floatval($row['training_hours']);
        $instructors = explode(', ', $row['instructors'] ?? 'Unknown');

        $dataByDate[$date]['total_hours'] += $hours;
        $dataByDate[$date]['sessions'][] = [
            'instructors' => $instructors
        ];

        $totalAllHours += $hours;
    }

} catch (mysqli_sql_exception $e) {
    echo '<pre style="color:red;">SQL ERROR: ' . $e->getMessage() . '</pre>';
    echo '<pre>SQL: ' . $sql . '</pre>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainee Sessions Summary</title>
    <link rel="stylesheet" href="css/view_instructors.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .date-summary {
            background: #e2e8f0;
            padding: 10px;
            margin-top: 10px;
            cursor: pointer;
            font-weight: bold;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .date-summary:hover {
            background: #cbd5e0;
        }

        .instructor-list {
            display: none;
            margin-left: 20px;
            padding: 10px;
            border-left: 3px solid #ccc;
            background: #f9fafb;
        }

        .instructor-item {
            margin: 5px 0;
        }

        .dashboard-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .back-button {
            display: inline-block;
            margin: 10px;
            font-size: 18px;
            text-decoration: none;
        }

        h1, h2 {
            margin: 10px 0;
        }

        .total-hours {
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
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
            : 'user_view_trainees.php' ?>';
    }
}
</script>

<div class="dashboard-container">
    <h1>Daily Summary</h1>
    <h2>Trainee&nbsp;Name:&nbsp;<?= htmlspecialchars($trainee['name']) ?></h2>
    <h2>National&nbsp;ID:&nbsp;<?= htmlspecialchars($trainee['national_id']) ?></h2>

    <?php if (!empty($dataByDate)): ?>
        <?php foreach ($dataByDate as $date => $info): ?>
            <div class="date-summary" onclick="toggleList('list_<?= $date ?>')">
                <?= htmlspecialchars($date) ?> — <?= $info['total_hours'] ?> hours
                <i class="fa fa-caret-down" style="float: right;"></i>
            </div>
            <div id="list_<?= $date ?>" class="instructor-list">
                <?php foreach ($info['sessions'] as $i => $session): ?>
                    <div class="instructor-item">
                        <strong>Session <?= $i + 1 ?>:</strong>
                        <?= implode(', ', array_map('htmlspecialchars', $session['instructors'])) ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="total-hours">
            Total All Hours: <?= $totalAllHours ?>
        </div>
    <?php else: ?>
        <p>No approved logs for this trainee.</p>
    <?php endif; ?>
</div>

<script>
function toggleList(id) {
    const list = document.getElementById(id);
    const header = list.previousElementSibling;
    const isOpen = list.style.display === 'block';

    list.style.display = isOpen ? 'none' : 'block';
    if (isOpen) {
        header.classList.remove('open');
    } else {
        header.classList.add('open');
    }
}

</script>

</body>
</html>
