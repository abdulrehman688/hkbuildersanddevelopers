<?php
require_once __DIR__ . '/../../config/database.php';

class Builder {

    private PDO $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // ---- Stats ----

    public function getStats(): array {
        try {
            $s = [];
            $s['builders']      = (int)$this->db->query("SELECT COUNT(*) FROM builders WHERE status='active'")->fetchColumn();
            $s['projects']      = (int)$this->db->query("SELECT COUNT(*) FROM builder_projects")->fetchColumn();
            $s['total_paid']    = (float)$this->db->query("SELECT COALESCE(SUM(amount),0) FROM builder_payments")->fetchColumn();
            return $s;
        } catch (\Throwable $e) {
            error_log('Builder::getStats - ' . $e->getMessage());
            return [];
        }
    }

    // ---- Builders ----

    public function getBuilders(string $status = ''): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($status) { $where[] = 'b.status = ?'; $params[] = $status; }
            $stmt = $this->db->prepare("
                SELECT b.*,
                    (SELECT COUNT(*) FROM builder_projects WHERE builder_id = b.id) AS project_count,
                    (SELECT COALESCE(SUM(amount),0) FROM builder_payments WHERE builder_id = b.id) AS total_paid
                FROM builders b
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.name ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getBuilders - ' . $e->getMessage());
            return [];
        }
    }

    public function findBuilderById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM builders WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }
    }

    public function addBuilder(array $d): int {
        $this->db->prepare("
            INSERT INTO builders (name, contact_person, phone, email, address, notes, status, created_by)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([$d['name'], $d['contact_person'], $d['phone'], $d['email'], $d['address'], $d['notes'], $d['status'], $d['created_by']]);
        return (int)$this->db->lastInsertId();
    }

    public function updateBuilder(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builders SET name=?, contact_person=?, phone=?, email=?, address=?, notes=?, status=? WHERE id=?
        ")->execute([$d['name'], $d['contact_person'], $d['phone'], $d['email'], $d['address'], $d['notes'], $d['status'], $id]);
    }

    public function deleteBuilder(int $id): void {
        $this->db->prepare("DELETE FROM builders WHERE id=?")->execute([$id]);
    }

    // ---- Projects ----

    public function getProjects(int $builderId = 0, string $status = ''): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'bp.builder_id = ?'; $params[] = $builderId; }
            if ($status)    { $where[] = 'bp.status = ?';     $params[] = $status; }
            $stmt = $this->db->prepare("
                SELECT bp.*, b.name AS builder_name,
                    (SELECT COALESCE(SUM(amount),0) FROM builder_payments WHERE project_id = bp.id) AS paid_amount
                FROM builder_projects bp
                LEFT JOIN builders b ON b.id = bp.builder_id
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.name, bp.name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getProjects - ' . $e->getMessage());
            return [];
        }
    }

    public function findProjectById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM builder_projects WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }
    }

    public function addProject(array $d): int {
        $this->db->prepare("
            INSERT INTO builder_projects (builder_id, name, location, total_plots, total_value, status, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?)
        ")->execute([$d['builder_id'], $d['name'], $d['location'], $d['total_plots'], $d['total_value'], $d['status'], $d['notes'], $d['created_by']]);
        return (int)$this->db->lastInsertId();
    }

    public function updateProject(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builder_projects SET builder_id=?, name=?, location=?, total_plots=?, total_value=?, status=?, notes=? WHERE id=?
        ")->execute([$d['builder_id'], $d['name'], $d['location'], $d['total_plots'], $d['total_value'], $d['status'], $d['notes'], $id]);
    }

    public function deleteProject(int $id): void {
        $this->db->prepare("DELETE FROM builder_projects WHERE id=?")->execute([$id]);
    }

    // ---- Payments ----

    public function getPayments(int $builderId = 0, int $projectId = 0, int $month = 0, int $year = 0): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'pay.builder_id = ?';   $params[] = $builderId; }
            if ($projectId) { $where[] = 'pay.project_id = ?';   $params[] = $projectId; }
            if ($month)     { $where[] = 'pay.payment_month = ?'; $params[] = $month; }
            if ($year)      { $where[] = 'pay.payment_year = ?';  $params[] = $year; }
            $stmt = $this->db->prepare("
                SELECT pay.*, b.name AS builder_name, bp.name AS project_name, u.name AS created_by_name
                FROM builder_payments pay
                LEFT JOIN builders b       ON b.id  = pay.builder_id
                LEFT JOIN builder_projects bp ON bp.id = pay.project_id
                LEFT JOIN users u          ON u.id  = pay.created_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY pay.payment_date DESC, pay.id DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getPayments - ' . $e->getMessage());
            return [];
        }
    }

    public function findPaymentById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("SELECT * FROM builder_payments WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }
    }

    public function addPayment(array $d): int {
        $this->db->prepare("
            INSERT INTO builder_payments
                (builder_id, project_id, amount, payment_type, payment_date, payment_month, payment_year, reference, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['builder_id'], $d['project_id'] ?: null, $d['amount'], $d['payment_type'],
            $d['payment_date'], $d['payment_month'], $d['payment_year'],
            $d['reference'] ?: null, $d['notes'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePayment(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builder_payments SET
                builder_id=?, project_id=?, amount=?, payment_type=?, payment_date=?,
                payment_month=?, payment_year=?, reference=?, notes=?
            WHERE id=?
        ")->execute([
            $d['builder_id'], $d['project_id'] ?: null, $d['amount'], $d['payment_type'],
            $d['payment_date'], $d['payment_month'], $d['payment_year'],
            $d['reference'] ?: null, $d['notes'] ?: null, $id,
        ]);
    }

    public function deletePayment(int $id): void {
        $this->db->prepare("DELETE FROM builder_payments WHERE id=?")->execute([$id]);
    }

    // ---- Helpers ----

    public function getBuilderStatement(int $builderId): array {
        try {
            $builder  = $this->findBuilderById($builderId);
            $projects = $this->getProjects($builderId);
            $payments = $this->getPayments($builderId);
            return compact('builder', 'projects', 'payments');
        } catch (\Throwable $e) {
            error_log('Builder::getBuilderStatement - ' . $e->getMessage());
            return [];
        }
    }

    public function getAllBuilders(): array {
        try {
            return $this->db->query("SELECT id, name FROM builders WHERE status='active' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    public function getProjectsForBuilder(int $builderId): array {
        try {
            $stmt = $this->db->prepare("SELECT id, name FROM builder_projects WHERE builder_id = ? ORDER BY name");
            $stmt->execute([$builderId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return []; }
    }

    // ---- Units ----

    public function getUnits(int $builderId = 0, int $projectId = 0, string $status = ''): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'u.builder_id = ?';          $params[] = $builderId; }
            if ($projectId) { $where[] = 'u.project_id = ?';          $params[] = $projectId; }
            if ($status)    { $where[] = 'u.commission_status = ?';    $params[] = $status; }
            $stmt = $this->db->prepare("
                SELECT u.*,
                    b.name  AS builder_name,
                    bp.name AS project_name,
                    uc.name AS created_by_name
                FROM builder_units u
                LEFT JOIN builders         b  ON b.id  = u.builder_id
                LEFT JOIN builder_projects bp ON bp.id = u.project_id
                LEFT JOIN users            uc ON uc.id = u.created_by
                WHERE " . implode(' AND ', $where) . "
                ORDER BY b.name, bp.name, u.unit_number ASC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getUnits - ' . $e->getMessage());
            return [];
        }
    }

    public function getUnitStats(int $builderId = 0, int $projectId = 0): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'builder_id = ?'; $params[] = $builderId; }
            if ($projectId) { $where[] = 'project_id = ?'; $params[] = $projectId; }
            $w = implode(' AND ', $where);
            $row = $this->db->prepare("
                SELECT
                    COUNT(*) AS total_units,
                    SUM(commission_amount) AS total_commission,
                    SUM(CASE WHEN commission_status='paid'   THEN commission_amount ELSE 0 END) AS paid_commission,
                    SUM(CASE WHEN commission_status='unpaid' THEN commission_amount ELSE 0 END) AS unpaid_commission
                FROM builder_units WHERE $w
            ");
            $row->execute($params);
            return $row->fetch(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) { return []; }
    }

    public function addUnit(array $d): int {
        $this->db->prepare("
            INSERT INTO builder_units
                (builder_id, project_id, unit_number, block_number, category, plot_size,
                 total_cost, down_payment, commission_amount, commission_status, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['builder_id'], $d['project_id'], $d['unit_number'], $d['block_number'] ?: null,
            $d['category'] ?: null, $d['plot_size'] ?: null,
            $d['total_cost'], $d['down_payment'], $d['commission_amount'],
            $d['commission_status'], $d['notes'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateUnit(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builder_units SET
                builder_id=?, project_id=?, unit_number=?, block_number=?, category=?, plot_size=?,
                total_cost=?, down_payment=?, commission_amount=?, commission_status=?, notes=?
            WHERE id=?
        ")->execute([
            $d['builder_id'], $d['project_id'], $d['unit_number'], $d['block_number'] ?: null,
            $d['category'] ?: null, $d['plot_size'] ?: null,
            $d['total_cost'], $d['down_payment'], $d['commission_amount'],
            $d['commission_status'], $d['notes'] ?: null, $id,
        ]);
    }

    public function deleteUnit(int $id): void {
        $this->db->prepare("DELETE FROM builder_units WHERE id=?")->execute([$id]);
    }

    public function toggleUnitCommission(int $id): void {
        $this->db->prepare("
            UPDATE builder_units
            SET commission_status = CASE WHEN commission_status='paid' THEN 'unpaid' ELSE 'paid' END
            WHERE id=?
        ")->execute([$id]);
    }
}
