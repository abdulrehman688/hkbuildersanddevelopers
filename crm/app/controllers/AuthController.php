<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/AuditLog.php';

class AuthController {
    private User $userModel;

    private const REMEMBER_COOKIE  = 'crm_remember';
    private const REMEMBER_DAYS    = 30;

    public function __construct() {
        Security::startSession();
        $this->userModel = new User();
    }

    public function showLogin(): void {
        // Auto-login via remember-me cookie
        if (empty($_SESSION['user_id'])) {
            $this->tryRememberMe();
        }
        if (!empty($_SESSION['user_id'])) {
            $this->redirectByRole($_SESSION['user_role']);
        }
        require_once __DIR__ . '/../views/auth/login.php';
    }

    public function handleLogin(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request. Please try again.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $email      = trim($_POST['email'] ?? '');
        $password   = $_POST['password'] ?? '';
        $rememberMe = !empty($_POST['remember_me']);

        if ($email === '' || $password === '') {
            $_SESSION['error'] = 'Email and password are required.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            password_verify($password, '$2y$12$invalid.hash.to.prevent.timing');
            AuditLog::log('login_fail_unknown_email');
            $_SESSION['error'] = 'Invalid email or password.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Account locked
        if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
            $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
            AuditLog::log('login_blocked_locked', $user['id']);
            $_SESSION['error'] = "Account locked. Try again in {$mins} minute(s).";
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Suspended
        if ($user['status'] === 'suspended') {
            AuditLog::log('login_blocked_suspended', $user['id']);
            $_SESSION['error'] = 'Your account has been suspended. Contact admin.';
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Wrong password
        if (!password_verify($password, $user['password'])) {
            $this->userModel->incrementFailedLogins($user['id']);
            AuditLog::log('login_fail', $user['id']);

            if ($user['failed_logins'] + 1 >= MAX_LOGIN_ATTEMPTS) {
                $this->userModel->lockAccount($user['id'], LOCKOUT_MINUTES);
                $_SESSION['error'] = 'Too many failed attempts. Account locked for ' . LOCKOUT_MINUTES . ' minutes.';
            } else {
                $remaining = MAX_LOGIN_ATTEMPTS - ($user['failed_logins'] + 1);
                $_SESSION['error'] = "Invalid email or password. {$remaining} attempt(s) remaining.";
            }
            header('Location: ' . APP_URL . '/login');
            exit;
        }

        // Success
        $this->userModel->resetFailedLogins($user['id']);
        Security::regenerateSession();

        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        AuditLog::log('login_success', $user['id']);

        if ($rememberMe) {
            $this->setRememberMeCookie($user['id']);
        }

        $this->redirectByRole($user['role']);
    }

    public function logout(): void {
        $userId = $_SESSION['user_id'] ?? null;
        AuditLog::log('logout', $userId);

        // Clear remember-me cookie and token
        if (isset($_COOKIE[self::REMEMBER_COOKIE])) {
            $this->userModel->clearRememberToken((int)$userId);
            setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/', '', false, true);
        }

        $_SESSION = [];
        session_destroy();
        header('Location: ' . APP_URL . '/login');
        exit;
    }

    // ---- Remember Me -----------------------------------------

    private function setRememberMeCookie(int $userId): void {
        $token  = bin2hex(random_bytes(32));
        $hashed = hash('sha256', $token);

        $this->userModel->setRememberToken($userId, $hashed);

        setcookie(
            self::REMEMBER_COOKIE,
            $userId . ':' . $token,
            time() + (self::REMEMBER_DAYS * 86400),
            '/',
            '',
            defined('HTTPS_ENABLED') && HTTPS_ENABLED,
            true
        );
    }

    private function tryRememberMe(): void {
        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? '';
        if (!$cookie) return;

        $parts = explode(':', $cookie, 2);
        if (count($parts) !== 2) return;

        [$userId, $token] = $parts;
        $userId = (int)$userId;
        if (!$userId || !$token) return;

        $user = $this->userModel->findById($userId);
        if (!$user || $user['status'] !== 'active') return;

        $stored = $this->userModel->getRememberToken($userId);
        if (!$stored || !hash_equals($stored, hash('sha256', $token))) return;

        // Valid — log them in and rotate token
        Security::regenerateSession();
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];

        $this->setRememberMeCookie($userId); // rotate token
        AuditLog::log('login_remember_me', $userId);
    }

    private function redirectByRole(string $role): void {
        header('Location: ' . APP_URL . '/' . ($role === 'admin' ? 'admin' : 'agent') . '/dashboard');
        exit;
    }
}
