<?php
Security::requireLogin();
// $myTeam, $teamMembers, $teamStats, $months, $memberTrends injected by AgentController::myTeam()
$pageTitle  = 'My Team';
$activePage = 'team';
ob_start();

$totalLeads  = (int)($teamStats['total_leads']  ?? 0);
$won         = (int)($teamStats['st_won']        ?? 0);
$lost        = (int)($teamStats['st_lost']       ?? 0);
$interested  = (int)($teamStats['interested']    ?? 0);
$closed      = $won + $lost;
$conversion  = $closed > 0 ? round(($won / $closed) * 100) : 0;
$totalBooking = (float)($teamStats['total_booking'] ?? 0);
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= $myTeam ? Security::e($myTeam['name']) : 'My Team' ?></h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span><span class="current">Team Overview</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print
        </button>
    </div>
</div>

<?php if (!$myTeam): ?>
<div class="card" style="padding:48px;text-align:center;color:var(--text-muted)">
    You haven't been assigned to a team yet. Contact admin to set up your team.
</div>
<?php else: ?>

<!-- Team Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label">Team Members</div>
        <div class="stat-value"><?= count($teamMembers) ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Leads</div>
        <div class="stat-value"><?= $totalLeads ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Won / Closed</div>
        <div class="stat-value" style="color:#10b981"><?= $won ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">In Pipeline</div>
        <div class="stat-value" style="color:#f59e0b"><?= $interested ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Team Conversion</div>
        <div class="stat-value"><?= $conversion ?>%</div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Booking Value</div>
        <div class="stat-value" style="font-size:15px;color:#10b981">Rs. <?= number_format($totalBooking) ?></div>
    </div>
</div>

<!-- Status Breakdown -->
<div class="card" style="padding:20px;margin-bottom:24px">
    <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:14px">Team Status Breakdown</div>
    <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px">
        <?php foreach ([
            ['New',         $teamStats['st_new']         ?? 0, '#3B82F6'],
            ['Contacted',   $teamStats['st_contacted']   ?? 0, '#8B5CF6'],
            ['Qualified',   $teamStats['st_qualified']   ?? 0, '#F59E0B'],
            ['Proposal',    $teamStats['st_proposal']    ?? 0, '#EC4899'],
            ['Negotiation', $teamStats['st_negotiation'] ?? 0, '#F97316'],
            ['Won',         $teamStats['st_won']         ?? 0, '#10B981'],
            ['Lost',        $teamStats['st_lost']        ?? 0, '#EF4444'],
            ['Dead',        $teamStats['st_dead']        ?? 0, '#6B7280'],
        ] as [$label, $count, $color]): ?>
        <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:12px;border-left:3px solid <?= $color ?>">
            <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$count ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Agent Leaderboard -->
<div class="card" style="padding:0;margin-bottom:24px">
    <div style="padding:16px 20px 12px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
        <span style="font-size:14px;font-weight:600;color:var(--navy)">Agent Performance</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= count($teamMembers) ?> agents</span>
    </div>
    <?php if (empty($teamMembers)): ?>
        <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px">No agents in your team yet.</div>
    <?php else: ?>
    <div class="table-wrapper" style="margin:0">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Agent</th>
                    <th>Total Leads</th>
                    <th>In Pipeline</th>
                    <th>Won</th>
                    <th>Lost</th>
                    <th>Conversion</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php
            // Sort by won desc for leaderboard
            usort($teamMembers, fn($a,$b) => (int)$b['won'] - (int)$a['won']);
            $rank = 0;
            foreach ($teamMembers as $m):
                $rank++;
                $mWon    = (int)$m['won'];
                $mLost   = (int)$m['lost'];
                $mClosed = $mWon + $mLost;
                $mConv   = $mClosed > 0 ? round(($mWon / $mClosed) * 100) : 0;
                $medal   = match($rank) { 1 => '🥇', 2 => '🥈', 3 => '🥉', default => '' };
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <?php if ($medal): ?><span style="font-size:18px"><?= $medal ?></span><?php endif; ?>
                        <div class="sidebar-avatar" style="width:32px;height:32px;font-size:13px"><?= strtoupper(substr($m['name'],0,1)) ?></div>
                        <div>
                            <div style="font-weight:600;color:var(--navy);font-size:13px"><?= Security::e($m['name']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted)"><?= $m['designation'] ? Security::e($m['designation']) : '' ?></div>
                        </div>
                    </div>
                </td>
                <td style="font-weight:600;color:var(--navy)"><?= (int)$m['total_leads'] ?></td>
                <td style="color:#f59e0b;font-weight:500"><?= (int)$m['active'] ?></td>
                <td style="color:#10b981;font-weight:600"><?= $mWon ?></td>
                <td style="color:#ef4444;font-weight:500"><?= $mLost ?></td>
                <td>
                    <?php if ($mClosed > 0): ?>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="conv-bar" style="width:70px"><div class="conv-fill" style="width:<?= $mConv ?>%"></div></div>
                        <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $mConv ?>%</span>
                    </div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($m['status'] === 'active'): ?>
                        <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Active</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">Suspended</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/agent/team-agent/<?= (int)$m['id'] ?>" class="btn btn-secondary btn-sm">View Details</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Quick comparison chart — leads per agent -->
<?php if (!empty($teamMembers)): ?>
<div class="card" style="padding:20px">
    <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px">Won vs Total — Agent Comparison</div>
    <canvas id="teamChart" height="80"></canvas>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const ctx = document.getElementById('teamChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= json_encode(array_column($teamMembers, 'name')) ?>,
            datasets: [
                {
                    label: 'Total Leads',
                    data: <?= json_encode(array_map(fn($m) => (int)$m['total_leads'], $teamMembers)) ?>,
                    backgroundColor: 'rgba(59,130,246,0.15)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 6,
                    order: 2
                },
                {
                    label: 'Won',
                    data: <?= json_encode(array_map(fn($m) => (int)$m['won'], $teamMembers)) ?>,
                    backgroundColor: 'rgba(16,185,129,0.85)',
                    borderColor: '#10b981',
                    borderWidth: 0,
                    borderRadius: 6,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'bottom' }, tooltip: { mode: 'index' } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
<?php endif; ?>

<?php endif; ?>

<style>
@media print {
    .sidebar,.hamburger-btn,.sidebar-overlay,.page-header-actions { display:none !important }
    .main-content { margin:0 !important; padding:20px !important }
    body::before {
        content: "HK Builders & Developers — Team Report — <?= Security::e($myTeam['name'] ?? '') ?> — <?= date('d M Y') ?>";
        display:block; font-size:13px; color:#666; margin-bottom:16px;
        border-bottom:2px solid #c9a84c; padding-bottom:10px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php';
?>
