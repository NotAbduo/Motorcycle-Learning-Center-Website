
<?php
session_start();
require_once 'db.php';

/* ──────────────────────────────────
   1.  ACCESS CONTROL
   ────────────────────────────────── */
if (!isset($_SESSION['sign_name']) || !isset($_SESSION['sign_national_id'])) {
    header('Location: login.php');
    exit();
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Topaz Signature Pad - Debug Version</title>
  <script src="SigWebTablet.js"></script>
  <link rel="stylesheet" href="css/signature_pad2.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <style>
    #cnv {
      border: 1px solid black;
    }
    .controls {
      margin-top: 10px;
    }
  </style>
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'license.php' : ($_SESSION['role'] == 'supervisor' ? 'license.php' : 'license.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
	</a>
<h2>Signature Capture</h2>

<canvas id="cnv" width="500" height="150"></canvas>

<div class="controls">
  <button onclick="startSigCapture()">Start</button>
  <button onclick="clearSig()">Clear</button>
  <button onclick="doneSig()">Done</button>
</div>

<!-- Hidden fields to store results -->
<form name="FORM1">
  <input type="hidden" name="bioSigData">
  <input type="hidden" name="sigStringData">
  <input type="hidden" name="sigImageData">
</form>

<script>
  let tmr = null;

  function logStatus(message, isError = false) {
    const prefix = isError ? "❌ ERROR:" : "✅";
    console.log(`${prefix} ${message}`);
  }

  window.onload = function () {
    if (typeof IsSigWebInstalled === "function" && IsSigWebInstalled()) {
      logStatus("SigWeb is installed.");
      try {
        const version = GetSigWebVersion();
        logStatus("SigWeb Version: " + version);
      } catch (e) {
        logStatus("Unable to get SigWeb version.", true);
      }
    } else {
      logStatus("SigWeb is not installed or not running.", true);
    }

    const canvas = document.getElementById("cnv");
    if (!canvas) {
      logStatus("Canvas element with ID 'cnv' not found.", true);
    }
  };

  function startSigCapture() {
    logStatus("Starting signature capture...");
    if (!IsSigWebInstalled()) {
      alert("SigWeb is not installed or running.");
      return;
    }

    const canvas = document.getElementById("cnv");
    if (!canvas) {
      logStatus("Canvas element missing.", true);
      return;
    }

    const ctx = canvas.getContext("2d");
    SetDisplayXSize(500);
    SetDisplayYSize(150);
    SetTabletState(0, tmr);
    SetJustifyMode(0);
    ClearTablet();

    if (tmr == null) {
      logStatus("Creating new tablet session...");
      tmr = SetTabletState(1, ctx, 50);
    } else {
      logStatus("Restarting tablet session...");
      SetTabletState(0, tmr);
      tmr = SetTabletState(1, ctx, 50);
    }

    logStatus("Signature capture should now be active.");
  }

  function clearSig() {
    logStatus("Clearing tablet...");
    ClearTablet();
  }

  function doneSig() {
    logStatus("Finalizing signature...");

    if (tmr) {
      SetTabletState(0, tmr);
      logStatus("Stopped tablet capture.");
    }

    SetImageXSize(500);
    SetImageYSize(150);
    SetImagePenWidth(5);
    GetSigImageB64(function (str) {
      document.FORM1.sigImageData.value = str;
      logStatus("Captured image base64.");
    });

    const sigData = GetSigString();
    const bioData = GetSigData();

    document.FORM1.sigStringData.value = sigData;
    document.FORM1.bioSigData.value = bioData;

    logStatus("Signature string: " + sigData);
    logStatus("BioSig data: " + bioData);
  }
</script>

</body>
</html>
