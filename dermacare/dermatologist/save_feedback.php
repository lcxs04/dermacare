<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireRole('dermatologist');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed.', [], 405);
verifyCsrf();

$caseId   = (int)($_POST['case_id']  ?? 0);
$feedback = trim($_POST['feedback']  ?? '');
$status   = $_POST['status']         ?? 'in_review';
$finalize = ($_POST['finalize']      ?? '0') === '1';

if (!$caseId) jsonResponse(false, 'Invalid case ID.');

$allowed = ['submitted', 'in_review', 'completed'];
if (!in_array($status, $allowed)) $status = 'in_review';

$db = getDB();


$check = $db->prepare('SELECT case_id FROM cases WHERE case_id = ? LIMIT 1');
$check->execute([$caseId]);
if (!$check->fetch()) jsonResponse(false, 'Case not found.', [], 404);


$db->prepare('UPDATE cases SET feedback = ?, status = ?, dermatologist_id = ? WHERE case_id = ?')
   ->execute([$feedback ?: null, $status, currentUserId(), $caseId]);

$msg = $finalize ? 'Feedback sent to patient successfully!' : 'Draft saved successfully.';
auditLog('feedback_' . ($finalize ? 'sent' : 'draft'), "Case ID: $caseId — Status: $status");

jsonResponse(true, $msg);
