<?php
require_once __DIR__ . '/../../config/database.php';

class ContactLog {
    private PDO $db;

    public const OUTCOMES = [
        'answered'     => 'Answered',
        'no_answer'    => 'No Answer',
        'busy'         => 'Busy',
        'switched_off' => 'Switched Off',
        'wrong_number' => 'Wrong Number',
    ];

    public const OUTCOME_COLORS = [
        'answered'     => '#16a34a',
        'no_answer'    => '#dc2626',
        'busy'         => '#f59e0b',
        'switched_off' => '#6b7280',
        'wrong_number' => '#8b5cf6',
    ];

    public function __construct() {
        $this->db = Database::connect();
    }

    // 3725 -> "1h 2m 5s", 125 -> "2m 5s", 45 -> "45s"
    public static function fmtDuration(?int $sec): string {
        if ($sec === null) return '-';
        $h = intdiv($sec, 3600);
        $m = intdiv($sec % 3600, 60);
        $s = $sec % 60;
        if ($h > 0) return "{$h}h {$m}m {$s}s";
        if ($m > 0) return "{$m}m {$s}s";
        return "{$s}s";
    }

    // ---- Writes (throw on error) --------------------------------

    public function start(?int $leadId, ?int $clientId, int $userId, ?string $phone): int {
        $this->db->prepare("
            INSERT INTO contact_logs (lead_id, client_id, user_id, phone, started_at)
            VALUES (?, ?, ?, ?, NOW())
        ")->execute([$leadId, $clientId, $userId, $phone]);
        return (int)$this->db->lastInsertId();
    }

    public function find(int $id): array|false {
        $stmt = $this->db->prepare("SELECT * FROM contact_logs WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Only the agent who made the call can fill in its outcome
    public function finish(int $id, int $userId, string $outcome, ?int $durationSec, string $note): void {
        $this->db->prepare("
            UPDATE contact_logs SET outcome = ?, duration_sec = ?, note = ?
            WHERE id = ? AND user_id = ?
        ")->execute([$outcome, $durationSec, $note !== '' ? $note : null, $id, $userId]);
    }

    // ---- Reads (log and return empty on error) ------------------

    // Calls for one lead or client. A lead and the client it became share history.
    public function getHistory(int $leadId, int $clientId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT cl.*, u.name AS agent_name
                FROM contact_logs cl
                JOIN users u ON u.id = cl.user_id
                WHERE (cl.lead_id = ? AND ? > 0) OR (cl.client_id = ? AND ? > 0)
                ORDER BY cl.started_at DESC
                LIMIT 200
            ");
            $stmt->execute([$leadId, $leadId, $clientId, $clientId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('ContactLog::getHistory ' . $e->getMessage());
            return [];
        }
    }

    public static function summarize(array $rows): array {
        $sum = ['calls' => count($rows), 'answered' => 0, 'talk_sec' => 0, 'last' => null];
        foreach ($rows as $r) {
            if ($r['outcome'] === 'answered') $sum['answered']++;
            $sum['talk_sec'] += (int)($r['duration_sec'] ?? 0);
            if ($sum['last'] === null || $r['started_at'] > $sum['last']) $sum['last'] = $r['started_at'];
        }
        return $sum;
    }

    private function reportWhere(array $f): array {
        $where  = ['1=1'];
        $params = [];
        if (!empty($f['agent_id']))  { $where[] = 'cl.user_id = ?';             $params[] = (int)$f['agent_id']; }
        if (!empty($f['date_from'])) { $where[] = 'DATE(cl.started_at) >= ?';   $params[] = $f['date_from']; }
        if (!empty($f['date_to']))   { $where[] = 'DATE(cl.started_at) <= ?';   $params[] = $f['date_to']; }
        if (!empty($f['outcome'])) {
            if ($f['outcome'] === 'pending') {
                $where[] = 'cl.outcome IS NULL';
            } elseif (isset(self::OUTCOMES[$f['outcome']])) {
                $where[] = 'cl.outcome = ?';
                $params[] = $f['outcome'];
            }
        }
        return [implode(' AND ', $where), $params];
    }

    public function getTotals(array $f): array {
        try {
            [$where, $params] = $this->reportWhere($f);
            $stmt = $this->db->prepare("
                SELECT COUNT(*)                              AS calls,
                       COALESCE(SUM(cl.outcome = 'answered'), 0) AS answered,
                       COALESCE(SUM(cl.duration_sec), 0)     AS talk_sec,
                       COUNT(DISTINCT cl.user_id)            AS agents
                FROM contact_logs cl
                WHERE {$where}
            ");
            $stmt->execute($params);
            return $stmt->fetch() ?: ['calls' => 0, 'answered' => 0, 'talk_sec' => 0, 'agents' => 0];
        } catch (\Throwable $e) {
            error_log('ContactLog::getTotals ' . $e->getMessage());
            return ['calls' => 0, 'answered' => 0, 'talk_sec' => 0, 'agents' => 0];
        }
    }

    public function getAgentSummary(array $f): array {
        try {
            [$where, $params] = $this->reportWhere($f);
            $stmt = $this->db->prepare("
                SELECT u.id, u.name,
                       COUNT(*)                              AS calls,
                       COALESCE(SUM(cl.outcome = 'answered'), 0) AS answered,
                       COALESCE(SUM(cl.duration_sec), 0)     AS talk_sec,
                       COUNT(DISTINCT COALESCE(CONCAT('L', cl.lead_id), CONCAT('C', cl.client_id))) AS contacts,
                       MAX(cl.started_at)                    AS last_call
                FROM contact_logs cl
                JOIN users u ON u.id = cl.user_id
                WHERE {$where}
                GROUP BY u.id, u.name
                ORDER BY talk_sec DESC, calls DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('ContactLog::getAgentSummary ' . $e->getMessage());
            return [];
        }
    }

    public function getLog(array $f, int $limit = 50, int $offset = 0): array {
        try {
            [$where, $params] = $this->reportWhere($f);
            $stmt = $this->db->prepare("
                SELECT cl.*, u.name AS agent_name,
                       COALESCE(c.name, l.name) AS contact_name
                FROM contact_logs cl
                JOIN users u        ON u.id = cl.user_id
                LEFT JOIN leads l   ON l.id = cl.lead_id
                LEFT JOIN clients c ON c.id = cl.client_id
                WHERE {$where}
                ORDER BY cl.started_at DESC
                LIMIT {$limit} OFFSET {$offset}
            ");
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('ContactLog::getLog ' . $e->getMessage());
            return [];
        }
    }
}
