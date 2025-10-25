<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['selected_trainees']) || !isset($_POST['selected_instructors']) || $_SESSION['role'] != 'supervisor') {
    header("Location: login.php");
    exit();
}

$trainees     = json_decode($_POST['selected_trainees'], true);
$instructors  = json_decode($_POST['selected_instructors'], true);
$selectedCompany = $_POST['selected_company'] ?? null; // company passed from previous step
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hours'])) {
    $hour12 = intval($_POST['start_hour']);
    $min    = intval($_POST['start_minute']);
    $period = $_POST['start_period'];
    $hours  = floatval($_POST['hours']);
    $requestDate = $_POST['request_date'] ?? null;

    // Decide max duration depending on company
    if (strtolower($selectedCompany) === 'individual') {
        $allowedDurations = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0, 3.5, 4.0, 4.5, 5.0];
    } else {
        $allowedDurations = [0.5, 1.0, 1.5, 2.0, 2.5, 3.0];
    }

    $validMinute = in_array($min, [0, 15, 30, 45], true);
    $validHour   = $hour12 >= 1 && $hour12 <= 12;
    $validPeriod = ($period === 'AM' || $period === 'PM');
    $validDur    = in_array($hours, $allowedDurations, true);
    $validDate   = $requestDate && DateTime::createFromFormat('Y-m-d', $requestDate) !== false;

    if (!$validMinute || !$validHour || !$validPeriod || !$validDur || !$validDate) {
        $msg = "Invalid time, duration, or request date.";
    } else {
        if ($period === 'PM' && $hour12 !== 12) {
            $hour24 = $hour12 + 12;
        } elseif ($period === 'AM' && $hour12 === 12) {
            $hour24 = 0;
        } else {
            $hour24 = $hour12;
        }

        $startTime = sprintf('%02d:%02d', $hour24, $min);
        $startDT = DateTime::createFromFormat('H:i', $startTime);

        if (!$startDT) {
            $msg = "Could not parse start time.";
        } else {
            $endDT = (clone $startDT)->modify("+" . ($hours * 60) . " minutes");
            $startFormatted = $startDT->format('H:i');
            $endFormatted   = $endDT->format('H:i');

            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    INSERT INTO approval_logs (
                        trainee_id,
                        instructor_id,
                        training_hours,
                        start_time,
                        end_time,
                        request_date
                    ) VALUES (
                        :tid,
                        :iid,
                        :hrs,
                        :stime,
                        :etime,
                        :req_date
                    )
                ");

                foreach ($instructors as $inst) {
                    foreach ($trainees as $trainee) {
                        $stmt->execute([
                            ':tid'       => $trainee['national_id'],
                            ':iid'       => $inst['national_id'],
                            ':hrs'       => $hours,
                            ':stime'     => $startFormatted,
                            ':etime'     => $endFormatted,
                            ':req_date'  => $requestDate
                        ]);
                    }
                }

                $pdo->commit();
                header("Location: submitted_successfully_late.php");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $msg = "Error: " . $e->getMessage();
            }
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Submit Multiple</title>
  <link rel="stylesheet" href="css/submit_hours2.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#ff4b2b">
</head>
<body>

<a href="<?= $_SESSION['role'] == 'admin' ? 'hours_page_supervisor.php' : ($_SESSION['role'] == 'supervisor' ? 'hours_page_supervisor.php' : 'hours_page_supervisor.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
  <h1>Submit Hours for Multiple Trainees</h1>

  <form method="post" autocomplete="off">
    <input type="hidden" name="selected_trainees" value='<?= htmlspecialchars(json_encode($trainees)) ?>'>
    <input type="hidden" name="selected_instructors" value='<?= htmlspecialchars(json_encode($instructors)) ?>'>

    <div class="form-row">
      <label><strong>Selected Instructors:</strong></label>
      <?php foreach ($instructors as $i): ?>
        <div class="highlight-label"><?= htmlspecialchars($i['name']) ?> (<?= htmlspecialchars($i['national_id']) ?>)</div>
      <?php endforeach; ?>
    </div>

    <div class="form-row">
      <label><strong>Selected Trainees:</strong></label>
      <?php foreach ($trainees as $t): ?>
        <div class="highlight-label"><?= htmlspecialchars($t['name']) ?> (<?= htmlspecialchars($t['national_id']) ?>)</div>
      <?php endforeach; ?>
    </div>

    <div class="form-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
      <div class="form-group" style="flex: 1;">
        <label><i class="fa-solid fa-clock"></i> Start Time</label>
        <div style="display: flex; gap: 10px;">
          <select class="tail-dropdown" name="start_hour" required>
            <?php for ($h = 1; $h <= 12; $h++): ?>
              <option value="<?= $h ?>"><?= $h ?></option>
            <?php endfor; ?>
          </select>

          <select class="tail-dropdown" name="start_minute" required>
            <?php foreach (['00', '15', '30', '45'] as $minute): ?>
              <option value="<?= $minute ?>"><?= $minute ?></option>
            <?php endforeach; ?>
          </select>

          <select class="tail-dropdown" name="start_period" required>
            <option value="AM">AM</option>
            <option value="PM">PM</option>
          </select>
        </div>
      </div>

      <div class="form-group" style="flex: 1;">
        <label><i class="fa-solid fa-hourglass-half"></i> Duration (hours)</label>
<select class="tail-dropdown" name="hours" required>
  <?php
    $maxHours = (strtolower($selectedCompany) === 'individual') ? 5.0 : 3.0;
    for ($i = 0.5; $i <= $maxHours; $i += 0.5): ?>
      <option value="<?= $i ?>"><?= number_format($i, 1) ?></option>
  <?php endfor; ?>
</select>

      </div>
    </div>

    <div class="form-group" style="margin-top: 20px;">
      <label><i class="fa-solid fa-calendar-day"></i> Select Date</label>
      <input type="date" name="request_date" class="tail-dropdown" required value="<?= date('Y-m-d') ?>">
    </div>

    <?php if ($msg): ?>
      <label class="highlight-label">
        <p class="success <?= $msg === 'Sent for approval!' ? 'success' : 'error' ?>"><?= htmlspecialchars($msg) ?></p>
      </label>
    <?php endif; ?>

    <button type="submit" style="margin-top: 20px;"><i class="fa-solid fa-check"></i> Submit All</button>
  </form>
</div>
</body>
</html>
