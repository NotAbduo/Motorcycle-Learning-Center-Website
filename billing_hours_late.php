<?php
session_start();
require_once 'db_pdo.php';

// Only store instructors in session if this is the initial load (from selection page)
if (isset($_POST['selected_instructors'])) {
    $_SESSION['selected_instructors'] = $_POST['selected_instructors'];
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'supervisor' || !isset($_SESSION['selected_instructors'])) {
    header("Location: login.php");
    exit();
}

$instructors = json_decode($_SESSION['selected_instructors'], true);
$msg = '';


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['instructor_hours'], $_POST['date'])) {
    try {
        $pdo->beginTransaction();

        $date = $_POST['date'];

        // Validate date format: YYYY-MM-DD
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new Exception("Invalid date format.");
        }

        foreach ($_POST['instructor_hours'] as $national_id => $data) {
            $hour = intval($data['hours']);
            $min = intval($data['minutes']);

            if ($hour < 1 || $hour > 6 || !in_array($min, [0, 30])) {
                throw new Exception("Invalid time for instructor ID: $national_id");
            }

            $decimalHours = number_format($hour + ($min === 30 ? 0.5 : 0.0), 1);

            $stmt = $pdo->prepare("
                INSERT INTO waiting_billing_logs (Instructor_ID, Hours, Date)
                VALUES (:iid, :hrs, :date)
            ");
            $stmt->execute([
                ':iid'  => $national_id,
                ':hrs'  => $decimalHours,
                ':date' => $date
            ]);
        }

        $pdo->commit();
        unset($_SESSION['selected_instructors']);
        header("Location: submitted_successfully_late.php");
        exit();

    } catch (Exception $e) {
        $pdo->rollBack();
        $msg = "Error: " . $e->getMessage();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Billing Hours</title>
  <link rel="stylesheet" href="css/submit_hours2.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<a href="submit_multiple_instructors_billing_late.php" class="back-button"><i class="fas fa-arrow-left"></i></a>

<div class="dashboard-container">
  <h1>Submit Billing Hours for Instructors</h1>

<form method="post">
  <!-- Date Picker -->
  <div class="form-group" style="margin-bottom: 25px;">
    <label for="date"><i class="fa fa-calendar"></i> Select Date:</label>
    <input type="date" class="tail-dropdown" name="date" id="date" required style="padding: 8px; font-size: 16px;">
  </div>

  <!-- Instructor Billing Inputs -->
  <?php foreach ($instructors as $inst): ?>
    <div class="form-group" style="margin-bottom: 30px;">
      <h3><?= htmlspecialchars($inst['name']) ?> (<?= htmlspecialchars($inst['national_id']) ?>)</h3>

      <div style="display: flex; gap: 15px;">
        <label>
          Hours:
          <select class="tail-dropdown" name="instructor_hours[<?= $inst['national_id'] ?>][hours]" required>
            <?php for ($i = 1; $i <= 6; $i++): ?>
              <option value="<?= $i ?>"><?= $i ?></option>
            <?php endfor; ?>
          </select>
        </label>

        <label>
          Minutes:
          <select class="tail-dropdown" name="instructor_hours[<?= $inst['national_id'] ?>][minutes]" required>
            <option value="0">00</option>
            <option value="30">30</option>
          </select>
        </label>
      </div>
    </div>
  <?php endforeach; ?>

  <!-- Error Message (if any) -->
  <?php if ($msg): ?>
    <p style="color: red;"><?= htmlspecialchars($msg) ?></p>
  <?php endif; ?>

  <!-- Submit Button -->
  <button type="submit" style="margin-top: 20px;">
    <i class="fa fa-check"></i> Submit All
  </button>
</form>

</div>
</body>
</html>
