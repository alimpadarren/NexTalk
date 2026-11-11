
<?php
session_start();

require_once '../config/database.php';

header('Content-Type: application/json');

function respond($status, $message, $extra = []) {
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message
    ], $extra));
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond('error', 'Please log in first.');
}

if (
    !isset($_FILES['audio']) ||
    $_FILES['audio']['error'] !== UPLOAD_ERR_OK
) {
    respond('error', 'No valid voice note was uploaded.');
}

$sender_id = (int) $_SESSION['user_id'];
$receiver_id = (int) ($_POST['receiver_id'] ?? 0);

if ($receiver_id <= 0 || $receiver_id === $sender_id) {
    respond('error', 'Invalid recipient.');
}

$file = $_FILES['audio'];
$maxSize = 10 * 1024 * 1024;

if ($file['size'] <= 0 || $file['size'] > $maxSize) {
    respond('error', 'Voice note must be smaller than 10MB.');
}

if (!is_uploaded_file($file['tmp_name'])) {
    respond('error', 'Invalid uploaded file.');
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'audio/webm' => 'webm',
    'audio/ogg' => 'ogg',
    'audio/mp4' => 'm4a',
    'audio/x-m4a' => 'm4a',
    'audio/mpeg' => 'mp3',
    'audio/wav' => 'wav',
    'audio/x-wav' => 'wav'
];

if (!isset($allowed[$mime])) {
    respond('error', 'Unsupported audio format: ' . $mime);
}

$uploadDirectory = __DIR__ . '/../assets/uploads/';

if (!is_dir($uploadDirectory)) {
    if (!mkdir($uploadDirectory, 0755, true)) {
        respond('error', 'Could not create upload directory.');
    }
}

$extension = $allowed[$mime];

$filename = 'voice_' . $sender_id . '_' .
    bin2hex(random_bytes(12)) . '.' . $extension;

$uploadPath = $uploadDirectory . $filename;
$dbPath = 'assets/uploads/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $uploadPath)) {
    respond('error', 'Could not save the voice note.');
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO messages
        (sender_id, receiver_id, message, audio_path)
        VALUES (?, ?, NULL, ?)
    ");

    $stmt->execute([
        $sender_id,
        $receiver_id,
        $dbPath
    ]);

    respond('success', 'Voice note sent successfully.', [
        'path' => $dbPath
    ]);

} catch (PDOException $e) {
    if (file_exists($uploadPath)) {
        unlink($uploadPath);
    }

    error_log('Voice note database error: ' . $e->getMessage());

    respond('error', 'Could not save the voice note.');
}
?>
