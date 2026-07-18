<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireRole('dermatologist');

$caseId = (int)($_GET['id'] ?? 0);
if (!$caseId) jsonResponse(false, 'Invalid case ID.');

$db   = getDB();
$stmt = $db->prepare('
    SELECT c.*, u.name as patient_name
    FROM cases c
    JOIN users u ON c.patient_id = u.user_id
    WHERE c.case_id = ?
    LIMIT 1
');
$stmt->execute([$caseId]);
$case = $stmt->fetch();

if (!$case) jsonResponse(false, 'Case not found.', [], 404);

$case['created_at'] = date('d M Y', strtotime($case['created_at']));

$imgs = $db->prepare('SELECT file_path FROM images WHERE case_id = ?');
$imgs->execute([$caseId]);
$images = $imgs->fetchAll();


if (!$case['dermatologist_id']) {
    $db->prepare('UPDATE cases SET dermatologist_id = ? WHERE case_id = ?')
       ->execute([currentUserId(), $caseId]);
}

jsonResponse(true, 'OK', ['case' => $case, 'images' => $images]);
