<?php
require_once __DIR__ . '/../../config/database.php';

class AgentLocation {
    private PDO $db;

    // A web point closer in time than this to the agent's last point is skipped
    public const MIN_GAP_SEC = 60;
    // Last point older than this = shown as offline on the map
    public const ONLINE_MIN  = 15;

    public function __construct() {
        $this->db = Database::connect();
    }

    // Distance in metres between two points (haversine)
    public static function distanceM(float $lat1, float $lng1, float $lat2, float $lng2): float {
        $r  = 6371000;
        $dl = deg2rad($lat2 - $lat1);
        $dn = deg2rad($lng2 - $lng1);
        $a  = sin($dl / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dn / 2) ** 2;
        return 2 * $r * asin(min(1, sqrt($a)));
    }

    // ---- Writes (throw on error) --------------------------------

    // Returns true when the point was saved, false when skipped
    public function add(int $userId, float $lat, float $lng, ?int $accuracy, ?int $battery,
                        string $source, ?string $deviceId, ?string $recordedAt): bool {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180 || ($lat == 0 && $lng == 0)) return false;

        // Never trust a device clock more than 1 minute in the future
        $ts = $recordedAt ? strtotime($recordedAt) : false;
        $recordedAt = date('Y-m-d H:i:s', ($ts && $ts <= time() + 60) ? $ts : time());

        if ($source === 'web') {
            $stmt = $this->db->prepare("
                SELECT recorded_at FROM agent_locations
                WHERE user_id = ? ORDER BY recorded_at DESC LIMIT 1
            ");
            $stmt->execute([$userId]);
            $last = $stmt->fetchColumn();
            if ($last && strtotime($recordedAt) - strtotime($last) < self::MIN_GAP_SEC) return false;
        }

        $stmt = $this->db->prepare("
            INSERT IGNORE INTO agent_locations
                (user_id, lat, lng, accuracy_m, battery_pct, source, device_point_id, recorded_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $userId, round($lat, 7), round($lng, 7),
            $accuracy !== null ? max(0, min(100000, $accuracy)) : null,
            $battery  !== null ? max(0, min(100, $battery)) : null,
            $source === 'app' ? 'app' : 'web',
            $deviceId !== null && $deviceId !== '' ? mb_substr($deviceId, 0, 64) : null,
            $recordedAt,
        ]);
        return $stmt->rowCount() === 1;
    }

    // ---- Reads (log and return empty on error) ------------------

    // Every active agent and sales manager with their last known point (or none)
    public function getLatest(): array {
        try {
            return $this->db->query("
                SELECT u.id, u.name, u.role, u.phone, t.name AS team_name,
                       al.lat, al.lng, al.accuracy_m, al.battery_pct, al.source, al.recorded_at
                FROM users u
                LEFT JOIN teams t ON t.id = u.team_id
                LEFT JOIN (
                    SELECT user_id, MAX(id) AS last_id FROM agent_locations GROUP BY user_id
                ) lp ON lp.user_id = u.id
                LEFT JOIN agent_locations al ON al.id = lp.last_id
                WHERE u.role IN ('agent','sales_manager') AND u.status = 'active'
                ORDER BY al.recorded_at IS NULL, al.recorded_at DESC, u.name
            ")->fetchAll();
        } catch (\Throwable $e) {
            error_log('AgentLocation::getLatest ' . $e->getMessage());
            return [];
        }
    }

    // One agent's points on one day, oldest first
    public function getRoute(int $userId, string $date): array {
        try {
            $stmt = $this->db->prepare("
                SELECT lat, lng, accuracy_m, battery_pct, source, recorded_at
                FROM agent_locations
                WHERE user_id = ? AND recorded_at >= ? AND recorded_at < ? + INTERVAL 1 DAY
                ORDER BY recorded_at ASC
                LIMIT 5000
            ");
            $stmt->execute([$userId, $date . ' 00:00:00', $date . ' 00:00:00']);
            return $stmt->fetchAll();
        } catch (\Throwable $e) {
            error_log('AgentLocation::getRoute ' . $e->getMessage());
            return [];
        }
    }

    // Total distance of a route in km, ignoring jumps from very inaccurate fixes
    public static function routeKm(array $points): float {
        $m = 0.0;
        $prev = null;
        foreach ($points as $p) {
            if ($p['accuracy_m'] !== null && (int)$p['accuracy_m'] > 500) continue;
            if ($prev) $m += self::distanceM((float)$prev['lat'], (float)$prev['lng'], (float)$p['lat'], (float)$p['lng']);
            $prev = $p;
        }
        return round($m / 1000, 1);
    }
}
