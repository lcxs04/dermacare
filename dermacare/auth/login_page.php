<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84;
    --accent:#C8965A; --bg:#F5F2EC; --border:#D8E8E3;
    --text-primary:#0F2820; --text-secondary:#4A6258; --text-muted:#7A9990;
    --danger:#C0392B; --success:#1A6B5C;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text-primary); min-height:100vh; display:flex; align-items:center; justify-content:center; }
  .card { background:white; border-radius:20px; border:1px solid var(--border); padding:40px; width:100%; max-width:420px; box-shadow:0 8px 40px rgba(11,61,53,0.08); }
  .logo { display:flex; align-items:center; gap:10px; justify-content:center; margin-bottom:28px; }
  .logo-box { width:36px; height:36px; background:var(--primary); border-radius:9px; display:flex; align-items:center; justify-content:center; }
  h1 { font-family:'Cormorant Garamond',serif; font-size:26px; font-weight:600; color:var(--primary); text-align:center; margin-bottom:6px; }
  .subtitle { text-align:center; font-size:14px; color:var(--text-muted); margin-bottom:28px; }
  label { display:block; font-size:13px; font-weight:500; color:var(--text-secondary); margin-bottom:6px; }
  input[type=email], input[type=password], input[type=text] {
    width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px;
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary);
    background:white; outline:none; transition:border-color 0.2s; margin-bottom:18px;
  }
  input:focus { border-color:var(--primary-light); }
  .field { margin-bottom:4px; }
  .role-group { display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:22px; }
  .role-btn {
    padding:10px; border:1.5px solid var(--border); border-radius:9px; background:white;
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-secondary);
    cursor:pointer; transition:all 0.2s; text-align:center;
  }
  .role-btn.selected { border-color:var(--primary); background:rgba(11,61,53,0.06); color:var(--primary); font-weight:500; }
  .btn-primary {
    width:100%; padding:13px; background:var(--primary); color:white; border:none;
    border-radius:9px; font-family:'DM Sans',sans-serif; font-size:15px; font-weight:500;
    cursor:pointer; transition:all 0.2s; margin-top:4px;
  }
  .btn-primary:hover { background:var(--primary-mid); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
  .divider { text-align:center; font-size:13px; color:var(--text-muted); margin:18px 0; }
  .link { color:var(--primary-mid); text-decoration:none; font-weight:500; }
  .link:hover { text-decoration:underline; }
  .alert { padding:12px 16px; border-radius:9px; font-size:13px; margin-bottom:18px; display:none; }
  .alert-error   { background:#FDE8E8; color:var(--danger);  border:1px solid #F5C6C6; }
  .alert-success { background:#E8F5F0; color:var(--success); border:1px solid #B2DDD1; }
  .spinner { display:inline-block; width:16px; height:16px; border:2px solid rgba(255,255,255,0.4); border-top-color:white; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
  @keyframes spin { to { transform:rotate(360deg); } }
</style>
</head>
<body>
<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';


if (isLoggedIn()) {
    $map = ['patient'=>'/dermacare/patient/dashboard.php','dermatologist'=>'/dermacare/dermatologist/dashboard.php','admin'=>'/dermacare/admin/dashboard.php'];
    header('Location: ' . ($map[currentRole()] ?? '/dermacare/index.php'));
    exit;
}

$csrf = csrfToken();
?>
<div class="card">
  <!-- Logo -->
  <div class="logo">
    <div class="logo-box">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="white" opacity="0.35"/>
        <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="white"/>
      </svg>
    </div>
    <span style="font-family:'Cormorant Garamond',serif;font-size:22px;font-weight:600;color:var(--primary);">DermaCare</span>
  </div>

  <h1>Welcome back</h1>
  <p class="subtitle">Sign in to your account to continue</p>

  <!-- Alert box -->
  <div id="alert" class="alert"></div>

  <!-- Role selector -->
  <label>I am a:</label>
  <div class="role-group">
    <button class="role-btn selected" id="role-patient"       onclick="selectRole('patient')">🧑‍⚕️ Patient</button>
    <button class="role-btn"          id="role-dermatologist" onclick="selectRole('dermatologist')">👩‍⚕️ Dermatologist</button>
  </div>

  <!-- Form -->
  <div class="field">
    <label for="email">Email address</label>
    <input type="email" id="email" placeholder="you@example.com" autocomplete="email"/>
  </div>
  <div class="field">
    <label for="password">Password</label>
    <input type="password" id="password" placeholder="••••••••" autocomplete="current-password"
           onkeydown="if(event.key==='Enter') submitLogin()"/>
  </div>

  <button class="btn-primary" id="login-btn" onclick="submitLogin()">Sign In</button>

  <div class="divider">Don't have an account? <a href="/dermacare/auth/register_page.php" class="link">Create one</a></div>
  <div style="text-align:center;font-size:13px;color:var(--text-muted);">
    <a href="/dermacare/auth/forgot_password.php" class="link" style="font-weight:400;color:var(--text-muted);">Forgot password?</a>
  </div>
</div>

<script>
  const CSRF = <?= json_encode($csrf) ?>;
  let selectedRole = 'patient';

  function selectRole(role) {
    selectedRole = role;
    document.querySelectorAll('.role-btn').forEach(b => b.classList.remove('selected'));
    document.getElementById('role-' + role).classList.add('selected');
  }

  function showAlert(msg, type = 'error') {
    const el = document.getElementById('alert');
    el.textContent = msg;
    el.className = 'alert alert-' + type;
    el.style.display = 'block';
  }

  async function submitLogin() {
    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    const btn      = document.getElementById('login-btn');

    if (!email || !password) {
      showAlert('Please fill in all fields.'); return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner"></span>Signing in…';

    const body = new URLSearchParams({ email, password, role: selectedRole, csrf_token: CSRF });

    try {
      const res = await fetch('/dermacare/auth/login.php', { method:'POST', body });
      let data;
      try { data = await res.json(); }
      catch (parseErr) {
        showAlert('Server error. Please try again.');
        btn.disabled = false; btn.textContent = 'Sign In'; return;
      }
      if (data.success) {
        showAlert('Login successful! Redirecting…', 'success');
        setTimeout(() => window.location.href = data.redirect, 800);
      } else {
        showAlert(data.message || 'Login failed. Please try again.');
        btn.disabled = false;
        btn.textContent = 'Sign In';
      }
    } catch (err) {
      showAlert('Unable to reach server. Please ensure Apache is running.');
      btn.disabled = false;
      btn.textContent = 'Sign In';
    }
  }
</script>
</body>
</html>
