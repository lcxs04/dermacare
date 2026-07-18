<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireRole('patient');

$caseId = (int) ($_GET['id'] ?? 0);
if (!$caseId) jsonResponse(false, 'Invalid case ID.');

$db = getDB();


$stmt = $db->prepare('SELECT * FROM cases WHERE case_id = ? AND patient_id = ? LIMIT 1');
$stmt->execute([$caseId, currentUserId()]);
$case = $stmt->fetch();

if (!$case) jsonResponse(false, 'Case not found.', [], 404);


$case['created_at'] = date('d M Y', strtotime($case['created_at']));


$imgs = $db->prepare('SELECT file_path, thumbnail_path FROM images WHERE case_id = ?');
$imgs->execute([$caseId]);
$images = $imgs->fetchAll();

jsonResponse(true, 'OK', ['case' => $case, 'images' => $images]);
