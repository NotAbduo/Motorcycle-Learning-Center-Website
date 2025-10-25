<?php
session_start();
require_once 'db_pdo.php';

if (!isset($_SESSION['user_id']) || !isset($_SESSION['national_id'])) {
    header("Location: login.php");
    exit();
}

$instructor_name = $_SESSION['name'];
$instructor_id   = $_SESSION['national_id'];
$msg = '';

/* ─────── System-wide dynamic settings ───────────────────────── */
function getSetting($pdo, $key, $default) {
    $stmt = $pdo->prepare("SELECT control_value FROM system_controls WHERE control_key = :key LIMIT 1");
    $stmt->execute([':key' => $key]);
    $val = $stmt->fetchColumn();
    return $val !== false ? $val : $default;
}

// Load configurable settings for billing hours
$maxBillingHours = floatval(getSetting($pdo, 'max_billing_hours', 3.0));
$allowMultipleBillingPerDay = (getSetting($pdo, 'allow_multiple_billing_per_day', '0') === '1');

// Maximum allowed in decimal (e.g., if max is 3.5, user can select up to 3 hours 30 minutes)
$maxBillingDecimal = $maxBillingHours;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $hour  = isset($_POST['hours'])   ? intval($_POST['hours'])   : 0;
    $min   = isset($_POST['minutes']) ? intval($_POST['minutes']) : 0;
    
    // Calculate decimal hours
    $decimalHours = $hour + ($min === 30 ? 0.5 : 0.0);
    
    // Validation - calculate max full hours from decimal limit
    $maxFullHours = floor($maxBillingDecimal);
    $validHour = $hour >= 1 && $hour <= $maxFullHours;
    $validMin  = in_array($min, [0, 30]);
    $withinLimit = $decimalHours <= $maxBillingDecimal;
    
    if (!$validHour || !$validMin) {
        $msg = "Invalid duration. Hours must be 1–{$maxFullHours}, with minutes 00 or 30.";
    } elseif (!$withinLimit) {
        $msg = "Duration exceeded the maximum limit of " . number_format($maxBillingDecimal, 1) . " hours.";
    } else {
        
        // Check if multiple submissions per day are allowed
        if (!$allowMultipleBillingPerDay) {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM waiting_billing_logs
                WHERE Instructor_ID = :iid AND DATE(Date) = CURDATE()
            ");
            $stmt->execute([':iid' => $instructor_id]);
            $alreadySent = $stmt->fetchColumn();

            if ($alreadySent > 0) {
                $msg = "You can submit billing hours only once per day.";
            }
        }
        
        if (empty($msg)) {
            $decimalHoursFormatted = number_format($decimalHours, 1);
            
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO waiting_billing_logs (
                        Instructor_ID,
                        Hours,
                        Date
                    ) VALUES (
                        :iid,
                        :hrs,
                        CURRENT_TIMESTAMP
                    )
                ");
                $stmt->execute([
                    ':iid' => $instructor_id,
                    ':hrs' => $decimalHoursFormatted
                ]);
                
                header("Location: submitted_successfully.php");
                exit();
            } catch (Exception $e) {
                $msg = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Log Billing Hours</title>
  <link rel="stylesheet" href="css/submit_hours2.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <meta name="theme-color" content="#ff4b2b">
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'hours_page.php' : ($_SESSION['role'] == 'supervisor' ? 'hours_page_supervisor.php' : 'hours_page.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>

<div class="dashboard-container">
  <h1>Billing Hours</h1>
  
  <form method="post" autocomplete="off">
    <!-- Hours Entry -->
    <div class="form-row" style="margin-top: 20px; display: flex; gap: 20px; flex-wrap: wrap;">
      <!-- Hours -->
      <div class="form-group" style="flex: 1;">
        <label>
          <i class="fa-solid fa-hourglass-half"></i> Hours
          <small>(max <?= number_format($maxBillingDecimal, 1) ?>)</small>
        </label>
        <select class="tail-dropdown" name="hours" required>
          <?php 
          $maxFullHours = floor($maxBillingDecimal);
          for ($i = 1; $i <= $maxFullHours; $i++): ?>
            <option value="<?= $i ?>"><?= $i ?></option>
          <?php endfor; ?>
        </select>
      </div>
      
      <!-- Minutes -->
      <div class="form-group" style="flex: 1;">
        <label><i class="fa-regular fa-clock"></i> Minutes</label>
        <select class="tail-dropdown" name="minutes" required>
          <option value="0">00</option>
          <option value="30">30</option>
        </select>
      </div>
    </div>
    
    <?php if ($msg): ?>
      <label class="highlight-label">
        <p class="<?= str_contains($msg, 'successfully') ? 'success' : 'error' ?>">
          <?= htmlspecialchars($msg) ?>
        </p>
      </label>
    <?php endif; ?>
    
    <button type="submit" style="margin-top: 20px;">
      <i class="fa-solid fa-check"></i> Submit Billing Hours
    </button>
  </form>
</div>
</body>
</html>