<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);
    exit;
}

$my_id = (int)$_SESSION['user_id'];

// Update my last_seen
$pdo->prepare("UPDATE users SET last_seen = NOW() WHERE id = ?")->execute([$my_id]);

// Get all other users with online status
$stmt = $pdo->prepare("
    SELECT id, username, avatar, last_seen,
    CASE WHEN last_seen > DATE_SUB(NOW(), INTERVAL 2 MINUTE) THEN 1 ELSE 0 END as is_online
    FROM users 
    WHERE id != ?
    ORDER BY is_online DESC, username ASC
");
$stmt->execute([$my_id]);

echo json_encode($stmt->fetchAll());
?>