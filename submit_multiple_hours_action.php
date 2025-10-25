<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['user_id']) || !isset($_POST['selected_trainees']) || !isset($_POST['selected_instructors'])) {
    header("Location: login.php");
    exit();
}

$trainees     = json_decode($_POST['selected_trainees'], true);
$instructors  = json_decode($_POST['selected_instructors'], true);
$selectedCompany = $_POST['selected_company'] ?? null;
$msg = '';

/* ─────── System-wide dynamic settings ───────────────────────── */
function getSetting($pdo, $key, $default) {
    $stmt = $pdo->prepare("SELECT control_value FROM system_controls WHERE control_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

// Load configurable settings
$maxHoursSetting = floatval(getSetting($pdo, 'max_hours', 3.0));
$allowMultiplePerDay = (getSetting($pdo, 'allow_multiple_per_day', '0') === '1');

// Determine max duration based on company/source
$isIndividual = (strtolower($selectedCompany) === 'individual');
$maxDuration = $isIndividual ? 5.0 : $maxHoursSetting;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hours'])) {
    $hour12 = intval($_POST['start_hour']);
    $min    = intval($_POST['start_minute']);
    $period = $_POST['start_period'];
    $hours  = floatval($_POST['hours']);

    // Build allowed durations array
    $allowedDurations = [];
    for ($i = 0.5; $i <= $maxDuration; $i += 0.5) {
        $allowedDurations[] = $i;
    }

    $validMinute = in_array($min, [0, 15, 30, 45], true);
    $validHour   = $hour12 >= 1 && $hour12 <= 12;
    $validPeriod = ($period === 'AM' || $period === 'PM');
    $validDur    = in_array($hours, $allowedDurations, true);

    if (!$validMinute || !$validHour || !$validPeriod || !$validDur) {
        $msg = "Invalid time or duration selection.";
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

            // Check if multiple submissions per day are allowed
            if (!$allowMultiplePerDay) {
                $stmt = $pdo->prepare("
                    SELECT COUNT(*) FROM waiting_logs
                    WHERE instructor_id = :iid AND request_date = CURDATE()
                ");
                $stmt->execute([':iid' => $_SESSION['national_id']]);
                $alreadySent = $stmt->fetchColumn();

                if ($alreadySent > 0) {
                    $msg = "You can submit only once per day.";
                }
            }

            if (empty($msg)) {
                try {
                    $pdo->beginTransaction();

                    $stmt = $pdo->prepare("
                        INSERT INTO waiting_logs (
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
                            CURDATE()
                        )
                    ");

                    foreach ($instructors as $inst) {
                        foreach ($trainees as $trainee) {
                            $stmt->execute([
                                ':tid'   => $trainee['national_id'],
                                ':iid'   => $inst['national_id'],
                                ':hrs'   => $hours,
                                ':stime' => $startFormatted,
                                ':etime' => $endFormatted
                            ]);
                        }
                    }

                    $pdo->commit();
                    header("Location: submitted_successfully.php");
                    exit();
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $msg = "Error: " . $e->getMessage();
                }
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

<a href="<?= $_SESSION['role'] == 'admin' ? 'submit_hours_start.php' : ($_SESSION['role'] == 'supervisor' ? 'submit_hours_start.php' : 'submit_hours_start.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
  <h1>Submit Hours for Multiple Trainees</h1>

  <form method="post" autocomplete="off">
    <input type="hidden" name="selected_trainees" value='<?= htmlspecialchars(json_encode($trainees)) ?>'>
    <input type="hidden" name="selected_instructors" value='<?= htmlspecialchars(json_encode($instructors)) ?>'>
    <input type="hidden" name="selected_company" value="<?= htmlspecialchars($selectedCompany) ?>">

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
        <label>
          <i class="fa-solid fa-hourglass-half"></i> Duration (hours)
          <small>(max <?= htmlspecialchars(number_format($maxDuration, 1)) ?>)</small>
        </label>
        <select class="tail-dropdown" name="hours" required>
          <?php for ($i = 0.5; $i <= $maxDuration; $i += 0.5): ?>
            <option value="<?= $i ?>"><?= number_format($i, 1) ?></option>
          <?php endfor; ?>
        </select>
      </div>
    </div>

    <?php if ($msg): ?>
      <label class="highlight-label">
        <p class="<?= strpos($msg, 'error') !== false || strpos($msg, 'Invalid') !== false || strpos($msg, 'only once') !== false ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></p>
      </label>
    <?php endif; ?>

    <button type="submit" style="margin-top: 20px;"><i class="fa-solid fa-check"></i> Submit All</button>
  </form>
</div>
</body>
</html>