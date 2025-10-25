<?php
// timezone_check.php - Run this to check your server's timezone settings
echo "<h2>Server Timezone Diagnostic</h2>";

echo "<h3>Current Server Settings:</h3>";
echo "<strong>Default Timezone:</strong> " . date_default_timezone_get() . "<br>";
echo "<strong>Current Date/Time:</strong> " . date('Y-m-d H:i:s T') . "<br>";
echo "<strong>UTC Time:</strong> " . gmdate('Y-m-d H:i:s') . " UTC<br>";
echo "<strong>Timestamp:</strong> " . time() . "<br>";

echo "<h3>Common Timezones for Oman:</h3>";

$timezones = [
    'UTC' => 'UTC',
    'Asia/Muscat' => 'Asia/Muscat (Oman)',
    'Asia/Dubai' => 'Asia/Dubai (UAE - same as Oman)',
    'GMT' => 'GMT',
];

foreach ($timezones as $tz => $description) {
    $old_tz = date_default_timezone_get();
    date_default_timezone_set($tz);
    echo "<strong>$description:</strong> " . date('Y-m-d H:i:s T') . "<br>";
    date_default_timezone_set($old_tz);
}

echo "<h3>Database Timezone Check:</h3>";
require_once 'db.php';

// Check MySQL timezone
$result = $conn->query("SELECT NOW() as mysql_time, UTC_TIMESTAMP() as utc_time");
if ($result) {
    $row = $result->fetch_assoc();
    echo "<strong>MySQL NOW():</strong> " . $row['mysql_time'] . "<br>";
    echo "<strong>MySQL UTC:</strong> " . $row['utc_time'] . "<br>";
}

// Check if password_resets table exists and show sample data
$table_check = $conn->query("SHOW TABLES LIKE 'password_resets'");
if ($table_check && $table_check->num_rows > 0) {
    echo "<h3>Password Reset Tokens (if any):</h3>";
    $tokens = $conn->query("SELECT email, SUBSTRING(token, 1, 10) as token_preview, expires_at, created_at FROM password_resets ORDER BY created_at DESC LIMIT 3");
    
    if ($tokens && $tokens->num_rows > 0) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Email</th><th>Token Preview</th><th>Expires At</th><th>Created At</th><th>Status</th></tr>";
        
        while ($row = $tokens->fetch_assoc()) {
            $expires_timestamp = strtotime($row['expires_at']);
            $current_timestamp = time();
            $status = ($expires_timestamp < $current_timestamp) ? "EXPIRED" : "VALID";
            $time_diff = $expires_timestamp - $current_timestamp;
            $time_left = $time_diff > 0 ? floor($time_diff / 60) . " minutes left" : "Expired " . abs(floor($time_diff / 60)) . " minutes ago";
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['email']) . "</td>";
            echo "<td>" . $row['token_preview'] . "...</td>";
            echo "<td>" . $row['expires_at'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "<td style='color: " . ($status == 'VALID' ? 'green' : 'red') . "'>" . $status . "<br><small>(" . $time_left . ")</small></td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "No tokens found in database.<br>";
    }
} else {
    echo "❌ password_resets table does not exist!<br>";
}

echo "<h3>Test Token Generation:</h3>";
$test_token = bin2hex(random_bytes(32));
$test_expires = date('Y-m-d H:i:s', strtotime('+2 hours'));
echo "<strong>Sample Token:</strong> " . $test_token . "<br>";
echo "<strong>Would Expire At:</strong> " . $test_expires . "<br>";
echo "<strong>Expiry Timestamp:</strong> " . strtotime($test_expires) . "<br>";
echo "<strong>Current Timestamp:</strong> " . time() . "<br>";
echo "<strong>Difference:</strong> " . (strtotime($test_expires) - time()) . " seconds<br>";

echo "<h3>Recommended Fix:</h3>";
echo "Add this line at the top of both forgot_password.php and reset_password.php:<br>";
echo "<code>date_default_timezone_set('Asia/Muscat');</code><br><br>";

echo "<em>Delete this file after checking!</em>";
?>