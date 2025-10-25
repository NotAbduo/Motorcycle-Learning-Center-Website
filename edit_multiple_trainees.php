<?php
session_start();
require_once 'db.php';

// Allow admin, supervisor, and registration roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor', 'registration'])) {
    header("Location: login.php");
    exit();
}

// Check if user is registration role (limited access)
$isRegistration = ($_SESSION['role'] === 'registration');

$msg = "";

// Get selected trainees from POST or die
if (!isset($_POST['selected_trainees'])) {
    die("No trainees selected.");
}

$traineeInput = json_decode($_POST['selected_trainees'], true);
if (!$traineeInput || !is_array($traineeInput)) {
    die("Invalid trainee selection.");
}

// Fetch source options
$sourceOptions = [];
$result = $conn->query("SELECT name FROM sources ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $sourceOptions[] = $row['name'];
}

// Extract all trainee IDs for update
$nationalIds = array_column($traineeInput, 'national_id');
$placeholders = implode(',', array_fill(0, count($nationalIds), '?'));
$stmt = $conn->prepare("SELECT * FROM trainees WHERE national_id IN ($placeholders)");

// Bind params dynamically for IN clause
$types_for_in = str_repeat('s', count($nationalIds));
$params = [];
$params[] = &$types_for_in;
for ($i = 0; $i < count($nationalIds); $i++) {
    $params[] = &$nationalIds[$i];
}
call_user_func_array([$stmt, 'bind_param'], $params);

$stmt->execute();
$result = $stmt->get_result();

$trainees = [];
while ($row = $result->fetch_assoc()) {
    $trainees[$row['id']] = $row;
}

if (empty($trainees)) {
    die("No trainees found.");
}

$traineeIds = array_keys($trainees);

// Handle form submission to update selected fields ONLY
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_all'])) {
    $fieldsToUpdate = [];

    // If registration role, only allow specific fields + batch
    if ($isRegistration) {
        if (isset($_POST['update_payment'])) {
            $fieldsToUpdate['payment'] = isset($_POST['payment']) && $_POST['payment'] === '1' ? 1 : 0;
        }
        if (isset($_POST['update_is_active'])) {
            $fieldsToUpdate['is_active'] = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        }
        if (isset($_POST['update_number_of_trials'])) {
            $fieldsToUpdate['number_of_trails'] = isset($_POST['number_of_trails']) ? (int)$_POST['number_of_trails'] : 1;
        }
        if (isset($_POST['update_try_road'])) {
            $fieldsToUpdate['try_road'] = isset($_POST['try_road']) ? (int)$_POST['try_road'] : 0;
        }
        if (isset($_POST['update_batch'])) {
            $fieldsToUpdate['batch'] = trim($_POST['batch']) ?? '';
        }
    } else {
        // Admin and Supervisor can update all fields
        if (isset($_POST['update_sign'])) {
            $fieldsToUpdate['sign'] = isset($_POST['sign']) && $_POST['sign'] === '1' ? 1 : 0;
        }
		if (isset($_POST['update_phone_number'])) {
    $fieldsToUpdate['phone_number'] = trim($_POST['phone_number']) ?? '';
}
        if (isset($_POST['update_payment'])) {
            $fieldsToUpdate['payment'] = isset($_POST['payment']) && $_POST['payment'] === '1' ? 1 : 0;
        }
        if (isset($_POST['update_is_active'])) {
            $fieldsToUpdate['is_active'] = isset($_POST['is_active']) && $_POST['is_active'] === '1' ? 1 : 0;
        }
        if (isset($_POST['update_source'])) {
            $fieldsToUpdate['source'] = $_POST['source'] ?? '';
        }
        if (isset($_POST['update_batch'])) {
            $fieldsToUpdate['batch'] = trim($_POST['batch']) ?? '';
        }
        if (isset($_POST['update_gender'])) {
            $fieldsToUpdate['gender'] = $_POST['gender'] ?? '';
        }
        if (isset($_POST['update_number_of_trials'])) {
            $fieldsToUpdate['number_of_trails'] = isset($_POST['number_of_trails']) ? (int)$_POST['number_of_trails'] : 1;
        }
        if (isset($_POST['update_try_road'])) {
            $fieldsToUpdate['try_road'] = isset($_POST['try_road']) ? (int)$_POST['try_road'] : 0;
        }
    }

    if (empty($fieldsToUpdate)) {
        $msg = "⚠️ Please select at least one field to update.";
    } else {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        $setParts = [];
        $types = '';
        $values = [];

        foreach ($fieldsToUpdate as $field => $value) {
            $setParts[] = "$field = ?";
            if (in_array($field, ['sign', 'payment', 'is_active', 'number_of_trails', 'try_road'])) {
                $types .= 'i';
                $values[] = (int)$value;
            } else {
                $types .= 's';
                $values[] = $value;
            }
        }

        $setSql = implode(', ', $setParts);
        $stmt = $conn->prepare("UPDATE trainees SET $setSql WHERE id = ?");

        if (!$stmt) {
            die("SQL Prepare Failed: " . $conn->error);
        }

        foreach ($traineeIds as $id) {
            $params = array_merge($values, [$id]);
            $stmt->bind_param($types . 'i', ...$params);

            if (!$stmt->execute()) {
                die("SQL Execution Failed: " . $stmt->error);
            }
        }

        $msg = "✅ Changes applied to all selected trainees successfully!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bulk Edit Selected Trainees - Select Fields</title>
    <link rel="stylesheet" href="css/edit_multiple_trainees.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .checkbox-inline { display: inline-flex; align-items: center; margin-right: 15px; }
        .checkbox-inline input[type="checkbox"] { margin-right: 5px; }
        fieldset { max-width: 400px; margin-bottom: 20px; }
        .msg { font-weight: bold; margin-bottom: 20px; }
        .msg.warning { color: orange; }
        .msg.success { color: green; }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1><i class="fas fa-users-cog"></i> Bulk Edit Selected Trainees - Select Fields</h1>

    <?php if ($msg): ?>
        <p class="msg <?= strpos($msg, '⚠️') === 0 ? 'warning' : 'success' ?>"><?= $msg ?></p>
    <?php endif; ?>

    <p><strong>Selected Trainees to Edit:</strong> <?= count($traineeIds) ?></p>

    <form method="POST" action="">
        <input type="hidden" name="selected_trainees" value='<?= htmlspecialchars(json_encode($traineeInput)) ?>'>

        <?php if (!$isRegistration): ?>
		<!-- Phone Number (Admin & Supervisor only) -->
<fieldset>
    <div class="checkbox-inline">
        <input type="checkbox" id="update_phone_number" name="update_phone_number" onchange="toggleInput(this, 'phone_number')">
        <label for="update_phone_number">Phone Number</label>
    </div>
    <input type="text" name="phone_number" id="phone_number" placeholder="e.g., +968 9123 4567" disabled style="padding: 5px; width: 200px;">
</fieldset>
        <!-- Sign Contract (Admin & Supervisor only) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_sign" name="update_sign" onchange="toggleInput(this, 'sign')">
                <label for="update_sign">Signed Contract</label>
            </div>
            <select name="sign" id="sign" disabled>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </fieldset>
        <?php endif; ?>

        <!-- Payment (All roles) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_payment" name="update_payment" onchange="toggleInput(this, 'payment')">
                <label for="update_payment">Payment Contract</label>
            </div>
            <select name="payment" id="payment" disabled>
                <option value="1">Yes</option>
                <option value="0">No</option>
            </select>
        </fieldset>

        <!-- Is Active (All roles) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_is_active" name="update_is_active" onchange="toggleInput(this, 'is_active')">
                <label for="update_is_active">Status</label>
            </div>
            <select name="is_active" id="is_active" disabled>
                <option value="1">Ongoing</option>
                <option value="0">Completed</option>
            </select>
        </fieldset>

        <!-- Batch (All roles) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_batch" name="update_batch" onchange="toggleInput(this, 'batch')">
                <label for="update_batch">Batch</label>
            </div>
            <input type="text" name="batch" id="batch" placeholder="e.g., Batch 2024-A" disabled style="padding: 5px; width: 200px;">
        </fieldset>

        <?php if (!$isRegistration): ?>
        <!-- Source (Admin & Supervisor only) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_source" name="update_source" onchange="toggleInput(this, 'source')">
                <label for="update_source">Source</label>
            </div>
            <select name="source" id="source" disabled>
                <option value="" disabled selected>Select source</option>
                <?php foreach ($sourceOptions as $option): ?>
                    <option value="<?= htmlspecialchars($option) ?>"><?= htmlspecialchars($option) ?></option>
                <?php endforeach; ?>
            </select>
        </fieldset>

        <!-- Gender (Admin & Supervisor only) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_gender" name="update_gender" onchange="toggleInput(this, 'gender')">
                <label for="update_gender">Gender</label>
            </div>
            <select name="gender" id="gender" disabled>
                <option value="" disabled selected>Select gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
            </select>
        </fieldset>
        <?php endif; ?>

        <!-- Number of Trials / Try 8 (All roles) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_number_of_trials" name="update_number_of_trials" onchange="toggleInput(this, 'number_of_trails')">
                <label for="update_number_of_trials">Try 8</label>
            </div>
            <select name="number_of_trails" id="number_of_trails" disabled>
                <?php for ($i=1;$i<=10;$i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Attempt<?= $i>1 ? 's':'' ?></option>
                <?php endfor; ?>
            </select>
        </fieldset>

        <!-- Try Road (All roles) -->
        <fieldset>
            <div class="checkbox-inline">
                <input type="checkbox" id="update_try_road" name="update_try_road" onchange="toggleInput(this, 'try_road')">
                <label for="update_try_road">Try Road</label>
            </div>
            <select name="try_road" id="try_road" disabled>
                <?php for ($i=0;$i<=10;$i++): ?>
                    <option value="<?= $i ?>"><?= $i ?> Attempt<?= $i!=1 ? 's':'' ?></option>
                <?php endfor; ?>
            </select>
        </fieldset>

        <button type="submit" name="update_all"><i class="fas fa-save"></i> Apply Changes to All Selected Trainees</button>
    </form>

    <br>
    <a href="<?= $isRegistration ? 'registration_view_trainees.php' : 'admin_view_trainees.php' ?>" class="back-button"><i class="fas fa-arrow-left"></i></a>
</div>

<script>
    function toggleInput(checkbox, inputId) {
        document.getElementById(inputId).disabled = !checkbox.checked;
    }
</script>

</body>
</html>
