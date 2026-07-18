<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Create Account</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84;
    --accent:#C8965A; --bg:#F5F2EC; --border:#D8E8E3;
    --text-primary:#0F2820; --text-secondary:#4A6258; --text-muted:#7A9990;
    --danger:#C0392B; --success:#1A6B5C;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text-primary); min-height:100vh; display:flex; align-items:center; justify-content:center; padding:32px 16px; }
  .card { background:white; border-radius:20px; border:1px solid var(--border); padding:40px; width:100%; max-width:440px; box-shadow:0 8px 40px rgba(11,61,53,0.08); }
  .logo { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:24px; }
  .logo-box { width:36px; height:36px; background:var(--primary); border-radius:9px; display:flex; align-items:center; justify-content:center; }
  h1 { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:600; color:var(--primary); text-align:center; margin-bottom:6px; }
  .subtitle { text-align:center; font-size:14px; color:var(--text-muted); margin-bottom:28px; }
  label { display:block; font-size:13px; font-weight:500; color:var(--text-secondary); margin-bottom:6px; }
  input[type=text], input[type=email], input[type=password] {
    width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px;
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary);
    background:white; outline:none; transition:border-color 0.2s; margin-bottom:18px;
  }
  input:focus { border-color:var(--primary-light); }
  input.invalid { border-color:var(--danger); }
  .btn-primary {
    width:100%; padding:13px; background:var(--primary); color:white; border:none;
    border-radius:9px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:500;
    cursor:pointer; transition:all 0.2s; margin-top:4px;
  }
  .btn-primary:hover { background:var(--primary-mid); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
  .divider { text-align:center; font-size:13px; color:var(--text-muted); margin-top:18px; }
  .link { color:var(--primary-mid); text-decoration:none; font-weight:500; }
  .link:hover { text-decoration:underline; }
  .alert { padding:12px 16px; border-radius:9px; font-size:13px; margin-bottom:18px; display:none; }
  .alert-error   { background:#FDE8E8; color:var(--danger);  border:1px solid #F5C6C6; }
  .alert-success { background:#E8F5F0; color:var(--success); border:1px solid #B2DDD1; }
  .pw-hints { font-size:12px; color:var(--text-muted); margin-top:-12px; margin-bottom:18px; line-height:1.6; }
  .pw-hints span { display:block; }
  .hint-ok  { color:var(--success); }
  .hint-bad { color:var(--text-muted); }
  .spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.4); border-top-color:white; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
  @keyframes spin { to { transform:rotate(360deg); } }
</style>
</head>
<body>
<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

if (isLoggedIn()) {
    header('Location: /dermacare/patient/dashboard.php'); exit;
}
$csrf = csrfToken();
?>
<div class="card">
  <div class="logo">
    <div class="logo-box">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="white" opacity="0.35"/>
        <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="white"/>
      </svg>
    </div>
    <span style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--primary);">DermaCare</span>
  </div>

  <h1>Create your account</h1>
  <p class="subtitle">Patients only — dermatologist accounts are created by admins.</p>

  <div id="alert" class="alert"></div>

  <label for="name">Full name</label>
  <input type="text" id="name" placeholder="Jane Doe" autocomplete="name"/>

  <label for="email">Email address</label>
  <input type="email" id="email" placeholder="you@example.com" autocomplete="email"/>

  <label for="password">Password</label>
  <input type="password" id="password" placeholder="Min 8 chars" autocomplete="new-password" oninput="checkPw()"/>
  <div class="pw-hints" id="pw-hints">
    <span id="h-len">✗ At least 8 characters</span>
    <span id="h-upper">✗ One uppercase letter</span>
    <span id="h-lower">✗ One lowercase letter</span>
    <span id="h-num">✗ One number</span>
    <span id="h-special">✗ One special character (!@#$…)</span>
  </div>

  <label for="confirm">Confirm password</label>
  <input type="password" id="confirm" placeholder="Repeat password" autocomplete="new-password"/>

  <button class="btn-primary" id="reg-btn" onclick="submitRegister()">Create Account</button>

  <div class="divider">Already have an account? <a href="/dermacare/auth/login_page.php" class="link">Sign in</a></div>
</div>

<script>
  const CSRF = <?= json_encode($csrf) ?>;

  function hint(id, ok) {
    const el = document.getElementById(id);
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

  function showAlert(msg, type = 'error') {
    const el = document.getElementById('alert');
    el.textContent = msg;
    el.className = 'alert alert-' + type;
    el.style.display = 'block';
    el.scrollIntoView({ behavior:'smooth', block:'nearest' });
  }

  async function submitRegister() {
    const name    = document.getElementById('name').value.trim();
    const email   = document.getElementById('email').value.trim();
    const password          = document.getElementById('password').value;
    const confirm_password  = document.getElementById('confirm').value;
    const btn = document.getElementById('reg-btn');

    if (!name || !email || !password || !confirm_password) {
      showAlert('Please fill in all fields.'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Creating account…';

    const body = new URLSearchParams({ name, email, password, confirm_password, csrf_token: CSRF });

    try {
      const res  = await fetch('/dermacare/auth/register.php', { method:'POST', body });
      const data = await res.json();

      if (data.success) {
        showAlert('Account created! Redirecting to your dashboard…', 'success');
        setTimeout(() => window.location.href = data.redirect, 1000);
      } else {
        showAlert(data.message || 'Registration failed. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Create Account';
      }
    } catch (err) {
      showAlert('Network error. Please check your connection.');
      btn.disabled = false;
      btn.textContent = 'Create Account';
    }
  }
</script>
</body>
</html>
