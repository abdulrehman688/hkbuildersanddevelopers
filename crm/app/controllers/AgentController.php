<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/User.php';

class AgentController {

    private Lead $lead;
    private User $user;

    public function __construct() {
        $this->lead = new Lead();
        $this->user = new User();
    }

    public function dashboard(): void {
        Security::requireLogin();
        require_once __DIR__ . '/../views/agent/dashboard.php';
    }

    public function myLeads(): void {
        Security::requireLogin();
        require_once __DIR__ . '/../views/agent/my_leads.php';
    }

    public function leadPool(): void {
        Security::requireLogin();
        require_once __DIR__ . '/../views/agent/lead_pool.php';
    }

    public function leadDetail(int $id): void {
        Security::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLeadAction($id);
            return;
        }

        $lead = $this->lead->findById($id);
        if (!$lead) {
            $_SESSION['error'] = 'Lead not found.';
            header('Location: ' . APP_URL . '/agent/leads');
            exit;
        }

        if ((int)$lead['assigned_to'] !== (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'You do not have access to this lead.';
            header('Location: ' . APP_URL . '/agent/leads');
            exit;
        }

        require_once __DIR__ . '/../views/agent/lead_detail.php';
    }

    private function handleLeadAction(int $id): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: ' . APP_URL . '/agent/lead/' . $id);
            exit;
        }

        $lead = $this->lead->findById($id);
        if (!$lead || (int)$lead['assigned_to'] !== (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . APP_URL . '/agent/leads');
            exit;
        }

        $action  = $_POST['action'] ?? '';
        $agentId = (int)$_SESSION['user_id'];
        $back    = APP_URL . '/agent/lead/' . $id;

        switch ($action) {
            case 'update_status':
                $statusId = (int)($_POST['status_id'] ?? 0);
                $note     = trim($_POST['note'] ?? '');
                if (!$statusId) break;
                $this->lead->updateStatus($id, $statusId);
                $this->lead->logActivity($id, $agentId, 'status_change',
                    $note ?: 'Status updated by agent.',
                    ['from_status' => (int)$lead['status_id'], 'to_status' => $statusId]
                );
                $_SESSION['success'] = 'Status updated.';
                break;

            case 'add_note':
                $note = trim($_POST['note'] ?? '');
                if ($note === '') { $_SESSION['error'] = 'Note cannot be empty.'; break; }
                $this->lead->logActivity($id, $agentId, 'note', $note);
                $_SESSION['success'] = 'Note added.';
                break;

            case 'schedule_followup':
                $date = trim($_POST['followup_date'] ?? '');
                $time = trim($_POST['followup_time'] ?? '10:00');
                $note = trim($_POST['followup_note'] ?? '');
                if (!$date) { $_SESSION['error'] = 'Please select a follow-up date.'; break; }
                $scheduledAt = $date . ' ' . $time . ':00';
                $this->lead->scheduleFollowUp($id, $agentId, $scheduledAt, $note);
                $_SESSION['success'] = 'Follow-up scheduled for ' . date('d M Y, h:i A', strtotime($scheduledAt)) . '.';
                break;

            case 'done_followup':
                $fupId = (int)($_POST['followup_id'] ?? 0);
                if ($fupId) $this->lead->markFollowUpDone($fupId, $agentId);
                $_SESSION['success'] = 'Follow-up marked as done.';
                break;
        }

        header('Location: ' . $back);
        exit;
    }

    public function claimLead(int $id): void {
        Security::requireLogin();

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . APP_URL . '/agent/pool');
            exit;
        }

        $claimed = $this->lead->claim($id, (int)$_SESSION['user_id']);

        if ($claimed) {
            $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'claim',
                'Lead claimed by ' . $_SESSION['user_name'] . '.');
            $_SESSION['success'] = 'Lead claimed. It is now assigned to you.';
            header('Location: ' . APP_URL . '/agent/lead/' . $id);
        } else {
            $_SESSION['error'] = 'This lead has already been claimed by another agent.';
            header('Location: ' . APP_URL . '/agent/pool');
        }
        exit;
    }

    public function changePassword(): void {
        Security::requireLogin();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/agent/password');
                exit;
            }

            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password']     ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $user = $this->user->findById((int)$_SESSION['user_id']);

            if (!password_verify($current, $user['password'])) {
                $_SESSION['error'] = 'Current password is incorrect.';
                header('Location: ' . APP_URL . '/agent/password');
                exit;
            }
            if (strlen($new) < 8) {
                $_SESSION['error'] = 'New password must be at least 8 characters.';
                header('Location: ' . APP_URL . '/agent/password');
                exit;
            }
            if ($new !== $confirm) {
                $_SESSION['error'] = 'Passwords do not match.';
                header('Location: ' . APP_URL . '/agent/password');
                exit;
            }

            $this->user->updatePassword((int)$_SESSION['user_id'], $new);
            $_SESSION['success'] = 'Password changed successfully.';
            header('Location: ' . APP_URL . '/agent/dashboard');
            exit;
        }

        require_once __DIR__ . '/../views/agent/change_password.php';
    }
}
