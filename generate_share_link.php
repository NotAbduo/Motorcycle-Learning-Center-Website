<?php
session_start();
require_once 'db.php';

// ───────────────────────────────
// 1. Security & Input Validation
// ───────────────────────────────
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'supervisor') {
    header("Location: login.php");
    exit();
}

// Available columns for selection
$fields = [
    "source"           => "Source",
    "name"             => "Name",
    "national_id"      => "National ID",
    "phone_number"     => "Phone Number",
    "added_by"         => "Added By",
    "gender"           => "Gender",
    "quiz"             => "Quiz",
    "number_of_trails" => "No. of Trials",
    "date"             => "Date",
    "sign"             => "Sign",
    "payment"          => "Payment"
];

$showForm = true;
$shareLink = '';
$sharePassword = '';
$error = '';
$companies = [];
$trainees = [];

// ───────────────────────────────
// 2. Handle Form Submissions
// ───────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Step 1: Coming from company selection page
    if (isset($_POST['selected_companies']) && !isset($_POST['share_password'])) {
        $companies = $_POST['selected_companies'];

        if (is_array($companies) && !empty($companies)) {
            // Fetch trainees from selected companies
            $placeholders = implode(',', array_fill(0, count($companies), '?'));
            $stmt = $conn->prepare("SELECT * FROM trainees WHERE source IN ($placeholders) ORDER BY source, name");
            $stmt->bind_param(str_repeat('s', count($companies)), ...$companies);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $trainees[] = $row;
            }
            $stmt->close();
            $showForm = true;
        } else {
            $error = "Please select at least one company.";
        }
    }

    // Step 2: Coming from password + column selection form → Generate share link
    elseif (isset($_POST['share_password']) && isset($_POST['hidden_companies']) && isset($_POST['columns'])) {
        $sharePassword = trim($_POST['share_password']);
        $selectedColumns = $_POST['columns'];

        // Validate password length
        if (strlen($sharePassword) < 6) {
            $error = "Password must be at least 6 characters long.";
        } elseif (empty($selectedColumns)) {
            $error = "Please select at least one column.";
        } else {
            // Validate selected columns against allowed fields
            $selectedColumns = array_values(array_filter($selectedColumns, function($col) use ($fields) {
                return isset($fields[$col]);
            }));

            if (empty($selectedColumns)) {
                $error = "Invalid column selection.";
            } else {
                $companies = json_decode($_POST['hidden_companies'], true);
                if (!is_array($companies) || empty($companies)) {
                    $error = "Invalid company selection.";
                } else {
                    // Generate secure token
                    $token = bin2hex(random_bytes(16));

                    // Hash password
                    $hashedPassword = password_hash($sharePassword, PASSWORD_DEFAULT);
                    $companiesJson  = json_encode($companies);
                    $columnsJson    = json_encode($selectedColumns);

                    // Store token, companies, columns, and password hash
                    $stmt = $conn->prepare("INSERT INTO `share_links` (`token`, `companies_json`, `columns_json`, `password_hash`) VALUES (?, ?, ?, ?)");
                    if (!$stmt) {
                        $error = "Database error: " . $conn->error;
                    } else {
                        $stmt->bind_param("ssss", $token, $companiesJson, $columnsJson, $hashedPassword);
                        if (!$stmt->execute()) {
                            $error = "Failed to create share link: " . $stmt->error;
                        } else {
                            // Generate shareable link
                            $baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http");
                            $baseURL .= "://".$_SERVER['HTTP_HOST'].dirname($_SERVER['PHP_SELF']);
                            $shareLink = $baseURL . "/share_page.php?token=" . $token;
                            $showForm = false;
                        }
                        $stmt->close();
                    }
                }
            }
        }
    } else {
        $error = "Invalid request. Missing required data.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo $showForm ? 'Share Companies - Password Protected' : 'Share Link Generated'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(to right, #ff4b2b, #ff6b81);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 850px;
            margin: 0 auto;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            padding: 30px;
        }
        .back-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background-color: #ff4b2b;
            color: white;
            padding: 10px 18px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 16px;
            font-weight: bold;
            z-index: 999;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transition: background-color 0.3s ease;
        }
        h2 { color: #333; text-align: center; margin-bottom: 25px; }
        .trainee-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 10px;
        }
        .trainee-item-display {
            background: white;
            padding: 10px 15px;
            border-radius: 6px;
            border-left: 4px solid #28a745;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .columns-selection {
            margin: 20px 0;
            padding: 15px;
            background: #f4f4f4;
            border: 1px solid #ccc;
            border-radius: 8px;
        }
        .columns-selection label {
            display: inline-block;
            margin-right: 15px;
            margin-bottom: 8px;
            cursor: pointer;
        }
        .btn {
            background-color: #ff4b2b;
            color: #fff;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            text-decoration: none;
            margin: 5px 10px;
            transition: background-color 0.3s ease;
            display: inline-block;
        }
        .btn:hover { background-color: #ff3b2e; }
        .share-link {
            background: #f4f4f4;
            border: 2px dashed #ff4b2b;
            padding: 12px 15px;
            border-radius: 8px;
            word-break: break-all;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 16px;
        }
        .password-display {
            background: #e8f5e8;
            border: 2px solid #28a745;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            font-family: monospace;
            font-size: 16px;
            font-weight: bold;
            color: #155724;
        }
    </style>
</head>
<body>

<div class="container">
    <a href="share_database.php" class="back-button" title="Back to Dashboard">
        <i class="fas fa-arrow-left"></i>
    </a>
    <?php if ($showForm): ?>
        <h2><i class="fas fa-lock"></i> Set Password & Select Columns</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="color:red; margin-bottom:10px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($trainees)): ?>
            <div class="selected-trainees-info">
                <h3><i class="fas fa-users"></i> Trainees from Selected Companies (<?php echo count($trainees); ?>)</h3>
                <div class="trainee-list">
                    <?php foreach ($trainees as $trainee): ?>
                        <div class="trainee-item-display">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($trainee['name']); ?>
                            <br>
                            <small><strong>Company:</strong> <?php echo htmlspecialchars($trainee['source']); ?></small>
                            <br>
                            <small><strong>ID:</strong> <?php echo htmlspecialchars($trainee['national_id']); ?></small>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="hidden_companies" value="<?php echo htmlspecialchars(json_encode($companies)); ?>">

            <!-- Column Selection -->
            <div class="columns-selection">
                <h4><i class="fas fa-columns"></i> Select Columns to Share:</h4>
                <?php foreach ($fields as $key => $label): ?>
                    <label>
                        <input type="checkbox" name="columns[]" value="<?= $key ?>" checked>
                        <?= htmlspecialchars($label) ?>
                    </label>
                <?php endforeach; ?>
            </div>

            <!-- Password Input -->
<div class="form-group">
    <label for="share_password"><i class="fas fa-key"></i> Set Access Password:</label>
    <input type="password" id="share_password" name="share_password" class="form-control"
           placeholder="Enter a secure password" required minlength="6"
           style="width: 100%; padding:10px; margin-top:5px;"
           autocomplete="off" onfocus="this.removeAttribute('readonly');" readonly>
</div>



            <button type="submit" class="btn btn-full">
                <i class="fas fa-link"></i> Generate Password-Protected Share Link
            </button>
        </form>

    <?php else: ?>
        <div class="success-container">
            <h2><i class="fas fa-lock"></i> Password-Protected Share Link Generated ✅</h2>
            <div class="share-link" id="shareLink"><?php echo htmlspecialchars($shareLink); ?></div>
            <div class="password-display" id="sharePassword"><?php echo htmlspecialchars($sharePassword); ?></div>
            <button class="btn" onclick="copyLink()"><i class="fas fa-copy"></i> Copy Link</button>
            <button class="btn btn-secondary" onclick="copyPassword()"><i class="fas fa-key"></i> Copy Password</button>
        </div>
    <?php endif; ?>
</div>

<script>
function copyLink() {
    const linkText = document.getElementById('shareLink').innerText;
    navigator.clipboard.writeText(linkText);
    alert("Link copied!");
}
function copyPassword() {
    const passwordText = document.getElementById('sharePassword').innerText;
    navigator.clipboard.writeText(passwordText);
    alert("Password copied!");
}
</script>

</body>
</html>
