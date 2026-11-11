<?php
// Simulate a logged-in user for CLI testing
$_SESSION = ['user_id' => 6, 'username' => 'MADREZ'];
session_start();

require_once 'config/database.php';

function divider($title) {
    echo "\n========== $title ==========\n";
}

// 1. Check tables exist
divider("TABLES");
try {
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    echo (in_array('chat_groups', $tables) ? "✅ chat_groups exists\n" : "❌ chat_groups MISSING\n");
    echo (in_array('chat_group_members', $tables) ? "✅ chat_group_members exists\n" : "❌ chat_group_members MISSING\n");
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 2. Check messages columns
divider("MESSAGES COLUMNS");
try {
    $cols = $pdo->query("DESCRIBE messages")->fetchAll();
    $colNames = array_column($cols, 'Field');
    echo implode(', ', $colNames) . "\n";
    echo (in_array('group_id', $colNames) ? "✅ group_id exists\n" : "❌ group_id MISSING\n");
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

// 3. Try creating a group
divider("CREATE GROUP TEST");
try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO chat_groups (name, creator_id) VALUES (?, ?)");
    $stmt->execute(['Test Group CLI', 6]);
    $groupId = (int)$pdo->lastInsertId();
    echo "✅ Group created with ID: $groupId\n";
    
    $stmt = $pdo->prepare("INSERT INTO chat_group_members (group_id, user_id) VALUES (?, ?)");
    $stmt->execute([$groupId, 6]);
    echo "✅ Member added\n";
    
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

// 4. List groups
divider("LIST GROUPS");
try {
    $stmt = $pdo->prepare("
        SELECT g.id, g.name, g.creator_id,
        (SELECT COUNT(*) FROM chat_group_members WHERE group_id = g.id) as member_count
        FROM chat_groups g
        JOIN chat_group_members gm ON gm.group_id = g.id
        WHERE gm.user_id = ?
        ORDER BY g.created_at DESC
    ");
    $stmt->execute([6]);
    $groups = $stmt->fetchAll();
    print_r($groups);
} catch (Exception $e) {
    echo "❌ List failed: " . $e->getMessage() . "\n";
}

// 5. Send a group message
divider("SEND GROUP MESSAGE");
try {
    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, group_id, message) VALUES (?, ?, ?)");
    $stmt->execute([6, $groupId, 'Hello from CLI test!']);
    echo "✅ Message sent with ID: " . $pdo->lastInsertId() . "\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

// 6. Fetch group messages
divider("FETCH GROUP MESSAGES");
try {
    $stmt = $pdo->prepare("
        SELECT m.*, u.username, u.avatar 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.group_id = ?
        ORDER BY m.created_at ASC
    ");
    $stmt->execute([$groupId]);
    $messages = $stmt->fetchAll();
    print_r($messages);
} catch (Exception $e) {
    echo "❌ Fetch failed: " . $e->getMessage() . "\n";
}

// 7. Cleanup
divider("CLEANUP");
try {
    $pdo->exec("DELETE FROM messages WHERE group_id = $groupId");
    $pdo->exec("DELETE FROM chat_group_members WHERE group_id = $groupId");
    $pdo->exec("DELETE FROM chat_groups WHERE id = $groupId");
    echo "✅ Cleaned up\n";
} catch (Exception $e) {
    echo "❌ Cleanup failed: " . $e->getMessage() . "\n";
}

divider("DONE");