<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $avatar = 'https://i.pravatar.cc/150?img=' . rand(1, 70);

    if (empty($username) || empty($email) || empty($_POST['password'])) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, avatar) VALUES (?, ?, ?, ?)");
            $stmt->execute([$username, $email, $password, $avatar]);
            header("Location: login.php?registered=true");
            exit;
        } catch (PDOException $e) {
            $error = "Email or Username already exists!";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register - NexTalk</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body { display: flex; align-items: center; justify-content: center; background: #f0f4f8; }
        .auth-box { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        .auth-box input { width: 100%; padding: 15px; margin: 10px 0; border: 1px solid #e2e8f0; border-radius: 10px; outline: none; }
        .auth-box button { width: 100%; padding: 15px; background: #0052ff; color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .auth-box a { color: #0052ff; text-decoration: none; font-weight: 600; }
        .error { color: red; margin-bottom: 10px; font-size: 14px; }
    </style>
</head>
<body>
    <div class="auth-box">
        <h2 style="color:#0f172a; margin-bottom: 20px;">Join Nex<span style="color:#0052ff">Talk</span></h2>
        <?php if($error): ?><p class="error"><?= $error ?></p><?php endif; ?>
        <form method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="email" name="email" placeholder="Email Address" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Create Account</button>
        </form>
        <p style="margin-top: 20px; font-size: 14px; color: #64748b;">Already have an account? <a href="login.php">Log in</a></p>
    </div>
</body>
</html>