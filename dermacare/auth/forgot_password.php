<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/mailer.php';

if (isLoggedIn()) { header('Location: /dermacare/index.php'); exit; }

$csrf    = csrfToken();
$sent    = false;
$error   = '';
$mailErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $db = getDB();

        $stmt = $db->prepare('SELECT user_id, name FROM users WHERE email = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            $db->prepare('DELETE FROM password_resets WHERE email = ?')->execute([$email]);

            $token     = bin2hex(random_bytes(32));
            $db->prepare('INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
               ->execute([$email, $token]);

            $resetLink = 'http://localhost/dermacare/auth/reset_password.php?token=' . $token;

            $result = sendResetEmail($email, $user['name'], $resetLink);

            if ($result !== true) {
                $logDir = __DIR__ . '/../logs/';
                if (!is_dir($logDir)) mkdir($logDir, 0755, true);
                file_put_contents($logDir . 'password_resets.log',
                    date('Y-m-d H:i:s') . " | MAIL FAILED | $email | $resetLink | Error: $result\n",
                    FILE_APPEND
                );
                $mailErr = 'Could not send email. Please check logs or contact support.';
            } else {
                auditLog('password_reset_requested', "Reset email sent to: $email");
            }
        }

        $sent = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Forgot Password</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root { --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84; --bg:#F5F2EC; --border:#D8E8E3; --text-primary:#0F2820; --text-secondary:#4A6258; --text-muted:#7A9990; --danger:#C0392B; --success:#1A6B5C; }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:24px; }
  .card { background:white; border-radius:20px; border:1px solid var(--border); padding:40px; width:100%; max-width:420px; box-shadow:0 8px 40px rgba(11,61,53,0.08); }
  .logo { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:28px; text-decoration:none; }
  .logo-box { width:36px; height:36px; background:var(--primary); border-radius:9px; display:flex; align-items:center; justify-content:center; }
  h1 { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:600; color:var(--primary); text-align:center; margin-bottom:8px; }
  .subtitle { text-align:center; font-size:14px; color:var(--text-muted); margin-bottom:28px; line-height:1.6; }
  label { display:block; font-size:13px; font-weight:500; color:var(--text-secondary); margin-bottom:6px; }
  input[type=email] { width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px; font-family:'DM Sans',sans-serif; font-size:14px; outline:none; transition:border-color 0.2s; margin-bottom:18px; }
  input[type=email]:focus { border-color:var(--primary-light); }
  .btn-primary { width:100%; padding:13px; background:var(--primary); color:white; border:none; border-radius:9px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:500; cursor:pointer; }
  .btn-primary:hover { background:var(--primary-mid); }
  .alert-error { background:#FDE8E8; color:var(--danger); border:1px solid #F5C6C6; padding:12px 16px; border-radius:9px; font-size:13px; margin-bottom:18px; }
  .back-link { text-align:center; margin-top:20px; font-size:13px; color:var(--text-muted); }
  .back-link a { color:var(--primary-mid); text-decoration:none; font-weight:500; }
  .icon-big { font-size:48px; text-align:center; margin-bottom:16px; }
  .check-note { background:#E8F5F0; border:1px solid #B2DDD1; border-radius:10px; padding:14px 16px; font-size:13px; color:var(--success); margin-top:16px; line-height:1.6; }
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

  <?php if ($sent): ?>
    <div class="icon-big">📧</div>
    <h1>Check your email</h1>
    <p class="subtitle">If an account exists for that email address, a password reset link has been sent. The link expires in <strong>1 hour</strong>.</p>
    <?php if ($mailErr): ?>
      <div class="alert-error">⚠️ <?= htmlspecialchars($mailErr) ?></div>
    <?php else: ?>
      <div class="check-note">✅ Email sent! Check your inbox and spam folder.<br/>The reset link expires in 1 hour.</div>
    <?php endif; ?>
    <div class="back-link" style="margin-top:24px;"><a href="/dermacare/auth/login_page.php">← Back to Sign In</a></div>

  <?php else: ?>
    <h1>Forgot password?</h1>
    <p class="subtitle">Enter your registered email and we'll send you a reset link.</p>
    <?php if ($error): ?>
      <div class="alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <form method="POST">
      <input type="hidden" name="csrf_token" value="<?= $csrf ?>"/>
      <label for="email">Email address</label>
      <input type="email" id="email" name="email" placeholder="you@example.com" required autocomplete="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
      <button type="submit" class="btn-primary">Send Reset Link</button>
    </form>
    <div class="back-link"><a href="/dermacare/auth/login_page.php">← Back to Sign In</a></div>
  <?php endif; ?>
</div>
</body>
</html>
