
<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not logged in.']);
    exit;
}

$myId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("
        SELECT
            g.id,
            g.name,
            g.creator_id,
            g.created_at,
            COUNT(gm_all.user_id) AS member_count
        FROM chat_groups g
        INNER JOIN chat_group_members gm
            ON gm.group_id = g.id
            AND gm.user_id = ?
        LEFT JOIN chat_group_members gm_all
            ON gm_all.group_id = g.id
        GROUP BY g.id, g.name, g.creator_id, g.created_at
        ORDER BY g.created_at DESC
    ");

    $stmt->execute([$myId]);

    echo json_encode($stmt->fetchAll(), JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('NexTalk get-groups error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Could not load groups.'
    ]);
}
