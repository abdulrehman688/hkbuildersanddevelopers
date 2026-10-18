<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/AgentLocation.php';

$locModel = new AgentLocation();
$latest   = $locModel->getLatest();

$agentId = (int)($_GET['agent_id'] ?? 0);
$date    = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $date = date('Y-m-d');

$agentRow = null;
foreach ($latest as $a) if ((int)$a['id'] === $agentId) $agentRow = $a;
$route = $agentRow ? $locModel->getRoute($agentId, $date) : [];

$ago = function (?string $dt): string {
    if (!$dt) return 'Never';
    $s = time() - strtotime($dt);
    if ($s < 60)    return 'just now';
    if ($s < 3600)  return intdiv($s, 60) . ' min ago';
    if ($s < 86400) return intdiv($s, 3600) . ' h ago';
    return intdiv($s, 86400) . ' days ago';
};
$isOnline = fn(?string $dt) => $dt && time() - strtotime($dt) <= AgentLocation::ONLINE_MIN * 60;

// Map markers: first draw, and the JSON the map re-reads every 60 seconds
$liveRows = array_map(fn($a) => [
    'id' => (int)$a['id'], 'name' => $a['name'], 'team' => $a['team_name'],
    'lat' => $a['lat'] !== null ? (float)$a['lat'] : null, 'lng' => $a['lng'] !== null ? (float)$a['lng'] : null,
    'acc' => $a['accuracy_m'] !== null ? (int)$a['accuracy_m'] : null,
    'battery' => $a['battery_pct'] !== null ? (int)$a['battery_pct'] : null,
    'time' => $a['recorded_at'] ? date('d M, h:i A', strtotime($a['recorded_at'])) : null,
    'ago' => $ago($a['recorded_at']), 'online' => $isOnline($a['recorded_at']),
], $latest);

if (($_GET['format'] ?? '') === 'json') {
    header('Content-Type: application/json');
    echo json_encode($liveRows);
    exit;
}

$online  = count(array_filter($latest, fn($a) => $isOnline($a['recorded_at'])));
$never   = count(array_filter($latest, fn($a) => !$a['recorded_at']));
$offline = count($latest) - $online - $never;

$pageTitle  = 'Agent Locations';
$activePage = 'locations';
$js = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
ob_start();
?>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css">

<div class="page-header">
    <div class="page-header-left">
        <h1>Agent Locations</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span>
            <?php if ($agentRow): ?>
                <a href="<?= APP_URL ?>/admin/locations" style="color:var(--text-muted)">Locations</a> <span class="sep">/</span>
                <span class="current"><?= Security::e($agentRow['name']) ?>, <?= date('d M Y', strtotime($date)) ?></span>
            <?php else: ?>
                <span class="current">Where agents are now</span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="<?= APP_URL ?>/admin/locations">
        <div class="filter-row">
            <select name="agent_id" class="filter-input">
                <option value="">All agents, live</option>
                <?php foreach ($latest as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= $agentId === (int)$a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <input type="date" name="date" class="filter-input" value="<?= Security::e($date) ?>" max="<?= date('Y-m-d') ?>" title="Day of the route">
            <button type="submit" class="btn btn-primary btn-sm">Show Route</button>
            <?php if ($agentRow): ?><a href="<?= APP_URL ?>/admin/locations" class="btn btn-secondary btn-sm">Back to Live</a><?php endif; ?>
        </div>
    </form>
</div>

<?php if (!$agentRow): ?>
<div class="chip-row" style="display:flex;flex-wrap:wrap;gap:10px;margin:16px 0">
    <?php foreach ([
        ['Online now', $online, '#16a34a', 'Sent a location in the last ' . AgentLocation::ONLINE_MIN . ' minutes'],
        ['Offline', $offline, '#6b7280', 'Logged out, app closed, or location turned off'],
        ['Never shared', $never, '#dc2626', 'Has not allowed location yet'],
    ] as [$label, $value, $color, $hint]): ?>
    <div title="<?= Security::e($hint) ?>" style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:150px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= (int)$value ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php else:
    $first = $route[0]['recorded_at'] ?? null;
    $last  = $route ? end($route)['recorded_at'] : null;
?>
<div class="chip-row" style="display:flex;flex-wrap:wrap;gap:10px;margin:16px 0">
    <?php foreach ([
        ['First seen', $first ? date('h:i A', strtotime($first)) : '-'],
        ['Last seen',  $last  ? date('h:i A', strtotime($last))  : '-'],
        ['Distance',   $route ? AgentLocation::routeKm($route) . ' km' : '-'],
        ['Points',     (string)count($route)],
    ] as [$label, $value]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:130px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:var(--navy)"><?= Security::e($value) ?></div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card" style="padding:0;overflow:hidden;margin-bottom:20px">
    <div id="agentMap" class="agent-map"></div>
    <?php if ($agentRow && !$route): ?>
        <div style="padding:14px;font-size:13px;color:var(--text-muted)">No locations for <?= Security::e($agentRow['name']) ?> on this day.</div>
    <?php endif; ?>
</div>

<?php if (!$agentRow): ?>
<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table table-cards">
            <thead><tr><th>Agent</th><th>Status</th><th>Last Seen</th><th>Accuracy</th><th>Battery</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($latest as $a): $on = $isOnline($a['recorded_at']); ?>
                <tr>
                    <td class="tc-title">
                        <?= Security::e($a['name']) ?>
                        <div class="lead-sub"><?= $a['role'] === 'sales_manager' ? 'Sales Manager' : Security::e($a['team_name'] ?? 'No team') ?></div>
                    </td>
                    <td>
                        <?php if (!$a['recorded_at']): ?>
                            <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">Never shared</span>
                        <?php elseif ($on): ?>
                            <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Online</span>
                        <?php else: ?>
                            <span class="status-pill" style="background:#f3f4f6;color:#6b7280;border:1px solid #e5e7eb">Offline</span>
                        <?php endif; ?>
                    </td>
                    <td style="white-space:nowrap">
                        <?= $ago($a['recorded_at']) ?>
                        <?php if ($a['recorded_at']): ?><div class="lead-sub"><?= date('d M, h:i A', strtotime($a['recorded_at'])) ?></div><?php endif; ?>
                    </td>
                    <td><?= $a['accuracy_m'] !== null ? (int)$a['accuracy_m'] . ' m' : '-' ?></td>
                    <td><?= $a['battery_pct'] !== null ? (int)$a['battery_pct'] . '%' : '-' ?></td>
                    <td>
                        <div style="display:flex;gap:6px">
                            <?php if ($a['recorded_at']): ?>
                                <button type="button" class="btn btn-secondary btn-sm" onclick="focusAgent(<?= (int)$a['id'] ?>)">Map</button>
                            <?php endif; ?>
                            <a href="<?= APP_URL ?>/admin/locations?agent_id=<?= (int)$a['id'] ?>&date=<?= date('Y-m-d') ?>" class="btn btn-secondary btn-sm">Route</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$latest): ?>
                <tr><td colspan="6" class="empty-row">No active agents.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div style="font-size:12px;color:var(--text-muted);margin-top:10px">
    Locations come from the CRM while an agent is logged in and has it open. With the Android app they will also come in the background.
    The map refreshes every minute.
</div>
<?php endif; ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
<script>
(function() {
    if (!window.L) { document.getElementById('agentMap').innerHTML = '<div style="padding:20px;color:#6b7280">Map could not load. Check the internet connection.</div>'; return; }
    var map = L.map('agentMap', { scrollWheelZoom: false }).setView([24.8607, 67.0011], 11); // Karachi
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19, attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : String(s); return d.innerHTML; }

<?php if ($agentRow): ?>
    // ---- One agent's route for the day ----
    var pts = <?= $js(array_map(fn($p) => [(float)$p['lat'], (float)$p['lng'], date('h:i A', strtotime($p['recorded_at'])), $p['accuracy_m'] !== null ? (int)$p['accuracy_m'] : null], $route)) ?>;
    if (pts.length) {
        // Fixes worse than 500 m are shown faintly and left out of the line
        var good = pts.filter(function(p) { return p[3] == null || p[3] <= 500; });
        var line = L.polyline((good.length ? good : pts).map(function(p) { return [p[0], p[1]]; }), { color: '#0a1628', weight: 4, opacity: .75 }).addTo(map);
        pts.forEach(function(p, i) {
            var first = i === 0, last = i === pts.length - 1, rough = p[3] != null && p[3] > 500;
            L.circleMarker([p[0], p[1]], {
                radius: first || last ? 9 : 4, weight: 2, color: '#fff', fillOpacity: rough ? .4 : 1,
                fillColor: rough ? '#9ca3af' : (first ? '#16a34a' : (last ? '#dc2626' : '#c9a84c'))
            }).addTo(map).bindPopup('<b>' + (first ? 'Start ' : (last ? 'Last ' : '')) + esc(p[2]) + '</b>'
                + (p[3] != null ? '<br>Accuracy ' + p[3] + ' m' + (rough ? ' (rough)' : '') : ''));
        });
        map.fitBounds(line.getBounds(), { padding: [30, 30], maxZoom: 16 });
    }
<?php else: ?>
    // ---- Live: every agent's last position ----
    var markers = {}, fitted = false;
    function draw(list) {
        var bounds = [];
        list.forEach(function(a) {
            if (a.lat == null) return;
            var html = '<b>' + esc(a.name) + '</b>' + (a.team ? '<br>' + esc(a.team) : '')
                + '<br>' + esc(a.ago) + ' (' + esc(a.time) + ')'
                + (a.acc != null ? '<br>Accuracy ' + a.acc + ' m' : '')
                + (a.battery != null ? '<br>Battery ' + a.battery + '%' : '')
                + '<br><a href="<?= APP_URL ?>/admin/locations?agent_id=' + a.id + '">Route today</a>';
            var style = { radius: 10, weight: 3, color: '#fff', fillColor: a.online ? '#16a34a' : '#9ca3af', fillOpacity: 1 };
            if (markers[a.id]) {
                markers[a.id].setLatLng([a.lat, a.lng]).setStyle(style).setPopupContent(html);
            } else {
                markers[a.id] = L.circleMarker([a.lat, a.lng], style).addTo(map).bindPopup(html)
                    .bindTooltip(esc(a.name), { permanent: true, direction: 'top', offset: [0, -10], className: 'agent-label' });
            }
            bounds.push([a.lat, a.lng]);
        });
        if (!fitted && bounds.length) { map.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 }); fitted = true; }
    }
    draw(<?= $js($liveRows) ?>);

    window.focusAgent = function(id) {
        var m = markers[id];
        if (!m) return;
        document.getElementById('agentMap').scrollIntoView({ behavior: 'smooth', block: 'center' });
        map.setView(m.getLatLng(), 16);
        m.openPopup();
    };

    setInterval(function() {
        fetch('<?= APP_URL ?>/admin/locations?format=json', { credentials: 'same-origin' })
            .then(function(r) { return r.ok ? r.json() : null; })
            .then(function(list) { if (list) draw(list); })
            .catch(function() {});
    }, 60000);
<?php endif; ?>
})();
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
