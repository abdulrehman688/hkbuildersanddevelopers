<?php
require_once __DIR__ . '/../../config/database.php';

class Lead {
    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getAgentPerformance(string $dateFrom = '', string $dateTo = ''): array {
        $where  = ["l.deleted_at IS NULL", "u.role = 'agent'"];
        $params = [];
        if ($dateFrom) { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)   { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $dateTo; }

        $stmt = $this->db->prepare("
            SELECT u.id, u.name,
                COUNT(l.id)               AS total,
                SUM(s.is_closed = 0)      AS active,
                SUM(s.name = 'Won')       AS won,
                SUM(s.name = 'Lost')      AS lost,
                SUM(s.name = 'New')       AS new_leads,
                MIN(l.claimed_at)         AS first_claim,
                MAX(l.updated_at)         AS last_activity
            FROM users u
            LEFT JOIN leads l ON l.assigned_to = u.id AND l.deleted_at IS NULL
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY u.id
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getSourceBreakdown(string $dateFrom = '', string $dateTo = ''): array {
        $where  = ["l.deleted_at IS NULL"];
        $params = [];
        if ($dateFrom) { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)   { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $dateTo; }

        $stmt = $this->db->prepare("
            SELECT
                COALESCE(src.name, 'Unknown') AS source_name,
                COUNT(l.id)             AS total,
                SUM(s.name = 'Won')     AS won,
                SUM(s.name = 'Lost')    AS lost
            FROM leads l
            LEFT JOIN lead_sources  src ON src.id = l.source_id
            LEFT JOIN lead_statuses s   ON s.id   = l.status_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY l.source_id
            ORDER BY total DESC
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getStatusBreakdown(string $dateFrom = '', string $dateTo = ''): array {
        $where  = ["l.deleted_at IS NULL"];
        $params = [];
        if ($dateFrom) { $where[] = 'DATE(l.created_at) >= ?'; $params[] = $dateFrom; }
        if ($dateTo)   { $where[] = 'DATE(l.created_at) <= ?'; $params[] = $dateTo; }

        $stmt = $this->db->prepare("
            SELECT s.name, s.color_hex, COUNT(l.id) AS total
            FROM leads l
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE " . implode(' AND ', $where) . "
            GROUP BY l.status_id
            ORDER BY s.sort_order
        ");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getLeadTrend(int $days = 30): array {
        $stmt = $this->db->prepare("
            SELECT DATE(created_at) AS date, COUNT(*) AS total
            FROM leads
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
              AND deleted_at IS NULL
            GROUP BY DATE(created_at)
            ORDER BY date ASC
        ");
        $stmt->execute([$days]);
        return $stmt->fetchAll();
    }

    // ---- Follow-ups --------------------------------------------

    public function scheduleFollowUp(int $leadId, int $agentId, string $scheduledAt, string $note = ''): void {
        $this->db->prepare("
            INSERT INTO follow_ups (lead_id, agent_id, scheduled_at, note)
            VALUES (?, ?, ?, ?)
        ")->execute([$leadId, $agentId, $scheduledAt, $note ?: null]);

        $this->logActivity($leadId, $agentId, 'followup_set',
            'Follow-up scheduled for ' . date('d M Y, h:i A', strtotime($scheduledAt)) . ($note ? '. Note: ' . $note : '.')
        );
    }

    public function markFollowUpDone(int $followUpId, int $agentId): bool {
        $stmt = $this->db->prepare(
            "UPDATE follow_ups SET is_done = 1, done_at = NOW()
             WHERE id = ? AND agent_id = ? AND is_done = 0"
        );
        $stmt->execute([$followUpId, $agentId]);
        return $stmt->rowCount() === 1;
    }

    public function getAgentFollowUps(int $agentId, bool $doneOnly = false): array {
        $where = $doneOnly ? 'f.is_done = 1' : 'f.is_done = 0';
        $stmt  = $this->db->prepare("
            SELECT f.*, l.name AS lead_name, l.phone AS lead_phone
            FROM follow_ups f
            JOIN leads l ON l.id = f.lead_id
            WHERE f.agent_id = ? AND $where AND l.deleted_at IS NULL
            ORDER BY f.scheduled_at ASC
            LIMIT 50
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetchAll();
    }

    public function getAgentStats(int $agentId): array {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total,
                SUM(s.is_closed = 0) AS active,
                SUM(s.name = 'Won')  AS won,
                SUM(s.name = 'Lost') AS lost
            FROM leads l
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE l.assigned_to = ? AND l.deleted_at IS NULL
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetch() ?: ['total'=>0,'active'=>0,'won'=>0,'lost'=>0];
    }

    public function getUnclaimedPool(int $limit = 50, int $offset = 0): array {
        $stmt = $this->db->prepare("
            SELECT l.*, s.name AS status_name, s.color_hex AS status_color,
                   src.name AS source_name
            FROM leads l
            LEFT JOIN lead_statuses  s   ON s.id   = l.status_id
            LEFT JOIN lead_sources   src ON src.id = l.source_id
            WHERE l.assigned_to IS NULL AND l.deleted_at IS NULL
            ORDER BY
                FIELD(l.priority,'hot','warm','cold'),
                l.created_at DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countUnclaimed(): int {
        return (int)$this->db->query(
            "SELECT COUNT(*) FROM leads WHERE assigned_to IS NULL AND deleted_at IS NULL"
        )->fetchColumn();
    }

    public function getDashboardStats(): array {
        $row = $this->db->query("
            SELECT
                COUNT(*) AS total,
                SUM(assigned_to IS NULL AND deleted_at IS NULL) AS unclaimed,
                SUM(assigned_to IS NOT NULL AND deleted_at IS NULL AND s.is_closed = 0) AS claimed,
                SUM(s.name = 'Won'  AND deleted_at IS NULL) AS won,
                SUM(s.name = 'Lost' AND deleted_at IS NULL) AS lost
            FROM leads l
            LEFT JOIN lead_statuses s ON s.id = l.status_id
            WHERE l.deleted_at IS NULL
        ")->fetch();
        return $row ?: ['total'=>0,'unclaimed'=>0,'claimed'=>0,'won'=>0,'lost'=>0];
    }

    public function getAll(array $opts = []): array {
        $limit    = isset($opts['limit'])     ? (int)$opts['limit']     : 50;
        $offset   = isset($opts['offset'])    ? (int)$opts['offset']    : 0;
        $agent    = isset($opts['agent_id'])  ? (int)$opts['agent_id']  : null;
        $status   = isset($opts['status_id']) ? (int)$opts['status_id'] : null;
        $priority = $opts['priority'] ?? null;
        $search   = isset($opts['search'])    ? '%' . $opts['search'] . '%' : null;
        $dateFrom = $opts['date_from'] ?? null;
        $dateTo   = $opts['date_to']   ?? null;

        $where  = ['l.deleted_at IS NULL'];
        $params = [];

        if ($agent) {
            $where[] = 'l.assigned_to = ?';
            $params[] = $agent;
        }
        if ($status) {
            $where[] = 'l.status_id = ?';
            $params[] = $status;
        }
        if ($priority && in_array($priority, ['hot','warm','cold'])) {
            $where[] = 'l.priority = ?';
            $params[] = $priority;
        }
        if ($search) {
            $where[] = '(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.company LIKE ?)';
            $params[] = $search; $params[] = $search;
            $params[] = $search; $params[] = $search;
        }
        if ($dateFrom) {
            $where[] = 'DATE(l.created_at) >= ?';
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $where[] = 'DATE(l.created_at) <= ?';
            $params[] = $dateTo;
        }

        $sql = "
            SELECT l.*, s.name AS status_name, s.color_hex AS status_color,
                   src.name AS source_name, u.name AS agent_name
            FROM leads l
            LEFT JOIN lead_statuses  s   ON s.id   = l.status_id
            LEFT JOIN lead_sources   src ON src.id = l.source_id
            LEFT JOIN users          u   ON u.id   = l.assigned_to
            WHERE " . implode(' AND ', $where) . "
            ORDER BY l.created_at DESC
            LIMIT $limit OFFSET $offset
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function findById(int $id) {
        $stmt = $this->db->prepare("
            SELECT l.*, s.name AS status_name, s.color_hex AS status_color,
                   src.name AS source_name, u.name AS agent_name
            FROM leads l
            LEFT JOIN lead_statuses  s   ON s.id   = l.status_id
            LEFT JOIN lead_sources   src ON src.id = l.source_id
            LEFT JOIN users          u   ON u.id   = l.assigned_to
            WHERE l.id = ? AND l.deleted_at IS NULL
            LIMIT 1
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function create(array $data, int $createdBy): int {
        $stmt = $this->db->prepare("
            INSERT INTO leads (name, email, phone, company, country, source_id, status_id, priority, initial_notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['email']    ?? null,
            $data['phone']    ?? null,
            $data['company']  ?? null,
            $data['country']  ?? null,
            $data['source_id']  ?? null,
            $data['status_id']  ?? 1,
            $data['priority']   ?? 'warm',
            $data['initial_notes'] ?? null,
            $createdBy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void {
        $stmt = $this->db->prepare("
            UPDATE leads SET name=?, email=?, phone=?, company=?, country=?,
                source_id=?, status_id=?, priority=?
            WHERE id=?
        ");
        $stmt->execute([
            $data['name'],
            $data['email']   ?? null,
            $data['phone']   ?? null,
            $data['company'] ?? null,
            $data['country'] ?? null,
            $data['source_id'] ?? null,
            $data['status_id'] ?? 1,
            $data['priority']  ?? 'warm',
            $id,
        ]);
    }

    public function softDelete(int $id): void {
        $this->db->prepare("UPDATE leads SET deleted_at = NOW() WHERE id = ?")
                 ->execute([$id]);
    }

    public function claim(int $leadId, int $agentId): bool {
        // Atomic claim — only succeeds if still unassigned
        $stmt = $this->db->prepare("
            UPDATE leads SET assigned_to = ?, claimed_at = NOW()
            WHERE id = ? AND assigned_to IS NULL AND deleted_at IS NULL
        ");
        $stmt->execute([$agentId, $leadId]);
        return $stmt->rowCount() === 1;
    }

    public function reassign(int $leadId, int $agentId): void {
        $this->db->prepare("
            UPDATE leads SET assigned_to = ?, claimed_at = NOW() WHERE id = ?
        ")->execute([$agentId, $leadId]);
    }

    public function unassign(int $leadId): void {
        $this->db->prepare("
            UPDATE leads SET assigned_to = NULL, claimed_at = NULL WHERE id = ?
        ")->execute([$leadId]);
    }

    public function updateStatus(int $leadId, int $statusId): void {
        $this->db->prepare("UPDATE leads SET status_id = ? WHERE id = ?")
                 ->execute([$statusId, $leadId]);
    }

    public function getSources(): array {
        return $this->db->query("SELECT * FROM lead_sources ORDER BY name")->fetchAll();
    }

    public function getStatuses(): array {
        return $this->db->query("SELECT * FROM lead_statuses ORDER BY sort_order")->fetchAll();
    }

    public function getActivities(int $leadId): array {
        $stmt = $this->db->prepare("
            SELECT a.*, u.name AS user_name
            FROM lead_activities a
            JOIN users u ON u.id = a.user_id
            WHERE a.lead_id = ?
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([$leadId]);
        return $stmt->fetchAll();
    }

    public function logActivity(int $leadId, int $userId, string $type, string $note = '', array $meta = []): void {
        $stmt = $this->db->prepare("
            INSERT INTO lead_activities (lead_id, user_id, type, note, meta)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $leadId,
            $userId,
            $type,
            $note,
            $meta ? json_encode($meta) : null,
        ]);
    }

    public function countAll(array $opts = []): int {
        $where  = ['l.deleted_at IS NULL'];
        $params = [];

        if (!empty($opts['agent_id'])) {
            $where[] = 'l.assigned_to = ?';
            $params[] = (int)$opts['agent_id'];
        }
        if (!empty($opts['status_id'])) {
            $where[] = 'l.status_id = ?';
            $params[] = (int)$opts['status_id'];
        }
        if (!empty($opts['priority']) && in_array($opts['priority'], ['hot','warm','cold'])) {
            $where[] = 'l.priority = ?';
            $params[] = $opts['priority'];
        }
        if (!empty($opts['search'])) {
            $s = '%' . $opts['search'] . '%';
            $where[] = '(l.name LIKE ? OR l.phone LIKE ? OR l.email LIKE ? OR l.company LIKE ?)';
            $params[] = $s; $params[] = $s; $params[] = $s; $params[] = $s;
        }
        if (!empty($opts['date_from'])) {
            $where[] = 'DATE(l.created_at) >= ?';
            $params[] = $opts['date_from'];
        }
        if (!empty($opts['date_to'])) {
            $where[] = 'DATE(l.created_at) <= ?';
            $params[] = $opts['date_to'];
        }

        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM leads l WHERE " . implode(' AND ', $where)
        );
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }
}
