<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
requireRole('patient');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') jsonResponse(false, 'Method not allowed.', [], 405);
verifyCsrf();

$title = trim($_POST['title']       ?? '');
$desc  = trim($_POST['description'] ?? '');

if (empty($title)) jsonResponse(false, 'Please enter a case title.');
if (empty($desc))  jsonResponse(false, 'Please describe your skin problem.');

$db = getDB();


$stmt = $db->prepare('INSERT INTO cases (patient_id, title, description, status) VALUES (?, ?, ?, ?)');
$stmt->execute([currentUserId(), $title, $desc, 'submitted']);
$caseId = (int) $db->lastInsertId();


$uploadDir = __DIR__ . '/../uploads/cases/' . $caseId . '/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
$maxSize = 5 * 1024 * 1024; // 5 MB

if (!empty($_FILES['photos']['name'][0])) {
    $files = $_FILES['photos'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;
        if ($files['size'][$i]  > $maxSize)        continue;
        if (!in_array($files['type'][$i], $allowed)) continue;

        $ext      = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
        $filename = bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . $filename;

        if (move_uploaded_file($files['tmp_name'][$i], $destPath)) {
            $relPath = 'uploads/cases/' . $caseId . '/' . $filename;
            $db->prepare('INSERT INTO images (case_id, file_path) VALUES (?, ?)')->execute([$caseId, $relPath]);
        }
    }
}

auditLog('case_submit', "Patient submitted case ID: $caseId — $title");

jsonResponse(true, 'Case submitted successfully! A dermatologist will review it soon.', ['case_id' => $caseId]);
