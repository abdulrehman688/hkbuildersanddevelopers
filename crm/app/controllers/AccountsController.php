<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/AuditLog.php';
require_once __DIR__ . '/../models/Accounts.php';

class AccountsController {

    private Accounts $accounts;

    public function __construct() {
        $this->accounts = new Accounts();
    }

    public function accounts(?string $sub): void {
        match($sub ?: 'overview') {
            'commission'       => $this->commission(),
            'commission-ajax'  => $this->commissionAjax(),
            'expenses'         => $this->expenses(),
            'salaries'         => $this->salaries(),
            default            => $this->overview(),
        };
    }

    // ---- Overview --------------------------------------------------

    private function overview(): void {
        $commStats = $this->accounts->getCommissionStats();
        $expStats  = $this->accounts->getExpenseStats();
        require APP_ROOT . '/app/views/admin/accounts/overview.php';
    }

    // ---- Commission ------------------------------------------------

    private function commission(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/commission');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addCommission($this->commissionPayload($uid));
                AuditLog::log('commission_added', $uid, 'commission', $id, 'Commission record added.');
                $_SESSION['success'] = 'Commission record added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['commission_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateCommission($id, $this->commissionPayload($uid));
                    AuditLog::log('commission_updated', $uid, 'commission', $id, 'Commission record updated.');
                    $_SESSION['success'] = 'Commission record updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['commission_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteCommission($id);
                    AuditLog::log('commission_deleted', $uid, 'commission', $id, 'Commission record deleted.');
                    $_SESSION['success'] = 'Commission record deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/commission');
            exit;
        }

        $filters = [
            'agent_id'        => (int)($_GET['agent_id']        ?? 0),
            'maturity_status' => $_GET['maturity_status'] ?? '',
            'payment_status'  => $_GET['payment_status']  ?? '',
            'sale_month'      => (int)($_GET['sale_month'] ?? 0),
            'sale_year'       => (int)($_GET['sale_year']  ?? 0),
        ];

        $commissions    = $this->accounts->getCommissions($filters);
        $agents         = $this->accounts->getAgents();
        $allProjects    = $this->accounts->getAllProjects();
        $pendingReasons = $this->accounts->getPendingReasons();
        $stats          = $this->accounts->getCommissionStats();

        require APP_ROOT . '/app/views/admin/accounts/commission.php';
    }

    private function commissionAjax(): void {
        Security::requireAdmin();
        header('Content-Type: application/json');
        $agentId   = (int)($_GET['agent_id']   ?? 0);
        $projectId = (int)($_GET['project_id'] ?? 0);
        if (!$agentId || !$projectId) {
            echo json_encode(['records' => 0, 'total_commission' => 0, 'paid_amount' => 0, 'remaining' => 0]);
            exit;
        }
        echo json_encode($this->accounts->getAgentProjectSummary($agentId, $projectId));
        exit;
    }

    private function commissionPayload(int $uid): array {
        $saleDate      = trim($_POST['sale_date'] ?? '');
        $dueDate       = trim($_POST['due_date']  ?? '');
        $ts            = $saleDate ? (strtotime($saleDate) ?: time()) : time();
        $sources       = ['cash','online','cheque','bank_transfer','other'];
        $paymentStatus = ($_POST['payment_status'] ?? '') === 'paid' ? 'paid' : 'pending';
        $totalComm     = (float)($_POST['total_commission'] ?? 0);
        return [
            'agent_id'          => (int)($_POST['agent_id']    ?? 0),
            'lead_id'           => (int)($_POST['lead_id']     ?? 0),
            'project_id'        => (int)($_POST['project_id']  ?? 0),
            'client_name'       => trim($_POST['client_name']  ?? ''),
            'project'           => trim($_POST['project_name_text'] ?? ''),
            'plot_number'       => trim($_POST['plot_number']  ?? ''),
            'total_commission'  => $totalComm,
            'maturity_status'   => ($_POST['maturity_status'] ?? '') === 'mature' ? 'mature' : 'immature',
            'payment_status'    => $paymentStatus,
            'paid_amount'       => $paymentStatus === 'paid' ? $totalComm : 0,
            'payment_source'    => in_array($_POST['payment_source'] ?? '', $sources, true) ? $_POST['payment_source'] : null,
            'reference_no'      => trim($_POST['reference_no'] ?? ''),
            'pending_reason_id' => (int)($_POST['pending_reason_id'] ?? 0),
            'pending_notes'     => trim($_POST['pending_notes'] ?? ''),
            'sale_date'         => $saleDate ?: null,
            'sale_month'        => $saleDate ? (int)date('n', $ts) : (int)($_POST['sale_month'] ?? 0),
            'sale_year'         => $saleDate ? (int)date('Y', $ts) : (int)($_POST['sale_year']  ?? 0),
            'due_date'          => $dueDate ?: null,
            'notes'             => trim($_POST['notes'] ?? ''),
            'created_by'        => $uid,
        ];
    }

    // ---- Expenses --------------------------------------------------

    private function expenses(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/expenses');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addExpense($this->expensePayload($uid));
                AuditLog::log('expense_added', $uid, 'expense', $id, 'Expense added.');
                $_SESSION['success'] = 'Expense added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateExpense($id, $this->expensePayload($uid));
                    AuditLog::log('expense_updated', $uid, 'expense', $id, 'Expense updated.');
                    $_SESSION['success'] = 'Expense updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteExpense($id);
                    AuditLog::log('expense_deleted', $uid, 'expense', $id, 'Expense deleted.');
                    $_SESSION['success'] = 'Expense deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/expenses');
            exit;
        }

        $type     = $_GET['type']      ?? '';
        $period   = $_GET['period']    ?? '';
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo   = trim($_GET['date_to']   ?? '');

        // Resolve period shortcuts to concrete date range
        $today = date('Y-m-d');
        switch ($period) {
            case 'today':
                $dateFrom = $dateTo = $today; break;
            case 'yesterday':
                $dateFrom = $dateTo = date('Y-m-d', strtotime('-1 day')); break;
            case 'this_week':
                $dateFrom = date('Y-m-d', strtotime('monday this week'));
                $dateTo   = $today; break;
            case 'last_week':
                $dateFrom = date('Y-m-d', strtotime('monday last week'));
                $dateTo   = date('Y-m-d', strtotime('sunday last week')); break;
            case 'this_month':
                $dateFrom = date('Y-m-01');
                $dateTo   = $today; break;
            case 'last_month':
                $dateFrom = date('Y-m-01', strtotime('first day of last month'));
                $dateTo   = date('Y-m-t',  strtotime('last day of last month')); break;
            case 'this_year':
                $dateFrom = date('Y-01-01');
                $dateTo   = $today; break;
        }

        $expenses = $this->accounts->getExpenses($type, 0, 0, 0, $dateFrom, $dateTo);
        $agents   = $this->accounts->getAgents();
        $stats    = $this->accounts->getExpenseStats();

        require APP_ROOT . '/app/views/admin/accounts/expenses.php';
    }

    private function expensePayload(int $uid): array {
        $date  = $_POST['expense_date'] ?? date('Y-m-d');
        $ts    = strtotime($date) ?: time();
        $types = ['marketing', 'salary', 'general'];
        return [
            'type'          => in_array($_POST['type'] ?? '', $types, true) ? $_POST['type'] : 'general',
            'category'      => trim($_POST['category']    ?? ''),
            'description'   => trim($_POST['description'] ?? ''),
            'amount'        => (float)($_POST['amount'] ?? 0),
            'agent_id'      => (int)($_POST['agent_id']  ?? 0),
            'expense_date'  => date('Y-m-d', $ts),
            'expense_month' => (int)date('n', $ts),
            'expense_year'  => (int)date('Y', $ts),
            'created_by'    => $uid,
        ];
    }

    // ---- Salaries --------------------------------------------------

    private function salaries(): void {
        $uid = (int)$_SESSION['user_id'];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/accounts/salaries');
                exit;
            }

            $act = $_POST['form_action'] ?? '';

            if ($act === 'add') {
                $id = $this->accounts->addExpense($this->salaryPayload($uid));
                AuditLog::log('salary_added', $uid, 'expense', $id, 'Salary record added.');
                $_SESSION['success'] = 'Salary record added.';

            } elseif ($act === 'edit') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->updateExpense($id, $this->salaryPayload($uid));
                    AuditLog::log('salary_updated', $uid, 'expense', $id, 'Salary record updated.');
                    $_SESSION['success'] = 'Salary record updated.';
                }

            } elseif ($act === 'delete') {
                $id = (int)($_POST['expense_id'] ?? 0);
                if ($id) {
                    $this->accounts->deleteExpense($id);
                    AuditLog::log('salary_deleted', $uid, 'expense', $id, 'Salary record deleted.');
                    $_SESSION['success'] = 'Salary record deleted.';
                }
            }

            header('Location: ' . APP_URL . '/admin/accounts/salaries');
            exit;
        }

        $month   = (int)($_GET['month']    ?? 0);
        $year    = (int)($_GET['year']     ?? 0);
        $agentId = (int)($_GET['agent_id'] ?? 0);

        $salaries = $this->accounts->getExpenses('salary', $month, $year, $agentId);
        $agents   = $this->accounts->getAgents();
        $stats    = $this->accounts->getExpenseStats();

        require APP_ROOT . '/app/views/admin/accounts/salaries.php';
    }

    private function salaryPayload(int $uid): array {
        $month = max(1, min(12, (int)($_POST['salary_month'] ?? date('n'))));
        $year  = (int)($_POST['salary_year'] ?? date('Y'));
        return [
            'type'          => 'salary',
            'category'      => 'Salary',
            'description'   => trim($_POST['description'] ?? ''),
            'amount'        => (float)($_POST['amount'] ?? 0),
            'agent_id'      => (int)($_POST['agent_id'] ?? 0),
            'expense_date'  => sprintf('%04d-%02d-01', $year, $month),
            'expense_month' => $month,
            'expense_year'  => $year,
            'created_by'    => $uid,
        ];
    }
}
