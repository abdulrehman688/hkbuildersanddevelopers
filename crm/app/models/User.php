<?php
require_once __DIR__ . '/../../config/database.php';

class User {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function findByEmail(string $email): array|false {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, password, role, status, failed_logins, locked_until
             FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->execute([$email]);
        return $stmt->fetch();
    }

    public function findById(int $id): array|false {
        $stmt = $this->db->prepare(
            'SELECT id, name, email, role, status, created_at FROM users WHERE id = ? LIMIT 1'
        );
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function incrementFailedLogins(int $id): void {
        $this->db->prepare(
            'UPDATE users SET failed_logins = failed_logins + 1 WHERE id = ?'
        )->execute([$id]);
    }

    public function lockAccount(int $id, int $minutes): void {
        $until = date('Y-m-d H:i:s', strtotime("+{$minutes} minutes"));
        $this->db->prepare(
            'UPDATE users SET locked_until = ?, failed_logins = ? WHERE id = ?'
        )->execute([$until, MAX_LOGIN_ATTEMPTS, $id]);
    }

    public function resetFailedLogins(int $id): void {
        $this->db->prepare(
            'UPDATE users SET failed_logins = 0, locked_until = NULL WHERE id = ?'
        )->execute([$id]);
    }

    public function createAgent(string $name, string $email, string $password): int {
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, "agent")'
        );
        $stmt->execute([$name, $email, $hash]);
        return (int)$this->db->lastInsertId();
    }

    public function getAllAgents(): array {
        return $this->db->query(
            'SELECT id, name, email, status, created_at FROM users WHERE role = "agent" ORDER BY name'
        )->fetchAll();
    }

    public function getAllAgentsWithStats(): array {
        return $this->db->query("
            SELECT u.id, u.name, u.email, u.status, u.created_at,
                COUNT(l.id)                                          AS total_leads,
                SUM(l.assigned_to IS NOT NULL AND s.is_closed = 0)  AS active_leads,
                SUM(s.name = 'Won')                                  AS won,
                SUM(s.name = 'Lost')                                 AS lost
            FROM users u
            LEFT JOIN leads l ON l.assigned_to = u.id AND l.deleted_at IS NULL
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE u.role = 'agent'
            GROUP BY u.id
            ORDER BY u.name
        ")->fetchAll();
    }

    public function setStatus(int $id, string $status): void {
        $this->db->prepare(
            'UPDATE users SET status = ? WHERE id = ?'
        )->execute([$status, $id]);
    }

    public function emailExists(string $email, int $excludeId = 0): bool {
        $stmt = $this->db->prepare(
            'SELECT COUNT(*) FROM users WHERE email = ? AND id != ?'
        );
        $stmt->execute([$email, $excludeId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function updatePassword(int $id, string $newPassword): void {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
        $this->db->prepare(
            'UPDATE users SET password = ? WHERE id = ?'
        )->execute([$hash, $id]);
    }

    public function setRememberToken(int $id, string $hashedToken): void {
        $this->db->prepare(
            'UPDATE users SET remember_token = ? WHERE id = ?'
        )->execute([$hashedToken, $id]);
    }

    public function getRememberToken(int $id): ?string {
        $stmt = $this->db->prepare('SELECT remember_token FROM users WHERE id = ? LIMIT 1');
        $stmt->execute([$id]);
        return $stmt->fetchColumn() ?: null;
    }

    public function clearRememberToken(int $id): void {
        $this->db->prepare(
            'UPDATE users SET remember_token = NULL WHERE id = ?'
        )->execute([$id]);
    }
}
