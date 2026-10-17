<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/AuditLog.php';
require_once __DIR__ . '/../models/LeadDistributor.php';
require_once __DIR__ . '/../models/Team.php';

// Distribute Leads page: /admin/distribute (all agents) and
// /agent/distribute (sales manager, own team only).
class DistributeController {

    private LeadDistributor $dist;

    public function __construct() {
        $this->dist = new LeadDistributor();
    }

    public function handle(string $section): void {
        $isAdmin = $section === 'admin';
        $uid     = (int)$_SESSION['user_id'];
        $teamId  = null;
        $team    = null;

        if (!$isAdmin) {
            if (($_SESSION['user_role'] ?? '') !== 'sales_manager') {
                header('Location: ' . APP_URL . '/agent/dashboard');
                exit;
            }
            $team = (new Team())->getManagerTeam($uid);
            if (!$team) {
                $_SESSION['error'] = 'You are not assigned to a team yet. Ask admin to set up your team.';
                header('Location: ' . APP_URL . '/agent/dashboard');
                exit;
            }
            $teamId = (int)$team['id'];
        }

        $agents = $this->dist->getAgents($teamId);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->post($section, $isAdmin, $agents);
            return;
        }

        $leads       = $this->dist->getUnassigned(500);
        $unassigned  = (new Lead())->countUnclaimed();
        require APP_ROOT . '/app/views/shared/distribute.php';
    }

    private function post(string $section, bool $isAdmin, array $agents): void {
        $back = APP_URL . '/' . $section . '/distribute';
        if (($_POST['back'] ?? '') === 'leads' && $isAdmin) $back = APP_URL . '/admin/leads';

        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request. Please try again.';
            header('Location: ' . $back);
            exit;
        }

        $uid     = (int)$_SESSION['user_id'];
        $byName  = $_SESSION['user_name'] ?? 'Admin';
        $allowed = array_column($agents, null, 'id');   // id => agent row

        try {
            if (($_POST['action'] ?? '') === 'auto') {
                $chosen = [];
                foreach ((array)($_POST['agent_ids'] ?? []) as $id) {
                    if (isset($allowed[(int)$id])) $chosen[] = $allowed[(int)$id];
                }
                if (!$chosen) {
                    $_SESSION['error'] = 'Tick at least one agent.';
                } else {
                    $max    = (int)($_POST['max_leads'] ?? 0);
                    $counts = $this->dist->autoDistribute($chosen, $max > 0 ? $max : 5000, $uid, $byName);
                    $total  = array_sum($counts);
                    if ($total === 0) {
                        $_SESSION['error'] = 'There are no unassigned leads to share.';
                    } else {
                        $parts = [];
                        foreach ($counts as $id => $n) $parts[] = $allowed[$id]['name'] . ' ' . $n;
                        $msg = "{$total} lead" . ($total === 1 ? '' : 's') . ' shared: ' . implode(', ', $parts) . '.';
                        AuditLog::log('leads_distributed', $uid, null, null, $msg);
                        $_SESSION['success'] = $msg;
                    }
                }

            } elseif (($_POST['action'] ?? '') === 'assign') {
                $agentId = (int)($_POST['agent_id'] ?? 0);
                $leadIds = (array)($_POST['lead_ids'] ?? []);
                if (!isset($allowed[$agentId])) {
                    $_SESSION['error'] = 'Pick an agent.';
                } elseif (!$leadIds) {
                    $_SESSION['error'] = 'Tick at least one lead.';
                } else {
                    // Admin may move leads that already have an agent (from All Leads)
                    $reassign = $isAdmin && ($_POST['back'] ?? '') === 'leads';
                    $n    = $this->dist->assignMany($leadIds, $agentId, $allowed[$agentId]['name'], $uid, $byName, !$reassign);
                    $skip = count(array_unique(array_map('intval', $leadIds))) - $n;
                    $msg  = "{$n} lead" . ($n === 1 ? '' : 's') . " assigned to {$allowed[$agentId]['name']}.";
                    if ($skip > 0) $msg .= " {$skip} skipped (already taken" . ($reassign ? ' by that agent' : '') . ').';
                    if ($n > 0) AuditLog::log('leads_assigned', $uid, 'user', $agentId, $msg);
                    $_SESSION[$n > 0 ? 'success' : 'error'] = $msg;
                }
            }
        } catch (\Throwable $e) {
            error_log('DistributeController ' . $e->getMessage());
            $_SESSION['error'] = 'Could not assign the leads. Nothing was changed.';
        }

        header('Location: ' . $back);
        exit;
    }
}
