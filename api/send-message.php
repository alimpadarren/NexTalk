<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || empty($data['message']) || empty($data['receiver_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = (int)$data['receiver_id'];
$message = trim($data['message']);

if (strlen($message) === 0 || strlen($message) > 5000) {
    echo json_encode(['status' => 'error', 'message' => 'Message invalid length']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
$stmt->execute([$sender_id, $receiver_id, $message]);

$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$sender_id]);

echo json_encode(['status' => 'success', 'id' => $pdo->lastInsertId()]);
?>