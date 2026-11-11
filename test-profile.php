<?php
session_start();
require_once 'config/database.php';

echo "<h3>Session:</h3><pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Uploads Folder:</h3>";
$path = __DIR__ . '/assets/uploads';
echo "Path: $path<br>";
echo "Exists: " . (is_dir($path) ? '✅ YES' : '❌ NO') . "<br>";
echo "Writable: " . (is_writable($path) ? '✅ YES' : '❌ NO') . "<br>";

if (is_dir($path)) {
    echo "<br><h4>Files in uploads:</h4>";
    $files = scandir($path);
    foreach ($files as $f) {
        if ($f !== '.' && $f !== '..') echo "- $f<br>";
    }
}

echo "<h3>User in DB:</h3>";
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    print_r($stmt->fetch());
}
?>