<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../models/AgentLocation.php';

// POST /agent/location: the agent's current position, sent every few minutes
// while they are logged in. Accepts one point as form fields (lat, lng,
// accuracy, battery) or several as JSON {"points":[...]} for the Android app.
class LocationController {

    private function json(array $data, int $code = 200): void {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function save(): void {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->json(['ok' => false, 'error' => 'POST only'], 405);
        }
        if (!in_array($_SESSION['user_role'] ?? '', ['agent', 'sales_manager'], true)) {
            $this->json(['ok' => false, 'error' => 'Only agents share location'], 403);
        }
        // JSON posts come from the Android app's background service. They need
        // no CSRF token: the session cookie is SameSite=Strict, and another site
        // cannot send a JSON body here without a CORS preflight we never allow.
        $isJson = str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json');
        $token  = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? '');
        if (!$isJson && !Security::verifyCsrfToken($token)) {
            $this->json(['ok' => false, 'error' => 'Session expired'], 403);
        }

        $points = [];
        $body   = $isJson ? json_decode(file_get_contents('php://input'), true) : null;
        if (is_array($body) && isset($body['points']) && is_array($body['points'])) {
            $points = array_slice($body['points'], 0, 500);
            $source = 'app';
        } else {
            $points = [$_POST];
            $source = 'web';
        }

        $model = new AgentLocation();
        $uid   = (int)$_SESSION['user_id'];
        $saved = 0;
        try {
            foreach ($points as $p) {
                if (!is_array($p) || !is_numeric($p['lat'] ?? null) || !is_numeric($p['lng'] ?? null)) continue;
                $ok = $model->add(
                    $uid,
                    (float)$p['lat'],
                    (float)$p['lng'],
                    isset($p['accuracy']) && is_numeric($p['accuracy']) ? (int)round((float)$p['accuracy']) : null,
                    isset($p['battery'])  && is_numeric($p['battery'])  ? (int)round((float)$p['battery'])  : null,
                    $source,
                    $source === 'app' ? (string)($p['id'] ?? '') : null,
                    $source === 'app' && !empty($p['time']) ? (string)$p['time'] : null
                );
                if ($ok) $saved++;
            }
        } catch (\Throwable $e) {
            error_log('LocationController ' . $e->getMessage());
            $this->json(['ok' => false, 'error' => 'Could not save location'], 500);
        }
        $this->json(['ok' => true, 'saved' => $saved]);
    }
}
