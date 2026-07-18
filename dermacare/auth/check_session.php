<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');

if (isLoggedIn()) {
    echo json_encode([
        'logged_in' => true,
        'name'      => $_SESSION['name'],
        'role'      => $_SESSION['role'],
    ]);
} else {
    echo json_encode(['logged_in' => false]);
}
