<?php
session_start();
require_once 'db.php';

/* ───────────────────────── Access control ───────────────────────── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor' || $_SESSION['is_active'] != 1) {
    header("Location: login.php");
    exit();
}


/* ────────────────────── Get employee to edit ────────────────────── */
if (!isset($_GET['id'])) {
    die("No employee ID provided.");
}

$employee_id = intval($_GET['id']);

/* ───────────────────────── Deactivate staff ─────────────────────── */
if (isset($_POST['delete'])) {
    $stmt = $conn->prepare(
        "UPDATE employees SET is_active = 0 WHERE id = ?"
    );
    $stmt->bind_param("s", $employee_id);

    if ($stmt->execute()) {
        header("Location: staff.php?deactivated=true");
        exit();
    }
    $error = "Failed to deactivate employee.";
}

// Handle update
if (isset($_POST['update'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role'];

    // Assuming $employee_id is already defined (e.g., from GET or POST)
    $stmt = $conn->prepare("UPDATE employees SET name=?, email=?, phone_number=?, role=? WHERE id=?");
    $stmt->bind_param("ssssi", $name, $email, $phone_number, $role, $employee_id);

    if ($stmt->execute()) {
        $success = "Employee updated successfully.";
    } else {
        $error = "Failed to update employee.";
    }
}


// Fetch employee data
$stmt = $conn->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    die("Employee not found.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Employee</title>
    <link rel="stylesheet" href="css/edit_employee2.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

</head>
<body>

<div class="dashboard-container">
    <h1><i class="fas fa-user-edit"></i> Edit Employee</h1>

    <?php if (isset($success)) echo "<p style='color: green;'><i class='fas fa-check-circle'></i> $success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color: red;'><i class='fas fa-exclamation-triangle'></i> $error</p>"; ?>

    <form method="POST">

        <label><i class="fas fa-user"></i> Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($employee['name']) ?>" required><br><br>

        <label><i class="fas fa-envelope"></i> Email:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($employee['email']) ?>" required><br><br>

        <label><i class="fas fa-phone"></i> Phone Number:</label><br>
        <input type="text" name="phone_number" value="<?= htmlspecialchars($employee['phone_number']) ?>" required><br><br>

        <label><i class="fas fa-user-tag"></i> Role:</label><br>
        <select name="role" required>
            <option value="staff" <?= $employee['role'] == 'staff' ? 'selected' : '' ?>>Staff</option>
            <option value="admin" <?= $employee['role'] == 'admin' ? 'selected' : '' ?>>Admin</option>
			<option value="supervisor" <?= $employee['role'] == 'supervisor' ? 'selected' : '' ?>>Supervisor</option>
			<option value="registration" <?= $employee['role'] == 'registration' ? 'selected' : '' ?>>Registration</option>
        </select><br><br>

        <button type="submit" name="update"><i class="fas fa-save"></i> Update</button>
        <button type="submit" name="delete" onclick="return confirm('Are you sure you want to delete this employee?');" style="background-color:red; color:white;">
            <i class="fas fa-trash-alt"></i> Delete
        </button>
    </form>

    <a href="staff.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
</div>

</body>
</html>
