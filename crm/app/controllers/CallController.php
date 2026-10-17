<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/ContactLog.php';

// JSON endpoint behind every Call button: /admin/call-log and /agent/call-log
//   action=start  -> saves the call the moment the button is tapped
//   action=finish -> saves outcome, talk time and note from the popup
class CallController {

    private function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function handle(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'POST only'], 405);
        }
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!Security::verifyCsrfToken($token)) {
            $this->json(['ok' => false, 'error' => 'Session expired. Reload the page.'], 403);
        }

        try {
            match ($_POST['action'] ?? '') {
                'start'  => $this->start(),
                'finish' => $this->finish(),
                default  => $this->json(['ok' => false, 'error' => 'Unknown action'], 400),
            };
        } catch (\Throwable $e) {
            error_log('CallController ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Could not save the call.'], 500);
        }
    }

    private function start(): void {
        $uid      = (int)$_SESSION['user_id'];
        $isAdmin  = ($_SESSION['user_role'] ?? '') === 'admin';
        $leadId   = (int)($_POST['lead_id']   ?? 0);
        $clientId = (int)($_POST['client_id'] ?? 0);

        $clientModel = new Client();
        $lead   = null;
        $client = null;

        if ($clientId) {
            $client = $clientModel->findById($clientId) ?: null;
            if (!$client) $this->json(['ok' => false, 'error' => 'Client not found.'], 404);
            if (!$leadId && $client['lead_id']) $leadId = (int)$client['lead_id'];
        }
        if ($leadId) {
            $lead = (new Lead())->findById($leadId) ?: null;
            if (!$lead && !$client) $this->json(['ok' => false, 'error' => 'Lead not found.'], 404);
            if ($lead && !$client) $client = $clientModel->findByLeadId($leadId) ?: null;
        }
        if (!$lead && !$client) $this->json(['ok' => false, 'error' => 'Nothing to call.'], 400);

        // Agents may call their own leads and clients, and unclaimed pool leads
        if (!$isAdmin) {
            $ownsLead   = $lead   && ($lead['assigned_to'] === null || (int)$lead['assigned_to'] === $uid);
            $ownsClient = $client && (int)$client['agent_id'] === $uid;
            if (!$ownsLead && !$ownsClient) {
                $this->json(['ok' => false, 'error' => 'Not your lead.'], 403);
            }
        }

        // The number always comes from the record, never from the browser
        $phone = $clientId && $client ? ($client['contact_no'] ?: ($lead['phone'] ?? null))
                                      : (($lead['phone'] ?? null) ?: ($client['contact_no'] ?? null));

        $id = (new ContactLog())->start(
            $lead ? (int)$lead['id'] : null,
            $client ? (int)$client['id'] : null,
            $uid,
            $phone
        );
        $this->json(['ok' => true, 'id' => $id]);
    }

    private function finish(): void {
        $uid     = (int)$_SESSION['user_id'];
        $id      = (int)($_POST['id'] ?? 0);
        $outcome = $_POST['outcome'] ?? '';
        $note    = mb_substr(trim($_POST['note'] ?? ''), 0, 500);

        if (!isset(ContactLog::OUTCOMES[$outcome])) {
            $this->json(['ok' => false, 'error' => 'Pick what happened on the call.'], 422);
        }

        $model = new ContactLog();
        $log   = $model->find($id);
        if (!$log || (int)$log['user_id'] !== $uid) {
            $this->json(['ok' => false, 'error' => 'Call not found.'], 404);
        }

        // Talk time only counts when the call was answered; cap at 6 hours
        $duration = null;
        if ($outcome === 'answered' && ($_POST['duration_sec'] ?? '') !== '') {
            $duration = max(0, min(21600, (int)$_POST['duration_sec']));
        } elseif ($outcome !== 'answered') {
            $duration = 0;
        }

        $model->finish($id, $uid, $outcome, $duration, $note);

        // Timeline entry only the first time the outcome is saved
        if ($log['lead_id'] && $log['outcome'] === null) {
            $text = 'Call: ' . ContactLog::OUTCOMES[$outcome]
                  . ($outcome === 'answered' && $duration !== null ? ', talk time ' . ContactLog::fmtDuration($duration) : '')
                  . ($note !== '' ? '. ' . $note : '.');
            (new Lead())->logActivity((int)$log['lead_id'], $uid, 'call', $text, ['contact_log_id' => $id]);
        }

        $this->json(['ok' => true]);
    }
}
