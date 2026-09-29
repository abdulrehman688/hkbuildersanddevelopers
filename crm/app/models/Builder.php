<?php
require_once __DIR__ . '/../../config/database.php';

class Builder {

    private PDO $db;

    // Payments linked to each unit (alias u), which decide whether the unit is paid, partial or unpaid.
    private const UNIT_PAID_JOIN = "
        LEFT JOIN (
            SELECT unit_id, SUM(amount) AS paid, COUNT(*) AS pay_count
            FROM builder_payments WHERE unit_id IS NOT NULL GROUP BY unit_id
        ) up ON up.unit_id = u.id";

    private const UNIT_PAID_COLUMNS = "
        COALESCE(up.paid, 0)      AS paid_amount,
        COALESCE(up.pay_count, 0) AS pay_count,
        GREATEST(u.commission_amount - COALESCE(up.paid, 0), 0) AS balance,
        CASE
            WHEN u.commission_amount > 0 AND COALESCE(up.paid, 0) >= u.commission_amount THEN 'paid'
            WHEN COALESCE(up.paid, 0) > 0 THEN 'partial'
            ELSE 'unpaid'
        END AS pay_status";

    public function __construct() {
        $this->db = Database::connect();
    }

    // ---- Stats ----

    public function getStats(): array {
        try {
            $s = $this->db->query("
                SELECT
                    (SELECT COUNT(*) FROM builders WHERE status='active')             AS builders,
                    (SELECT COUNT(*) FROM builder_projects)                           AS projects,
                    (SELECT COUNT(*) FROM builder_units)                              AS units,
                    (SELECT COALESCE(SUM(commission_amount),0) FROM builder_units)    AS total_commission,
                    (SELECT COALESCE(SUM(amount),0) FROM builder_payments)            AS total_paid
            ")->fetch(PDO::FETCH_ASSOC);
            // Summed per builder so one builder's overpayment never hides another builder's balance.
            $s['unpaid'] = (float)$this->db->query("
                SELECT COALESCE(SUM(GREATEST(t.commission - t.paid, 0)), 0) FROM (
                    SELECT
                        (SELECT COALESCE(SUM(commission_amount),0) FROM builder_units   WHERE builder_id = b.id) AS commission,
                        (SELECT COALESCE(SUM(amount),0)            FROM builder_payments WHERE builder_id = b.id) AS paid
                    FROM builders b
                ) t
            ")->fetchColumn();
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
                    (SELECT COUNT(*) FROM builder_projects WHERE builder_id = b.id)                    AS project_count,
                    (SELECT COUNT(*) FROM builder_units    WHERE builder_id = b.id)                    AS unit_count,
                    (SELECT COALESCE(SUM(commission_amount),0) FROM builder_units WHERE builder_id = b.id) AS total_commission,
                    (SELECT COALESCE(SUM(amount),0) FROM builder_payments WHERE builder_id = b.id)       AS total_paid
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
                    (SELECT COUNT(*) FROM builder_units WHERE project_id = bp.id)                          AS unit_count,
                    (SELECT COALESCE(SUM(commission_amount),0) FROM builder_units WHERE project_id = bp.id) AS total_commission,
                    (SELECT COALESCE(SUM(amount),0) FROM builder_payments WHERE project_id = bp.id)         AS paid_amount
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
                SELECT pay.*, b.name AS builder_name, bp.name AS project_name, u.name AS created_by_name,
                    bu.unit_number, bu.block_number
                FROM builder_payments pay
                LEFT JOIN builders b          ON b.id  = pay.builder_id
                LEFT JOIN builder_projects bp ON bp.id = pay.project_id
                LEFT JOIN builder_units bu    ON bu.id = pay.unit_id
                LEFT JOIN users u             ON u.id  = pay.created_by
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
                (builder_id, project_id, unit_id, amount, payment_type, payment_date, payment_month, payment_year, reference, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['builder_id'], $d['project_id'] ?: null, $d['unit_id'] ?: null, $d['amount'], $d['payment_type'],
            $d['payment_date'], $d['payment_month'], $d['payment_year'],
            $d['reference'] ?: null, $d['notes'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updatePayment(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builder_payments SET
                builder_id=?, project_id=?, unit_id=?, amount=?, payment_type=?, payment_date=?,
                payment_month=?, payment_year=?, reference=?, notes=?
            WHERE id=?
        ")->execute([
            $d['builder_id'], $d['project_id'] ?: null, $d['unit_id'] ?: null, $d['amount'], $d['payment_type'],
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
            $units    = $this->getUnits($builderId);
            $payments = $this->getPayments($builderId);
            return compact('builder', 'projects', 'units', 'payments');
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

    // ---- Units ----

    public function getUnits(int $builderId = 0, int $projectId = 0, string $maturity = '', string $payStatus = ''): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'u.builder_id = ?';      $params[] = $builderId; }
            if ($projectId) { $where[] = 'u.project_id = ?';      $params[] = $projectId; }
            if ($maturity)  { $where[] = 'u.maturity_status = ?'; $params[] = $maturity; }
            $having = '';
            if ($payStatus) { $having = 'HAVING pay_status = ?';  $params[] = $payStatus; }
            $stmt = $this->db->prepare("
                SELECT u.*, b.name AS builder_name, bp.name AS project_name, " . self::UNIT_PAID_COLUMNS . "
                FROM builder_units u
                LEFT JOIN builders         b  ON b.id  = u.builder_id
                LEFT JOIN builder_projects bp ON bp.id = u.project_id
                " . self::UNIT_PAID_JOIN . "
                WHERE " . implode(' AND ', $where) . "
                $having
                ORDER BY b.name, bp.name, u.block_number, u.unit_number
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getUnits - ' . $e->getMessage());
            return [];
        }
    }

    // Paid comes from every payment in scope, including ones not yet linked to a unit.
    public function getUnitStats(int $builderId = 0, int $projectId = 0): array {
        try {
            $where  = ['1=1'];
            $params = [];
            if ($builderId) { $where[] = 'builder_id = ?'; $params[] = $builderId; }
            if ($projectId) { $where[] = 'project_id = ?'; $params[] = $projectId; }
            $w = implode(' AND ', $where);

            $stmt = $this->db->prepare("
                SELECT
                    COUNT(*)                                       AS total_units,
                    COALESCE(SUM(maturity_status = 'mature'), 0)   AS mature_units,
                    COALESCE(SUM(maturity_status = 'immature'), 0) AS immature_units,
                    COALESCE(SUM(commission_amount), 0)            AS total_commission
                FROM builder_units WHERE $w
            ");
            $stmt->execute($params);
            $s = $stmt->fetch(PDO::FETCH_ASSOC);

            $stmt = $this->db->prepare("
                SELECT
                    COALESCE(SUM(amount), 0)                                   AS total_paid,
                    COALESCE(SUM(CASE WHEN unit_id IS NULL THEN amount END), 0) AS unassigned_paid
                FROM builder_payments WHERE $w
            ");
            $stmt->execute($params);
            $s += $stmt->fetch(PDO::FETCH_ASSOC);

            $s['unpaid'] = max(0, (float)$s['total_commission'] - (float)$s['total_paid']);
            return $s;
        } catch (\Throwable $e) {
            error_log('Builder::getUnitStats - ' . $e->getMessage());
            return [];
        }
    }

    public function findUnitById(int $id): array|false {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, " . self::UNIT_PAID_COLUMNS . "
                FROM builder_units u " . self::UNIT_PAID_JOIN . "
                WHERE u.id = ?
            ");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { return false; }
    }

    // Units for the payment form's unit dropdown, with what is still owed on each.
    public function getUnitOptions(): array {
        try {
            return $this->db->query("
                SELECT u.id, u.builder_id, u.project_id, u.unit_number, u.block_number, u.commission_amount,
                    " . self::UNIT_PAID_COLUMNS . "
                FROM builder_units u " . self::UNIT_PAID_JOIN . "
                ORDER BY u.block_number, u.unit_number
            ")->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            error_log('Builder::getUnitOptions - ' . $e->getMessage());
            return [];
        }
    }

    // Values already used, offered as suggestions so categories and sizes stay consistent.
    public function getUnitSuggestions(): array {
        try {
            return [
                'categories' => $this->db->query("SELECT DISTINCT category FROM builder_units WHERE category <> '' ORDER BY category")->fetchAll(PDO::FETCH_COLUMN),
                'plot_sizes' => $this->db->query("SELECT DISTINCT plot_size FROM builder_units WHERE plot_size <> '' ORDER BY plot_size")->fetchAll(PDO::FETCH_COLUMN),
            ];
        } catch (\Throwable $e) {
            return ['categories' => [], 'plot_sizes' => []];
        }
    }

    public function addUnit(array $d): int {
        $this->db->prepare("
            INSERT INTO builder_units
                (builder_id, project_id, unit_number, block_number, category, plot_size,
                 total_cost, down_payment, commission_amount, maturity_status, notes, created_by)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)
        ")->execute([
            $d['builder_id'], $d['project_id'], $d['unit_number'], $d['block_number'] ?: null,
            $d['category'] ?: null, $d['plot_size'] ?: null,
            $d['total_cost'], $d['down_payment'], $d['commission_amount'],
            $d['maturity_status'], $d['notes'] ?: null, $d['created_by'],
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateUnit(int $id, array $d): void {
        $this->db->prepare("
            UPDATE builder_units SET
                builder_id=?, project_id=?, unit_number=?, block_number=?, category=?, plot_size=?,
                total_cost=?, down_payment=?, commission_amount=?, maturity_status=?, notes=?
            WHERE id=?
        ")->execute([
            $d['builder_id'], $d['project_id'], $d['unit_number'], $d['block_number'] ?: null,
            $d['category'] ?: null, $d['plot_size'] ?: null,
            $d['total_cost'], $d['down_payment'], $d['commission_amount'],
            $d['maturity_status'], $d['notes'] ?: null, $id,
        ]);
    }

    public function toggleUnitMaturity(int $id): void {
        $this->db->prepare("
            UPDATE builder_units SET maturity_status = IF(maturity_status = 'mature', 'immature', 'mature') WHERE id=?
        ")->execute([$id]);
    }

    public function deleteUnitPayments(int $unitId): int {
        $stmt = $this->db->prepare("DELETE FROM builder_payments WHERE unit_id = ?");
        $stmt->execute([$unitId]);
        return $stmt->rowCount();
    }

    public function deleteUnit(int $id): void {
        $this->db->prepare("DELETE FROM builder_units WHERE id=?")->execute([$id]);
    }
}
