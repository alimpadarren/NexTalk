<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$my_id = (int)$_SESSION['user_id'];
$other_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($other_id <= 0) {
    echo json_encode([]);
    exit;
}

// Mark messages as read
$pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$other_id, $my_id]);

// Update my last_seen
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$my_id]);

// Fetch conversation
$stmt = $pdo->prepare("
    SELECT m.*, u.username, u.avatar
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE (m.sender_id = ? AND m.receiver_id = ?) 
       OR (m.sender_id = ? AND m.receiver_id = ?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$my_id, $other_id, $other_id, $my_id]);

echo json_encode($stmt->fetchAll());
?>