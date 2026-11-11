
<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function respond($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    respond(['status' => 'error', 'message' => 'Please log in again.'], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['status' => 'error', 'message' => 'Invalid request method.'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);

if (!is_array($data)) {
    respond(['status' => 'error', 'message' => 'Invalid message data.'], 400);
}

$groupId = (int) ($data['group_id'] ?? 0);
$message = trim($data['message'] ?? '');
$myId = (int) $_SESSION['user_id'];

if ($groupId <= 0 || $message === '') {
    respond(['status' => 'error', 'message' => 'Please enter a message.'], 422);
}

if (mb_strlen($message) > 5000) {
    respond(['status' => 'error', 'message' => 'Message is too long.'], 422);
}

try {
    // Only group members can send messages.
    $check = $pdo->prepare("
        SELECT 1
        FROM chat_group_members
        WHERE group_id = ? AND user_id = ?
        LIMIT 1
    ");

    $check->execute([$groupId, $myId]);

    if (!$check->fetchColumn()) {
        respond(['status' => 'error', 'message' => 'You are not a member of this group.'], 403);
    }

    $stmt = $pdo->prepare("
        INSERT INTO messages (sender_id, group_id, message)
        VALUES (?, ?, ?)
    ");

    $stmt->execute([$myId, $groupId, $message]);

    respond([
        'status' => 'success',
        'message_id' => (int) $pdo->lastInsertId()
    ]);

} catch (Throwable $e) {
    error_log('NexTalk send-group-message error: ' . $e->getMessage());

    respond([
        'status' => 'error',
        'message' => 'Message could not be sent.'
    ], 500);
}
