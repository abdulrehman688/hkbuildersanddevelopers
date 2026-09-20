<?php
require_once __DIR__ . '/../../config/database.php';

class Accounts {

    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // ---- Commission stats ----

    public function getCommissionStats(): array {
        try {
            $stats = $this->db->query("
                SELECT
                    COUNT(*) AS total_entries,
                    COALESCE(SUM(total_commission), 0)               AS total_commission,
                    COALESCE(SUM(paid_amount), 0)                    AS total_paid,
                    COALESCE(SUM(total_commission - paid_amount), 0)  AS total_remaining,
                    SUM(CASE WHEN maturity_status='mature'   THEN 1 ELSE 0 END) AS mature_count,
                    SUM(CASE WHEN maturity_status='immature' THEN 1 ELSE 0 END) AS immature_count,
                    SUM(CASE WHEN payment_status='paid'    THEN 1 ELSE 0 END) AS paid_count,
                    SUM(CASE WHEN payment_status='pending' THEN 1 ELSE 0 END) AS pending_count
                FROM commission_payments
            ")->fetch(PDO::FETCH_ASSOC) ?: [];

            $recent = $this->db->query("
                SELECT cp.paid_amount, cp.client_name, cp.created_at, u.name AS agent_name
                FROM commission_payments cp
                LEFT JOIN users u ON u.id = cp.agent_id
                WHERE cp.payment_status = 'paid'
                ORDER BY cp.created_at DESC LIMIT 1
            ")->fetch(PDO::FETCH_ASSOC);
            $stats['recently_paid_amount'] = $recent['paid_amount']  ?? 0;
            $stats['recently_paid_agent']  = $recent['agent_name']   ?? '';
            $stats['recently_paid_date']   = $recent['created_at']   ?? null;
            return $stats;
        } catch (\Throwable $e) {
            error_log('Accounts::getCommissionStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getCommissions(array $f = []): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if (!empty($f['agent_id']))        { $where[] = 'cp.agent_id = ?';        $params[] = (int)$f['agent_id']; }
            if (!empty($f['maturity_status'])) { $where[] = 'cp.maturity_status = ?'; $params[] = $f['maturity_status']; }
            if (!empty($f['payment_status']))  { $where[] = 'cp.payment_status = ?';  $params[] = $f['payment_status']; }
            if (!empty($f['sale_month']))      { $where[] = 'cp.sale_month = ?';      $params[] = (int)$f['sale_month']; }
            if (!empty($f['sale_year']))       { $where[] = 'cp.sale_year = ?';       $params[] = (int)$f['sale_year']; }

            $stmt = $this->db->prepare("
                SELECT cp.*,
                    u.name  AS agent_name,
                    bp.name AS project_name,
                    pr.reason AS pending_reason_text
                FROM commission_payments cp
                LEFT JOIN users u  ON u.id  = cp.agent_id
                LEFT JOIN builder_projects bp ON bp.id = cp.project_id
                LEFT JOIN commission_pending_reason pr ON pr.id = cp.pending_reason_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY cp.created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Accounts::getCommissions - ' . $e->getMessage());
            return [];
        }
    }

    public function getAgentProjectSummary(int $agentId, int $projectId): array {
        try {
            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*) AS records,
                    COALESCE(SUM(total_commission), 0) AS total_commission,
                    COALESCE(SUM(paid_amount), 0)      AS paid_amount,
                    COALESCE(SUM(total_commission - paid_amount), 0) AS remaining
                FROM commission_payments
                WHERE agent_id = ? AND project_id = ?
            ");
            $stmt->execute([$agentId, $projectId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public function findCommissionById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM commission_payments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function addCommission(array $d): int {
        $this->db->prepare("
            INSERT INTO commission_payments
                (agent_id, lead_id, project_id, client_name, project, plot_number,
                 total_commission, maturity_status, payment_status, paid_amount,
                 payment_source, reference_no, pending_reason_id, pending_notes,
                 sale_month, sale_year, sale_date, due_date, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['agent_id'], $d['lead_id'] ?: null, $d['project_id'] ?: null,
            $d['client_name'] ?: null, $d['project'] ?: null, $d['plot_number'] ?: null,
            $d['total_commission'], $d['maturity_status'], $d['payment_status'],
            $d['paid_amount'], $d['payment_source'] ?: null, $d['reference_no'] ?: null,
            $d['pending_reason_id'] ?: null, $d['pending_notes'] ?: null,
            $d['sale_month'] ?: null, $d['sale_year'] ?: null,
            $d['sale_date'] ?: null, $d['due_date'] ?: null,
            $d['notes'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateCommission(int $id, array $d): void {
        $this->db->prepare("
            UPDATE commission_payments SET
                agent_id=?, lead_id=?, project_id=?, client_name=?, project=?, plot_number=?,
                total_commission=?, maturity_status=?, payment_status=?, paid_amount=?,
                payment_source=?, reference_no=?, pending_reason_id=?, pending_notes=?,
                sale_month=?, sale_year=?, sale_date=?, due_date=?, notes=?
            WHERE id=?
        ")->execute([
            $d['agent_id'], $d['lead_id'] ?: null, $d['project_id'] ?: null,
            $d['client_name'] ?: null, $d['project'] ?: null, $d['plot_number'] ?: null,
            $d['total_commission'], $d['maturity_status'], $d['payment_status'],
            $d['paid_amount'], $d['payment_source'] ?: null, $d['reference_no'] ?: null,
            $d['pending_reason_id'] ?: null, $d['pending_notes'] ?: null,
            $d['sale_month'] ?: null, $d['sale_year'] ?: null,
            $d['sale_date'] ?: null, $d['due_date'] ?: null,
            $d['notes'] ?: null, $id,
        ]);
    }

    public function deleteCommission(int $id): void {
        $this->db->prepare("DELETE FROM commission_payments WHERE id=?")->execute([$id]);
    }

    // ---- Expense stats ----

    public function getExpenseStats(): array {
        try {
            return $this->db->query("
                SELECT
                    COALESCE(SUM(amount), 0)                                           AS total,
                    COALESCE(SUM(CASE WHEN type='marketing' THEN amount ELSE 0 END), 0) AS marketing,
                    COALESCE(SUM(CASE WHEN type='salary'    THEN amount ELSE 0 END), 0) AS salary,
                    COALESCE(SUM(CASE WHEN type='general'   THEN amount ELSE 0 END), 0) AS general
                FROM expenses
            ")->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('Accounts::getExpenseStats - ' . $e->getMessage());
            return [];
        }
    }

    public function getExpenses(string $type = '', int $month = 0, int $year = 0, int $agentId = 0, string $dateFrom = '', string $dateTo = ''): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($type)     { $where[] = 'e.type = ?';            $params[] = $type; }
            if ($month)    { $where[] = 'e.expense_month = ?';   $params[] = $month; }
            if ($year)     { $where[] = 'e.expense_year = ?';    $params[] = $year; }
            if ($agentId)  { $where[] = 'e.agent_id = ?';        $params[] = $agentId; }
            if ($dateFrom) { $where[] = 'e.expense_date >= ?';   $params[] = $dateFrom; }
            if ($dateTo)   { $where[] = 'e.expense_date <= ?';   $params[] = $dateTo; }

            $stmt = $this->db->prepare("
                SELECT e.*, u.name AS agent_name, cb.name AS created_by_name
                FROM expenses e
                LEFT JOIN users u  ON u.id  = e.agent_id
                LEFT JOIN users cb ON cb.id = e.created_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY e.expense_date DESC, e.id DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Accounts::getExpenses - ' . $e->getMessage());
            return [];
        }
    }

    public function findExpenseById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM expenses WHERE id=?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function addExpense(array $d): int {
        $this->db->prepare("
            INSERT INTO expenses
                (type, category, description, amount, agent_id,
                 expense_date, expense_month, expense_year, created_by)
            VALUES (?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['type'], $d['category'], $d['description'] ?: null, $d['amount'],
            $d['agent_id'] ?: null, $d['expense_date'],
            $d['expense_month'] ?: null, $d['expense_year'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateExpense(int $id, array $d): void {
        $this->db->prepare("
            UPDATE expenses SET
                type=?, category=?, description=?, amount=?, agent_id=?,
                expense_date=?, expense_month=?, expense_year=?
            WHERE id=?
        ")->execute([
            $d['type'], $d['category'], $d['description'] ?: null, $d['amount'],
            $d['agent_id'] ?: null, $d['expense_date'],
            $d['expense_month'] ?: null, $d['expense_year'] ?: null, $id,
        ]);
    }

    public function deleteExpense(int $id): void {
        $this->db->prepare("DELETE FROM expenses WHERE id=?")->execute([$id]);
    }

    // ---- Helpers ----

    public function getPendingReasons(): array {
        try {
            return $this->db->query("SELECT * FROM commission_pending_reason ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getAgents(): array {
        try {
            return $this->db->query("SELECT id, name FROM users WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getAllProjects(): array {
        try {
            return $this->db->query("
                SELECT bp.id, bp.name, bp.builder_id, b.name AS builder_name
                FROM builder_projects bp
                LEFT JOIN builders b ON b.id = bp.builder_id
                ORDER BY b.name, bp.name
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }
}
