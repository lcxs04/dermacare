<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requireRole('patient');

$db     = getDB();
$userId = currentUserId();
$name   = $_SESSION['name'];


$stmt = $db->prepare('SELECT * FROM cases WHERE patient_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$cases = $stmt->fetchAll();


$total     = count($cases);
$submitted = count(array_filter($cases, fn($c) => $c['status'] === 'submitted'));
$inReview  = count(array_filter($cases, fn($c) => $c['status'] === 'in_review'));
$completed = count(array_filter($cases, fn($c) => $c['status'] === 'completed'));

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Patient Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84;
    --accent:#C8965A; --accent-light:#E8B86D; --bg:#F5F2EC;
    --surface:#FFFFFF; --surface-alt:#EFF7F4; --border:#D8E8E3;
    --text-primary:#0F2820; --text-secondary:#4A6258; --text-muted:#7A9990;
    --danger:#C0392B; --success:#1A6B5C; --warning:#D4870A;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text-primary); min-height:100vh; }
  ::-webkit-scrollbar { width:4px; } ::-webkit-scrollbar-thumb { background:var(--border); border-radius:2px; }
  @keyframes fadeUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }
  .fade-up { animation:fadeUp 0.4s ease forwards; }

  
  nav { background:white; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
  .nav-inner { max-width:1100px; margin:0 auto; padding:0 32px; display:flex; align-items:center; justify-content:space-between; height:64px; }
  .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
  .logo-box { width:32px; height:32px; background:var(--primary); border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .nav-right { display:flex; align-items:center; gap:16px; }
  .user-badge { display:flex; align-items:center; gap:8px; background:var(--surface-alt); border:1px solid var(--border); border-radius:24px; padding:6px 14px 6px 8px; }
  .avatar { width:28px; height:28px; border-radius:50%; background:var(--primary); color:white; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; }
  .btn-logout { background:transparent; border:1.5px solid var(--border); border-radius:8px; padding:7px 16px; font-family:'DM Sans',sans-serif; font-size:13px; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; }
  .btn-logout:hover { border-color:var(--danger); color:var(--danger); }

  
  .container { max-width:1100px; margin:0 auto; padding:32px; }
  .grid-2 { display:grid; grid-template-columns:1fr 1.1fr; gap:28px; align-items:start; }

  
  .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px; }
  .stat-card { background:white; border:1px solid var(--border); border-radius:14px; padding:20px; }
  .stat-num { font-family:'Cormorant Garamond',serif; font-size:32px; font-weight:600; color:var(--primary); line-height:1; margin-bottom:4px; }
  .stat-label { font-size:12px; color:var(--text-muted); }

  
  .card { background:white; border:1px solid var(--border); border-radius:16px; }
  .card-header { padding:20px 24px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
  .card-title { font-size:16px; font-weight:500; color:var(--text-primary); }
  .card-body { padding:24px; }

  
  label { display:block; font-size:13px; font-weight:500; color:var(--text-secondary); margin-bottom:6px; }
  input[type=text], textarea {
    width:100%; padding:11px 14px; border:1.5px solid var(--border); border-radius:9px;
    font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary);
    background:white; outline:none; transition:border-color 0.2s; margin-bottom:16px;
  }
  input[type=text]:focus, textarea:focus { border-color:var(--primary-light); }
  textarea { resize:vertical; min-height:100px; }
  .upload-area { border:2px dashed var(--border); border-radius:10px; padding:28px; text-align:center; cursor:pointer; transition:all 0.2s; margin-bottom:16px; }
  .upload-area:hover { border-color:var(--primary-light); background:var(--surface-alt); }
  .upload-area input { display:none; }
  .btn-primary { background:var(--primary); color:white; border:none; border-radius:9px; padding:12px 24px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:500; cursor:pointer; transition:all 0.2s; width:100%; }
  .btn-primary:hover { background:var(--primary-mid); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }

  
  .case-item { padding:16px 20px; border-bottom:1px solid var(--border); cursor:pointer; transition:background 0.15s; }
  .case-item:last-child { border-bottom:none; }
  .case-item:hover { background:var(--bg); }
  .case-item-title { font-size:15px; font-weight:500; color:var(--text-primary); margin-bottom:4px; }
  .case-item-desc { font-size:13px; color:var(--text-muted); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; max-width:280px; margin-bottom:6px; }
  .case-meta { font-size:12px; color:var(--text-muted); }
  .tag { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
  .tag-submitted { background:#EFF7F4; color:var(--primary); }
  .tag-in_review { background:#FEF3E0; color:#8B5E0A; }
  .tag-completed { background:#E8F5F0; color:var(--success); }
  .empty-state { text-align:center; padding:48px 24px; color:var(--text-muted); }
  .empty-icon { font-size:40px; margin-bottom:12px; }

  
  .alert { padding:12px 16px; border-radius:9px; font-size:13px; margin-bottom:18px; display:none; }
  .alert-error   { background:#FDE8E8; color:var(--danger); border:1px solid #F5C6C6; }
  .alert-success { background:#E8F5F0; color:var(--success); border:1px solid #B2DDD1; }

  
  .modal-overlay { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.4); z-index:200; align-items:center; justify-content:center; }
  .modal-overlay.open { display:flex; }
  .modal { background:white; border-radius:18px; padding:32px; max-width:520px; width:90%; max-height:85vh; overflow-y:auto; }
  .modal-title { font-size:18px; font-weight:500; margin-bottom:20px; color:var(--text-primary); }
  .feedback-box { background:var(--surface-alt); border-radius:10px; padding:16px; font-size:14px; line-height:1.7; color:var(--text-primary); margin-top:12px; }
  .spinner-sm { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.4); border-top-color:white; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
  @keyframes spin { to{transform:rotate(360deg)} }
  #preview-list { display:flex; flex-wrap:wrap; gap:8px; margin-bottom:12px; }
  .preview-thumb { width:64px; height:64px; object-fit:cover; border-radius:8px; border:1px solid var(--border); }
</style>
</head>
<body>


<nav>
  <div class="nav-inner">
    <a href="/dermacare/home.php" class="logo">
      <div class="logo-box">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none">
          <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" fill="white" opacity="0.3"/>
          <path d="M12 6c-3.31 0-6 2.69-6 6s2.69 6 6 6 6-2.69 6-6-2.69-6-6-6zm0 10c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4z" fill="white"/>
        </svg>
      </div>
      <span style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:600;color:var(--primary);">DermaCare</span>
    </a>
    <div class="nav-right">
      <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
        <span style="font-size:13px;font-weight:500;color:var(--text-primary);"><?= htmlspecialchars($name) ?></span>
        <span class="tag tag-submitted" style="margin-left:4px;">Patient</span>
      </div>
      <a href="/dermacare/auth/logout.php"><button class="btn-logout">Logout</button></a>
    </div>
  </div>
</nav>

<div class="container">

  
  <div style="margin-bottom:28px;" class="fade-up">
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:30px;font-weight:600;color:var(--primary);margin-bottom:4px;">
      Welcome back, <?= htmlspecialchars(explode(' ', $name)[0]) ?> 👋
    </h1>
    <p style="font-size:14px;color:var(--text-muted);">Manage your skin consultations and track your cases below.</p>
  </div>

  
  <div class="stats-row fade-up">
    <div class="stat-card">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total Cases</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--text-secondary);"><?= $submitted ?></div>
      <div class="stat-label">Submitted</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--warning);"><?= $inReview ?></div>
      <div class="stat-label">In Review</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--success);"><?= $completed ?></div>
      <div class="stat-label">Completed</div>
    </div>
  </div>

  
  <div class="grid-2 fade-up">

    
    <div class="card">
      <div class="card-header">
        <span class="card-title">📋 Submit New Case</span>
      </div>
      <div class="card-body">
        <div id="form-alert" class="alert"></div>

        <label for="case-title">Case Title</label>
        <input type="text" id="case-title" placeholder="e.g. Rash on left arm"/>

        <label for="case-desc">Describe your skin problem</label>
        <textarea id="case-desc" placeholder="Describe your symptoms, when they started, affected area, etc."></textarea>

        <label>Upload Photos</label>
        <div class="upload-area" onclick="document.getElementById('photo-input').click()">
          <input type="file" id="photo-input" accept="image/*" multiple onchange="previewImages(this)"/>
          <div style="font-size:28px;margin-bottom:8px;">📷</div>
          <div style="font-size:14px;color:var(--text-muted);">Click to upload photos</div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:4px;">JPG, PNG up to 5MB each</div>
        </div>
        <div id="preview-list"></div>

        <button class="btn-primary" id="submit-btn" onclick="submitCase()">Submit Case</button>
      </div>
    </div>

    
    <div class="card">
      <div class="card-header">
        <span class="card-title">📁 My Cases</span>
        <span style="font-size:13px;color:var(--text-muted);"><?= $total ?> total</span>
      </div>
      <?php if (empty($cases)): ?>
        <div class="empty-state">
          <div class="empty-icon">🩺</div>
          <p style="font-size:14px;">No cases yet.<br/>Submit your first case on the left.</p>
        </div>
      <?php else: ?>
        <div>
          <?php foreach ($cases as $case): ?>
            <div class="case-item" onclick="viewCase(<?= $case['case_id'] ?>)">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:10px;">
                <div style="flex:1;min-width:0;">
                  <div class="case-item-title"><?= htmlspecialchars($case['title']) ?></div>
                  <div class="case-item-desc"><?= htmlspecialchars($case['description']) ?></div>
                  <div class="case-meta"><?= date('d M Y', strtotime($case['created_at'])) ?></div>
                </div>
                <span class="tag tag-<?= $case['status'] ?>"><?= ucfirst(str_replace('_', ' ', $case['status'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>


<div class="modal-overlay" id="case-modal">
  <div class="modal">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
      <div class="modal-title" id="modal-title" style="margin-bottom:0;">Case Detail</div>
      <button onclick="closeModal()" style="background:none;border:none;font-size:20px;cursor:pointer;color:var(--text-muted);">✕</button>
    </div>
    <div id="modal-body"></div>
  </div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;


function previewImages(input) {
  const list = document.getElementById('preview-list');
  list.innerHTML = '';
  Array.from(input.files).forEach(file => {
    const img = document.createElement('img');
    img.className = 'preview-thumb';
    img.src = URL.createObjectURL(file);
    list.appendChild(img);
  });
}


async function submitCase() {
  const title = document.getElementById('case-title').value.trim();
  const desc  = document.getElementById('case-desc').value.trim();
  const files = document.getElementById('photo-input').files;
  const btn   = document.getElementById('submit-btn');
  const alert = document.getElementById('form-alert');

  alert.style.display = 'none';

  if (!title) { showFormAlert('Please enter a case title.'); return; }
  if (!desc)  { showFormAlert('Please describe your skin problem.'); return; }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-sm"></span>Submitting…';

  const form = new FormData();
  form.append('csrf_token', CSRF);
  form.append('title', title);
  form.append('description', desc);
  Array.from(files).forEach(f => form.append('photos[]', f));

  try {
    const res  = await fetch('/dermacare/patient/submit_case.php', { method:'POST', body: form });
    const data = await res.json();

    if (data.success) {
      showFormAlert(data.message, 'success');
      setTimeout(() => location.reload(), 1200);
    } else {
      showFormAlert(data.message || 'Submission failed.');
    }
  } catch(e) {
    showFormAlert('Network error. Please try again.');
  }

  btn.disabled = false;
  btn.textContent = 'Submit Case';
}

function showFormAlert(msg, type = 'error') {
  const el = document.getElementById('form-alert');
  el.textContent = msg;
  el.className = 'alert alert-' + type;
  el.style.display = 'block';
}


async function viewCase(id) {
  document.getElementById('case-modal').classList.add('open');
  document.getElementById('modal-body').innerHTML = '<p style="color:var(--text-muted);font-size:14px;">Loading…</p>';

  const res  = await fetch('/dermacare/patient/get_case.php?id=' + id);
  const data = await res.json();

  if (!data.success) {
    document.getElementById('modal-body').innerHTML = '<p style="color:var(--danger);">Could not load case.</p>';
    return;
  }

  const c = data.case;
  const statusMap = { submitted:'Submitted', in_review:'In Review', completed:'Completed' };
  const tagMap    = { submitted:'tag-submitted', in_review:'tag-in_review', completed:'tag-completed' };

  let imagesHtml = '';
  if (data.images && data.images.length > 0) {
    imagesHtml = '<div style="display:flex;flex-wrap:wrap;gap:8px;margin-top:8px;">' +
      data.images.map(img => `<img src="/dermacare/${img.file_path}" style="width:80px;height:80px;object-fit:cover;border-radius:8px;border:1px solid var(--border);" />`).join('') +
      '</div>';
  }

  let feedbackHtml = '';
  if (c.feedback) {
    feedbackHtml = `<div style="margin-top:20px;"><div style="font-size:13px;font-weight:500;color:var(--text-secondary);margin-bottom:6px;">🩺 Dermatologist Feedback</div><div class="feedback-box">${c.feedback.replace(/\n/g,'<br>')}</div></div>`;
  } else {
    feedbackHtml = `<div style="margin-top:20px;background:var(--bg);border-radius:10px;padding:14px;font-size:13px;color:var(--text-muted);">⏳ Awaiting dermatologist review…</div>`;
  }

  document.getElementById('modal-title').textContent = c.title;
  document.getElementById('modal-body').innerHTML = `
    <div style="display:flex;align-items:center;gap:10px;margin-bottom:18px;">
      <span class="tag ${tagMap[c.status]}">${statusMap[c.status]}</span>
      <span style="font-size:12px;color:var(--text-muted);">Submitted ${c.created_at}</span>
    </div>
    <div style="font-size:13px;font-weight:500;color:var(--text-secondary);margin-bottom:6px;">Description</div>
    <p style="font-size:14px;line-height:1.7;color:var(--text-primary);">${c.description.replace(/\n/g,'<br>')}</p>
    ${imagesHtml ? '<div style="margin-top:16px;"><div style="font-size:13px;font-weight:500;color:var(--text-secondary);margin-bottom:6px;">Photos</div>' + imagesHtml + '</div>' : ''}
    ${feedbackHtml}
  `;
}

function closeModal() {
  document.getElementById('case-modal').classList.remove('open');
}
</script>
</body>
</html>
