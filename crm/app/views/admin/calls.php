<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../helpers/CallUi.php';

$callModel = new ContactLog();
$agents    = (new User())->getAllAgents();

// Default: this month
$filters = [
    'agent_id'  => (int)($_GET['agent_id'] ?? 0) ?: null,
    'date_from' => $_GET['date_from'] ?? date('Y-m-01'),
    'date_to'   => $_GET['date_to']   ?? date('Y-m-d'),
    'outcome'   => $_GET['outcome']   ?? '',
];
foreach (['date_from', 'date_to'] as $k) {
    if ($filters[$k] !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$k])) $filters[$k] = '';
}

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 50;

$totals  = $callModel->getTotals($filters);
$byAgent = $callModel->getAgentSummary($filters);
$log     = $callModel->getLog($filters, $perPage, ($page - 1) * $perPage);
$pages   = (int)ceil(((int)$totals['calls']) / $perPage);

$avgSec  = (int)$totals['answered'] > 0 ? (int)round($totals['talk_sec'] / $totals['answered']) : 0;

$qs = fn(array $o = []) => '?' . http_build_query(array_merge([
    'agent_id'  => $filters['agent_id'] ?? '',
    'date_from' => $filters['date_from'],
    'date_to'   => $filters['date_to'],
    'outcome'   => $filters['outcome'],
], $o));

$pageTitle  = 'Call Log';
$activePage = 'calls';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Call Log</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span><span class="current">Calls with leads and clients</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">Print</button>
    </div>
</div>

<div class="filter-bar">
    <form method="GET" action="<?= APP_URL ?>/admin/calls">
        <div class="filter-row">
            <select name="agent_id" class="filter-input">
                <option value="">All Agents</option>
                <?php foreach ($agents as $a): ?>
                    <option value="<?= (int)$a['id'] ?>" <?= ($filters['agent_id'] ?? 0) === (int)$a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="outcome" class="filter-input">
                <option value="">All Results</option>
                <?php foreach (ContactLog::OUTCOMES as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $filters['outcome'] === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
                <option value="pending" <?= $filters['outcome'] === 'pending' ? 'selected' : '' ?>>Not updated</option>
            </select>
            <input type="date" name="date_from" class="filter-input" value="<?= Security::e($filters['date_from']) ?>" title="From">
            <input type="date" name="date_to"   class="filter-input" value="<?= Security::e($filters['date_to']) ?>" title="To">
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
            <a href="<?= APP_URL ?>/admin/calls" class="btn btn-secondary btn-sm">This Month</a>
        </div>
    </form>
</div>

<div class="chip-row" style="display:flex;flex-wrap:wrap;gap:10px;margin:16px 0 20px">
    <?php foreach ([
        ['Total Calls',     (string)(int)$totals['calls'],                    'var(--navy)'],
        ['Answered',        (string)(int)$totals['answered'],                 '#16a34a'],
        ['Total Talk Time', ContactLog::fmtDuration((int)$totals['talk_sec']), 'var(--gold)'],
        ['Avg per Answered Call', ContactLog::fmtDuration($avgSec),           'var(--text)'],
    ] as [$label, $value, $color]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:170px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= Security::e($value) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="section-header"><h2>By Agent</h2></div>
<div class="card" style="margin-bottom:24px">
    <div style="overflow-x:auto">
        <table class="data-table table-cards">
            <thead><tr><th>Agent</th><th>Calls</th><th>Answered</th><th>Not Answered</th><th>People Called</th><th>Talk Time</th><th>Last Call</th></tr></thead>
            <tbody>
            <?php foreach ($byAgent as $a): ?>
                <tr>
                    <td class="tc-title"><a href="<?= Security::e($qs(['agent_id' => (int)$a['id'], 'page' => 1])) ?>" style="color:var(--navy);font-weight:600"><?= Security::e($a['name']) ?></a></td>
                    <td data-label="Calls"><?= (int)$a['calls'] ?></td>
                    <td data-label="Answered" style="color:#16a34a;font-weight:600"><?= (int)$a['answered'] ?></td>
                    <td data-label="Not Answered" style="color:#dc2626"><?= (int)$a['calls'] - (int)$a['answered'] ?></td>
                    <td data-label="People Called"><?= (int)$a['contacts'] ?></td>
                    <td data-label="Talk Time" style="font-weight:700;color:var(--gold)"><?= ContactLog::fmtDuration((int)$a['talk_sec']) ?></td>
                    <td data-label="Last Call" style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d M Y, h:i A', strtotime($a['last_call'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$byAgent): ?>
                <tr><td colspan="7" class="empty-row">No calls in this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="section-header"><h2>Every Call</h2></div>
<div class="card">
    <div style="overflow-x:auto">
        <table class="data-table table-cards">
            <thead><tr><th>Date &amp; Time</th><th>Agent</th><th>Lead / Client</th><th>Phone</th><th>Result</th><th>Talk Time</th><th>Note</th></tr></thead>
            <tbody>
            <?php foreach ($log as $r):
                $link = $r['client_id'] ? APP_URL . '/admin/client/' . (int)$r['client_id']
                      : ($r['lead_id'] ? APP_URL . '/admin/lead/' . (int)$r['lead_id'] : null);
            ?>
                <tr>
                    <td class="tc-title" style="white-space:nowrap"><?= date('d M Y, h:i A', strtotime($r['started_at'])) ?></td>
                    <td data-label="Agent"><?= Security::e($r['agent_name']) ?></td>
                    <td data-label="Lead / Client">
                        <?php if ($link): ?>
                            <a href="<?= $link ?>" style="color:var(--navy);font-weight:500"><?= Security::e($r['contact_name'] ?: 'Unknown') ?></a>
                            <div class="lead-sub"><?= $r['client_id'] ? 'Client' : 'Lead' ?></div>
                        <?php else: ?>-<?php endif; ?>
                    </td>
                    <td data-label="Phone" style="white-space:nowrap"><?= Security::e($r['phone'] ?? '-') ?></td>
                    <td data-label="Result"><?= CallUi::outcomePill($r['outcome']) ?><?= CallUi::direction($r) ?></td>
                    <td data-label="Talk Time" style="font-weight:600"><?= CallUi::talkTime($r) ?></td>
                    <td class="tc-full" data-label="Note" style="font-size:12px;color:var(--text-muted)"><?= Security::e($r['note'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$log): ?>
                <tr><td colspan="7" class="empty-row">No calls in this period.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="<?= Security::e($qs(['page' => $page - 1])) ?>" class="page-btn">Prev</a><?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="<?= Security::e($qs(['page' => $page + 1])) ?>" class="page-btn">Next</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media print {
    .filter-bar, .pagination { display: none !important; }
    body::before {
        content: "HK Builders & Developers  -  Call Log  -  <?= Security::e($filters['date_from']) ?> to <?= Security::e($filters['date_to']) ?>";
        display: block; font-size: 12px; font-weight: 600; color: #002147;
        border-bottom: 2px solid #c9a84c; padding-bottom: 8px; margin-bottom: 14px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
