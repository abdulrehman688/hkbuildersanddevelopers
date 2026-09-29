<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/AuditLog.php';
require_once __DIR__ . '/../models/Builder.php';

class BuildersController {

    private Builder $builder;

    public function __construct() {
        $this->builder = new Builder();
    }

    public function handle(?string $param, ?string $sub): void {
        match($param ?: 'list') {
            'projects'  => $this->projects(),
            'units'     => $this->units(),
            'payments'  => $this->payments(),
            'detail'    => $this->detail((int)($sub ?? 0)),
            'statement' => $this->statement((int)($sub ?? 0)),
            default     => $this->list(),
        };
    }

    // ---- Builders list ----

    private function list(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/builders');
                exit;
            }
            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->builder->addBuilder($this->builderPayload($uid));
                AuditLog::log('builder_added', $uid, 'builder', $id, 'Builder added.');
                $_SESSION['success'] = 'Builder added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['builder_id'] ?? 0);
                if ($id) {
                    $this->builder->updateBuilder($id, $this->builderPayload($uid));
                    AuditLog::log('builder_updated', $uid, 'builder', $id, 'Builder updated.');
                    $_SESSION['success'] = 'Builder updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['builder_id'] ?? 0);
                if ($id) {
                    $this->builder->deleteBuilder($id);
                    AuditLog::log('builder_deleted', $uid, 'builder', $id, 'Builder deleted.');
                    $_SESSION['success'] = 'Builder deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/builders');
            exit;
        }

        $stats    = $this->builder->getStats();
        $builders = $this->builder->getBuilders();
        require APP_ROOT . '/app/views/admin/builders/list.php';
    }

    private function builderPayload(int $uid): array {
        $statuses = ['active', 'inactive'];
        return [
            'name'           => trim($_POST['name']           ?? ''),
            'contact_person' => trim($_POST['contact_person'] ?? ''),
            'phone'          => trim($_POST['phone']          ?? ''),
            'email'          => trim($_POST['email']          ?? ''),
            'address'        => trim($_POST['address']        ?? ''),
            'notes'          => trim($_POST['notes']          ?? ''),
            'status'         => in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'active',
            'created_by'     => $uid,
        ];
    }

    // ---- Projects ----

    private function projects(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/builders/projects');
                exit;
            }
            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->builder->addProject($this->projectPayload($uid));
                AuditLog::log('builder_project_added', $uid, 'builder_project', $id, 'Project added.');
                $_SESSION['success'] = 'Project added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['project_id'] ?? 0);
                if ($id) {
                    $this->builder->updateProject($id, $this->projectPayload($uid));
                    AuditLog::log('builder_project_updated', $uid, 'builder_project', $id, 'Project updated.');
                    $_SESSION['success'] = 'Project updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['project_id'] ?? 0);
                if ($id) {
                    $this->builder->deleteProject($id);
                    AuditLog::log('builder_project_deleted', $uid, 'builder_project', $id, 'Project deleted.');
                    $_SESSION['success'] = 'Project deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/builders/projects');
            exit;
        }

        $fBuilderId = (int)($_GET['builder_id'] ?? 0);
        $fStatus    = $_GET['status'] ?? '';

        $projects  = $this->builder->getProjects($fBuilderId, $fStatus);
        $allBuilders = $this->builder->getAllBuilders();
        require APP_ROOT . '/app/views/admin/builders/projects.php';
    }

    private function projectPayload(int $uid): array {
        $statuses = ['active', 'completed', 'on_hold'];
        return [
            'builder_id'   => (int)($_POST['builder_id']  ?? 0),
            'name'         => trim($_POST['name']         ?? ''),
            'location'     => trim($_POST['location']     ?? ''),
            'total_plots'  => (int)($_POST['total_plots'] ?? 0),
            'total_value'  => (float)($_POST['total_value'] ?? 0),
            'status'       => in_array($_POST['status'] ?? '', $statuses, true) ? $_POST['status'] : 'active',
            'notes'        => trim($_POST['notes']        ?? ''),
            'created_by'   => $uid,
        ];
    }

    // ---- Units ----

    private function units(): void {
        $uid      = (int)$_SESSION['user_id'];
        $filters  = $this->unitFilters();
        $qs       = http_build_query(array_filter($filters));
        $filterQs = $qs ? '?' . $qs : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/builders/units' . $filterQs);
                exit;
            }
            $act = $_POST['form_action'] ?? '';
            $id  = (int)($_POST['unit_id'] ?? 0);

            if ($act === 'add' || $act === 'edit') {
                $unit = $this->unitPayload($uid);
                if (!$unit['project_id'] || $unit['unit_number'] === '') {
                    $_SESSION['error'] = 'Select a project and enter the unit number.';
                } elseif ($act === 'add') {
                    $id = $this->builder->addUnit($unit);
                    AuditLog::log('builder_unit_added', $uid, 'builder_unit', $id, 'Unit added.');
                    $_SESSION['success'] = 'Unit added.';
                } elseif ($id) {
                    $this->builder->updateUnit($id, $unit);
                    AuditLog::log('builder_unit_updated', $uid, 'builder_unit', $id, 'Unit updated.');
                    $_SESSION['success'] = 'Unit updated.';
                }

            } elseif ($act === 'maturity' && $id) {
                $this->builder->toggleUnitMaturity($id);
                AuditLog::log('builder_unit_maturity', $uid, 'builder_unit', $id, 'Unit maturity status changed.');
                $_SESSION['success'] = 'Maturity status updated.';

            } elseif ($act === 'pay' && $id) {
                $payment = $this->paymentPayload($uid);
                if ($payment['unit_id'] !== $id) {
                    $_SESSION['error'] = 'Unit not found.';
                } elseif ($payment['amount'] <= 0) {
                    $_SESSION['error'] = 'Enter a payment amount greater than zero.';
                } else {
                    $payId = $this->builder->addPayment($payment);
                    AuditLog::log('builder_payment_added', $uid, 'builder_payment', $payId, 'Payment recorded for unit ' . $id . '.');
                    $_SESSION['success'] = 'Payment recorded.';
                }

            } elseif ($act === 'unpay' && $id) {
                $removed = $this->builder->deleteUnitPayments($id);
                AuditLog::log('builder_unit_unpaid', $uid, 'builder_unit', $id, "Unit marked unpaid, {$removed} payment(s) removed.");
                $_SESSION['success'] = 'Unit marked unpaid.';

            } elseif ($act === 'delete' && $id) {
                $this->builder->deleteUnit($id);
                AuditLog::log('builder_unit_deleted', $uid, 'builder_unit', $id, 'Unit deleted.');
                $_SESSION['success'] = 'Unit deleted.';
            }

            header('Location: ' . APP_URL . '/admin/builders/units' . $filterQs);
            exit;
        }

        [
            'builder_id' => $fBuilderId,
            'project_id' => $fProjectId,
            'maturity'   => $fMaturity,
            'pay'        => $fPay,
        ] = $filters;

        $units       = $this->builder->getUnits($fBuilderId, $fProjectId, $fMaturity, $fPay);
        $unitStats   = $this->builder->getUnitStats($fBuilderId, $fProjectId);
        $suggestions = $this->builder->getUnitSuggestions();
        $allBuilders = $this->builder->getAllBuilders();
        $allProjects = $this->builder->getProjects();
        require APP_ROOT . '/app/views/admin/builders/units.php';
    }

    private function unitFilters(): array {
        return [
            'builder_id' => (int)($_GET['builder_id'] ?? 0),
            'project_id' => (int)($_GET['project_id'] ?? 0),
            'maturity'   => in_array($_GET['maturity'] ?? '', ['mature', 'immature'], true) ? $_GET['maturity'] : '',
            'pay'        => in_array($_GET['pay'] ?? '', ['paid', 'partial', 'unpaid'], true) ? $_GET['pay'] : '',
        ];
    }

    private function unitPayload(int $uid): array {
        // The project decides the builder, so a unit can never point at a mismatched pair.
        $projectId = (int)($_POST['project_id'] ?? 0);
        $project   = $projectId ? $this->builder->findProjectById($projectId) : false;
        return [
            'builder_id'        => $project ? (int)$project['builder_id'] : 0,
            'project_id'        => $project ? $projectId : 0,
            'unit_number'       => trim($_POST['unit_number']          ?? ''),
            'block_number'      => trim($_POST['block_number']         ?? ''),
            'category'          => trim($_POST['category']             ?? ''),
            'plot_size'         => trim($_POST['plot_size']            ?? ''),
            'total_cost'        => (float)($_POST['total_cost']        ?? 0),
            'down_payment'      => (float)($_POST['down_payment']      ?? 0),
            'commission_amount' => (float)($_POST['commission_amount'] ?? 0),
            'maturity_status'   => ($_POST['maturity_status'] ?? '') === 'mature' ? 'mature' : 'immature',
            'notes'             => trim($_POST['notes']                ?? ''),
            'created_by'        => $uid,
        ];
    }

    // ---- Payments ----

    private function payments(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/builders/payments');
                exit;
            }
            $act = $_POST['form_action'] ?? '';

            if ($act === 'add' || $act === 'edit') {
                $payment = $this->paymentPayload($uid);
                $id      = (int)($_POST['payment_id'] ?? 0);
                if (!$payment['builder_id'] || $payment['amount'] <= 0) {
                    $_SESSION['error'] = 'Select a builder and enter an amount greater than zero.';
                } elseif ($act === 'add') {
                    $id = $this->builder->addPayment($payment);
                    AuditLog::log('builder_payment_added', $uid, 'builder_payment', $id, 'Payment added.');
                    $_SESSION['success'] = 'Payment added.';
                } elseif ($id) {
                    $this->builder->updatePayment($id, $payment);
                    AuditLog::log('builder_payment_updated', $uid, 'builder_payment', $id, 'Payment updated.');
                    $_SESSION['success'] = 'Payment updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['payment_id'] ?? 0);
                if ($id) {
                    $this->builder->deletePayment($id);
                    AuditLog::log('builder_payment_deleted', $uid, 'builder_payment', $id, 'Payment deleted.');
                    $_SESSION['success'] = 'Payment deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/builders/payments');
            exit;
        }

        $fBuilderId = (int)($_GET['builder_id'] ?? 0);
        $fProjectId = (int)($_GET['project_id'] ?? 0);
        $fMonth     = (int)($_GET['month']      ?? 0);
        $fYear      = (int)($_GET['year']       ?? 0);

        $payments    = $this->builder->getPayments($fBuilderId, $fProjectId, $fMonth, $fYear);
        $allBuilders = $this->builder->getAllBuilders();
        $allProjects = $this->builder->getProjects();
        $unitOptions = $this->builder->getUnitOptions();
        require APP_ROOT . '/app/views/admin/builders/payments.php';
    }

    private function paymentPayload(int $uid): array {
        $date      = $_POST['payment_date'] ?? date('Y-m-d');
        $ts        = strtotime($date) ?: time();
        $types     = ['advance', 'installment', 'final', 'other'];
        $builderId = (int)($_POST['builder_id'] ?? 0);
        $projectId = (int)($_POST['project_id'] ?? 0);
        $unitId    = (int)($_POST['unit_id']    ?? 0);

        // The unit decides the project and the project decides the builder,
        // so a payment is always counted against the right builder, project and unit.
        $unit    = $unitId ? $this->builder->findUnitById($unitId) : false;
        $project = (!$unit && $projectId) ? $this->builder->findProjectById($projectId) : false;
        if ($unit) {
            $builderId = (int)$unit['builder_id'];
            $projectId = (int)$unit['project_id'];
        } elseif ($project) {
            $builderId = (int)$project['builder_id'];
            $unitId    = 0;
        } else {
            $projectId = 0;
            $unitId    = 0;
        }

        return [
            'builder_id'    => $builderId,
            'project_id'    => $projectId,
            'unit_id'       => $unitId,
            'amount'        => (float)($_POST['amount']    ?? 0),
            'payment_type'  => in_array($_POST['payment_type'] ?? '', $types, true) ? $_POST['payment_type'] : 'installment',
            'payment_date'  => date('Y-m-d', $ts),
            'payment_month' => (int)date('n', $ts),
            'payment_year'  => (int)date('Y', $ts),
            'reference'     => trim($_POST['reference'] ?? ''),
            'notes'         => trim($_POST['notes']     ?? ''),
            'created_by'    => $uid,
        ];
    }

    // ---- Builder Detail ----

    private function detail(int $id): void {
        if (!$id) {
            header('Location: ' . APP_URL . '/admin/builders');
            exit;
        }
        $builderRow = $this->builder->findBuilderById($id);
        if (!$builderRow) {
            $_SESSION['error'] = 'Builder not found.';
            header('Location: ' . APP_URL . '/admin/builders');
            exit;
        }
        $projects = $this->builder->getProjects($id);
        $units    = $this->builder->getUnits($id);
        $payments = $this->builder->getPayments($id);
        require APP_ROOT . '/app/views/admin/builders/detail.php';
    }

    // ---- Printable Statement ----

    private function statement(int $id): void {
        if (!$id) {
            header('Location: ' . APP_URL . '/admin/builders');
            exit;
        }
        $data = $this->builder->getBuilderStatement($id);
        if (empty($data['builder'])) {
            header('Location: ' . APP_URL . '/admin/builders');
            exit;
        }
        extract($data);
        require APP_ROOT . '/app/views/admin/builders/statement.php';
    }
}
