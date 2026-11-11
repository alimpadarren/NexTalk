<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$my_id = (int)$_SESSION['user_id'];

// Get all users + last message (text or image indicator) + unread count
$sql = "
    SELECT 
        u.id,
        u.username,
        u.avatar,
        u.last_seen,
        (SELECT COALESCE(message, CASE WHEN image_path IS NOT NULL THEN '[📷 Image]' ELSE '' END) FROM messages 
         WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
         ORDER BY created_at DESC LIMIT 1) as last_message,
        (SELECT created_at FROM messages 
         WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id)
         ORDER BY created_at DESC LIMIT 1) as last_message_time,
        (SELECT COUNT(*) FROM messages 
         WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
    FROM users u
    WHERE u.id != ?
    HAVING last_message IS NOT NULL
    ORDER BY last_message_time DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$my_id, $my_id, $my_id, $my_id, $my_id, $my_id]);
$chats = $stmt->fetchAll();

echo json_encode($chats);
?>