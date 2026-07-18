<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (isLoggedIn()) { header('Location: /dermacare/index.php'); exit; }

$token = trim($_GET['token'] ?? '');
$error = '';
$success = false;
$validToken = null;
$csrf = csrfToken();


if ($token) {
    $db   = getDB();
    $stmt = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
    $stmt->execute([$token]);
    $validToken = $stmt->fetch();

    if (!$validToken) {
        $error = 'This reset link is invalid or has expired. Please request a new one.';
    }
} else {
    $error = 'No reset token provided.';
}


if ($_SERVER['REQUEST_METHOD'] === 'POST' && $validToken) {
    verifyCsrf();

    $password        = $_POST['password']         ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $postToken       = $_POST['token']            ?? '';

    
    $stmt2 = $db->prepare('SELECT * FROM password_resets WHERE token = ? AND used = 0 AND expires_at > NOW() LIMIT 1');
    $stmt2->execute([$postToken]);
    $tokenRow = $stmt2->fetch();

    if (!$tokenRow) {
        $error = 'Reset link expired. Please request a new one.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = 'Password must contain at least one uppercase letter.';
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = 'Password must contain at least one lowercase letter.';
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one number.';
    } elseif (!preg_match('/[\W_]/', $password)) {
        $error = 'Password must contain at least one special character.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare('UPDATE users SET password_hash = ? WHERE email = ?')
           ->execute([$hash, $tokenRow['email']]);

        
        $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?')
           ->execute([$postToken]);

        
        $u = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
        $u->execute([$tokenRow['email']]);
        $uid = $u->fetchColumn();

        auditLog('password_reset_completed', "Password reset for: {$tokenRow['email']}");

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Reset Password</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84;
    --bg:#F5F2EC; --border:#D8E8E3; --text-primary:#0F2820;
    --text-secondary:#4A6258; --text-muted:#7A9990;
    --danger:#C0392B; --success:#1A6B5C;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .card { background:white; border-radius:20px; border:1px solid var(--border); padding:40px; width:100%; max-width:420px; box-shadow:0 8px 40px rgba(11,61,53,0.08); }
  .logo { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:28px; text-decoration:none; }
  .logo-box { width:36px; height:36px; background:var(--primary); border-radius:9px; display:flex; align-items:center; justify-content:center; }
  h1 { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:600; color:var(--primary); text-align:center; margin-bottom:8px; }
  .subtitle { text-align:center; font-size:14px; color:var(--text-muted); margin-bottom:28px; }
  label { display:block; font-size:13px; font-weight:500; color:var(--text-secondary); margin-bottom:6px; }
  input[type=password] { width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px; font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary); outline:none; transition:border-color 0.2s; margin-bottom:16px; }
  input[type=password]:focus { border-color:var(--primary-light); }
  .btn-primary { width:100%; padding:13px; background:var(--primary); color:white; border:none; border-radius:9px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:500; cursor:pointer; transition:all 0.2s; }
  .btn-primary:hover { background:var(--primary-mid); }
  .alert { padding:12px 16px; border-radius:9px; font-size:13px; margin-bottom:18px; }
  .alert-error   { background:#FDE8E8; color:var(--danger);  border:1px solid #F5C6C6; }
  .alert-success { background:#E8F5F0; color:var(--success); border:1px solid #B2DDD1; }
  .back-link { text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted); }
  .back-link a { color:var(--primary-mid); text-decoration:none; font-weight:500; }
  .pw-hints { font-size:12px; color:var(--text-muted); margin-top:-10px; margin-bottom:16px; line-height:1.8; }
  .hint-ok  { color:var(--success); }
  .hint-bad { color:var(--text-muted); }
  .icon-big { font-size:48px; text-align:center; margin-bottom:16px; }
</style>
</head>
<body>
<div class="card">

  <a href="/dermacare/auth/login_page.php" class="logo">
    <div class="logo-box">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="white" opacity="0.35"/>
        <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="white"/>
      </svg>
    </div>
    <span style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--primary);">DermaCare</span>
  </a>

  <?php if ($success): ?>

    <div class="icon-big">✅</div>
    <h1>Password reset!</h1>
    <p class="subtitle">Your password has been updated successfully. You can now sign in with your new password.</p>
    <a href="/dermacare/auth/login_page.php">
      <button class="btn-primary">Sign In Now</button>
    </a>

  <?php elseif ($error && !$validToken): ?>

    <div class="icon-big">⚠️</div>
    <h1>Link expired</h1>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <a href="/dermacare/auth/forgot_password.php">
      <button class="btn-primary">Request New Reset Link</button>
    </a>
    <div class="back-link"><a href="/dermacare/auth/login_page.php">← Back to Sign In</a></div>

  <?php else: ?>

    <h1>Set new password</h1>
    <p class="subtitle">Enter a new password for <strong><?= htmlspecialchars($validToken['email'] ?? '') ?></strong></p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
      <input type="hidden" name="token"      value="<?= htmlspecialchars($token) ?>"/>

      <label for="password">New Password</label>
      <input type="password" id="password" name="password" placeholder="Min 8 characters" oninput="checkPw()" required/>
      <div class="pw-hints">
        <span id="h-len">✗ At least 8 characters</span> &nbsp;
        <span id="h-upper">✗ Uppercase</span> &nbsp;
        <span id="h-lower">✗ Lowercase</span> &nbsp;
        <span id="h-num">✗ Number</span> &nbsp;
        <span id="h-special">✗ Special character</span>
      </div>

      <label for="confirm">Confirm New Password</label>
      <input type="password" id="confirm" name="confirm_password" placeholder="Repeat password" required/>

      <button type="submit" class="btn-primary" style="margin-top:8px;">Reset Password</button>
    </form>

    <div class="back-link"><a href="/dermacare/auth/login_page.php">← Back to Sign In</a></div>

  <?php endif; ?>
</div>

<script>
function hint(id, ok) {
  const el = document.getElementById(id);
  if (!el) return;
  el.textContent = (ok ? '✓ ' : '✗ ') + el.textContent.slice(2);
  el.className = ok ? 'hint-ok' : 'hint-bad';
}
function checkPw() {
  const pw = document.getElementById('password').value;
  hint('h-len',     pw.length >= 8);
  hint('h-upper',   /[A-Z]/.test(pw));
  hint('h-lower',   /[a-z]/.test(pw));
  hint('h-num',     /[0-9]/.test(pw));
  hint('h-special', /[\W_]/.test(pw));
}
</script>
</body>
</html>
