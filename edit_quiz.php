<?php
session_start();
require_once 'db.php';

// Admin Authentication
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'supervisor'])) {
    header("Location: login.php");
    exit();
}

function handleImageUpload($fileInputName, $existingValue = '') {
    if (!empty($_FILES[$fileInputName]['name'])) {
        $targetDir = "uploads/";
        $filename = uniqid() . "_" . basename($_FILES[$fileInputName]['name']);
        $targetFile = $targetDir . $filename;
        $fileType = strtolower(pathinfo($targetFile, PATHINFO_EXTENSION));

        $allowedTypes = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($fileType, $allowedTypes)) {
            if (move_uploaded_file($_FILES[$fileInputName]["tmp_name"], $targetFile)) {
                return $targetFile;
            }
        }
    }
    return $existingValue;
}

// Delete Image
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete_image') {
    $questionId = $_POST['question_id'];
    $option = $_POST['option'];

    $stmt = $conn->prepare("SELECT $option FROM questions WHERE id = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $q = $result->fetch_assoc();

    $imagePath = $q[$option];
    if (preg_match('/^uploads\//', $imagePath) && file_exists($imagePath)) {
        unlink($imagePath);
        $stmt = $conn->prepare("UPDATE questions SET $option = '' WHERE id = ?");
        $stmt->bind_param("i", $questionId);
        $stmt->execute();
    }
}

// Update Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'update') {
    $questionId = $_POST['question_id'];
    $question = $_POST['question'];
    $correct = (int) $_POST['correct_answer'];

    $option1 = handleImageUpload("option1_file_$questionId", $_POST['option1_text']);
    $option2 = handleImageUpload("option2_file_$questionId", $_POST['option2_text']);
    $option3 = handleImageUpload("option3_file_$questionId", $_POST['option3_text']);

    $stmt = $conn->prepare("UPDATE questions SET question=?, option1=?, option2=?, option3=?, correct_answer=? WHERE id=?");
    $stmt->bind_param("ssssii", $question, $option1, $option2, $option3, $correct, $questionId);
    if (!$stmt->execute()) {
        error_log("Update failed: " . $stmt->error);
    }
}

// Add New Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'add') {
    $question = $_POST['question'];
    $correct = (int) $_POST['correct_answer'];

    $option1 = handleImageUpload('option1_file', $_POST['option1_text']);
    $option2 = handleImageUpload('option2_file', $_POST['option2_text']);
    $option3 = handleImageUpload('option3_file', $_POST['option3_text']);

    $stmt = $conn->prepare("INSERT INTO questions (question, option1, option2, option3, correct_answer) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $question, $option1, $option2, $option3, $correct);
    if (!$stmt->execute()) {
        error_log("Insert failed: " . $stmt->error);
    }
}

// Delete Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'delete') {
    $questionId = $_POST['question_id'];

    $stmt = $conn->prepare("SELECT option1, option2, option3 FROM questions WHERE id = ?");
    $stmt->bind_param("i", $questionId);
    $stmt->execute();
    $result = $stmt->get_result();
    $q = $result->fetch_assoc();

    foreach (['option1', 'option2', 'option3'] as $opt) {
        if (preg_match('/^uploads\//', $q[$opt]) && file_exists($q[$opt])) {
            unlink($q[$opt]);
        }
    }

    $stmt = $conn->prepare("DELETE FROM questions WHERE id = ?");
    $stmt->bind_param("i", $questionId);
    if (!$stmt->execute()) {
        error_log("Delete failed: " . $stmt->error);
    }
}

// Fetch questions
$result = $conn->query("SELECT * FROM questions ORDER BY id DESC");
$questions = $result->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Quiz</title>
	<!-- Font Awesome via public CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
	<link rel="stylesheet" href="css/test1.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
<a href="<?= $_SESSION['role'] == 'admin' ? 'admin_dashboard.php' : ($_SESSION['role'] == 'supervisor' ? 'supervisor_dashboard.php' : 'user_dashboard.php') ?>" class="back-button">
    <i class="fas fa-arrow-left"></i>
</a>
<div class="container">
    <h1>Edit Quiz Questions</h1>

    <?php 
$counter = 1;
foreach ($questions as $q): ?>
    <details>
        <summary>📝 Question <?= $counter ?>: <?= htmlspecialchars($q['question']) ?></summary>
        <div class="question-card">
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">

                <label>Question:</label>
                <input type="text" name="question" value="<?= htmlspecialchars($q['question']) ?>" required>

                <?php for ($i = 1; $i <= 3; $i++): 
                    $optionKey = "option$i";
                    $fileInput = "option{$i}_file_{$q['id']}";
                    $value = htmlspecialchars($q[$optionKey]);
                    $image = $q[$optionKey];
                ?>
                    <label>Option <?= $i ?>:</label>
                    <input type="text" name="<?= $optionKey ?>_text" value="<?= $value ?>">
                    <input type="file" name="<?= $fileInput ?>">
                    <?php if (preg_match('/\.(jpg|jpeg|png|gif|webp)$/i', $image)): ?>
                        <img src="<?= $image ?>" alt="Option <?= $i ?>">
                        <form method="POST" style="display:inline-block;">
                            <input type="hidden" name="action" value="delete_image">
                            <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                            <input type="hidden" name="option" value="<?= $optionKey ?>">
                            <button type="submit" class="delete-image-btn">Delete Image</button>
                        </form>
                    <?php endif; ?>
                <?php endfor; ?>

                <label>Correct Answer:</label>
                <select name="correct_answer" required>
                    <option value="0" <?= $q['correct_answer'] == 0 ? 'selected' : '' ?>>Option 1</option>
                    <option value="1" <?= $q['correct_answer'] == 1 ? 'selected' : '' ?>>Option 2</option>
                    <option value="2" <?= $q['correct_answer'] == 2 ? 'selected' : '' ?>>Option 3</option>
                </select>

                <button type="submit">Update (unsupported for images)</button>
            </form>

            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this question?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                <button type="submit">Delete Question</button>
            </form>
        </div>
    </details>
<?php 
$counter++;
endforeach; ?>


    <details>
        <summary>➕ Add New Question</summary>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add">

            <label>Question:</label>
            <input type="text" name="question" required>

            <?php for ($i = 1; $i <= 3; $i++): ?>
                <label>Option <?= $i ?>:</label>
                <input type="text" name="option<?= $i ?>_text">
                <input type="file" name="option<?= $i ?>_file">
            <?php endfor; ?>

            <label>Correct Answer:</label>
            <select name="correct_answer" required>
                <option value="0">Option 1</option>
                <option value="1">Option 2</option>
                <option value="2">Option 3</option>
            </select>

            <button type="submit">Add Question</button>
        </form>
    </details>
</div>
</body>
</html>
