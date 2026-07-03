<?php
require_once __DIR__ . '/../../config/database.php';

class Notice {

    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function create(array $data): int {
        $stmt = $this->db->prepare("
            INSERT INTO notices (type, title, message, attachment, created_by)
            VALUES (:type, :title, :message, :attachment, :created_by)
        ");
        $stmt->execute([
            ':type'       => $data['type'],
            ':title'      => $data['title'],
            ':message'    => $data['message'],
            ':attachment' => $data['attachment'] ?? null,
            ':created_by' => $data['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function getAll(): array {
        return $this->db->query("
            SELECT n.*,
                   u.name AS creator_name,
                   (SELECT COUNT(*) FROM notice_completions nc WHERE nc.notice_id = n.id) AS done_count,
                   (SELECT COUNT(*) FROM users WHERE role IN ('agent','sales_manager') AND status = 'active') AS total_staff
            FROM notices n
            LEFT JOIN users u ON u.id = n.created_by
            ORDER BY n.created_at DESC
        ")->fetchAll();
    }

    public function getForUser(int $userId): array {
        $stmt = $this->db->prepare("
            SELECT n.*,
                   u.name AS creator_name,
                   nc.marked_done_at
            FROM notices n
            LEFT JOIN users u ON u.id = n.created_by
            LEFT JOIN notice_completions nc ON nc.notice_id = n.id AND nc.user_id = :uid
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([':uid' => $userId]);
        return $stmt->fetchAll();
    }

    public function markDone(int $noticeId, int $userId): void {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO notice_completions (notice_id, user_id)
            VALUES (:notice_id, :user_id)
        ");
        $stmt->execute([':notice_id' => $noticeId, ':user_id' => $userId]);
    }

    public function unmarkDone(int $noticeId, int $userId): void {
        $stmt = $this->db->prepare("
            DELETE FROM notice_completions WHERE notice_id = :notice_id AND user_id = :user_id
        ");
        $stmt->execute([':notice_id' => $noticeId, ':user_id' => $userId]);
    }

    public function delete(int $id): void {
        $row = $this->db->prepare("SELECT attachment FROM notices WHERE id = :id");
        $row->execute([':id' => $id]);
        $notice = $row->fetch();
        if ($notice && $notice['attachment']) {
            $path = APP_ROOT . '/uploads/notices/' . basename($notice['attachment']);
            if (file_exists($path)) @unlink($path);
        }
        $this->db->prepare("DELETE FROM notices WHERE id = :id")->execute([':id' => $id]);
    }

    public function getCompletions(int $noticeId): array {
        $stmt = $this->db->prepare("
            SELECT u.name, nc.marked_done_at
            FROM notice_completions nc
            JOIN users u ON u.id = nc.user_id
            WHERE nc.notice_id = :nid
            ORDER BY nc.marked_done_at DESC
        ");
        $stmt->execute([':nid' => $noticeId]);
        return $stmt->fetchAll();
    }

    public function getAllCompletions(): array {
        $rows = $this->db->query("
            SELECT nc.notice_id, u.name, nc.marked_done_at
            FROM notice_completions nc
            JOIN users u ON u.id = nc.user_id
            ORDER BY nc.marked_done_at DESC
        ")->fetchAll();
        $grouped = [];
        foreach ($rows as $r) {
            $grouped[$r['notice_id']][] = $r;
        }
        return $grouped;
    }

    public function getPendingTaskCount(int $userId): int {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) FROM notices n
            WHERE n.type = 'task'
            AND NOT EXISTS (
                SELECT 1 FROM notice_completions nc
                WHERE nc.notice_id = n.id AND nc.user_id = :uid
            )
        ");
        $stmt->execute([':uid' => $userId]);
        return (int)$stmt->fetchColumn();
    }

    public function getActivityFeed(int $page = 1, int $per = 60): array {
        $offset = ($page - 1) * $per;
        $stmt = $this->db->prepare("
            SELECT
                'system' AS source,
                a.id,
                a.user_id,
                u.name AS user_name,
                u.role AS user_role,
                a.action AS event_type,
                CONCAT_WS(' ', a.target_type, a.target_id) AS detail,
                NULL AS lead_id,
                NULL AS lead_name,
                a.ip_address,
                a.created_at
            FROM audit_log a
            LEFT JOIN users u ON u.id = a.user_id

            UNION ALL

            SELECT
                'lead' AS source,
                la.id,
                la.user_id,
                u.name AS user_name,
                u.role AS user_role,
                la.type AS event_type,
                la.note AS detail,
                la.lead_id,
                COALESCE(l.name, l.phone, 'Unknown') AS lead_name,
                NULL AS ip_address,
                la.created_at
            FROM lead_activities la
            LEFT JOIN users u ON u.id = la.user_id
            LEFT JOIN leads l ON l.id = la.lead_id

            ORDER BY created_at DESC
            LIMIT :per OFFSET :offset
        ");
        $stmt->bindValue(':per',    $per,    PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getActivityFeedCount(): int {
        return (int)$this->db->query("
            SELECT (SELECT COUNT(*) FROM audit_log) + (SELECT COUNT(*) FROM lead_activities)
        ")->fetchColumn();
    }
}
