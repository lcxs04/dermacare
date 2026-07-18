<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

requireRole('dermatologist');

$db     = getDB();
$userId = currentUserId();
$name   = $_SESSION['name'];


$stmt = $db->prepare('
    SELECT c.*, u.name as patient_name
    FROM cases c
    JOIN users u ON c.patient_id = u.user_id
    WHERE c.dermatologist_id = ? OR c.dermatologist_id IS NULL
    ORDER BY c.created_at DESC
');
$stmt->execute([$userId]);
$cases = $stmt->fetchAll();


$total     = count($cases);
$pending   = count(array_filter($cases, fn($c) => $c['status'] === 'submitted'));
$inReview  = count(array_filter($cases, fn($c) => $c['status'] === 'in_review'));
$completed = count(array_filter($cases, fn($c) => $c['status'] === 'completed'));

$csrf = csrfToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
<title>DermaCare — Dermatologist Dashboard</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@500;600&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet"/>
<style>
  :root {
    --primary:#0B3D35; --primary-mid:#1A6B5C; --primary-light:#2E9B84;
    --accent:#C8965A; --bg:#F5F2EC; --surface:#FFFFFF; --surface-alt:#EFF7F4;
    --border:#D8E8E3; --text-primary:#0F2820; --text-secondary:#4A6258;
    --text-muted:#7A9990; --danger:#C0392B; --success:#1A6B5C; --warning:#D4870A;
  }
  * { box-sizing:border-box; margin:0; padding:0; }
  body { font-family:'DM Sans',sans-serif; background:var(--bg); color:var(--text-primary); min-height:100vh; }
  @keyframes fadeUp { from{opacity:0;transform:translateY(12px)} to{opacity:1;transform:translateY(0)} }
  .fade-up { animation:fadeUp 0.35s ease forwards; }

  
  nav { background:white; border-bottom:1px solid var(--border); position:sticky; top:0; z-index:100; }
  .nav-inner { max-width:1200px; margin:0 auto; padding:0 32px; display:flex; align-items:center; justify-content:space-between; height:64px; }
  .logo { display:flex; align-items:center; gap:10px; text-decoration:none; }
  .logo-box { width:32px; height:32px; background:var(--primary); border-radius:8px; display:flex; align-items:center; justify-content:center; }
  .user-badge { display:flex; align-items:center; gap:8px; background:var(--surface-alt); border:1px solid var(--border); border-radius:24px; padding:6px 14px 6px 8px; }
  .avatar { width:28px; height:28px; border-radius:50%; background:var(--primary-mid); color:white; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:600; }
  .btn-logout { background:transparent; border:1.5px solid var(--border); border-radius:8px; padding:7px 16px; font-family:'DM Sans',sans-serif; font-size:13px; color:var(--text-secondary); cursor:pointer; transition:all 0.2s; }
  .btn-logout:hover { border-color:var(--danger); color:var(--danger); }

  
  .container { max-width:1200px; margin:0 auto; padding:32px; }
  .main-grid { display:grid; grid-template-columns:340px 1fr; gap:24px; align-items:start; }

  
  .stats-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; margin-bottom:28px; }
  .stat-card { background:white; border:1px solid var(--border); border-radius:14px; padding:20px; }
  .stat-num { font-family:'Cormorant Garamond',serif; font-size:32px; font-weight:600; color:var(--primary); line-height:1; margin-bottom:4px; }
  .stat-label { font-size:12px; color:var(--text-muted); }

  
  .card { background:white; border:1px solid var(--border); border-radius:16px; overflow:hidden; }
  .card-header { padding:18px 20px; border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; }
  .card-title { font-size:15px; font-weight:500; color:var(--text-primary); }

  
  .search-wrap { padding:14px 16px; border-bottom:1px solid var(--border); }
  .search-input { width:100%; padding:9px 14px; border:1.5px solid var(--border); border-radius:9px; font-family:'DM Sans',sans-serif; font-size:13px; outline:none; transition:border-color 0.2s; }
  .search-input:focus { border-color:var(--primary-light); }

  
  .case-item { padding:14px 18px; border-bottom:1px solid var(--border); cursor:pointer; transition:background 0.15s; }
  .case-item:last-child { border-bottom:none; }
  .case-item:hover { background:var(--bg); }
  .case-item.active { background:var(--surface-alt); border-left:3px solid var(--primary); }
  .case-name { font-size:14px; font-weight:500; color:var(--text-primary); margin-bottom:2px; }
  .case-patient { font-size:12px; color:var(--text-muted); margin-bottom:6px; }
  .case-date { font-size:11px; color:var(--text-muted); }
  .tag { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:500; }
  .tag-submitted { background:#EFF7F4; color:var(--primary); }
  .tag-in_review { background:#FEF3E0; color:#8B5E0A; }
  .tag-completed { background:#E8F5F0; color:var(--success); }

  
  .detail-empty { display:flex; flex-direction:column; align-items:center; justify-content:center; min-height:400px; color:var(--text-muted); text-align:center; }
  .detail-body { padding:28px; }
  .detail-title { font-family:'Cormorant Garamond',serif; font-size:24px; font-weight:600; color:var(--primary); margin-bottom:8px; }
  .detail-meta { font-size:13px; color:var(--text-muted); margin-bottom:20px; display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
  .section-label { font-size:12px; font-weight:600; color:var(--text-muted); text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px; }
  .desc-box { background:var(--bg); border-radius:10px; padding:16px; font-size:14px; line-height:1.7; color:var(--text-primary); margin-bottom:20px; }
  .photos-grid { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; }
  .photo-thumb { width:90px; height:90px; object-fit:cover; border-radius:10px; border:1px solid var(--border); cursor:pointer; transition:transform 0.2s; }
  .photo-thumb:hover { transform:scale(1.05); }
  textarea { width:100%; padding:14px; border:1.5px solid var(--border); border-radius:10px; font-family:'DM Sans',sans-serif; font-size:14px; color:var(--text-primary); resize:vertical; min-height:120px; outline:none; transition:border-color 0.2s; margin-bottom:14px; }
  textarea:focus { border-color:var(--primary-light); }

  
  .status-row { display:flex; gap:8px; margin-bottom:20px; }
  .status-btn { padding:7px 16px; border-radius:20px; border:1.5px solid var(--border); background:white; font-family:'DM Sans',sans-serif; font-size:13px; cursor:pointer; transition:all 0.2s; color:var(--text-secondary); }
  .status-btn.active-submitted { border-color:var(--primary); background:#EFF7F4; color:var(--primary); }
  .status-btn.active-in_review { border-color:#D4870A; background:#FEF3E0; color:#8B5E0A; }
  .status-btn.active-completed { border-color:var(--success); background:#E8F5F0; color:var(--success); }

  
  .action-row { display:flex; gap:10px; }
  .btn-primary { background:var(--primary); color:white; border:none; border-radius:9px; padding:11px 22px; font-family:'DM Sans',sans-serif; font-size:14px; font-weight:500; cursor:pointer; transition:all 0.2s; }
  .btn-primary:hover { background:var(--primary-mid); }
  .btn-primary:disabled { opacity:0.6; cursor:not-allowed; }
  .btn-outline { background:white; color:var(--text-secondary); border:1.5px solid var(--border); border-radius:9px; padding:11px 22px; font-family:'DM Sans',sans-serif; font-size:14px; cursor:pointer; transition:all 0.2s; }
  .btn-outline:hover { border-color:var(--primary); color:var(--primary); }

  
  .alert { padding:11px 16px; border-radius:9px; font-size:13px; margin-bottom:14px; display:none; }
  .alert-error   { background:#FDE8E8; color:var(--danger); border:1px solid #F5C6C6; }
  .alert-success { background:#E8F5F0; color:var(--success); border:1px solid #B2DDD1; }

  
  .lightbox { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:300; align-items:center; justify-content:center; }
  .lightbox.open { display:flex; }
  .lightbox img { max-width:90vw; max-height:90vh; border-radius:10px; }
  .lightbox-close { position:absolute; top:20px; right:24px; color:white; font-size:28px; cursor:pointer; background:none; border:none; }

  .empty-state { text-align:center; padding:32px; color:var(--text-muted); font-size:14px; }
  .spinner-sm { display:inline-block; width:14px; height:14px; border:2px solid rgba(255,255,255,0.4); border-top-color:white; border-radius:50%; animation:spin 0.7s linear infinite; vertical-align:middle; margin-right:6px; }
  @keyframes spin { to{transform:rotate(360deg)} }
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
    <div style="display:flex;align-items:center;gap:16px;">
      <div class="user-badge">
        <div class="avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
        <span style="font-size:13px;font-weight:500;"><?= htmlspecialchars($name) ?></span>
        <span class="tag tag-in_review" style="margin-left:4px;">Dermatologist</span>
      </div>
      <a href="/dermacare/auth/logout.php"><button class="btn-logout">Logout</button></a>
    </div>
  </div>
</nav>

<div class="container">

  
  <div style="margin-bottom:24px;" class="fade-up">
    <h1 style="font-family:'Cormorant Garamond',serif;font-size:28px;font-weight:600;color:var(--primary);margin-bottom:4px;">
      Dermatologist Dashboard
    </h1>
    <p style="font-size:14px;color:var(--text-muted);">Review patient cases and provide your professional feedback.</p>
  </div>

  
  <div class="stats-row fade-up">
    <div class="stat-card">
      <div class="stat-num"><?= $total ?></div>
      <div class="stat-label">Total Cases</div>
    </div>
    <div class="stat-card">
      <div class="stat-num" style="color:var(--primary-light);"><?= $pending ?></div>
      <div class="stat-label">Pending Review</div>
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

  
  <div class="main-grid fade-up">

    
    <div class="card">
      <div class="card-header">
        <span class="card-title">📋 Assigned Cases</span>
        <span style="background:var(--primary);color:white;border-radius:50%;width:22px;height:22px;display:flex;align-items:center;justify-content:center;font-size:11px;"><?= $total ?></span>
      </div>
      <div class="search-wrap">
        <input class="search-input" type="text" id="search-box" placeholder="🔍 Search by patient or case…" oninput="filterCases()"/>
      </div>

      <?php if (empty($cases)): ?>
        <div class="empty-state">No cases assigned yet.</div>
      <?php else: ?>
        <div id="case-list">
          <?php foreach ($cases as $case): ?>
            <div class="case-item"
                 id="case-item-<?= $case['case_id'] ?>"
                 data-search="<?= strtolower(htmlspecialchars($case['title'] . ' ' . $case['patient_name'])) ?>"
                 onclick="loadCase(<?= $case['case_id'] ?>)">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:8px;">
                <div style="flex:1;min-width:0;">
                  <div class="case-name"><?= htmlspecialchars($case['title']) ?></div>
                  <div class="case-patient">👤 <?= htmlspecialchars($case['patient_name']) ?></div>
                  <div class="case-date"><?= date('d M Y', strtotime($case['created_at'])) ?></div>
                </div>
                <span class="tag tag-<?= $case['status'] ?>"><?= ucfirst(str_replace('_',' ',$case['status'])) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

    
    <div class="card" id="detail-panel">
      <div class="detail-empty">
        <div style="font-size:48px;margin-bottom:12px;">🩺</div>
        <p style="font-size:15px;font-weight:500;margin-bottom:6px;">Select a case to review</p>
        <p style="font-size:13px;">Click any case on the left to view details and provide feedback.</p>
      </div>
    </div>

  </div>
</div>


<div class="lightbox" id="lightbox" onclick="closeLightbox()">
  <button class="lightbox-close" onclick="closeLightbox()">✕</button>
  <img id="lightbox-img" src="" alt="Case photo"/>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
let currentCaseId = null;
let currentStatus = null;


function filterCases() {
  const q = document.getElementById('search-box').value.toLowerCase();
  document.querySelectorAll('.case-item').forEach(item => {
    item.style.display = item.dataset.search.includes(q) ? '' : 'none';
  });
}


async function loadCase(id) {
  
  document.querySelectorAll('.case-item').forEach(i => i.classList.remove('active'));
  const el = document.getElementById('case-item-' + id);
  if (el) el.classList.add('active');

  currentCaseId = id;
  document.getElementById('detail-panel').innerHTML = '<div class="detail-body" style="color:var(--text-muted);font-size:14px;">Loading…</div>';

  const res  = await fetch('/dermacare/dermatologist/get_case.php?id=' + id);
  const data = await res.json();

  if (!data.success) {
    document.getElementById('detail-panel').innerHTML = '<div class="detail-body" style="color:var(--danger);">Could not load case.</div>';
    return;
  }

  const c = data.case;
  currentStatus = c.status;

  const statusLabels = { submitted:'Submitted', in_review:'In Review', completed:'Completed' };

  let photosHtml = '';
  if (data.images && data.images.length > 0) {
    photosHtml = `
      <div class="section-label">Photos</div>
      <div class="photos-grid">
        ${data.images.map(img => `<img class="photo-thumb" src="/dermacare/${img.file_path}" onclick="openLightbox('/dermacare/${img.file_path}')" />`).join('')}
      </div>`;
  }

  document.getElementById('detail-panel').innerHTML = `
    <div class="detail-body">
      <div id="detail-alert" class="alert"></div>

      <div class="detail-title">${escHtml(c.title)}</div>
      <div class="detail-meta">
        <span>👤 ${escHtml(c.patient_name)}</span>
        <span>📅 ${c.created_at}</span>
        <span class="tag tag-${c.status}">${statusLabels[c.status]}</span>
      </div>

      <div class="section-label">Patient Description</div>
      <div class="desc-box">${escHtml(c.description).replace(/\n/g,'<br>')}</div>

      ${photosHtml}

      <div class="section-label" style="margin-bottom:10px;">Update Status</div>
      <div class="status-row" style="margin-bottom:20px;">
        <button class="status-btn ${c.status==='submitted'?'active-submitted':''}"  onclick="setStatus('submitted')">Submitted</button>
        <button class="status-btn ${c.status==='in_review'?'active-in_review':''}" onclick="setStatus('in_review')">In Review</button>
        <button class="status-btn ${c.status==='completed'?'active-completed':''}" onclick="setStatus('completed')">Completed</button>
      </div>

      <div class="section-label">Write Feedback / Diagnosis</div>
      <textarea id="feedback-box" placeholder="Provide your professional assessment, diagnosis, and treatment recommendations…">${escHtml(c.feedback || '')}</textarea>

      <div class="action-row">
        <button class="btn-primary" id="send-btn" onclick="sendFeedback()">✉️ Send Feedback</button>
        <button class="btn-outline" onclick="saveDraft()">💾 Save Draft</button>
      </div>
    </div>
  `;
}

function setStatus(status) {
  currentStatus = status;
  document.querySelectorAll('.status-btn').forEach(b => {
    b.className = 'status-btn';
    if (b.textContent.toLowerCase().replace(' ','_') === status ||
        (status==='in_review' && b.textContent==='In Review') ||
        (status==='submitted' && b.textContent==='Submitted') ||
        (status==='completed' && b.textContent==='Completed')) {
      b.classList.add('active-' + status);
    }
  });
}

async function sendFeedback() {
  await submitFeedback(true);
}

async function saveDraft() {
  await submitFeedback(false);
}

async function submitFeedback(send) {
  const feedback = document.getElementById('feedback-box').value.trim();
  const btn      = document.getElementById('send-btn');

  if (send && !feedback) {
    showDetailAlert('Please write feedback before sending.'); return;
  }

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-sm"></span>' + (send ? 'Sending…' : 'Saving…');

  const body = new URLSearchParams({
    csrf_token: CSRF,
    case_id:    currentCaseId,
    feedback:   feedback,
    status:     send ? (currentStatus === 'submitted' ? 'in_review' : currentStatus) : currentStatus,
    finalize:   send ? '1' : '0'
  });

  const res  = await fetch('/dermacare/dermatologist/save_feedback.php', { method:'POST', body });
  const data = await res.json();

  if (data.success) {
    showDetailAlert(data.message, 'success');
    
    const itemEl = document.getElementById('case-item-' + currentCaseId);
    if (itemEl) {
      const tagEl = itemEl.querySelector('.tag');
      if (tagEl) {
        const newStatus = send ? (currentStatus === 'submitted' ? 'in_review' : currentStatus) : currentStatus;
        tagEl.className = 'tag tag-' + newStatus;
        tagEl.textContent = newStatus === 'in_review' ? 'In Review' : newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
      }
    }
  } else {
    showDetailAlert(data.message || 'Something went wrong.');
  }

  btn.disabled = false;
  btn.innerHTML = '✉️ Send Feedback';
}

function showDetailAlert(msg, type='error') {
  const el = document.getElementById('detail-alert');
  if (!el) return;
  el.textContent = msg;
  el.className = 'alert alert-' + type;
  el.style.display = 'block';
  setTimeout(() => el.style.display = 'none', 4000);
}

function escHtml(str) {
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function openLightbox(src) {
  document.getElementById('lightbox-img').src = src;
  document.getElementById('lightbox').classList.add('open');
}
function closeLightbox() {
  document.getElementById('lightbox').classList.remove('open');
}
</script>
</body>
</html>
