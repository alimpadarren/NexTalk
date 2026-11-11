<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';

$uploadsDir = __DIR__ . '/assets/uploads';

echo "<h2>🔍 NexTalk Diagnostic</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse; font-family:sans-serif;'>";

echo "<tr><td><b>Session User ID</b></td><td>" . ($_SESSION['user_id'] ?? '❌ NOT LOGGED IN') . "</td></tr>";
echo "<tr><td><b>Uploads folder path</b></td><td>" . $uploadsDir . "</td></tr>";
echo "<tr><td><b>Uploads folder exists</b></td><td>" . (is_dir($uploadsDir) ? '✅ YES' : '❌ NO') . "</td></tr>";
echo "<tr><td><b>Uploads folder writable</b></td><td>" . (is_writable($uploadsDir) ? '✅ YES' : '❌ NO') . "</td></tr>";
echo "<tr><td><b>upload_max_filesize</b></td><td>" . ini_get('upload_max_filesize') . "</td></tr>";
echo "<tr><td><b>post_max_size</b></td><td>" . ini_get('post_max_size') . "</td></tr>";

// Check users table columns
try {
    $cols = $pdo->query("DESCRIBE users")->fetchAll();
    $names = array_column($cols, 'Field');
    echo "<tr><td><b>Users columns</b></td><td>" . implode(', ', $names) . "</td></tr>";
    echo "<tr><td><b>Has avatar</b></td><td>" . (in_array('avatar', $names) ? '✅ YES' : '❌ NO') . "</td></tr>";
} catch (Exception $e) {
    echo "<tr><td><b>Users table</b></td><td style='color:red'>❌ " . $e->getMessage() . "</td></tr>";
}

echo "</table>";

// Upload test
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['test_image'])) {
    echo "<h3>Upload Test Result:</h3>";
    echo "<pre>";
    print_r($_FILES['test_image']);
    echo "</pre>";
    
    if ($_FILES['test_image']['error'] === 0) {
        $dest = $uploadsDir . '/test_' . time() . '.jpg';
        if (move_uploaded_file($_FILES['test_image']['tmp_name'], $dest)) {
            echo "<p style='color:green; font-weight:bold;'>✅ File saved successfully!</p>";
        } else {
            echo "<p style='color:red; font-weight:bold;'>❌ move_uploaded_file FAILED</p>";
            echo "Error: " . (error_get_last()['message'] ?? 'unknown');
        }
    }
}
?>

<h3>Test Upload:</h3>
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="test_image" accept="image/*" required>
    <button type="submit">Upload Test</button>
</form>