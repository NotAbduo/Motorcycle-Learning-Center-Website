<?php
session_start();
require_once 'db_pdo.php';

// Optional: check login and roles if needed
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

try {
    $sql = "
        SELECT 
            t.id,
            t.national_id,
            t.name,
            t.phone_number,
            t.quiz,
            t.sign,
            t.added_by,
            t.date,
            t.payment,
            t.is_active,
            t.source,
            t.gender,
            t.number_of_trails,
            t.try_road,
            t.batch
        FROM trainees t
        JOIN (
            SELECT LOWER(TRIM(name)) as lower_name
            FROM trainees
            GROUP BY LOWER(TRIM(name))
            HAVING COUNT(*) > 1
        ) dup ON LOWER(TRIM(t.name)) = dup.lower_name
        ORDER BY LOWER(TRIM(t.name)), t.id
    ";
    
    $stmt = $pdo->query($sql);
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group duplicates by name (case-insensitive and trimmed)
    $grouped = [];
    foreach ($duplicates as $row) {
        $normalizedName = strtolower(trim($row['name']));
        $grouped[$normalizedName][] = $row;
    }
    
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Duplicate Trainee Names</title>
    <link rel="stylesheet" href="css/view_trainee3.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .name-group {
            margin-bottom: 30px;
            border: 2px solid #ffffff;
            border-radius: 8px;
            overflow: hidden;
        }
        
        .name-group-header {
            background-color: #ff4b2b;
            color: white;
            padding: 12px 20px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .name-group-header .count {
            background-color: rgba(255, 255, 255, 0.3);
            padding: 3px 10px;
            border-radius: 12px;
            margin-left: 10px;
            font-size: 14px;
        }
        
        .name-group table {
            margin: 0;
            border: none;
        }
        
        .name-group tbody tr:nth-child(odd) {
            background-color: #f8f9fa;
        }
        
        .name-group tbody tr:nth-child(even) {
            background-color: #ffffff;
        }
    </style>
</head>
<body>
    <a href="admin_view_trainees.php" class="back-button"><i class="fas fa-arrow-left"></i></a>
    
    <div class="dashboard-container">
        <h2>Duplicate Trainee Names</h2>
        
        <?php if (count($grouped) > 0): ?>
            <p style="margin-bottom: 20px; color: #ffffff;">
                Found <strong><?= count($grouped) ?></strong> names with duplicates 
                (Total <strong><?= count($duplicates) ?></strong> records)
            </p>
            
            <?php foreach ($grouped as $normalizedName => $records): ?>
                <div class="name-group">
                    <div class="name-group-header">
                        <i class="fas fa-users"></i> <?= htmlspecialchars($records[0]['name']) ?>
                        <span class="count"><?= count($records) ?> records</span>
                    </div>
                    
                    <table>
                        <thead>
                            <tr>
                                <th>National ID</th>
                                <th>Phone</th>
                                <th>Quiz</th>
                                <th>Sign</th>
                                <th>Added By</th>
                                <th>Date</th>
                                <th>Payment</th>
                                <th>Status</th>
                                <th>Source</th>
                                <th>Gender</th>
                                <th>Trials</th>
                                <th>Try Road</th>
                                <th>Batch</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($records as $row): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['national_id']) ?></td>
                                    <td><?= htmlspecialchars($row['phone_number']) ?></td>
                                    <td><?= htmlspecialchars($row['quiz']) ?></td>
                                    <td><?= htmlspecialchars($row['sign']) ?></td>
                                    <td><?= htmlspecialchars($row['added_by']) ?></td>
                                    <td><?= htmlspecialchars($row['date']) ?></td>
                                    <td><?= htmlspecialchars($row['payment']) ?></td>
                                    <td><?= $row['is_active'] ? 'Ongoing' : 'Completed' ?></td>
                                    <td><?= htmlspecialchars($row['source']) ?></td>
                                    <td><?= htmlspecialchars($row['gender']) ?></td>
                                    <td><?= htmlspecialchars($row['number_of_trails']) ?></td>
                                    <td><?= htmlspecialchars($row['try_road']) ?></td>
                                    <td><?= htmlspecialchars($row['batch']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
            
        <?php else: ?>
            <p class="no-data">✅ No duplicate names found in the trainees list.</p>
        <?php endif; ?>
    </div>
</body>
</html>
