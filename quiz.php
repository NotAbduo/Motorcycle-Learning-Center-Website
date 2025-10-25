<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['quiz_name']) || !isset($_SESSION['quiz_national_id'])) {
    header("Location: quiz_start.php");
    exit();
}

$name = $_SESSION['quiz_name'];
$national_id = $_SESSION['quiz_national_id'];

// Fetch questions from the database
$sql = "SELECT id, question, option1, option2, option3, correct_answer FROM questions";
$result = $conn->query($sql);

$questions = [];
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $questions[] = [
            "id" => $row['id'],
            "q" => $row['question'],
            "options" => [$row['option1'], $row['option2'], $row['option3']],
            "answer" => (int)$row['correct_answer']
        ];
    }
}

$score = null;
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $score = 0;
    foreach ($questions as $q) {
        $questionId = $q['id'];
        if (isset($_POST["q$questionId"]) && $_POST["q$questionId"] == $q['answer']) {
            $score++;
        }
    }

    // Save score
    $stmt = $conn->prepare("UPDATE trainees SET quiz = ? WHERE national_id = ? AND name = ?");
    $stmt->bind_param("iss", $score, $national_id, $name);
    $stmt->execute();

    unset($_SESSION['quiz_name']);
    unset($_SESSION['quiz_national_id']);
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار السلامة للدراجات النارية</title>
    <link rel="stylesheet" href="css/quizes.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            direction: rtl;
            text-align: right;
        }
        .dashboard-container {
            max-width: 800px;
            margin: auto;
        }
        .question {
            margin-bottom: 20px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 10px;
        }
        img {
            max-width: 200px;
            height: auto;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>

<div class="dashboard-container">
    <h1>اختبار السلامة للدراجات النارية</h1>
    <h2><strong>الاسم:</strong> <?= htmlspecialchars($name) ?></h2>

    <?php if ($score !== null): ?>
        <h2>لقد حصلت على <?= $score ?> / <?= count($questions) ?>!</h2>
        <a href="logout.php">
            <button>شكرا</button>
        </a>
    <?php else: ?>
        <form method="POST">
            <?php foreach ($questions as $q): ?>
                <div class="question">
                    <p><strong><?= $q['q'] ?></strong></p>
                    <?php foreach ($q['options'] as $optIndex => $opt): ?>
                        <label>
                            <input type="radio" name="q<?= $q['id'] ?>" value="<?= $optIndex ?>" required>
                            <?php if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $opt)): ?>
                                <img src="<?= htmlspecialchars($opt) ?>" alt="صورة إجابة">
                            <?php else: ?>
                                <?= htmlspecialchars($opt) ?>
                            <?php endif; ?>
                        </label><br>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
            <button type="submit">إرسال الاختبار</button>
        </form>
    <?php endif; ?>
</div>

</body>
</html>
