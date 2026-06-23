<?php
// ============================================================
// Front Controller — all requests pass through here
// NOTE: On production (Hostinger), this file sits at crm/ root.
//       On local (XAMPP), it sits at public/ with ../ paths.
//       The APP_ROOT constant handles both cases.
// ============================================================

define('APP_ROOT', is_dir(__DIR__ . '/../app') ? __DIR__ . '/..' : __DIR__);

require_once APP_ROOT . '/config/config.php';
require_once APP_ROOT . '/app/helpers/Security.php';

Security::startSession();
Security::sendSecureHeaders();

// Parse URL
$url = trim($_GET['url'] ?? '', '/');
$segments = explode('/', $url);

$section  = $segments[0] ?? '';
$action   = $segments[1] ?? '';
$param    = $segments[2] ?? null;

// ---- Route Table --------------------------------------------

if ($section === 'login') {
    require_once APP_ROOT . '/app/controllers/AuthController.php';
    $ctrl = new AuthController();
    $_SERVER['REQUEST_METHOD'] === 'POST' ? $ctrl->handleLogin() : $ctrl->showLogin();
    exit;
}

if ($section === 'logout') {
    require_once APP_ROOT . '/app/controllers/AuthController.php';
    (new AuthController())->logout();
    exit;
}

if ($section === '') {
    if (!empty($_SESSION['user_id'])) {
        $role = $_SESSION['user_role'];
        header('Location: ' . APP_URL . '/' . ($role === 'admin' ? 'admin' : 'agent') . '/dashboard');
    } else {
        header('Location: ' . APP_URL . '/login');
    }
    exit;
}

if ($section === 'admin') {
    Security::requireAdmin();
    require_once APP_ROOT . '/app/controllers/AdminController.php';
    $ctrl = new AdminController();
    match($action) {
        'leads'        => $ctrl->leads(),
        'lead'         => $ctrl->leadDetail((int)$param),
        'agents'       => $ctrl->agents(),
        'reports'      => $ctrl->reports(),
        'csv-template' => $ctrl->downloadCsvTemplate(),
        'password'     => $ctrl->changePassword(),
        'audit'        => $ctrl->auditLog(),
        default        => $ctrl->dashboard(),
    };
    exit;
}

if ($section === 'agent') {
    Security::requireLogin();
    require_once APP_ROOT . '/app/controllers/AgentController.php';
    $ctrl = new AgentController();
    match($action) {
        'leads'    => $ctrl->myLeads(),
        'pool'     => $ctrl->leadPool(),
        'lead'     => $ctrl->leadDetail((int)$param),
        'claim'    => $ctrl->claimLead((int)$param),
        'password' => $ctrl->changePassword(),
        default    => $ctrl->dashboard(),
    };
    exit;
}

http_response_code(404);
echo '<h1>404 — Page not found</h1>';
