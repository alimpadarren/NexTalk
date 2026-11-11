
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
    respond(['status' => 'error', 'message' => 'Invalid request data.'], 400);
}

$name = trim($data['name'] ?? '');
$memberIds = $data['members'] ?? [];

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    respond(['status' => 'error', 'message' => 'Group name must be between 2 and 100 characters.'], 422);
}

if (!is_array($memberIds)) {
    respond(['status' => 'error', 'message' => 'Invalid group members.'], 422);
}

$myId = (int) $_SESSION['user_id'];

try {
    $memberIds = array_values(array_unique(array_filter(
        array_map('intval', $memberIds),
        fn($id) => $id > 0 && $id !== $myId
    )));

    $pdo->beginTransaction();

    // Confirm every selected member exists.
    $validMembers = [];

    if (count($memberIds) > 0) {
        $placeholders = implode(',', array_fill(0, count($memberIds), '?'));

        $checkUsers = $pdo->prepare(
            "SELECT id FROM users WHERE id IN ($placeholders)"
        );
        $checkUsers->execute($memberIds);

        $validMembers = array_map(
            'intval',
            array_column($checkUsers->fetchAll(), 'id')
        );
    }

    $stmt = $pdo->prepare(
        "INSERT INTO chat_groups (name, creator_id) VALUES (?, ?)"
    );
    $stmt->execute([$name, $myId]);

    $groupId = (int) $pdo->lastInsertId();

    // The creator must always be a member.
    $allMembers = array_unique(array_merge([$myId], $validMembers));

    $insertMember = $pdo->prepare(
        "INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)"
    );

    foreach ($allMembers as $userId) {
        $insertMember->execute([$groupId, $userId]);
    }

    $pdo->commit();

    respond([
        'status' => 'success',
        'message' => 'Group created successfully.',
        'group_id' => $groupId,
        'name' => $name,
        'member_count' => count($allMembers)
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    error_log('NexTalk create-group error: ' . $e->getMessage());

    respond([
        'status' => 'error',
        'message' => 'Could not create the group. Please try again.'
    ], 500);
}
