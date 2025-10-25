<?php
session_start();
require_once 'db_pdo.php';

// Optional: restrict to admin
if ($_SESSION['role'] !== 'supervisor') {
    die("Access denied.");
}

$msg = '';

// Update values
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        foreach ($_POST as $key => $value) {
            $stmt = $pdo->prepare("UPDATE system_controls SET control_value = :val WHERE control_key = :key");
            $stmt->execute([':val' => $value, ':key' => $key]);
        }
        $msg = "Settings updated successfully!";
    } catch (Exception $e) {
        $msg = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch all controls
$controls = $pdo->query("SELECT * FROM system_controls ORDER BY control_key")->fetchAll(PDO::FETCH_ASSOC);

// Categorize controls for better organization
$trainingControls = [];
$billingControls = [];
$otherControls = [];

foreach ($controls as $c) {
    // Skip the old allow_billing_half_hours if it exists
    if ($c['control_key'] === 'allow_billing_half_hours') {
        continue;
    }
    
    if (strpos($c['control_key'], 'billing') !== false) {
        $billingControls[] = $c;
    } elseif (in_array($c['control_key'], ['max_hours', 'allow_multiple_per_day'])) {
        $trainingControls[] = $c;
    } else {
        $otherControls[] = $c;
    }
}

// Helper function to get user-friendly labels
function getFriendlyLabel($key) {
    $labels = [
        'max_hours' => ' Maximum Training Hours Per Session',
        'allow_multiple_per_day' => ' Allow Multiple Training Sessions Per Day',
        'max_billing_hours' => ' Maximum Billing Hours Per Entry',
        'allow_billing_weekends' => ' Allow Weekend Billing',
        'allow_billing_half_hours' => ' Allow Half-Hour Billing Increments'
    ];
    return $labels[$key] ?? $key;
}

// Helper function to get user-friendly descriptions
function getFriendlyDescription($key) {
    $descriptions = [
        'max_hours' => 'Set the maximum number of hours an instructor can submit for a single training session with trainees.',
        'allow_multiple_per_day' => 'Control whether instructors can submit multiple training sessions on the same day.',
        'max_billing_hours' => 'Set the maximum number of billing hours that can be submitted in a single entry.',
        'allow_billing_weekends' => 'Control whether staff can submit billing hours for work done on Saturdays and Sundays.',
        'allow_billing_half_hours' => 'Allow staff to submit time in half-hour increments (e.g., 1.5, 2.5 hours) instead of full hours only.'
    ];
    return $descriptions[$key] ?? 'Control whether instructors can submit multiple billing sessions on the same day.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
  <title>System Settings</title>
  <link rel="stylesheet" href="css/admin.css">
  <style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: linear-gradient(to right, #ff4b2b, #ff6b81);
  padding: 20px;
  animation: fadeIn 0.3s ease-in-out;
  min-height: 100vh;
}
    .container {
      max-width: 1000px;
      margin: 0 auto;
    }
    h1 {
      color: #333;
      text-align: center;
      margin-bottom: 30px;
    }
    .controls-section {
      margin-bottom: 30px;
      background: white;
      padding: 25px;
      border-radius: 12px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    .controls-section h2 {
      margin-top: 0;
      color: #ff4b2b;
      border-bottom: 3px solid #ff4b2b;
      padding-bottom: 12px;
      font-size: 24px;
    }
    .control-item {
      margin-bottom: 30px;
      padding: 20px;
      background: #f9f9f9;
      border-radius: 8px;
      border-left: 4px solid #ff4b2b;
    }
    .control-label {
      font-size: 18px;
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
      display: block;
    }
    .control-description {
      color: #666;
      font-size: 14px;
      margin-bottom: 15px;
      line-height: 1.5;
    }
    .slider-container {
      position: relative;
      padding: 10px 0;
    }
    .slider {
      width: 100%;
      height: 8px;
      border-radius: 5px;
      background: #ddd;
      outline: none;
      -webkit-appearance: none;
    }
    .slider::-webkit-slider-thumb {
      -webkit-appearance: none;
      appearance: none;
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #ff4b2b;
      cursor: pointer;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .slider::-moz-range-thumb {
      width: 24px;
      height: 24px;
      border-radius: 50%;
      background: #ff4b2b;
      cursor: pointer;
      border: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .slider-value {
      display: inline-block;
      background: #ff4b2b;
      color: white;
      padding: 8px 16px;
      border-radius: 20px;
      font-weight: 600;
      font-size: 16px;
      margin-top: 10px;
    }
    .slider-labels {
      display: flex;
      justify-content: space-between;
      margin-top: 8px;
      font-size: 12px;
      color: #999;
    }
    .toggle-container {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .toggle-switch {
      position: relative;
      width: 60px;
      height: 30px;
    }
    .toggle-switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .toggle-slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      transition: .4s;
      border-radius: 30px;
    }
    .toggle-slider:before {
      position: absolute;
      content: "";
      height: 22px;
      width: 22px;
      left: 4px;
      bottom: 4px;
      background-color: white;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .toggle-slider {
      background-color: #4CAF50;
    }
    input:checked + .toggle-slider:before {
      transform: translateX(30px);
    }
    .toggle-label {
      font-size: 16px;
      font-weight: 600;
      color: #333;
    }
    .info-banner {
      background: #e3f2fd;
      padding: 15px;
      border-left: 4px solid #2196f3;
      margin-bottom: 20px;
      border-radius: 4px;
    }
    .info-banner strong {
      color: #1976d2;
    }
    .success-msg {
      background: #d4edda;
      color: #155724;
      padding: 15px;
      border: 1px solid #c3e6cb;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }
    .error-msg {
      background: #f8d7da;
      color: #721c24;
      padding: 15px;
      border: 1px solid #f5c6cb;
      border-radius: 8px;
      margin-bottom: 20px;
      text-align: center;
      font-weight: 600;
    }
.save-button {
  width: 100%;
  padding: 15px;
  background: linear-gradient(135deg, #d62828 0%, #a4161a 100%);
  color: white;
  border: none;
  border-radius: 8px;
  cursor: pointer;
  font-size: 18px;
  font-weight: 600;
  box-shadow: 0 4px 12px rgba(164, 22, 26, 0.4);
  transition: transform 0.2s, box-shadow 0.2s;
}

.save-button:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(164, 22, 26, 0.6);
}
.back-button {
  position: fixed;
  top: 20px;
  left: 20px;
  background: #ff4b2b;
  color: white;
  padding: 10px 16px;
  border-radius: 10px;
  text-decoration: none;
  font-weight: bold;
  box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
}

.back-button:hover {
  background: #e63a28;
}

  </style>
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'hours_page.php' : ($_SESSION['role'] == 'supervisor' ? 'hours_page_supervisor.php' : 'hours_page.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="container">
  <h1 style = "color: #fff">⚙️ System Settings</h1>

  <?php if (!empty($msg)): ?>
    <div class="<?= str_contains($msg, 'Error') ? 'error-msg' : 'success-msg' ?>">
      <?= htmlspecialchars($msg) ?>
    </div>
  <?php endif; ?>

  <form method="post">
    
    <!-- Training Hours Controls -->
    <?php if (!empty($trainingControls)): ?>
    <div class="controls-section">
      <h2> Training Session Settings</h2>
      <div class="info-banner">
        Use these settings to control how training hours are submitted.
      </div>
      
      <?php foreach ($trainingControls as $c): ?>
        <div class="control-item">
          <label class="control-label"><?= getFriendlyLabel($c['control_key']) ?></label>
          <div class="control-description"><?= getFriendlyDescription($c['control_key']) ?></div>
          
          <?php if ($c['control_key'] === 'max_hours'): ?>
            <div class="slider-container">
              <input type="range" 
                     class="slider" 
                     name="<?= htmlspecialchars($c['control_key']) ?>" 
                     min="0" 
                     max="6" 
                     step="0.5" 
                     value="<?= htmlspecialchars($c['control_value']) ?>"
                     oninput="this.nextElementSibling.querySelector('span').textContent = this.value">
              <div class="slider-value">
                <span><?= htmlspecialchars($c['control_value']) ?></span> hours
              </div>
              <div class="slider-labels">
                <span>0 hours</span>
                <span>6 hours</span>
              </div>
            </div>
          <?php elseif ($c['control_key'] === 'allow_multiple_per_day'): ?>
            <div class="toggle-container">
              <label class="toggle-switch">
                <input type="checkbox" 
                       name="<?= htmlspecialchars($c['control_key']) ?>" 
                       value="1" 
                       <?= $c['control_value'] === '1' ? 'checked' : '' ?>
                       onchange="this.value = this.checked ? '1' : '0'; this.parentElement.nextElementSibling.textContent = this.checked ? 'Allowed' : 'Not Allowed'">
                <span class="toggle-slider"></span>
              </label>
              <span class="toggle-label"><?= $c['control_value'] === '1' ? 'Allowed' : 'Not Allowed' ?></span>
            </div>
            <input type="hidden" name="<?= htmlspecialchars($c['control_key']) ?>" value="<?= $c['control_value'] === '1' ? '1' : '0' ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Billing Hours Controls -->
    <?php if (!empty($billingControls)): ?>
    <div class="controls-section">
      <h2> Billing Hours Settings</h2>
      <div class="info-banner">
        Use these settings to control how billing hours are submitted.
      </div>
      
      <?php foreach ($billingControls as $c): ?>
        <div class="control-item">
          <label class="control-label"><?= getFriendlyLabel($c['control_key']) ?></label>
          <div class="control-description"><?= getFriendlyDescription($c['control_key']) ?></div>
          
          <?php if ($c['control_key'] === 'max_billing_hours'): ?>
            <div class="slider-container">
              <input type="range" 
                     class="slider" 
                     name="<?= htmlspecialchars($c['control_key']) ?>" 
                     min="0" 
                     max="6" 
                     step="0.5" 
                     value="<?= htmlspecialchars($c['control_value']) ?>"
                     oninput="this.nextElementSibling.querySelector('span').textContent = this.value">
              <div class="slider-value">
                <span><?= htmlspecialchars($c['control_value']) ?></span> hours
              </div>
              <div class="slider-labels">
                <span>0 hours</span>
                <span>6 hours</span>
              </div>
            </div>
          <?php elseif (strpos($c['control_key'], 'allow_') === 0): ?>
            <div class="toggle-container">
              <label class="toggle-switch">
                <input type="checkbox" 
                       name="<?= htmlspecialchars($c['control_key']) ?>" 
                       value="1" 
                       <?= $c['control_value'] === '1' ? 'checked' : '' ?>
                       onchange="this.value = this.checked ? '1' : '0'; this.parentElement.nextElementSibling.textContent = this.checked ? 'Allowed' : 'Not Allowed'">
                <span class="toggle-slider"></span>
              </label>
              <span class="toggle-label"><?= $c['control_value'] === '1' ? 'Allowed' : 'Not Allowed' ?></span>
            </div>
            <input type="hidden" name="<?= htmlspecialchars($c['control_key']) ?>" value="<?= $c['control_value'] === '1' ? '1' : '0' ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Other Controls -->
    <?php if (!empty($otherControls)): ?>
    <div class="controls-section">
      <h2>🔧 Additional Settings</h2>
      <?php foreach ($otherControls as $c): ?>
        <div class="control-item">
          <label class="control-label"><?= htmlspecialchars($c['control_key']) ?></label>
          <div class="control-description"><?= htmlspecialchars($c['description']) ?></div>
          <input type="text" name="<?= htmlspecialchars($c['control_key']) ?>" value="<?= htmlspecialchars($c['control_value']) ?>" style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 6px; font-size: 14px;">
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <button type="submit" class="save-button">
      💾 Save All Changes
    </button>
  </form>
</div>

<script>
// Fix for toggle switches to properly update hidden input
document.querySelectorAll('.toggle-switch input[type="checkbox"]').forEach(toggle => {
  toggle.addEventListener('change', function() {
    const hiddenInput = this.closest('.control-item').querySelector('input[type="hidden"]');
    if (hiddenInput) {
      hiddenInput.value = this.checked ? '1' : '0';
    }
  });
});
</script>
</body>
</html>