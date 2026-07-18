<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

$db = getDB();


$tokens = $db->query('SELECT *, NOW() as server_now, expires_at > NOW() as is_valid FROM password_resets ORDER BY created_at DESC')->fetchAll();


$urlToken = trim($_GET['token'] ?? '');
$urlCheck = null;
if ($urlToken) {
    $s = $db->prepare('SELECT *, NOW() as server_now, expires_at > NOW() as is_valid FROM password_resets WHERE token = ? LIMIT 1');
    $s->execute([$urlToken]);
    $urlCheck = $s->fetch();
}
?>
<!DOCTYPE html>
<html>
<head><title>Debug Reset</title>
<style>
  body { font-family:monospace; padding:24px; background:#f5f5f5; }
  table { border-collapse:collapse; width:100%; background:white; margin-top:16px; }
  th,td { border:1px solid #ccc; padding:8px 12px; font-size:13px; text-align:left; }
  th { background:#0B3D35; color:white; }
  .ok  { color:green; font-weight:bold; }
  .bad { color:red;   font-weight:bold; }
  .box { background:white; border:1px solid #ccc; border-radius:8px; padding:16px; margin-bottom:20px; }
</style>
</head>
<body>
<h2>🔍 Debug: Password Reset Tokens</h2>

<div class="box">
  <strong>Server Time (NOW()):</strong>
  <?php echo $db->query('SELECT NOW()')->fetchColumn(); ?>
</div>

<?php if ($urlToken): ?>
<div class="box">
  <strong>Token from URL:</strong> <?= htmlspecialchars(substr($urlToken,0,20)) ?>...<br><br>
  <?php if ($urlCheck): ?>
    <table>
      <tr><th>Field</th><th>Value</th></tr>
      <tr><td>Email</td><td><?= htmlspecialchars($urlCheck['email']) ?></td></tr>
      <tr><td>Created At</td><td><?= $urlCheck['created_at'] ?></td></tr>
      <tr><td>Expires At</td><td><?= $urlCheck['expires_at'] ?></td></tr>
      <tr><td>Server NOW()</td><td><?= $urlCheck['server_now'] ?></td></tr>
      <tr><td>Used</td><td><?= $urlCheck['used'] ? '<span class="bad">YES (already used)</span>' : '<span class="ok">NO</span>' ?></td></tr>
      <tr><td>Is Valid (expires_at > NOW())</td><td><?= $urlCheck['is_valid'] ? '<span class="ok">YES ✓</span>' : '<span class="bad">NO — EXPIRED or wrong timezone</span>' ?></td></tr>
    </table>
  <?php else: ?>
    <span class="bad">❌ Token NOT FOUND in database at all!</span>
  <?php endif; ?>
</div>
<?php endif; ?>

<h3>All Tokens in Database:</h3>
<?php if (empty($tokens)): ?>
  <p class="bad">No tokens found — table may be empty or not imported correctly.</p>
<?php else: ?>
  <table>
    <tr>
      <th>ID</th><th>Email</th><th>Token (first 16)</th>
      <th>Created At</th><th>Expires At</th><th>Used</th><th>Valid?</th>
    </tr>
    <?php foreach ($tokens as $t): ?>
    <tr>
      <td><?= $t['id'] ?></td>
      <td><?= htmlspecialchars($t['email']) ?></td>
      <td><?= substr($t['token'],0,16) ?>...</td>
      <td><?= $t['created_at'] ?></td>
      <td><?= $t['expires_at'] ?></td>
      <td><?= $t['used'] ? '<span class="bad">YES</span>' : 'No' ?></td>
      <td><?= $t['is_valid'] ? '<span class="ok">✓ Valid</span>' : '<span class="bad">✗ Expired</span>' ?></td>
    </tr>
    <?php endforeach; ?>
  </table>
<?php endif; ?>

<br>
<p style="color:red"><strong>⚠️ Delete this file after debugging: dermacare/tools/debug_reset.php</strong></p>
</body>
</html>
