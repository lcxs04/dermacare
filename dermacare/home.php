<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';

$loggedIn = isLoggedIn();
$userName = htmlspecialchars($_SESSION['name'] ?? '');
$userRole = $_SESSION['role'] ?? '';
$dashUrl  = ($userRole === 'dermatologist')
    ? '/dermacare/dermatologist/dashboard.php'
    : '/dermacare/patient/dashboard.php';


$html = file_get_contents(__DIR__ . '/DermaCare.html');


if ($loggedIn) {
    $navHtml = '<span style="font-size:13px;color:var(--text-secondary);">Hi, <strong style="color:var(--primary);">' . $userName . '</strong></span>'
             . '<button class="btn-outline" style="padding:8px 18px;font-size:13px;" onclick="window.location.href=\'' . $dashUrl . '\'">My Dashboard</button>'
             . '<button class="btn-primary" style="padding:8px 18px;font-size:13px;" onclick="window.location.href=\'/dermacare/auth/logout.php\'">Logout</button>';
    $showDoctor = ($userRole === 'dermatologist') ? 'inline' : 'none';
    $doctorUrl  = '/dermacare/dermatologist/dashboard.php';
} else {
    $navHtml = '<button class="btn-outline" style="padding:8px 18px;font-size:13px;" onclick="window.location.href=\'/dermacare/auth/login_page.php\'">Sign In</button>'
             . '<button class="btn-primary" style="padding:8px 18px;font-size:13px;" onclick="window.location.href=\'/dermacare/auth/register_page.php\'">Get Started</button>';
    $showDoctor = 'none';
    $doctorUrl  = '/dermacare/auth/login_page.php';
}


$html = preg_replace(
    '/<div[^>]*id="nav-auth-buttons"[^>]*>.*?<\/div>/s',
    '<div style="display:flex;gap:10px;align-items:center;" id="nav-auth-buttons">' . $navHtml . '</div>',
    $html
);


$html = preg_replace(
    '/<span[^>]*id="nav-doctor"[^>]*>.*?<\/span>/s',
    '<span class="nav-link" id="nav-doctor" onclick="window.location.href=\'' . $doctorUrl . '\'" style="display:' . $showDoctor . ';">Doctor Portal</span>',
    $html
);

echo $html;
