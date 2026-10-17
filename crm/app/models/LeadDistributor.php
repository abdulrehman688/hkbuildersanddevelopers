<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/Lead.php';

// Hands unassigned leads to agents, automatically (equal share) or by hand.
// A lead is only taken while it is still unassigned, so an agent claiming the
// same lead from the pool at the same moment never gets overwritten.
class LeadDistributor {
    private PDO $db;
    private Lead $lead;

    public function __construct() {
        $this->db   = Database::connect();
        $this->lead = new Lead();
    }

    // Agents who can receive leads, with how many open leads each has now.
    // $teamId null = every active agent and sales manager (admin view).
    public function getAgents(?int $teamId = null): array {
        try {
            $where  = $teamId === null
                ? "u.role IN ('agent','sales_manager')"
                : "u.role = 'agent' AND u.team_id = ?";
            $stmt = $this->db->prepare("
                SELECT u.id, u.name, u.role, u.designation, t.name AS team_name,
                       COUNT(l.id) AS open_leads
                FROM users u
                LEFT JOIN teams t ON t.id = u.team_id
                LEFT JOIN leads l ON l.assigned_to = u.id AND l.deleted_at IS NULL
                    AND l.status_id IN (SELECT id FROM lead_statuses WHERE is_closed = 0)
                WHERE {$where} AND u.status = 'active'
                GROUP BY u.id, u.name, u.role, u.designation, t.name
                ORDER BY u.role = 'sales_manager', u.name
            ");
            $stmt->execute($teamId === null ? [] : [$teamId]);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('LeadDistributor::getAgents ' . $e->getMessage());
            return [];
        }
    }

    // Hot first, then oldest first, so every agent gets a fair mix
    public function getUnassigned(int $limit = 500): array {
        try {
            $stmt = $this->db->prepare("
                SELECT l.id, COALESCE(l.name,'Unknown') AS name, l.phone, l.project, l.priority,
                       l.created_at, src.name AS source_name
                FROM leads l
                LEFT JOIN lead_sources src ON src.id = l.source_id
                WHERE l.assigned_to IS NULL AND l.deleted_at IS NULL
                ORDER BY FIELD(l.priority,'hot','warm','cold'), l.created_at ASC
                LIMIT {$limit}
            ");
            $stmt->execute();
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('LeadDistributor::getUnassigned ' . $e->getMessage());
            return [];
        }
    }

    // Equal share. Leftovers go to the agents with the fewest open leads.
    // $agents: rows from getAgents() limited to the chosen ones.
    // Returns [agentId => number of leads given].
    public function autoDistribute(array $agents, int $maxLeads, int $byUserId, string $byName): array {
        if (!$agents) return [];
        usort($agents, fn($a, $b) => [(int)$a['open_leads'], $a['name']] <=> [(int)$b['open_leads'], $b['name']]);

        $leads  = $this->getUnassigned(max(1, $maxLeads));
        $counts = array_fill_keys(array_map(fn($a) => (int)$a['id'], $agents), 0);
        $names  = array_column($agents, 'name', 'id');
        $k      = count($agents);
        $i      = 0;

        $this->db->beginTransaction();
        try {
            foreach ($leads as $lead) {
                $agentId = (int)$agents[$i % $k]['id'];
                if ($this->assignIfUnassigned((int)$lead['id'], $agentId, $byUserId, $byName, $names[$agentId], 'auto distribute')) {
                    $counts[$agentId]++;
                    $i++;
                }
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $counts;
    }

    // Manual: chosen leads to one agent. $onlyUnassigned false lets admin reassign.
    // Returns how many leads were assigned.
    public function assignMany(array $leadIds, int $agentId, string $agentName, int $byUserId, string $byName, bool $onlyUnassigned): int {
        $done = 0;
        $this->db->beginTransaction();
        try {
            foreach (array_unique(array_map('intval', $leadIds)) as $leadId) {
                if ($leadId <= 0) continue;
                $ok = $onlyUnassigned
                    ? $this->assignIfUnassigned($leadId, $agentId, $byUserId, $byName, $agentName, 'manual')
                    : $this->reassign($leadId, $agentId, $byUserId, $byName, $agentName);
                if ($ok) $done++;
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
        return $done;
    }

    private function assignIfUnassigned(int $leadId, int $agentId, int $byUserId, string $byName, string $agentName, string $how): bool {
        $stmt = $this->db->prepare("
            UPDATE leads SET assigned_to = ?, claimed_at = NOW()
            WHERE id = ? AND assigned_to IS NULL AND deleted_at IS NULL
        ");
        $stmt->execute([$agentId, $leadId]);
        if ($stmt->rowCount() !== 1) return false;
        $this->lead->logActivity($leadId, $byUserId, 'reassign',
            "Assigned to {$agentName} by {$byName} ({$how}).", ['to_agent' => $agentId]);
        return true;
    }

    private function reassign(int $leadId, int $agentId, int $byUserId, string $byName, string $agentName): bool {
        $stmt = $this->db->prepare("
            UPDATE leads SET assigned_to = ?, claimed_at = NOW()
            WHERE id = ? AND deleted_at IS NULL AND (assigned_to IS NULL OR assigned_to <> ?)
        ");
        $stmt->execute([$agentId, $leadId, $agentId]);
        if ($stmt->rowCount() !== 1) return false;
        $this->lead->logActivity($leadId, $byUserId, 'reassign',
            "Assigned to {$agentName} by {$byName} (bulk).", ['to_agent' => $agentId]);
        return true;
    }
}
