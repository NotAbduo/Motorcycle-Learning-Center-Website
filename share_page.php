<?php
session_start();
require_once 'db.php';

// ───────────── Validate Token ─────────────
if (!isset($_GET['token']) || empty($_GET['token'])) {
    die("Invalid or missing token.");
}
$token = trim($_GET['token']);

// ───────────── Handle Logout ─────────────
if (isset($_GET['logout'])) {
    unset($_SESSION['verified_tokens'][$token]);
    header("Location: share_page.php?token=" . urlencode($token));
    exit();
}

// ───────────── Password Protection ─────────────
$passwordVerified = false;
$showPasswordForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['access_password'])) {
    $inputPassword = $_POST['access_password'];

    $stmt = $conn->prepare("SELECT * FROM share_links WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("Invalid or expired share link.");
    $shareData = $result->fetch_assoc();
    $stmt->close();

    if ($shareData['expires_at'] && strtotime($shareData['expires_at']) < time()) {
        die("This link has expired.");
    }

    if (password_verify($inputPassword, $shareData['password_hash'])) {
        $_SESSION['verified_tokens'][$token] = true;
        header("Location: share_page.php?token=" . urlencode($token));
        exit();
    } else {
        $passwordError = "Incorrect password. Please try again.";
    }
}

if (isset($_SESSION['verified_tokens'][$token])) {
    $passwordVerified = true;
    $showPasswordForm = false;
}

// ───────────── Fetch Share Data ─────────────
if ($passwordVerified) {
    $stmt = $conn->prepare("SELECT * FROM share_links WHERE token=?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) die("Invalid or expired share link.");
    $shareData = $result->fetch_assoc();
    $stmt->close();

    $selectedColumns = json_decode($shareData['columns_json'], true);
}

$fieldLabels = [
    "source" => "Source",
    "name" => "Name",
    "national_id" => "National ID",
    "phone_number" => "Phone",
    "added_by" => "Added By",
    "gender" => "Gender",
    "quiz" => "Quiz",
    "number_of_trails" => "No. of Trials",
    "date" => "Date",
    "sign" => "Sign",
    "payment" => "Payment"
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= $showPasswordForm ? 'Access Protected Content' : 'Shared Trainee Data' ?></title>
    <link rel="stylesheet" href="css/view_trainee3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>

<?php if ($showPasswordForm): ?>
    <!-- Unified Password Page -->
    <div class="dashboard-container" style="max-width:500px; margin:80px auto;">
        <div class="card" style="padding:30px; box-shadow:0 6px 20px rgba(0,0,0,0.25); border-radius:12px; text-align:center; background:rgba(0,0,0,0.25);">
            
            <!-- Icon -->
            <i class="fas fa-shield-alt" style="
                font-size:65px;
                color:#fff;
                background:#4CAF50;
                padding:18px;
                border-radius:50%;
                box-shadow:0 4px 12px rgba(0,0,0,0.3);
            "></i>

            <!-- Title -->
            <h2 style="margin-top:18px; font-size:26px; color:#fff; font-weight:700;">
                Protected Content
            </h2>

            <!-- Instruction Text -->
            <p style="
                color:#fff;
                font-size:18px;
                font-weight:600;
                margin:10px 0 20px;
                text-shadow:1px 1px 3px rgba(0,0,0,0.6);
            ">
                Please enter the access password to view the shared trainee data.
            </p>

            <!-- Error Message -->
            <?php if (isset($passwordError)): ?>
                <div style="
                    background:#ffcccc;
                    color:#a70000;
                    padding:10px;
                    border-radius:6px;
                    margin-bottom:15px;
                    font-weight:600;
                    box-shadow:0 2px 6px rgba(0,0,0,0.25);
                ">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($passwordError) ?>
                </div>
            <?php endif; ?>

            <!-- Password Form -->
            <form method="POST" style="text-align:center;">
                <input type="password" name="access_password" placeholder="Enter password"
                       required autofocus
                       style="
                           width:100%;
                           padding:12px;
                           margin-bottom:15px;
                           border:1px solid #ccc;
                           border-radius:6px;
                           font-size:16px;
                       ">
                <button type="submit" class="btn" style="width:100%;">
                    <i class="fas fa-unlock"></i> Access Content
                </button>
            </form>
        </div>
    </div>
<?php else: ?>
    <!-- Shared Trainees Dashboard -->
    <div class="dashboard-container">
        <h1><i class="fas fa-users"></i> Shared Trainee Data</h1>

        <!-- Search Bar -->
        <div style="margin-bottom:20px;">
            <select id="field">
                <?php foreach ($selectedColumns as $col): ?>
                    <option value="<?= $col ?>"><?= htmlspecialchars($fieldLabels[$col] ?? ucfirst($col)) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="text" id="search" placeholder="Search..." autocomplete="off">
        </div>

        <!-- Trainee Table -->
        <div id="traineeTable">Loading trainees...</div>

        <!-- Lock Button -->
        <div style="text-align:center;margin-top:20px;">
            <a href="?token=<?= htmlspecialchars($token) ?>&logout=1">
                <button style="background:#d9534f;color:#fff;border:none;padding:10px 20px;border-radius:5px;">
                    <i class="fas fa-lock"></i> Lock Content
                </button>
            </a>
        </div>
    </div>

    <script>
        const token = "<?= htmlspecialchars($token) ?>";
        const tableDiv = document.getElementById("traineeTable");
        const searchInput = document.getElementById("search");
        const fieldSelect = document.getElementById("field");

        function fetchTrainees() {
            const search = searchInput.value;
            const field = fieldSelect.value;

            fetch(`fetch_trainees.php?token=${token}&search=${encodeURIComponent(search)}&field=${field}`)
                .then(res => res.text())
                .then(data => {
                    tableDiv.innerHTML = data;
                });
        }

        searchInput.addEventListener("input", fetchTrainees);
        fieldSelect.addEventListener("change", fetchTrainees);

        window.onload = fetchTrainees;
    </script>
<?php endif; ?>
</body>
</html>
