<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/Client.php';
require_once __DIR__ . '/../models/ContactLog.php';

// JSON endpoint behind every Call button: /admin/call-log and /agent/call-log
//   action=start   -> saves the call the moment the button is tapped
//   action=finish  -> saves outcome, talk time and note from the popup
//   action=numbers -> last 10 digits of the agent's lead and client numbers, so
//                     the Android app only sends calls with them (never personal calls)
//   action=sync    -> calls from the phone's call log: exact talk time
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
                'start'   => $this->start(),
                'finish'  => $this->finish(),
                'numbers' => $this->numbers(),
                'sync'    => $this->sync(),
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

    private function numbers(): void {
        $model = new ContactLog();
        $uid   = (int)$_SESSION['user_id'];
        $nums  = array_map('strval', array_keys($model->getAgentNumbers($uid)));
        // Plus Call-button calls still waiting for their exact talk time (e.g. a pool lead)
        foreach ($model->getPendingButtonPhones($uid) as $p) $nums[] = ContactLog::last10($p);
        $this->json(['ok' => true, 'numbers' => array_values(array_unique(array_filter($nums, fn($n) => strlen($n) === 10)))]);
    }

    private function sync(): void {
        $uid   = (int)$_SESSION['user_id'];
        $calls = json_decode($_POST['calls'] ?? '[]', true);
        if (!is_array($calls)) $this->json(['ok' => false, 'error' => 'Bad data'], 400);

        $model   = new ContactLog();
        $numbers = $model->getAgentNumbers($uid);
        $lead    = new Lead();
        $types   = [1 => 'incoming', 2 => 'outgoing', 3 => 'missed', 5 => 'missed', 6 => 'missed', 7 => 'incoming'];
        $words   = ['incoming' => 'Incoming', 'outgoing' => 'Outgoing', 'missed' => 'Missed'];
        $count   = ['added' => 0, 'updated' => 0, 'skipped' => 0];

        foreach (array_slice($calls, 0, 300) as $c) {
            if (!is_array($c)) continue;
            $ms = (int)($c['date'] ?? 0);
            if (empty($c['id']) || $ms <= 0 || !isset($types[(int)($c['type'] ?? 0)])) continue;
            $call = [
                'device_id'  => mb_substr((string)$c['id'] . '-' . $ms, 0, 64),
                'number'     => (string)($c['number'] ?? ''),
                'direction'  => $types[(int)$c['type']],
                'started_at' => date('Y-m-d H:i:s', (int)floor($ms / 1000)),
                'duration'   => max(0, min(21600, (int)($c['duration'] ?? 0))),
            ];
            if ($ms / 1000 > time() + 60) continue;   // clock in the future: ignore
            $result = $model->syncPhoneCall($uid, $call, $numbers);
            $count[$result]++;

            // New call found only in the phone log: note it on the lead's timeline
            $match = $numbers[ContactLog::last10($call['number'])] ?? null;
            if ($result === 'added' && $match && $match[0]) {
                $lead->logActivity($match[0], $uid, 'call',
                    'Call from phone: ' . $words[$call['direction']]
                    . ($call['duration'] > 0 ? ', talk time ' . ContactLog::fmtDuration($call['duration']) : ', not answered') . '.');
            }
        }
        $this->json(['ok' => true] + $count);
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
