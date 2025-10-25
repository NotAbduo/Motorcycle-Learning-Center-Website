<?php
session_start();
require_once 'db.php';   //  ==>  $conn (MySQLi connection)


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

/* ── 2.  Get comments from instructors ─────────────────────────── */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
$sql = "
    SELECT e.name AS instructor_name,
           c.comments,
           c.created_at,
           c.employee_national_id
      FROM `comment` AS c
 LEFT JOIN employees AS e ON c.employee_national_id = e.national_id
     WHERE c.trainee_national_id = ?
  ORDER BY c.created_at DESC
";


    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $trainee_id);
    $stmt->execute();
    $commentResult = $stmt->get_result();

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
<title>Trainee Comments</title>
<link rel="stylesheet" href="css/view_trainee3.css">
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
            : 'user_view_trainees.php' ?>';
    }
}
</script>

<div class="dashboard-container">
    <h1>Instructor Comments</h1>
    <h2>Trainee&nbsp;Name:&nbsp;<?= htmlspecialchars($trainee['name']) ?></h2>
    <h2>National&nbsp;ID:&nbsp;<?= htmlspecialchars($trainee['national_id']) ?></h2>

    <table border="1">
<thead>
    <tr><th>Instructor</th><th>Comment</th><th>Last Updated</th></tr>
</thead>
<tbody>
<?php if ($commentResult->num_rows): ?>
    <?php while ($row = $commentResult->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($row['instructor_name'] ?? 'Unknown') ?></td>
            <td><?= nl2br(htmlspecialchars($row['comments'])) ?></td>
            <td><?= date("Y-m-d H:i", strtotime($row['created_at'])) ?></td>
        </tr>
    <?php endwhile; ?>
<?php else: ?>
    <tr><td colspan="3">No comments available for this trainee.</td></tr>
<?php endif; ?>
</tbody>

    </table>
</div>
</body>
</html>
