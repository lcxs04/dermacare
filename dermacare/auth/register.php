<?php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';

header('Content-Type: application/json');


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}


verifyCsrf();


$name            = trim($_POST['name']            ?? '');
$email           = trim($_POST['email']           ?? '');
$password        = $_POST['password']             ?? '';
$confirmPassword = $_POST['confirm_password']     ?? '';


$errors = [];

if (empty($name) || mb_strlen($name) < 2) {
    $errors[] = 'Full name must be at least 2 characters.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'Please enter a valid email address.';
}


if (strlen($password) < 8) {
    $errors[] = 'Password must be at least 8 characters.';
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = 'Password must contain at least one uppercase letter.';
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors[] = 'Password must contain at least one lowercase letter.';
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = 'Password must contain at least one number.';
} elseif (!preg_match('/[\W_]/', $password)) {
    $errors[] = 'Password must contain at least one special character.';
}

if ($password !== $confirmPassword) {
    $errors[] = 'Passwords do not match.';
}

if (!empty($errors)) {
    jsonResponse(false, implode(' ', $errors), ['errors' => $errors], 422);
}


$db   = getDB();
$stmt = $db->prepare('SELECT user_id FROM users WHERE email = ? LIMIT 1');
$stmt->execute([$email]);

if ($stmt->fetch()) {
    jsonResponse(false, 'An account with this email already exists.', [], 409);
}


$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

$insert = $db->prepare(
    'INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)'
);
$insert->execute([$name, $email, $hash, 'patient']);
$newUserId = (int) $db->lastInsertId();


session_regenerate_id(true);
$_SESSION['user_id'] = $newUserId;
$_SESSION['name']    = $name;
$_SESSION['email']   = $email;
$_SESSION['role']    = 'patient';


auditLog('register', "New patient registered: $email");

jsonResponse(true, 'Account created successfully! Welcome to DermaCare.', [
    'redirect' => '/dermacare/patient/dashboard.php',
    'user'     => ['name' => $name, 'role' => 'patient'],
]);
