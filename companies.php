<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor']))  {
    header("Location: login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['add_source'])) {
    $source_name = trim($_POST['source_name']);

    if (!empty($source_name)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO sources (name) VALUES (?)");
        $stmt->bind_param("s", $source_name);
        $success = $stmt->execute() ? "Source added." : "Error adding source.";
    } else {
        $error = "Source name cannot be empty.";
    }
}

if (isset($_GET['delete'])) {
    $toDelete = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM sources WHERE name = ?");
    $stmt->bind_param("s", $toDelete);
    $success = $stmt->execute() ? "Source deleted." : "Error deleting source.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['edit_source'])) {
    $old_name = $_POST['original_name'];
    $new_name = trim($_POST['new_name']);

    if (!empty($new_name)) {
        $stmt = $conn->prepare("UPDATE sources SET name = ? WHERE name = ?");
        $stmt->bind_param("ss", $new_name, $old_name);
        $success = $stmt->execute() ? "Source updated." : "Error updating source.";
    } else {
        $error = "New source name cannot be empty.";
    }
}

$sources = [];
$result = $conn->query("SELECT name FROM sources ORDER BY name ASC");
while ($row = $result->fetch_assoc()) {
    $sources[] = $row['name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Sources</title>
    <link rel="stylesheet" href="css/companies.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
    <h1><i class="fas fa-building"></i> Manage Sources</h1>

    <?php if (isset($success)) echo "<p style='color:limegreen;'><i class='fas fa-check-circle'></i> $success</p>"; ?>
    <?php if (isset($error)) echo "<p style='color:orange;'><i class='fas fa-exclamation-triangle'></i> $error</p>"; ?>

    <form method="POST" autocomplete="off">
        <input type="text" name="source_name" placeholder="Enter new source (e.g. Talabat)" required>
        <button type="submit" name="add_source"><i class="fas fa-plus"></i> Add Source</button>
    </form>

    <table>
        <thead>
            <tr>
                <th>Source Name</th>
                <th>Edit</th>
                <th>Delete</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($sources)): ?>
                <tr><td colspan="3">No sources available.</td></tr>
            <?php else: ?>
                <?php foreach ($sources as $src): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($src) ?></strong></td>

                        <td>
                            <form method="POST" class="inline" style="display:flex; gap:5px;">
                                <input type="hidden" name="original_name" value="<?= htmlspecialchars($src) ?>">
                                <input type="text" name="new_name" placeholder="New name" required>
                                <button type="submit" name="edit_source"><i class="fas fa-edit"></i></button>
                            </form>
                        </td>

                        <td>
                            <form method="GET" class="inline" onsubmit="return confirm('Are you sure you want to delete this source?');">
                                <input type="hidden" name="delete" value="<?= htmlspecialchars($src) ?>">
                                <button type="submit"><i class="fas fa-trash-alt"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

</body>
</html>
