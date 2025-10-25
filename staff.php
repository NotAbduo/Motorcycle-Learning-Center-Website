<?php
session_start();
require_once 'db.php';

/* ───────────────────────── Access control ───────────────────────── */
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor' || $_SESSION['is_active'] != 1) {
    header("Location: login.php");
    exit();
}
// Handle employee addition
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_employee'])) {
    $national_id = $_POST['national_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $phone_number = $_POST['phone_number'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("INSERT INTO employees (national_id, name, email, password, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $national_id, $name, $email, $password, $phone_number, $role);
    if ($stmt->execute()) {
        $success_message = "Employee added successfully!";
    } else {
        $error_message = "Failed to add employee.";
    }
}

// Fetch all employees
$result = $conn->query("SELECT * FROM employees WHERE is_active = 1");

// Fetch deleted (inactive) employees
$deleted_result = $conn->query("SELECT * FROM employees WHERE is_active = 0");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Management</title>
    <link rel="stylesheet" href="css/staff3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?=
    $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' :
    ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php')
?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
    <h1><i class="fas fa-users"></i> Staff Management</h1>

    <!-- Success or Error messages -->
    <?php if (isset($success_message)) echo "<p class='success'><i class='fas fa-check-circle'></i> $success_message</p>"; ?>
    <?php if (isset($error_message)) echo "<p class='error'><i class='fas fa-times-circle'></i> $error_message</p>"; ?>

    <!-- Add Employee Form -->
    <h3><i class="fas fa-user-plus"></i> Add New Employee</h3>
    <form method="POST" autocomplete="off">
        <input type="text" name="national_id" placeholder="National ID" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"><br>
        <input type="text" name="name" placeholder="Employee Name" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"><br>
        <input type="email" name="email" placeholder="Email" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"><br>
        <input type="password" name="password" placeholder="Password" required autocomplete="new-password"><br>
        <input type="text" name="phone_number" placeholder="Phone Number" required autocomplete="off" readonly onfocus="this.removeAttribute('readonly');"><br>

        <select name="role" required autocomplete="off">
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
            <option value="supervisor">Supervisor</option>
            <option value="registration">Registration</option>
        </select><br>
        <button type="submit" name="add_employee"><i class="fas fa-plus-circle"></i> Add Employee</button>
    </form>

    <!-- Employee List -->
    <h3><i class="fas fa-list"></i> Employee List</h3>
	<div class="table-wrapper">
    <table border="1">
        <thead>
            <tr>
                <th>National ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
<tr>
    <td data-label="National ID"><?= htmlspecialchars($row['national_id']) ?></td>
    <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
    <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
    <td data-label="Phone Number"><?= htmlspecialchars($row['phone_number']) ?></td>
    <td data-label="Role"><?= htmlspecialchars($row['role']) ?></td>
    <td data-label="Actions">
        <a href="edit_employee.php?id=<?= $row['id'] ?>">
            <i class="fas fa-edit" style="color:blue;"></i> Edit
        </a>
    </td>
</tr>
            <?php endwhile; ?>
        </tbody>
    </table>
	</div>
	<br></br>
	<!-- Deleted (Inactive) Employee List -->
    <h3><i class="fas fa-user-slash"></i> Deleted Account List</h3>
	<div class="table-wrapper">
    <table border="1">
        <thead>
            <tr>
                <th>National ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Phone Number</th>
                <th>Role</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($deleted_result && $deleted_result->num_rows > 0): ?>
                <?php while ($row = $deleted_result->fetch_assoc()): ?>
<tr>
    <td data-label="National ID"><?= htmlspecialchars($row['national_id']) ?></td>
    <td data-label="Name"><?= htmlspecialchars($row['name']) ?></td>
    <td data-label="Email"><?= htmlspecialchars($row['email']) ?></td>
    <td data-label="Phone Number"><?= htmlspecialchars($row['phone_number']) ?></td>
    <td data-label="Role"><?= htmlspecialchars($row['role']) ?></td>
</tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No deleted accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
	</div>
</div>

</body>
</html>
