<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Lead.php';

$leadModel = new Lead();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
$statusId = (int)($_GET['status_id'] ?? 0);

$agents   = $leadModel->getAgentPerformance($dateFrom, $dateTo);
$sources  = $leadModel->getSourceBreakdown($dateFrom, $dateTo);
$statuses = $leadModel->getStatusBreakdown($dateFrom, $dateTo);
$trend    = $leadModel->getLeadTrend(30);
$allStats = $leadModel->getDashboardStats();

// Totals for source chart max
$totalLeads = array_sum(array_column($sources, 'total')) ?: 1;
$totalStatusLeads = array_sum(array_column($statuses, 'total')) ?: 1;

// Build export URL preserving filters
$exportUrl = APP_URL . '/admin/reports?export=csv'
    . ($dateFrom  ? '&date_from=' . urlencode($dateFrom)  : '')
    . ($dateTo    ? '&date_to='   . urlencode($dateTo)    : '')
    . ($statusId  ? '&status_id=' . $statusId : '');

// Build trend data for chart (last 30 days filled)
$trendMap = [];
foreach ($trend as $t) $trendMap[$t['date']] = (int)$t['total'];
$trendDays = [];
for ($i = 29; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $trendDays[] = ['date' => $d, 'label' => date('d M', strtotime($d)), 'total' => $trendMap[$d] ?? 0];
}
$trendMax = max(array_column($trendDays, 'total') ?: [1]);

$pageTitle  = 'Reports';
$activePage = 'reports';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Reports</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Reports</span></div>
    </div>
    <div class="page-header-actions">
        <a href="<?= $exportUrl ?>" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Export CSV
        </a>
    </div>
</div>

<!-- Date Filter -->
<div class="filter-bar" style="margin-bottom:24px">
    <form method="GET" action="<?= APP_URL ?>/admin/reports">
        <div class="filter-row">
            <span style="font-size:12px;font-weight:600;color:var(--text-muted);letter-spacing:0.5px;text-transform:uppercase">Filter by Date</span>
            <div class="filter-date-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <input type="date" name="date_from" value="<?= Security::e($dateFrom) ?>" placeholder="From">
            </div>
            <span style="color:var(--text-muted);font-size:12px">to</span>
            <div class="filter-date-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <input type="date" name="date_to" value="<?= Security::e($dateTo) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Apply</button>
            <?php if ($dateFrom || $dateTo): ?>
                <a href="<?= APP_URL ?>/admin/reports" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>

            <!-- Quick ranges -->
            <div style="display:flex;gap:6px;margin-left:8px">
                <?php
                $ranges = [
                    'Today'      => [date('Y-m-d'), date('Y-m-d')],
                    'This Week'  => [date('Y-m-d', strtotime('monday this week')), date('Y-m-d')],
                    'This Month' => [date('Y-m-01'), date('Y-m-d')],
                    'Last Month' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('last month'))],
                ];
                foreach ($ranges as $label => [$from, $to]):
                    $active = $dateFrom === $from && $dateTo === $to;
                ?>
                <a href="<?= APP_URL ?>/admin/reports?date_from=<?= $from ?>&date_to=<?= $to ?>"
                   class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-secondary' ?>"
                   style="font-size:11px;padding:5px 10px">
                    <?= $label ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </form>
</div>

<!-- Summary Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card gold">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg></div>
        <div class="stat-number"><?= (int)$allStats['total'] ?></div>
        <div class="stat-label">Total Leads</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg></div>
        <div class="stat-number"><?= (int)$allStats['unclaimed'] ?></div>
        <div class="stat-label">Unclaimed</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="stat-number"><?= (int)$allStats['won'] ?></div>
        <div class="stat-label">Won</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="stat-number"><?= (int)$allStats['lost'] ?></div>
        <div class="stat-label">Lost</div>
    </div>
    <?php
    $totalWon  = (int)$allStats['won'];
    $totalLost = (int)$allStats['lost'];
    $closed    = $totalWon + $totalLost;
    $overallConv = $closed > 0 ? round(($totalWon / $closed) * 100) : 0;
    ?>
    <div class="stat-card purple">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg></div>
        <div class="stat-number"><?= $overallConv ?>%</div>
        <div class="stat-label">Conversion Rate</div>
    </div>
</div>

<!-- Charts row -->
<div class="two-col" style="margin-bottom:24px;align-items:start">

    <!-- Lead Trend Chart -->
    <div class="form-card">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18L9 11.25l4.306 4.307a11.95 11.95 0 015.814-5.519l2.74-1.22m0 0l-5.94-2.28m5.94 2.28l-2.28 5.941"/></svg>
            <h3>Lead Volume — Last 30 Days</h3>
        </div>
        <div class="form-card-body">
            <div class="bar-chart">
                <?php foreach ($trendDays as $i => $day): ?>
                    <?php $h = $trendMax > 0 ? round(($day['total'] / $trendMax) * 100) : 0; ?>
                    <div class="bar-col" title="<?= $day['label'] ?>: <?= $day['total'] ?> lead<?= $day['total'] !== 1 ? 's' : '' ?>">
                        <div class="bar-fill" style="height:<?= max($h, $day['total'] > 0 ? 4 : 0) ?>%"></div>
                        <?php if ($i % 5 === 0): ?>
                            <div class="bar-label"><?= date('d', strtotime($day['date'])) ?></div>
                        <?php else: ?>
                            <div class="bar-label"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php if ($trendMax === 0 || array_sum(array_column($trendDays, 'total')) === 0): ?>
                <p style="text-align:center;color:var(--text-muted);font-size:13px;padding:20px 0">No leads in the last 30 days.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Breakdown -->
    <div class="form-card">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/></svg>
            <h3>Leads by Status</h3>
        </div>
        <div class="form-card-body" style="padding-bottom:8px">
            <?php foreach ($statuses as $st): ?>
                <?php $pct = round(($st['total'] / $totalStatusLeads) * 100); ?>
                <div class="status-bar-row">
                    <div class="status-bar-label">
                        <span class="status-dot" style="background:<?= Security::e($st['color_hex']) ?>"></span>
                        <?= Security::e($st['name']) ?>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width:<?= $pct ?>%;background:<?= Security::e($st['color_hex']) ?>"></div>
                    </div>
                    <div class="status-bar-count"><?= (int)$st['total'] ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($statuses)): ?>
                <p style="color:var(--text-muted);font-size:13px;text-align:center;padding:16px 0">No data.</p>
            <?php endif; ?>
        </div>
    </div>

</div>

<!-- Agent Performance -->
<div class="section-header"><h2>Agent Performance</h2></div>
<div class="table-wrapper" style="margin-bottom:24px">
    <table class="data-table">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Total Leads</th>
                <th>Active</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Conversion</th>
                <th>Last Activity</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($agents as $agent): ?>
            <?php
            $won    = (int)$agent['won'];
            $lost   = (int)$agent['lost'];
            $closed = $won + $lost;
            $conv   = $closed > 0 ? round(($won / $closed) * 100) : 0;
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="sidebar-avatar" style="width:32px;height:32px;font-size:12px;flex-shrink:0">
                            <?= strtoupper(substr($agent['name'], 0, 1)) ?>
                        </div>
                        <span class="lead-name"><?= Security::e($agent['name']) ?></span>
                    </div>
                </td>
                <td style="font-weight:600;color:var(--navy)"><?= (int)$agent['total'] ?></td>
                <td style="color:#8b5cf6;font-weight:500"><?= (int)$agent['active'] ?></td>
                <td style="color:#10b981;font-weight:500"><?= $won ?></td>
                <td style="color:#ef4444;font-weight:500"><?= $lost ?></td>
                <td>
                    <?php if ($closed > 0): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="conv-bar" style="width:80px">
                                <div class="conv-fill" style="width:<?= $conv ?>%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:600;color:var(--navy);min-width:32px"><?= $conv ?>%</span>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px">No closed leads</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px">
                    <?= $agent['last_activity'] ? date('d M Y', strtotime($agent['last_activity'])) : '—' ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($agents)): ?>
            <tr><td colspan="7" class="empty-row">No agents yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Lead Source Breakdown -->
<div class="section-header"><h2>Lead Source Breakdown</h2></div>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Source</th>
                <th>Total Leads</th>
                <th>Share</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Conversion</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sources as $src): ?>
            <?php
            $won   = (int)$src['won'];
            $lost  = (int)$src['lost'];
            $cl    = $won + $lost;
            $conv  = $cl > 0 ? round(($won / $cl) * 100) : 0;
            $share = round(($src['total'] / $totalLeads) * 100);
            ?>
            <tr>
                <td class="lead-name"><?= Security::e($src['source_name']) ?></td>
                <td style="font-weight:600;color:var(--navy)"><?= (int)$src['total'] ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:8px">
                        <div class="conv-bar" style="width:80px;background:#e5e7eb">
                            <div style="height:100%;background:var(--gold);border-radius:3px;width:<?= $share ?>%"></div>
                        </div>
                        <span style="font-size:12px;color:var(--text-muted)"><?= $share ?>%</span>
                    </div>
                </td>
                <td style="color:#10b981;font-weight:500"><?= $won ?></td>
                <td style="color:#ef4444;font-weight:500"><?= $lost ?></td>
                <td>
                    <?php if ($cl > 0): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="conv-bar"><div class="conv-fill" style="width:<?= $conv ?>%"></div></div>
                            <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $conv ?>%</span>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($sources)): ?>
            <tr><td colspan="6" class="empty-row">No data yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
