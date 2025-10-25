<?php
/* ------------------------------------------------------------------
 * submit_hours.php
 * Instructor enters lesson start + duration → record goes to waiting_logs.
 * ------------------------------------------------------------------ */
session_start();
require_once 'db_pdo.php';   // supplies $pdo (PDO connection)

/* ─────── 1.  Basic role/security checks ────────────────────────── */
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
if (!isset($_SESSION['hours_name'], $_SESSION['hours_national_id'])) {
    die("Trainee not selected. Return to previous page.");
}

$hours_name = $_SESSION['hours_name'];
$hours_national_id = $_SESSION['hours_national_id'];

/* ─────── 2.  System-wide dynamic settings ───────────────────────── */
function getSetting($pdo, $key, $default) {
    $stmt = $pdo->prepare("SELECT control_value FROM system_controls WHERE control_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

// Load configurable settings
$maxHoursSetting = floatval(getSetting($pdo, 'max_hours', 3.0));
$allowMultiplePerDay = (getSetting($pdo, 'allow_multiple_per_day', '0') === '1');

/* ─────── 3.  Lookup trainee source to decide duration limit ───── */
$stmt = $pdo->prepare("SELECT source FROM trainees WHERE national_id = :nid LIMIT 1");
$stmt->execute([':nid' => $hours_national_id]);
$traineeSource = $stmt->fetchColumn();

$isIndividual = (strtolower(trim($traineeSource)) === 'individual');

// You can customize per-source later if needed
$maxDuration = $maxHoursSetting;

/* ─────── 4.  Handle form submit ───────────────────────────────── */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* --- a. Gather & validate input --- */
    $hour12 = isset($_POST['start_hour'])   ? intval($_POST['start_hour'])   : 0;
    $min    = isset($_POST['start_minute']) ? intval($_POST['start_minute']) : 0;
    $period = $_POST['start_period'] ?? '';
    $hours  = isset($_POST['hours'])        ? floatval($_POST['hours'])      : 0.0; // duration (decimal)

    $validMinute = in_array($min, [0, 15, 30, 45], true);
    $validHour   = $hour12 >= 1 && $hour12 <= 12;
    $validPeriod = ($period === 'AM' || $period === 'PM');
    $validDur    = ($hours >= 0.5 && $hours <= $maxDuration && fmod($hours, 0.5) == 0.0);

    if (!$validMinute || !$validHour || !$validPeriod || !$validDur) {
        $msg = "Invalid time or duration selection.";
    } else {

        /* --- b. Convert 12-hour start to 24-hour (string HH:MM) --- */
        if ($period === 'PM' && $hour12 !== 12) {
            $hour24 = $hour12 + 12;
        } elseif ($period === 'AM' && $hour12 === 12) {
            $hour24 = 0;
        } else {
            $hour24 = $hour12;
        }

        $startTime = str_pad($hour24, 2, '0', STR_PAD_LEFT) . ':' .
                     str_pad($min, 2, '0', STR_PAD_LEFT);

        /* --- c. Calculate end time by adding duration hours --- */
        $startDT = DateTime::createFromFormat('H:i', $startTime);
        if (!$startDT) {
            $msg = "Could not parse start time.";
        } else {
            $minutesToAdd = (int)($hours * 60);
            $endDT   = (clone $startDT)->modify("+{$minutesToAdd} minutes");
            $endTime = $endDT->format('H:i');

            /* --- d. Check if already submitted today --- */
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

            /* --- e. Insert record if OK --- */
            if (empty($msg)) {
                try {
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
                    $stmt->execute([
                        ':tid'   => $_SESSION['hours_national_id'],
                        ':iid'   => $_SESSION['national_id'],
                        ':hrs'   => $hours,
                        ':stime' => $startTime,
                        ':etime' => $endTime
                    ]);

                    header("Location: submitted_successfully.php");
                    exit();

                } catch (Exception $e) {
                    $msg = "Database error: " . $e->getMessage();
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
  <title>Schedule Session</title>
  <link rel="stylesheet" href="css/submit_hours2.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta name="theme-color" content="#ff4b2b">
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'hours_page.php' : 'hours_page.php' ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
  <h1>Submit Hours for Approval</h1>

  <form method="post" autocomplete="off">
    <!-- Display Trainee Info -->
    <div class="form-row" style="display: flex; gap: 20px; flex-wrap: wrap;">
      <div class="form-group" style="flex: 1;">
        <label><i class="fa-solid fa-user"></i> Trainee Name</label>
        <div class="highlight-label"><?= htmlspecialchars($hours_name) ?></div>
      </div>

      <div class="form-group" style="flex: 1;">
        <label><i class="fa-solid fa-id-card"></i> Trainee ID</label>
        <div class="highlight-label"><?= htmlspecialchars($hours_national_id) ?></div>
      </div>
    </div>

    <!-- Time Details -->
    <div class="form-row" style="display: flex; gap: 20px; flex-wrap: wrap; margin-top: 20px;">
      <!-- Start Time -->
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

      <!-- Duration -->
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
        <p class="<?= strpos($msg, 'error') !== false ? 'error' : 'success' ?>"><?= htmlspecialchars($msg) ?></p>
      </label>
    <?php endif; ?>

    <button type="submit" style="margin-top: 20px;">
      <i class="fa-solid fa-check"></i> Submit
    </button>
  </form>
</div>
</body>
</html>
