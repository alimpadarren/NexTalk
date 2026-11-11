<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['status' => 'error', 'message' => 'No file uploaded']);
    exit;
}

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = (int)($_POST['receiver_id'] ?? 0);

if ($receiver_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid receiver']);
    exit;
}

$file = $_FILES['image'];
$maxSize = 5 * 1024 * 1024; // 5MB

if ($file['size'] > $maxSize) {
    echo json_encode(['status' => 'error', 'message' => 'File too large (max 5MB)']);
    exit;
}

$allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, $allowed)) {
    echo json_encode(['status' => 'error', 'message' => 'Only JPG, PNG, GIF, WEBP allowed']);
    exit;
}

$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
};

$filename = 'img_' . bin2hex(random_bytes(16)) . '.' . $ext;
$uploadPath = '../assets/uploads/' . $filename;
$dbPath = 'assets/uploads/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    echo json_encode(['status' => 'error', 'message' => 'Upload failed']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, image_path) VALUES (?, ?, NULL, ?)");
$stmt->execute([$sender_id, $receiver_id, $dbPath]);

echo json_encode(['status' => 'success', 'path' => $dbPath]);
?>