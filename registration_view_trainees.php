<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'registration') {
    header("Location: login.php");
    exit();
}

// Fields for searching & filtering
$fields = [
    "source" => "Source",
    "batch" => "Batch",
    "name" => "Name",
    "national_id" => "National ID",
    "phone_number" => "Phone Number",
    "added_by" => "Added By",
    "gender" => "Gender",
    "quiz" => "Quiz",
    "number_of_trails" => "Try 8",
    "try_road" => "Try Road",
    "date" => "Date"
];

$search = '';
$field = 'name';
$whereClause = '';
$sortDirection = 'ASC';

if (isset($_GET['search']) && !empty(trim($_GET['search']))) {
    $search = trim($_GET['search']);
    $field = $_GET['field'] ?? 'name';
    $searchEscaped = $conn->real_escape_string($search);

    if (array_key_exists($field, $fields)) {
        $whereClause = "WHERE $field LIKE '%$searchEscaped%'";
    }
}

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

$query = "SELECT * FROM trainees $whereClause ORDER BY $sortField $sortDirection";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Trainees - Admin</title>
    <link rel="stylesheet" href="css/view_trainee3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        table th,
        table td {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        .export-buttons {
            display: inline-flex;
            gap: 10px;
            margin-bottom: 10px;
        }
        .export-buttons button {
            padding: 8px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .pdf-btn {
            background-color: #dc3545;
            color: white;
        }
        .excel-btn {
            background-color: #28a745;
            color: white;
        }
        .advanced-btn {
            background-color: #6c757d;
            color: white;
        }
        .export-buttons button:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<a href="user_trainee_database.php" class="back-button"><i class="fas fa-arrow-left"></i></a>

<div class="dashboard-container">
    <h1>All Trainees - Registration Panel</h1>

    <!-- Search Form -->
    <form method="GET" autocomplete="off" style="margin-bottom: 20px;">
        <select name="field">
            <?php foreach ($fields as $key => $label): ?>
                <option value="<?= $key ?>" <?= $field == $key ? 'selected' : '' ?>><?= $label ?></option>
            <?php endforeach; ?>
        </select>
        <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search) ?>" autocomplete="new-password">
        <button type="submit"><i class="fas fa-search"></i> Search</button>
        <a href="admin_view_trainees.php"><button type="button"><i class="fas fa-times-circle"></i> Clear</button></a>
    </form>

    <!-- Export Buttons -->
    <div class="export-buttons">
        <form action="export_pdf.php" method="post" style="display:inline;">
            <button type="submit" class="pdf-btn"><i class="fas fa-file-pdf"></i> Export to PDF</button>
        </form>
        
        <form action="export_excel.php" method="post" style="display:inline;">
            <button type="submit" class="excel-btn"><i class="fas fa-file-excel"></i> Export to Excel</button>
        </form>
        
        <button type="button" class="advanced-btn" onclick="openExportModal()">
            <i class="fas fa-cogs"></i> Advanced Export
        </button>
    </div>

    <br><br>

    <!-- Table -->
    <table border="1">
        <thead>
            <tr>
                <th>#</th>
                <th>
                    <a href="?sort=source&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Source <i class="fas fa-sort<?= $sortField == 'source' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=batch&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Batch <i class="fas fa-sort<?= $sortField == 'batch' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=name&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Name <i class="fas fa-sort<?= $sortField == 'name' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>National ID</th>
                <th>Phone</th>
                <th>
                    <a href="?sort=gender&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Gender <i class="fas fa-sort<?= $sortField == 'gender' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>Added By</th>
                <th>
                    <a href="?sort=date&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Date <i class="fas fa-sort<?= $sortField == 'date' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=quiz&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Quiz <i class="fas fa-sort<?= $sortField == 'quiz' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=number_of_trails&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Try 8 <i class="fas fa-sort<?= $sortField == 'number_of_trails' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=try_road&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Try Road <i class="fas fa-sort<?= $sortField == 'try_road' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=sign&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Sign <i class="fas fa-sort<?= $sortField == 'sign' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=payment&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Payment <i class="fas fa-sort<?= $sortField == 'payment' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>
                    <a href="?sort=is_active&dir=<?= $sortDirection == 'ASC' ? 'DESC' : 'ASC' ?>">
                        Status <i class="fas fa-sort<?= $sortField == 'is_active' ? ($sortDirection == 'ASC' ? '-up' : '-down') : '' ?>"></i>
                    </a>
                </th>
                <th>Instructors</th>
                <th>Comments</th>
                <th>
                    <a href="select_company_for_edit.php?">
                        <button><i class="fas fa-edit"></i> Edit</button>
                    </a>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result->num_rows > 0): ?>
                <?php $counter = 1; ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($row['source']) ?></td>
                        <td><?= htmlspecialchars($row['batch']) ?></td>
                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['national_id']) ?></td>
                        <td><?= htmlspecialchars($row['phone_number']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($row['gender'])) ?></td>
                        <td><?= htmlspecialchars($row['added_by']) ?></td>
                        <td><?= date('j-n-Y h:i A', strtotime($row['date'])) ?></td>
                        <td><?= $row['quiz'] ?? '—' ?></td>
                        <td><?= htmlspecialchars($row['number_of_trails']) ?></td>
                        <td><?= htmlspecialchars($row['try_road'] ?? '—') ?></td>
                        <td>
                            <?php if ($row['sign']): ?>
                                <a href="signatures/<?= htmlspecialchars($row['national_id']) ?>.png" target="_blank" title="View Signature">
                                    <i class="fas fa-check-circle sign-icon green"></i>
                                </a>
                            <?php else: ?>
                                <i class="fas fa-times-circle sign-icon red"></i>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($row['payment']): ?>
                                <i class="fas fa-check-circle sign-icon green"></i>
                            <?php else: ?>
                                <i class="fas fa-times-circle sign-icon red"></i>
                            <?php endif; ?>
                        </td>
                        <td>
							<?php if ($row['is_active']): ?>
								<span style="color: orange;">Ongoing</span>
							<?php else: ?>
								<span style="color: green;">Completed</span>
							<?php endif; ?>
                        </td>
                        <td>
                            <a href="view_instructors.php?trainee_id=<?= $row['national_id'] ?>">
                                <button><i class="fas fa-chalkboard-teacher"></i> View</button>
                            </a>
                        </td>
                        <td>
                            <a href="view_comments.php?trainee_id=<?= $row['national_id'] ?>">
                                <button><i class="fas fa-comment"></i> View</button>
                            </a>
                        </td>
                        <td>
                            <a href="edit_trainee_registration.php?id=<?= $row['id'] ?>">
                                <button><i class="fas fa-edit"></i> Edit</button>
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="18">No trainees found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Export Modal -->
<div id="exportModal" style="display: none; position: fixed; background: rgba(0,0,0,0.6); top: 0; left: 0; right: 0; bottom: 0; z-index: 9999; align-items: center; justify-content: center;">
  <div style="background: white; padding: 30px; border-radius: 10px; max-width: 700px; width: 90%; max-height: 80%; overflow-y: auto;">
    <h2 style="margin-top: 0; color: #333;">Advanced Export Options</h2>
    
    <!-- Export Format Selection -->
    <div style="margin-bottom: 20px;">
      <h3>Export Format</h3>
      <label style="margin-right: 20px;">
        <input type="radio" name="export_format" value="pdf" checked> 
        <i class="fas fa-file-pdf" style="color: #dc3545;"></i> PDF
      </label>
      <label>
        <input type="radio" name="export_format" value="excel"> 
        <i class="fas fa-file-excel" style="color: #28a745;"></i> Excel
      </label>
    </div>

    <form id="exportForm" method="post" style="margin: 0;">
      <h3>Select Columns to Export</h3>
      <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 20px;">
        <label><input type="checkbox" name="columns[]" value="source" checked> Source</label>
        <label><input type="checkbox" name="columns[]" value="batch" checked> Batch</label>
        <label><input type="checkbox" name="columns[]" value="name" checked> Name</label>
        <label><input type="checkbox" name="columns[]" value="national_id" checked> National ID</label>
        <label><input type="checkbox" name="columns[]" value="phone_number" checked> Phone Number</label>
        <label><input type="checkbox" name="columns[]" value="gender" checked> Gender</label>
        <label><input type="checkbox" name="columns[]" value="added_by" checked> Added By</label>
        <label><input type="checkbox" name="columns[]" value="date" checked> Date</label>
        <label><input type="checkbox" name="columns[]" value="quiz" checked> Quiz</label>
        <label><input type="checkbox" name="columns[]" value="number_of_trails" checked> Try 8</label>
        <label><input type="checkbox" name="columns[]" value="try_road" checked> Try Road</label>
        <label><input type="checkbox" name="columns[]" value="sign" checked> Sign</label>
        <label><input type="checkbox" name="columns[]" value="payment" checked> Payment</label>
        <label><input type="checkbox" name="columns[]" value="is_active" checked> Active Status</label>
      </div>

      <div style="margin-bottom: 15px;">
        <button type="button" onclick="selectAllColumns()" style="margin-right: 10px; padding: 5px 10px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">Select All</button>
        <button type="button" onclick="deselectAllColumns()" style="padding: 5px 10px; background: #6c757d; color: white; border: none; border-radius: 3px; cursor: pointer;">Deselect All</button>
      </div>

      <h3>Filters (Optional)</h3>
      <div id="filters" style="margin-bottom: 20px;"></div>
      <button type="button" onclick="addFilter()" style="padding: 8px 15px; background: #28a745; color: white; border: none; border-radius: 5px; cursor: pointer; margin-bottom: 20px;">
        <i class="fas fa-plus"></i> Add Filter
      </button>

      <div style="border-top: 1px solid #ddd; padding-top: 20px; text-align: right;">
        <button type="button" onclick="closeExportModal()" style="padding: 10px 20px; background: #6c757d; color: white; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px;">Cancel</button>
        <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer;">
          <i class="fas fa-download"></i> Export
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function openExportModal() {
  document.getElementById("exportModal").style.display = "flex";
}

function closeExportModal() {
  document.getElementById("exportModal").style.display = "none";
}

function selectAllColumns() {
  const checkboxes = document.querySelectorAll('input[name="columns[]"]');
  checkboxes.forEach(cb => cb.checked = true);
}

function deselectAllColumns() {
  const checkboxes = document.querySelectorAll('input[name="columns[]"]');
  checkboxes.forEach(cb => cb.checked = false);
}

function addFilter() {
  const filters = document.getElementById("filters");
  const div = document.createElement("div");
  div.className = "filter-row";
  div.style.cssText = "margin-bottom: 10px; display: flex; gap: 10px; align-items: center; padding: 10px; border: 1px solid #ddd; border-radius: 5px; background: #f8f9fa;";
  div.innerHTML = `
    <select name="filter_field[]" onchange="handleFilterChange(this)" style="padding: 5px; border-radius: 3px; border: 1px solid #ccc;">
      <option value="source">Source</option>
      <option value="batch">Batch</option>
      <option value="name">Name</option>
      <option value="national_id">National ID</option>
      <option value="phone_number">Phone Number</option>
      <option value="gender">Gender</option>
      <option value="added_by">Added By</option>
      <option value="date">Date</option>
      <option value="quiz">Quiz</option>
      <option value="number_of_trails">Try 8</option>
      <option value="try_road">Try Road</option>
      <option value="sign">Sign</option>
      <option value="payment">Payment</option>
      <option value="is_active">Active Status</option>
    </select>
    <select name="filter_operator[]" style="padding: 5px; border-radius: 3px; border: 1px solid #ccc;">
      <option value="contains">Contains</option>
      <option value="equals">Equals</option>
      <option value="starts">Starts With</option>
      <option value="ends">Ends With</option>
      <option value="gt">Greater Than</option>
      <option value="lt">Less Than</option>
    </select>
    <input name="filter_value[]" type="text" placeholder="Value" style="padding: 5px; border-radius: 3px; border: 1px solid #ccc; flex: 1;">
    <input name="filter_value_from[]" type="date" style="display:none; padding: 5px; border-radius: 3px; border: 1px solid #ccc;" placeholder="From">
    <input name="filter_value_to[]" type="date" style="display:none; padding: 5px; border-radius: 3px; border: 1px solid #ccc;" placeholder="To">
    <button type="button" onclick="this.parentElement.remove()" style="padding: 5px 8px; background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">
      <i class="fas fa-trash"></i>
    </button>
  `;
  filters.appendChild(div);
}

function handleFilterChange(select) {
  const row = select.parentElement;
  const valInput = row.querySelector('input[name="filter_value[]"]');
  const fromInput = row.querySelector('input[name="filter_value_from[]"]');
  const toInput   = row.querySelector('input[name="filter_value_to[]"]');

  if (select.value === 'date') {
    valInput.style.display = 'none';
    valInput.value = '';
    fromInput.style.display = 'block';
    toInput.style.display = 'block';
  } else {
    valInput.style.display = 'block';
    fromInput.style.display = 'none';
    fromInput.value = '';
    toInput.style.display = 'none';
    toInput.value = '';
  }
}

// Legacy functions for backwards compatibility
function openExportPopup() {
    openExportModal();
}

function closeExportPopup() {
    closeExportModal();
}

// Handle form submission based on selected format
document.getElementById('exportForm').addEventListener('submit', function(e) {
  e.preventDefault();
  
  const format = document.querySelector('input[name="export_format"]:checked').value;
  
  if (format === 'pdf') {
    this.action = 'export_advance.php';
  } else if (format === 'excel') {
    this.action = 'export_excel_advanced.php';
  }
  
  this.submit();
});
</script>
</body>
</html>