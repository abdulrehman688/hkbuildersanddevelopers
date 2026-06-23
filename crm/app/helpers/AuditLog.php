<?php
require_once __DIR__ . '/../../config/database.php';

class AuditLog {

    public static function log(
        string $action,
        ?int   $userId     = null,
        ?string $targetType = null,
        ?int   $targetId   = null
    ): void {
        try {
            $db = Database::connect();
            $db->prepare("
                INSERT INTO audit_log (user_id, action, target_type, target_id, ip_address, user_agent)
                VALUES (?, ?, ?, ?, ?, ?)
            ")->execute([
                $userId,
                $action,
                $targetType,
                $targetId,
                self::ip(),
                substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300),
            ]);
        } catch (Exception $e) {
            // Never let audit logging break the app
            error_log('AuditLog error: ' . $e->getMessage());
        }
    }

    private static function ip(): string {
        foreach (['HTTP_X_FORWARDED_FOR','HTTP_CLIENT_IP','REMOTE_ADDR'] as $key) {
            if (!empty($_SERVER[$key])) {
                return trim(explode(',', $_SERVER[$key])[0]);
            }
        }
        return '0.0.0.0';
    }
}
