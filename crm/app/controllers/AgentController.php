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

                // Check if new status is Won
                $statuses   = $this->lead->getStatuses();
                $statusName = '';
                foreach ($statuses as $s) {
                    if ((int)$s['id'] === $statusId) { $statusName = $s['name']; break; }
                }

                $this->lead->updateStatus($id, $statusId);
                $this->lead->logActivity($id, $agentId, 'status_change',
                    $note ?: 'Status updated by agent.',
                    ['from_status' => (int)$lead['status_id'], 'to_status' => $statusId]
                );

                // Won → redirect to convert-client page
                if ($statusName === 'Won') {
                    require_once APP_ROOT . '/app/models/Client.php';
                    $clientModel = new Client();
                    $existing    = $clientModel->findByLeadId($id);
                    if (!$existing) {
                        $_SESSION['success'] = 'Great work! Deal marked as Won. Please fill in the client details below.';
                        header('Location: ' . APP_URL . '/agent/lead/' . $id . '/convert');
                        exit;
                    }
                }
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

    public function myTeam(): void {
        Security::requireLogin();
        if ($_SESSION['user_role'] !== 'sales_manager') {
            header('Location: ' . APP_URL . '/agent/dashboard');
            exit;
        }
        require_once APP_ROOT . '/app/models/Team.php';
        $teamModel   = new Team();
        $myTeam      = $teamModel->getManagerTeam((int)$_SESSION['user_id']);
        $teamMembers = $myTeam ? $teamModel->getMembers((int)$myTeam['id']) : [];
        $teamStats   = $myTeam ? $teamModel->getTeamStats((int)$myTeam['id']) : [];
        $months      = $this->user->getAvailableMonths();

        // Monthly trend per team member for chart
        $memberTrends = [];
        foreach ($teamMembers as $m) {
            $memberTrends[$m['id']] = $this->user->getAgentMonthlyTrend((int)$m['id']);
        }

        require_once __DIR__ . '/../views/agent/my_team.php';
    }

    public function teamAgentDetail(int $agentId): void {
        Security::requireLogin();
        if ($_SESSION['user_role'] !== 'sales_manager') {
            header('Location: ' . APP_URL . '/agent/dashboard');
            exit;
        }
        require_once APP_ROOT . '/app/models/Team.php';
        $teamModel = new Team();
        $myTeam    = $teamModel->getManagerTeam((int)$_SESSION['user_id']);

        // Security: agent must belong to this manager's team
        if (!$myTeam) {
            $_SESSION['error'] = 'You are not assigned to a team.';
            header('Location: ' . APP_URL . '/agent/dashboard');
            exit;
        }

        $members   = $teamModel->getMembers((int)$myTeam['id']);
        $memberIds = array_column($members, 'id');
        if (!in_array($agentId, array_map('intval', $memberIds))) {
            $_SESSION['error'] = 'This agent is not in your team.';
            header('Location: ' . APP_URL . '/agent/team');
            exit;
        }

        $detail         = $this->user->getAgentDetail($agentId, $_GET['month'] ?? '');
        $agentLeads     = $this->user->getAgentLeads($agentId, $_GET['month'] ?? '');
        $months         = $this->user->getAvailableMonths();
        $monthlyTrend   = $this->user->getAgentMonthlyTrend($agentId);
        $followUpStats  = $this->user->getAgentFollowUpStats($agentId);
        $sourceBreakdown = $this->user->getAgentSourceBreakdown($agentId);
        $wonDeals       = $this->user->getAgentWonDeals($agentId);

        require_once __DIR__ . '/../views/agent/team_agent_detail.php';
    }

    public function convertClient(int $id): void {
        Security::requireLogin();
        require_once APP_ROOT . '/app/models/Client.php';

        $lead = $this->lead->findById($id);
        if (!$lead || (int)$lead['assigned_to'] !== (int)$_SESSION['user_id']) {
            $_SESSION['error'] = 'Access denied.';
            header('Location: ' . APP_URL . '/agent/leads');
            exit;
        }

        // Already converted — go back to lead
        $clientModel = new Client();
        $existing    = $clientModel->findByLeadId($id);
        if ($existing) {
            $_SESSION['success'] = 'Client record already exists.';
            header('Location: ' . APP_URL . '/agent/lead/' . $id);
            exit;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/agent/lead/' . $id . '/convert');
                exit;
            }

            $clientModel->create([
                'lead_id'        => $id,
                'name'           => trim($_POST['name']           ?? '') ?: ($lead['name'] ?? null),
                'address'        => trim($_POST['address']        ?? '') ?: null,
                'contact_no'     => trim($_POST['contact_no']     ?? '') ?: ($lead['phone'] ?? null),
                'project'        => trim($_POST['project']        ?? '') ?: null,
                'block'          => trim($_POST['block']          ?? '') ?: null,
                'unit_no'        => trim($_POST['unit_no']        ?? '') ?: null,
                'category'       => trim($_POST['category']       ?? '') ?: null,
                'booking_amount' => trim($_POST['booking_amount'] ?? '') ?: null,
                'agent_id'       => (int)$_SESSION['user_id'],
                'source_id'      => $lead['source_id'] ?? null,
                'file_status'    => in_array($_POST['file_status'] ?? '', ['mature','immature']) ? $_POST['file_status'] : 'mature',
                'flag_reason'    => trim($_POST['flag_reason']    ?? '') ?: null,
            ], (int)$_SESSION['user_id']);

            $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'note',
                'Client record created. Booking amount: ' .
                (!empty($_POST['booking_amount']) ? 'Rs. ' . number_format((float)$_POST['booking_amount']) : 'not specified') . '.'
            );

            $_SESSION['success'] = 'Client record saved successfully!';
            header('Location: ' . APP_URL . '/agent/lead/' . $id);
            exit;
        }

        $sources = $this->lead->getSources();
        require_once __DIR__ . '/../views/agent/convert_client.php';
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
