<?php

ob_start(); 

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

ob_clean(); 
header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}


verifyCsrf();


$email    = trim($_POST['email']    ?? '');
$password = $_POST['password']      ?? '';
$role     = $_POST['role']          ?? 'patient';


if (!filter_var($email, FILTER_VALIDATE_EMAIL) || empty($password)) {
    jsonResponse(false, 'Please enter a valid email and password.', [], 422);
}

if (!in_array($role, ['patient', 'dermatologist', 'admin'], true)) {
    jsonResponse(false, 'Invalid role selected.', [], 422);
}


$ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$attemptKey  = 'login_attempts_' . md5($ip);
$maxAttempts = 5;
$windowSecs  = 900;

$attempts    = (int) ($_SESSION[$attemptKey]['count']  ?? 0);
$windowStart = (int) ($_SESSION[$attemptKey]['window'] ?? 0);

if (time() - $windowStart > $windowSecs) {
    $_SESSION[$attemptKey] = ['count' => 0, 'window' => time()];
    $attempts = 0;
}

if ($attempts >= $maxAttempts) {
    $waitMin = ceil(($windowSecs - (time() - $windowStart)) / 60);
    jsonResponse(false, "Too many failed attempts. Please try again in {$waitMin} minute(s).", [], 429);
}


$db   = getDB();
$stmt = $db->prepare(
    'SELECT user_id, name, email, password_hash, role, is_active FROM users WHERE email = ? LIMIT 1'
);
$stmt->execute([$email]);
$user = $stmt->fetch();


$validPassword = $user && password_verify($password, $user['password_hash']);

if (!$user || !$validPassword) {
    $_SESSION[$attemptKey]['count'] = $attempts + 1;
    auditLog('login_failed', "Failed login for: $email from IP: $ip");
    jsonResponse(false, 'Invalid email or password.', [], 401);
}


if ($user['role'] !== $role && $user['role'] !== 'admin') {
    jsonResponse(false, 'You are not registered as a ' . $role . '.', [], 403);
}


if (!$user['is_active']) {
    jsonResponse(false, 'Your account has been deactivated. Please contact support.', [], 403);
}


if (password_needs_rehash($user['password_hash'], PASSWORD_BCRYPT, ['cost' => 12])) {
    $newHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?')
       ->execute([$newHash, $user['user_id']]);
}


session_regenerate_id(true);
$_SESSION['user_id'] = (int)  $user['user_id'];
$_SESSION['name']    =        $user['name'];
$_SESSION['email']   =        $user['email'];
$_SESSION['role']    =        $user['role'];


unset($_SESSION[$attemptKey]);


auditLog('login', "User logged in: {$user['email']} as {$user['role']}");

$redirectMap = [
    'patient'       => '/dermacare/patient/dashboard.php',
    'dermatologist' => '/dermacare/dermatologist/dashboard.php',
    'admin'         => '/dermacare/admin/dashboard.php',
];

jsonResponse(true, 'Login successful! Redirecting…', [
    'redirect' => $redirectMap[$user['role']] ?? '/dermacare/index.php',
    'user'     => ['name' => $user['name'], 'role' => $user['role']],
]);
