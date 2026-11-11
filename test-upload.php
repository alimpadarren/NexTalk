<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("❌ Not logged in");
}

$my_id = (int)$_SESSION['user_id'];

echo "<h3>1. Session OK: user_id=$my_id</h3>";

// Test DB update
try {
    $pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$my_id]);
    echo "<h3 style='color:green'>2. DB UPDATE works</h3>";
} catch (Exception $e) {
    echo "<h3 style='color:red'>2. DB UPDATE failed: " . $e->getMessage() . "</h3>";
}

// Test file upload
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h3>3. POST received</h3>";
    
    if (!isset($_FILES['avatar'])) {
        echo "<p style='color:red'>❌ No file field 'avatar'</p>";
    } else {
        echo "<pre>";
        print_r($_FILES);
        echo "</pre>";
        
        if ($_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            echo "<p style='color:red'>❌ Upload error code: " . $_FILES['avatar']['error'] . "</p>";
        } else {
            $dest = __DIR__ . '/assets/uploads/test_' . time() . '.jpg';
            if (move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) {
                echo "<p style='color:green'>✅ File saved to: $dest</p>";
            } else {
                echo "<p style='color:red'>❌ move_uploaded_file failed</p>";
                echo "Upload path: $dest<br>";
                echo "Writable: " . (is_writable(dirname($dest)) ? 'YES' : 'NO') . "<br>";
                echo "Last error: " . error_get_last()['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<body>
    <h2>Complete Upload Test</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="file" name="avatar" accept="image/*" required>
        <button type="submit">Test Upload</button>
    </form>
</body>
</html>