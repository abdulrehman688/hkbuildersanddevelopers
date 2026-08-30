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
            'SELECT id, name, email, role, status, phone, address, cnic, guardian_phone,
                    designation, base_salary, commission_rate, team_id, created_at
             FROM users WHERE id = ? LIMIT 1'
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

    public function createAgent(array $data): int {
        $hash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        $role = in_array($data['role'] ?? 'agent', ['agent','sales_manager']) ? ($data['role'] ?? 'agent') : 'agent';
        $stmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, phone, address, cnic, guardian_phone,
                                designation, base_salary, commission_rate)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $hash,
            $role,
            $data['phone']           ?? null,
            $data['address']         ?? null,
            $data['cnic']            ?? null,
            $data['guardian_phone']  ?? null,
            $data['designation']     ?? null,
            (float)($data['base_salary']      ?? 0),
            (float)($data['commission_rate']  ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateAgent(int $id, array $data): void {
        $role = in_array($data['role'] ?? 'agent', ['agent','sales_manager']) ? ($data['role'] ?? 'agent') : 'agent';
        $stmt = $this->db->prepare(
            'UPDATE users SET name=?, email=?, role=?, phone=?, address=?, cnic=?,
                              guardian_phone=?, designation=?, base_salary=?, commission_rate=?
             WHERE id=?'
        );
        $stmt->execute([
            $data['name'],
            $data['email'],
            $role,
            $data['phone']           ?? null,
            $data['address']         ?? null,
            $data['cnic']            ?? null,
            $data['guardian_phone']  ?? null,
            $data['designation']     ?? null,
            (float)($data['base_salary']     ?? 0),
            (float)($data['commission_rate'] ?? 0),
            $id,
        ]);
    }

    public function getAllAgents(): array {
        return $this->db->query(
            "SELECT id, name, email, role, status, designation, phone, created_at
             FROM users WHERE role IN ('agent','sales_manager') ORDER BY name"
        )->fetchAll();
    }

    public function getAllAgentsWithStats(): array {
        return $this->db->query("
            SELECT u.id, u.name, u.email, u.role, u.status, u.designation, u.created_at,
                COUNT(l.id)                                          AS total_leads,
                SUM(l.assigned_to IS NOT NULL AND s.is_closed = 0)  AS active_leads,
                SUM(s.name = 'Won')                                  AS won,
                SUM(s.name = 'Lost')                                 AS lost
            FROM users u
            LEFT JOIN leads l ON l.assigned_to = u.id AND l.deleted_at IS NULL
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE u.role IN ('agent','sales_manager')
            GROUP BY u.id
            ORDER BY u.name
        ")->fetchAll();
    }

    public function getAgentSummaryStats(): array {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(status = 'active') AS active,
                SUM(status = 'suspended') AS suspended
            FROM users WHERE role IN ('agent','sales_manager')
        ")->fetch();
        return $row ?: ['total'=>0,'active'=>0,'suspended'=>0];
    }

    public function getAgentDetail(int $id, string $month = ''): array|false {
        $agent = $this->findById($id);
        if (!$agent) return false;

        $params = [$id];
        $monthWhere = '';
        if ($month) {
            $monthWhere = "AND DATE_FORMAT(l.created_at,'%Y-%m') = ?";
            $params[] = $month;
        }

        $stmt = $this->db->prepare("
            SELECT
                COUNT(l.id) AS total_leads,
                SUM(s.name = 'New')         AS st_new,
                SUM(s.name = 'Contacted')   AS st_contacted,
                SUM(s.name = 'Qualified')   AS st_qualified,
                SUM(s.name = 'Proposal')    AS st_proposal,
                SUM(s.name = 'Negotiation') AS st_negotiation,
                SUM(s.name = 'Won')         AS st_won,
                SUM(s.name = 'Lost')        AS st_lost,
                SUM(s.name = 'Dead')        AS st_dead,
                SUM(s.name IN ('Qualified','Proposal','Negotiation')) AS interested,
                SUM(s.name IN ('Lost','Dead'))                         AS not_interested,
                SUM(s.name = 'Won')                                    AS closed
            FROM leads l
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL $monthWhere
        ");
        $stmt->execute($params);
        $stats = $stmt->fetch() ?: [];

        return array_merge($agent, ['stats' => $stats]);
    }

    public function getAgentLeads(int $id, string $month = '', int $limit = 100, int $offset = 0): array {
        $params = [$id];
        $monthWhere = '';
        if ($month) {
            $monthWhere = "AND DATE_FORMAT(l.created_at,'%Y-%m') = ?";
            $params[] = $month;
        }
        $stmt = $this->db->prepare("
            SELECT l.id, COALESCE(l.name,'Unknown') AS name, l.phone, l.project, l.category,
                   s.name AS status_name, s.color_hex AS status_color, l.created_at
            FROM leads l
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL $monthWhere
            ORDER BY l.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAvailableMonths(): array {
        return $this->db->query("
            SELECT DISTINCT DATE_FORMAT(created_at,'%Y-%m') AS ym,
                   DATE_FORMAT(created_at,'%M %Y') AS label
            FROM leads WHERE deleted_at IS NULL
            ORDER BY ym DESC
            LIMIT 24
        ")->fetchAll();
    }

    public function getAgentMonthlyTrend(int $id): array {
        $stmt = $this->db->prepare("
            SELECT DATE_FORMAT(l.created_at,'%b %Y')  AS label,
                   DATE_FORMAT(l.created_at,'%Y-%m')  AS ym,
                   COUNT(l.id)                         AS total,
                   SUM(ls.name = 'Won')                AS won
            FROM leads l
            LEFT JOIN lead_statuses ls ON ls.id = l.status_id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL
              AND l.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
            GROUP BY ym, label
            ORDER BY ym ASC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function getAgentFollowUpStats(int $id): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*)                                         AS total,
                SUM(is_done = 1)                                 AS done,
                SUM(is_done = 0)                                 AS pending,
                SUM(is_done = 0 AND scheduled_at < NOW())        AS overdue
            FROM follow_ups
            WHERE agent_id = ?
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: ['total'=>0,'done'=>0,'pending'=>0,'overdue'=>0];
    }

    public function getAgentSourceBreakdown(int $id): array {
        $stmt = $this->db->prepare("
            SELECT COALESCE(src.name,'Unknown') AS source_name,
                   COUNT(l.id)                  AS total,
                   SUM(ls.name = 'Won')          AS won,
                   SUM(ls.name = 'Lost')         AS lost
            FROM leads l
            LEFT JOIN lead_sources  src ON src.id = l.source_id
            LEFT JOIN lead_statuses ls  ON ls.id  = l.status_id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL
            GROUP BY l.source_id, src.name
            ORDER BY total DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function getAgentWonDeals(int $id): array {
        $stmt = $this->db->prepare("
            SELECT l.id, COALESCE(l.name,'Unknown') AS name, l.phone,
                   l.project, l.category, l.updated_at,
                   COALESCE(c.booking_amount, l.investment_amount)  AS booking_amount,
                   COALESCE(c.file_status, 'mature')                AS file_status,
                   c.unit_no, c.block
            FROM leads l
            JOIN lead_statuses ls ON ls.id = l.status_id
            LEFT JOIN clients c ON c.lead_id = l.id
            WHERE l.assigned_to = ? AND ls.name = 'Won' AND l.deleted_at IS NULL
            ORDER BY l.updated_at DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
    }

    public function getAgentCommissionSummary(int $id): array {
        $stmt = $this->db->prepare("
            SELECT
                COALESCE(SUM(c.booking_amount), 0)  AS total_booking,
                COALESCE(SUM(c.booking_amount), 0) * (u.commission_rate / 100) AS total_commission,
                COALESCE(SUM(CASE WHEN c.file_status = 'mature'   THEN c.booking_amount ELSE 0 END), 0)
                    * (u.commission_rate / 100)     AS mature_commission,
                COALESCE(SUM(CASE WHEN c.file_status = 'immature' THEN c.booking_amount ELSE 0 END), 0)
                    * (u.commission_rate / 100)     AS immature_commission,
                COUNT(c.id)                         AS total_clients,
                COALESCE(SUM(c.file_status = 'mature'),   0) AS mature_count,
                COALESCE(SUM(c.file_status = 'immature'), 0) AS immature_count
            FROM users u
            LEFT JOIN clients c ON c.agent_id = u.id
            WHERE u.id = ?
            GROUP BY u.id, u.commission_rate
        ");
        $stmt->execute([$id]);
        return $stmt->fetch() ?: [
            'total_booking' => 0, 'total_commission' => 0,
            'mature_commission' => 0, 'immature_commission' => 0,
            'total_clients' => 0, 'mature_count' => 0, 'immature_count' => 0,
        ];
    }

    public function getAgentCareerByYear(int $id): array {
        $stmt = $this->db->prepare("
            SELECT
                YEAR(l.created_at)  AS year,
                COUNT(l.id)         AS total_assigned,
                COALESCE(SUM(ls.name = 'Won'),  0)  AS won,
                COALESCE(SUM(ls.name = 'Lost'), 0)  AS lost,
                COALESCE(SUM(CASE WHEN ls.name = 'Won' THEN COALESCE(c.booking_amount, 0) ELSE 0 END), 0)
                    AS booking_value,
                COALESCE(SUM(CASE WHEN ls.name = 'Won' THEN COALESCE(c.booking_amount, 0) ELSE 0 END), 0)
                    * (u.commission_rate / 100) AS commission
            FROM leads l
            JOIN users u ON u.id = l.assigned_to
            LEFT JOIN lead_statuses ls ON ls.id = l.status_id
            LEFT JOIN clients c ON c.lead_id = l.id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL
            GROUP BY YEAR(l.created_at)
            ORDER BY year DESC
        ");
        $stmt->execute([$id]);
        return $stmt->fetchAll();
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
