<?php
session_start();
require_once 'db.php';

// ── Require password login ──────────────────────────────
if (!isset($_SESSION['trainee_viewer']) || $_SESSION['trainee_viewer'] !== true) {
    header("Location: user_view_login.php");
    exit();
}

// ── 1. Get visible columns ──────────────────────────────
$colResult = $conn->query("SELECT * FROM trainee_columns WHERE is_visible = 1 ORDER BY id");
$visibleCols = $colResult->fetch_all(MYSQLI_ASSOC);

if (empty($visibleCols)) {
    die("No columns selected. Please ask admin to enable columns in Manage Columns.");
}

// ── 2. Get allowed companies ──────────────────────────────
$allowedCompanies = [];
$res = $conn->query("SELECT company_name FROM trainee_company_filters");
while ($row = $res->fetch_assoc()) {
    $allowedCompanies[] = $conn->real_escape_string($row['company_name']);
}

$whereCompany = '';
if (!empty($allowedCompanies)) {
    $companyList = "'" . implode("','", $allowedCompanies) . "'";
    $whereCompany = "source IN ($companyList)";
}

// ── 3. Search and Sorting ──────────────────────────────
$search = '';
$field = 'name';
$whereClause = '';
$sortDirection = 'ASC';

// Only allow searching in visible columns
$searchableFields = array_column($visibleCols, 'column_name');

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $field = $_GET['field'] ?? 'name';
    $searchEscaped = $conn->real_escape_string($search);

    if (in_array($field, $searchableFields)) {
        $whereClause = "$field LIKE '%$searchEscaped%'";
    }
}

// Combine search + company filters
$conditions = [];
if ($whereClause) $conditions[] = $whereClause;
if ($whereCompany) $conditions[] = $whereCompany;

$finalWhere = '';
if (!empty($conditions)) {
    $finalWhere = "WHERE " . implode(" AND ", $conditions);
}

// Sorting
if (isset($_GET['sort'])) {
    $sortField = $_GET['sort'];
    $sortDirection = $_GET['dir'] ?? 'ASC';
    if (!in_array($sortDirection, ['ASC', 'DESC'])) {
        $sortDirection = 'ASC';
    }
} else {
    $sortField = 'date';
    $sortDirection = 'DESC';
}

// ── 4. Query trainees ──────────────────────────────
$query = "SELECT * FROM trainees $finalWhere ORDER BY $sortField $sortDirection";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Trainees</title>
    <link rel="stylesheet" href="css/view_trainee3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        table th, table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .green { color: green; }
        .red { color: red; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>All Trainees</h1>

    <!-- Search Form -->
    <form method="GET" autocomplete="off" style="margin-bottom: 20px;">
        <select name="field">
            <?php foreach ($visibleCols as $col): ?>
                <option value="<?= $col['column_name'] ?>" <?= $field == $col['column_name'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars($col['label']) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" autocomplete="new-password">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <a href="ola_view_trainees.php"><button type="button"><i class="fas fa-times-circle"></i> Clear</button></a>
    </form>

    <!-- Trainees Table -->
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <?php foreach ($visibleCols as $col): ?>
                    <th>
                        <a href="?sort=<?= $col['column_name'] ?>&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                            <?= htmlspecialchars($col['label']) ?>
                            <i class="fas fa-sort<?= $sortField == $col['column_name'] ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                        </a>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $counter = 1; while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <?php foreach ($visibleCols as $col): ?>
                            <td>
                              <?php
                              switch ($col['column_name']) {
                                  case 'date':
                                      echo date('j-n-Y', strtotime($row['date']));
                                      break;
                                  case 'sign':
                                      echo $row['sign']
                                        ? '<i class="fas fa-check-circle green"></i>'
                                        : '<i class="fas fa-times-circle red"></i>';
                                      break;
                                  case 'payment':
                                      echo $row['payment']
                                        ? '<i class="fas fa-check-circle green"></i>'
                                        : '<i class="fas fa-times-circle red"></i>';
                                      break;
                                  case 'is_active':
                                      echo $row['is_active']
										? '<span style="color: orange;">Ongoing</span>'
										: '<span style="color: green;">Completed</span>';
                                      break;
                                  case 'instructors':
                                      echo '<a href="view_instructors.php?trainee_id='.$row['national_id'].'"><button><i class="fas fa-chalkboard-teacher"></i> View</button></a>';
                                      break;
                                  case 'comments':
                                      echo '<a href="view_comments.php?trainee_id='.$row['national_id'].'"><button><i class="fas fa-comment"></i> View</button></a>';
                                      break;
                                  default:
                                      echo htmlspecialchars($row[$col['column_name']]);
                              }
                              ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="<?= count($visibleCols) + 1 ?>">No trainees found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
