<?php


ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');


@session_start(); 


function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}


function requireLogin(string $redirectTo = '/dermacare/auth/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirectTo);
        exit;
    }
}


function requireRole(string $role, string $redirectTo = '/dermacare/auth/login.php'): void {
    requireLogin($redirectTo);
    if (($_SESSION['role'] ?? '') !== $role) {
        header('Location: /dermacare/auth/unauthorized.php');
        exit;
    }
}


function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}


function currentRole(): ?string {
    return $_SESSION['role'] ?? null;
}


function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}


function verifyCsrf(): void {
    $submitted = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        die(json_encode(['success' => false, 'message' => 'Invalid CSRF token.']));
    }
}



function auditLog(string $action, ?string $details = null): void {
    try {
        $db = getDB();
        $stmt = $db->prepare(
            'INSERT INTO audit_log (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            currentUserId(),
            $action,
            $details,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    } catch (Throwable $e) {
        error_log('Audit log error: ' . $e->getMessage());
    }
}



function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}
