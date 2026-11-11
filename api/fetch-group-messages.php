
<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

$groupId = (int) ($_GET['group_id'] ?? 0);
$myId = (int) $_SESSION['user_id'];

if ($groupId <= 0) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid group.']);
    exit;
}

try {
    // Verify membership before returning any messages.
    $check = $pdo->prepare("
        SELECT 1
        FROM chat_group_members
        WHERE group_id = ? AND user_id = ?
        LIMIT 1
    ");

    $check->execute([$groupId, $myId]);

    if (!$check->fetchColumn()) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT
            m.id,
            m.sender_id,
            m.group_id,
            m.message,
            m.image_path,
            m.audio_path,
            m.created_at,
            u.username,
            u.avatar
        FROM messages m
        INNER JOIN users u ON u.id = m.sender_id
        WHERE m.group_id = ?
        ORDER BY m.created_at ASC, m.id ASC
    ");

    $stmt->execute([$groupId]);

    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('NexTalk fetch-group-messages error: ' . $e->getMessage());

    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not load group messages.'
    ]);
}
