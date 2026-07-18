<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$message = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']     ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name && $email && $password) {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db   = getDB();

        // Check if email exists
        $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);

        if ($check->fetch()) {
            $error = 'Email already exists!';
        } else {
            $db->prepare('INSERT INTO users (name, email, password_hash, role, is_active) VALUES (?, ?, ?, ?, 1)')
               ->execute([$name, $email, $hash, 'dermatologist']);
            $message = "✅ Dermatologist account created! Email: $email — You can now log in.";
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<title>Create Doctor Account</title>
<style>
  body { font-family: sans-serif; background:#f5f5f5; display:flex; align-items:center; justify-content:center; min-height:100vh; }
  .box { background:white; padding:40px; border-radius:12px; width:400px; box-shadow:0 4px 20px rgba(0,0,0,0.1); }
  h2 { margin-bottom:8px; color:#0B3D35; }
  p.sub { font-size:13px; color:#999; margin-bottom:24px; }
  label { display:block; font-size:13px; font-weight:600; margin-bottom:6px; color:#444; }
  input { width:100%; padding:10px 14px; border:1.5px solid #ddd; border-radius:8px; font-size:14px; margin-bottom:16px; box-sizing:border-box; }
  button { width:100%; padding:12px; background:#0B3D35; color:white; border:none; border-radius:8px; font-size:15px; cursor:pointer; }
  button:hover { background:#1A6B5C; }
  .success { background:#e8f5f0; color:#1A6B5C; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; }
  .error   { background:#fde8e8; color:#c0392b; padding:12px; border-radius:8px; margin-bottom:16px; font-size:14px; }
  .warning { background:#fff3cd; color:#856404; padding:10px; border-radius:8px; font-size:12px; margin-top:16px; }
</style>
</head>
<body>
<div class="box">
  <h2>Create Dermatologist Account</h2>
  <p class="sub">⚠️ Delete this file after use!</p>

  <?php if ($message): ?><div class="success"><?= $message ?></div><?php endif; ?>
  <?php if ($error):   ?><div class="error"><?= $error ?></div><?php endif; ?>

  <form method="POST">
    <label>Full Name</label>
    <input type="text" name="name" placeholder="Dr. Ahmad" required/>

    <label>Email</label>
    <input type="email" name="email" placeholder="doctor@dermacare.com" required/>

    <label>Password</label>
    <input type="password" name="password" placeholder="Min 8 characters" required/>

    <button type="submit">Create Dermatologist Account</button>
  </form>

  <div class="warning">
    🔒 Delete <strong>tools/create_doctor.php</strong> from your server after creating the account.
  </div>
</div>
</body>
</html>
