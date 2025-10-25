<?php
session_start();
require_once 'db.php';

if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid request.");
}
$token = trim($_GET['token']);

if (!isset($_SESSION['verified_tokens'][$token])) {
    die("<p style='color:red;'>Unauthorized access.</p>");
}

// Fetch share data
$stmt = $conn->prepare("SELECT * FROM share_links WHERE token=?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) die("Invalid token.");
$shareData = $result->fetch_assoc();
$stmt->close();

$companies = json_decode($shareData['companies_json'], true);
$selectedColumns = json_decode($shareData['columns_json'], true);

$fieldLabels = [
    "source" => "Source",
    "name" => "Name",
    "national_id" => "National ID",
    "phone_number" => "Phone",
    "added_by" => "Added By",
    "gender" => "Gender",
    "quiz" => "Quiz",
    "number_of_trails" => "No. of Trials",
    "date" => "Date",
    "sign" => "Sign",
    "payment" => "Payment"
];

// Search setup
$search = $_GET['search'] ?? '';
$field = $_GET['field'] ?? $selectedColumns[0];
$whereClause = '';

if ($search && in_array($field, $selectedColumns)) {
    $searchEscaped = $conn->real_escape_string($search);
    $whereClause = " AND $field LIKE '%$searchEscaped%'";
}

// Fetch trainees
$trainees = [];
if (!empty($companies)) {
    $placeholders = implode(',', array_fill(0, count($companies), '?'));
    $types = str_repeat('s', count($companies));
    $query = "SELECT * FROM trainees WHERE source IN ($placeholders) $whereClause ORDER BY date DESC";
    $stmt = $conn->prepare($query);

    $tmp = [];
    foreach ($companies as $k => $v) $tmp[$k] = &$companies[$k];
    array_unshift($tmp, $types);
    call_user_func_array([$stmt, 'bind_param'], $tmp);

    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $trainees[] = $row;
    $stmt->close();
}
?>
<table border="1">
    <thead>
        <tr>
            <th>#</th>
            <?php foreach ($selectedColumns as $col): ?>
                <th><?= htmlspecialchars($fieldLabels[$col] ?? ucfirst($col)) ?></th>
            <?php endforeach; ?>
            <th><i class="fas fa-chalkboard-teacher"></i> Instructors</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!empty($trainees)): $counter=1; foreach ($trainees as $row): ?>
            <tr>
                <td><?= $counter++ ?></td>
                <?php foreach ($selectedColumns as $col): ?>
                    <td>
                        <?php
                        if ($col === 'sign') {
                            echo $row['sign']
                                ? '<i class="fas fa-check-circle sign-icon green" title="Signed"></i>'
                                : '<i class="fas fa-times-circle sign-icon red" title="Not Signed"></i>';
                        } elseif ($col === 'payment') {
                            echo $row['payment']
                                ? '<i class="fas fa-check-circle sign-icon green" title="Paid"></i>'
                                : '<i class="fas fa-times-circle sign-icon red" title="Not Paid"></i>';
                        } elseif ($col === 'date') {
                            echo date('j-n-Y h:i A', strtotime($row['date']));
                        } else {
                            echo htmlspecialchars($row[$col] ?? '—');
                        }
                        ?>
                    </td>
                <?php endforeach; ?>
                <td>
                    <a href="view_instructors.php?trainee_id=<?= $row['national_id'] ?>&token=<?= htmlspecialchars($token) ?>">
                        <button><i class="fas fa-chalkboard-teacher"></i> View</button>
                    </a>
                </td>
            </tr>
        <?php endforeach; else: ?>
            <tr>
                <td colspan="<?= count($selectedColumns)+2 ?>" style="text-align:center;padding:40px;color:#666;">
                    <i class="fas fa-users" style="font-size:40px;display:block;margin-bottom:10px;"></i>
                    No trainees found.
                </td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>
