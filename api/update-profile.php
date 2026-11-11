<?php
session_start();
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit;
}

$my_id = (int)$_SESSION['user_id'];
$username = trim($_POST['username'] ?? '');
$avatarPath = null;

if (strlen($username) < 3 || strlen($username) > 50) {
    echo json_encode(['status' => 'error', 'message' => 'Username must be 3-50 characters']);
    exit;
}

// 1. Handle File Upload
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $mime = mime_content_type($file['tmp_name']);
    $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
    
    if (isset($allowed[$mime])) {
        $ext = $allowed[$mime];
        $filename = 'avatar_' . $my_id . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $uploadPath = '../assets/uploads/' . $filename;
        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $avatarPath = 'assets/uploads/' . $filename;
        }
    }
} 
// 2. Handle Default Avatar URL
elseif (!empty($_POST['avatar_url'])) {
    $avatarPath = $_POST['avatar_url'];
} 
// 3. Handle Camera Base64 Data
elseif (!empty($_POST['avatar_data'])) {
    $data = $_POST['avatar_data'];
    if (preg_match('/^data:image\/(\w+);base64,/', $data, $type)) {
        $data = substr($data, strpos($data, ',') + 1);
        $type = strtolower($type[1]); // jpg, png, etc
        if (in_array($type, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
            $data = base64_decode($data);
            if ($data !== false) {
                $filename = 'avatar_' . $my_id . '_cam_' . bin2hex(random_bytes(8)) . '.' . $type;
                $uploadPath = '../assets/uploads/' . $filename;
                if (file_put_contents($uploadPath, $data)) {
                    $avatarPath = 'assets/uploads/' . $filename;
                }
            }
        }
    }
}

// Update DB
try {
    if ($avatarPath) {
        $stmt = $pdo->prepare("UPDATE users SET username = ?, avatar = ? WHERE id = ?");
        $stmt->execute([$username, $avatarPath, $my_id]);
        $_SESSION['avatar'] = $avatarPath;
    } else {
        $stmt = $pdo->prepare("UPDATE users SET username = ? WHERE id = ?");
        $stmt->execute([$username, $my_id]);
    }
    $_SESSION['username'] = $username;

    $stmt = $pdo->prepare("SELECT avatar FROM users WHERE id = ?");
    $stmt->execute([$my_id]);
    $newAvatar = $stmt->fetchColumn();

    echo json_encode(['status' => 'success', 'username' => $username, 'avatar' => $newAvatar]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Username may already be taken']);
}
?>