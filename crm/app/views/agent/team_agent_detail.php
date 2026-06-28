<?php
Security::requireLogin();
// $detail, $agentLeads, $months, $monthlyTrend, $followUpStats, $sourceBreakdown, $wonDeals
// all injected by AgentController::teamAgentDetail()
$agent  = $detail;
$stats  = $agent['stats'];
$month  = $_GET['month'] ?? '';

$pageTitle  = 'Agent: ' . ($agent['name'] ?? 'Detail');
$activePage = 'team';
ob_start();

$total        = (int)$stats['total_leads'];
$won          = (int)$stats['st_won'];
$lost         = (int)$stats['st_lost'];
$closed       = $won + $lost;
$conversion   = $closed > 0 ? round(($won / $closed) * 100) : 0;
$initials     = strtoupper(substr($agent['name'] ?? 'A', 0, 1));
$totalBooking = array_sum(array_column($wonDeals, 'investment_amount'));
$estComm      = $totalBooking * ((float)$agent['commission_rate'] / 100);
$fuTotal      = (int)$followUpStats['total'];
$fuRate       = $fuTotal > 0 ? round(((int)$followUpStats['done'] / $fuTotal) * 100) : 0;
$chartLabels  = json_encode(array_column($monthlyTrend, 'label'));
$chartTotal   = json_encode(array_map(fn($r) => (int)$r['total'], $monthlyTrend));
$chartWon     = json_encode(array_map(fn($r) => (int)$r['won'],   $monthlyTrend));
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($agent['name'] ?? 'Agent') ?></h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <a href="<?= APP_URL ?>/agent/team">My Team</a>
            <span class="sep">/</span>
            <span class="current"><?= Security::e($agent['name'] ?? 'Detail') ?></span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/agent/team" class="btn btn-secondary">← Back to Team</a>
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
    </div>
</div>

<!-- Month Filter -->
<form method="GET" action="<?= APP_URL ?>/agent/team-agent/<?= (int)$agent['id'] ?>" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <label style="font-size:13px;font-weight:500;color:var(--text-muted)">Filter by month:</label>
    <select name="month" onchange="this.form.submit()" style="font-size:13px;padding:7px 12px;border:1px solid var(--border);border-radius:8px;background:#fff">
        <option value="">All Time</option>
        <?php foreach ($months as $m): ?>
            <option value="<?= Security::e($m['ym']) ?>" <?= $month === $m['ym'] ? 'selected' : '' ?>><?= Security::e($m['label']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($month): ?>
        <a href="<?= APP_URL ?>/agent/team-agent/<?= (int)$agent['id'] ?>" style="font-size:13px;color:var(--text-muted)">Clear</a>
    <?php endif; ?>
</form>

<div style="display:grid;grid-template-columns:290px 1fr;gap:24px;align-items:start">

    <!-- LEFT: Profile + Follow-ups -->
    <div style="display:flex;flex-direction:column;gap:18px">

        <!-- Profile Card -->
        <div class="card" style="padding:20px">
            <div style="text-align:center;padding:0 0 16px">
                <div class="sidebar-avatar" style="width:68px;height:68px;font-size:27px;margin:0 auto 12px">
                    <?= Security::e($initials) ?>
                </div>
                <div style="font-size:18px;font-weight:600;color:var(--navy)"><?= Security::e($agent['name'] ?? '—') ?></div>
                <div style="margin-top:6px">
                    <span class="status-pill" style="background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fa">Agent</span>
                    <?php if ($agent['status'] === 'suspended'): ?>
                        <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;margin-left:4px">Suspended</span>
                    <?php endif; ?>
                </div>
                <?php if ($agent['designation']): ?>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:6px"><?= Security::e($agent['designation']) ?></div>
                <?php endif; ?>
            </div>
            <hr style="border:none;border-top:1px solid var(--border);margin:0 0 14px">
            <div style="display:flex;flex-direction:column;gap:10px">
                <?php foreach ([
                    ['Phone',    $agent['phone']          ?? null],
                    ['Email',    $agent['email']          ?? null],
                    ['CNIC',     $agent['cnic']           ?? null],
                    ['Address',  $agent['address']        ?? null],
                    ['Joined',   date('d M Y', strtotime($agent['created_at']))],
                ] as [$label, $value]): if (!$value) continue; ?>
                <div style="display:flex;gap:10px;font-size:13px">
                    <span style="color:var(--text-muted);width:60px;flex-shrink:0"><?= $label ?></span>
                    <span style="color:var(--navy);font-weight:500;word-break:break-word"><?= Security::e($value) ?></span>
                </div>
                <?php endforeach; ?>
                <!-- Salary info visible to manager -->
                <div style="border-top:1px solid var(--border);padding-top:10px;margin-top:4px">
                    <div style="display:flex;gap:10px;font-size:13px;margin-bottom:6px">
                        <span style="color:var(--text-muted);width:60px;flex-shrink:0">Salary</span>
                        <span style="color:var(--navy);font-weight:500">Rs. <?= number_format((float)$agent['base_salary']) ?>/mo</span>
                    </div>
                    <div style="display:flex;gap:10px;font-size:13px">
                        <span style="color:var(--text-muted);width:60px;flex-shrink:0">Comm.</span>
                        <span style="color:var(--navy);font-weight:500"><?= number_format((float)$agent['commission_rate'], 1) ?>%</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Follow-up Discipline -->
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:14px">Follow-up Discipline</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
                <?php foreach ([
                    ['Total',   $followUpStats['total'],   'var(--navy)'],
                    ['Done',    $followUpStats['done'],    '#10b981'],
                    ['Pending', $followUpStats['pending'], '#f59e0b'],
                    ['Overdue', $followUpStats['overdue'], '#ef4444'],
                ] as [$label, $val, $color]): ?>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$val ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">Completion rate</div>
            <div style="background:#f0f0f0;border-radius:20px;height:8px;overflow:hidden">
                <div style="height:100%;width:<?= $fuRate ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:20px"></div>
            </div>
            <div style="font-size:13px;font-weight:600;color:#10b981;margin-top:6px"><?= $fuRate ?>% completed</div>
            <?php if ((int)$followUpStats['overdue'] > 0): ?>
            <div style="margin-top:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:12px;color:#dc2626">
                ⚠ <?= (int)$followUpStats['overdue'] ?> overdue follow-up<?= $followUpStats['overdue'] > 1 ? 's' : '' ?> need attention
            </div>
            <?php endif; ?>
        </div>

    </div>

    <!-- RIGHT: Analytics -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Summary Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Leads</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Won</div>
                <div class="stat-value" style="color:#10b981"><?= $won ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lost / Dead</div>
                <div class="stat-value" style="color:#ef4444"><?= (int)$stats['not_interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Pipeline</div>
                <div class="stat-value" style="color:#f59e0b"><?= (int)$stats['interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Conversion</div>
                <div class="stat-value"><?= $conversion ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Est. Commission</div>
                <div class="stat-value" style="font-size:15px;color:#10b981">Rs. <?= number_format($estComm) ?></div>
            </div>
        </div>

        <!-- Monthly Trend Chart -->
        <?php if (!empty($monthlyTrend)): ?>
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px">Monthly Trend — Last 6 Months</div>
            <canvas id="trendChart" height="90"></canvas>
        </div>
        <?php endif; ?>

        <!-- Status Breakdown -->
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:14px">Status Breakdown</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:10px">
                <?php foreach ([
                    ['New',         $stats['st_new'],         '#3B82F6'],
                    ['Contacted',   $stats['st_contacted'],   '#8B5CF6'],
                    ['Qualified',   $stats['st_qualified'],   '#F59E0B'],
                    ['Proposal',    $stats['st_proposal'],    '#EC4899'],
                    ['Negotiation', $stats['st_negotiation'], '#F97316'],
                    ['Won',         $stats['st_won'],         '#10B981'],
                    ['Lost',        $stats['st_lost'],        '#EF4444'],
                    ['Dead',        $stats['st_dead'],        '#6B7280'],
                ] as [$label, $count, $color]): ?>
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:12px;border-left:3px solid <?= $color ?>">
                    <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$count ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Source Breakdown -->
        <?php if (!empty($sourceBreakdown)): ?>
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">Lead Source Breakdown</div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr><th>Source</th><th>Total</th><th>Won</th><th>Lost</th><th>Conversion</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sourceBreakdown as $src):
                        $sWon = (int)$src['won']; $sLost = (int)$src['lost'];
                        $sClosed = $sWon + $sLost;
                        $sConv = $sClosed > 0 ? round(($sWon / $sClosed) * 100) : 0;
                    ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy)"><?= Security::e($src['source_name']) ?></td>
                        <td style="font-weight:600"><?= (int)$src['total'] ?></td>
                        <td style="color:#10b981;font-weight:500"><?= $sWon ?></td>
                        <td style="color:#ef4444;font-weight:500"><?= $sLost ?></td>
                        <td>
                            <?php if ($sClosed > 0): ?>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="conv-bar" style="width:60px"><div class="conv-fill" style="width:<?= $sConv ?>%"></div></div>
                                <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $sConv ?>%</span>
                            </div>
                            <?php else: ?>—<?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Won Deals -->
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:8px">
                <span style="font-size:14px;font-weight:600;color:var(--navy)">Won Deals (<?= count($wonDeals) ?>)</span>
                <?php if ($totalBooking > 0): ?>
                <span style="font-size:12px;color:var(--text-muted)">
                    Total: <b style="color:var(--navy)">Rs. <?= number_format($totalBooking) ?></b>
                    &nbsp;·&nbsp; Est. Comm: <b style="color:#10b981">Rs. <?= number_format($estComm) ?></b>
                </span>
                <?php endif; ?>
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr><th>Client</th><th>Project</th><th>Category</th><th>Investment</th><th>Est. Comm.</th><th>Closed On</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wonDeals as $deal):
                        $dComm = $deal['investment_amount']
                            ? (float)$deal['investment_amount'] * ((float)$agent['commission_rate'] / 100) : 0;
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;color:var(--navy)"><?= Security::e($deal['name']) ?></div>
                            <?php if ($deal['phone']): ?><div style="font-size:11px;color:var(--text-muted)"><?= Security::e($deal['phone']) ?></div><?php endif; ?>
                        </td>
                        <td style="font-size:13px"><?= $deal['project'] ? Security::e($deal['project']) : '—' ?></td>
                        <td style="font-size:13px"><?= $deal['category'] ? Security::e($deal['category']) : '—' ?></td>
                        <td style="font-weight:600;color:var(--navy)"><?= $deal['investment_amount'] ? 'Rs. '.number_format((float)$deal['investment_amount']) : '—' ?></td>
                        <td style="color:#10b981;font-weight:500"><?= $dComm > 0 ? 'Rs. '.number_format($dComm) : '—' ?></td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($deal['updated_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($wonDeals)): ?>
                        <tr><td colspan="6" class="empty-row">No won deals yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- All Leads -->
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">
                All Leads<?= $month ? ' — '.Security::e($month) : '' ?> (<?= count($agentLeads) ?>)
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr><th>Name / Phone</th><th>Project</th><th>Category</th><th>Status</th><th>Date</th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($agentLeads as $row): ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;color:var(--navy)"><?= Security::e($row['name']) ?></div>
                            <?php if ($row['phone']): ?><div style="font-size:11px;color:var(--text-muted)"><?= Security::e($row['phone']) ?></div><?php endif; ?>
                        </td>
                        <td style="font-size:13px"><?= $row['project'] ? Security::e($row['project']) : '—' ?></td>
                        <td style="font-size:13px"><?= $row['category'] ? Security::e($row['category']) : '—' ?></td>
                        <td>
                            <span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44">
                                <?= Security::e($row['status_name']) ?>
                            </span>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($agentLeads)): ?>
                        <tr><td colspan="5" class="empty-row">No leads<?= $month ? ' for this month' : '' ?>.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php if (!empty($monthlyTrend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= $chartLabels ?>,
            datasets: [
                { label:'Leads', data:<?= $chartTotal ?>, backgroundColor:'rgba(59,130,246,0.15)', borderColor:'#3b82f6', borderWidth:2, borderRadius:6, order:2 },
                { label:'Won',   data:<?= $chartWon ?>,   backgroundColor:'rgba(16,185,129,0.85)', borderColor:'#10b981', borderWidth:0, borderRadius:6, order:1 }
            ]
        },
        options: {
            responsive:true,
            plugins:{ legend:{ position:'bottom' }, tooltip:{ mode:'index' } },
            scales:{ y:{ beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'#f0f0f0' } }, x:{ grid:{ display:false } } }
        }
    });
})();
</script>
<?php endif; ?>

<style>
@media print {
    .sidebar,.hamburger-btn,.sidebar-overlay,.page-header-actions,form[method="GET"] { display:none !important }
    .main-content { margin:0 !important; padding:20px !important }
    body::before {
        content:"HK Builders & Developers — Agent Report: <?= Security::e($agent['name'] ?? '') ?> — <?= date('d M Y') ?>";
        display:block; font-size:13px; color:#666; margin-bottom:16px;
        border-bottom:2px solid #c9a84c; padding-bottom:10px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php';
?>
