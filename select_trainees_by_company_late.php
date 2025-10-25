<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['selected_company']) || !isset($_POST['selected_instructors']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

$selectedCompany = $_POST['selected_company'];
$instructors = json_decode($_POST['selected_instructors'], true);

// Fetch trainees from the selected company
$stmt = $conn->prepare("SELECT name, national_id FROM trainees WHERE is_active = 1 AND source = ? ORDER BY name ASC");
$stmt->bind_param("s", $selectedCompany);
$stmt->execute();
$result = $stmt->get_result();
$trainees = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Select Trainees</title>
    <link rel="stylesheet" href="css/submit_hours_start.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        body {
            padding-bottom: 90px;
        }

        .dashboard-container form {
            display: block;
            width: 100%;
        }

        .trainee-table-container {
            max-height: 70vh;
            overflow-y: auto;
            margin-top: 20px;
        }

        .trainee-table-container table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .trainee-table-container table th,
        .trainee-table-container table td {
            padding: 12px;
            text-align: left;
            border: 1px solid #ddd;
        }

        .trainee-table-container table th {
            background-color: #ff4b2b;
            color: white;
            font-weight: bold;
        }

        .trainee-table-container table td {
            color: #333;
            background-color: #fff;
        }

        .trainee-table-container table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .trainee-table-container input[type="checkbox"] {
            transform: scale(1.2);
        }

        .sticky-submit {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            padding: 10px 20px;
            border-top: 1px solid #ccc;
            text-align: center;
            z-index: 9999;
            box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.1);
        }

        .sticky-submit button {
            width: 100%;
            padding: 14px;
            font-size: 16px;
            background-color: #ff4b2b;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .sticky-submit button:hover {
            background-color: #ff3b2e;
        }

        @media (max-width: 768px) {
            .dashboard-container h1 {
                font-size: 24px;
            }

            .sticky-submit button {
                font-size: 14px;
                padding: 12px;
            }

            .trainee-table-container table th,
            .trainee-table-container table td {
                font-size: 14px;
                padding: 8px;
            }
        }
    </style>
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'hours_page_supervisor.php' : ($_SESSION['role'] == 'supervisor' ? 'hours_page_supervisor.php' : 'hours_page_supervisor.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
    <h1>Select Trainees from <?= htmlspecialchars($selectedCompany) ?></h1>

    <form id="traineeForm" method="POST" action="submit_multiple_hours_action_late.php">
        <input type="hidden" name="selected_trainees" id="selectedTrainees">
        <input type="hidden" name="selected_instructors" value='<?= htmlspecialchars(json_encode($instructors)) ?>'>
		<input type="hidden" name="selected_company" value="<?= htmlspecialchars($_POST['selected_company']) ?>">

        <div class="trainee-table-container">
            <table>
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>Name</th>
                        <th>National ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($trainees)): ?>
                        <tr><td colspan="3" style="text-align:center;">No trainees found for this company.</td></tr>
                    <?php else: ?>
                        <?php foreach ($trainees as $trainee): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="trainee-checkbox"
                                           data-name="<?= htmlspecialchars($trainee['name']) ?>"
                                           data-id="<?= htmlspecialchars($trainee['national_id']) ?>">
                                </td>
                                <td><?= htmlspecialchars($trainee['name']) ?></td>
                                <td><?= htmlspecialchars($trainee['national_id']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<div class="sticky-submit">
    <button onclick="submitSelected()"><i class="fas fa-check"></i> Submit Selected</button>
</div>

<script>
    document.getElementById('selectAll').addEventListener('change', function () {
        const checkboxes = document.querySelectorAll('.trainee-checkbox');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });

    function submitSelected() {
        const selected = [];
        const checkboxes = document.querySelectorAll('.trainee-checkbox:checked');

        checkboxes.forEach(cb => {
            selected.push({
                name: cb.dataset.name,
                national_id: cb.dataset.id
            });
        });

        if (selected.length === 0) {
            alert("Please select at least one trainee.");
            return;
        }

        document.getElementById('selectedTrainees').value = JSON.stringify(selected);
        document.getElementById('traineeForm').submit();
    }
</script>

</body>
</html>
