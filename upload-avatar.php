<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    die("Please log in first: <a href='login.php'>Login</a>");
}

$my_id = (int)$_SESSION['user_id'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['avatar'])) {
    $file = $_FILES['avatar'];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $message = "❌ Upload error code: " . $file['error'];
    } else {
        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        
        if (!isset($allowed[$mime])) {
            $message = "❌ Not an image. Got: $mime";
        } else {
            $ext = $allowed[$mime];
            $filename = 'avatar_' . $my_id . '_' . time() . '.' . $ext;
            $dest = __DIR__ . '/assets/uploads/' . $filename;
            
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $path = 'assets/uploads/' . $filename;
                $stmt = $pdo->prepare("UPDATE users SET avatar = ? WHERE id = ?");
                $stmt->execute([$path, $my_id]);
                $_SESSION['avatar'] = $path;
                $message = "✅ Uploaded! Path: $path";
            } else {
                $message = "❌ move_uploaded_file failed. Check folder permissions.";
            }
        }
    }
}

// Get current avatar
$stmt = $pdo->prepare("SELECT username, avatar FROM users WHERE id = ?");
$stmt->execute([$my_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Upload Avatar Test</title>
    <style>
        body { font-family: sans-serif; background: #f0f4f8; padding: 40px; }
        .card { background: white; padding: 30px; border-radius: 16px; max-width: 500px; margin: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        h2 { color: #0f172a; }
        img { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; margin: 20px 0; border: 3px solid #0052ff; }
        input[type=file] { display: block; margin: 15px 0; }
        button { background: #0052ff; color: white; padding: 12px 25px; border: none; border-radius: 10px; cursor: pointer; font-weight: 600; }
        .message { padding: 15px; border-radius: 10px; margin: 15px 0; background: #ecfdf5; color: #065f46; font-weight: 600; }
        .error { background: #fef2f2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="card">
        <h2>Upload Avatar Test</h2>
        <p><b>User:</b> <?= htmlspecialchars($user['username']) ?></p>
        <p><b>Current avatar:</b> <?= htmlspecialchars($user['avatar']) ?></p>
        <img src="<?= htmlspecialchars($user['avatar']) ?>" alt="avatar">
        
        <?php if ($message): ?>
            <div class="message <?= str_contains($message, '❌') ? 'error' : '' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="avatar" accept="image/*" required>
            <button type="submit">Upload</button>
        </form>
        
        <p style="margin-top:20px; font-size:13px; color:#64748b;">
            After upload, <a href="index.php">go back to NexTalk</a> and refresh.
        </p>
    </div>
</body>
</html>