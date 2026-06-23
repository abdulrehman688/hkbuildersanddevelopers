<?php

class Security {

    // ---- CSRF --------------------------------------------------

    public static function generateCsrfToken(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(CSRF_TOKEN_LENGTH));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken(string $token): bool {
        return isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function csrfField(): string {
        return '<input type="hidden" name="csrf_token" value="'
            . self::generateCsrfToken() . '">';
    }

    // ---- Output Escaping ---------------------------------------

    public static function e(mixed $value): string {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }

    // ---- Session -----------------------------------------------

    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(SESSION_NAME);
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => defined('HTTPS_ENABLED') && HTTPS_ENABLED,
                'httponly' => true,
                'samesite' => 'Strict',
            ]);
            session_start();
        }
    }

    public static function regenerateSession(): void {
        session_regenerate_id(true);
    }

    // ---- Auth Checks -------------------------------------------

    public static function requireLogin(): void {
        if (empty($_SESSION['user_id'])) {
            header('Location: ' . APP_URL . '/login');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if ($_SESSION['user_role'] !== 'admin') {
            http_response_code(403);
            die('Access denied.');
        }
    }

    public static function requireAgent(): void {
        self::requireLogin();
        if ($_SESSION['user_role'] !== 'agent') {
            header('Location: ' . APP_URL . '/admin/dashboard');
            exit;
        }
    }

    // ---- Secure Headers ----------------------------------------

    public static function sendSecureHeaders(): void {
        header_remove('X-Powered-By');
        header('X-Frame-Options: SAMEORIGIN');
        header('X-Content-Type-Options: nosniff');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
    }

    // ---- Input Sanitization ------------------------------------

    public static function int(mixed $value): int {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    public static function str(mixed $value, int $maxLen = 500): string {
        return mb_substr(trim((string)$value), 0, $maxLen);
    }

    // ---- Rate Limiting (login brute force) ---------------------

    public static function isRateLimited(string $key, int $maxAttempts = 5, int $windowSeconds = 300): bool {
        $sessionKey = 'rl_' . md5($key);
        $now        = time();

        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = ['count' => 0, 'window_start' => $now];
        }

        $data = &$_SESSION[$sessionKey];

        // Reset window if expired
        if ($now - $data['window_start'] > $windowSeconds) {
            $data = ['count' => 0, 'window_start' => $now];
        }

        $data['count']++;
        return $data['count'] > $maxAttempts;
    }

    public static function clearRateLimit(string $key): void {
        unset($_SESSION['rl_' . md5($key)]);
    }
}
