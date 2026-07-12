<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Lead.php';
require_once __DIR__ . '/../../models/User.php';

$leadModel = new Lead();
$userModel = new User();

$filterAgent   = !empty($_GET['agent_id'])  ? (int)$_GET['agent_id']  : null;
$filterDateFrom = $_GET['date_from'] ?? '';
$filterDateTo   = $_GET['date_to']   ?? '';
$filterDone     = ($_GET['status'] ?? 'pending') === 'done';

$followUps = $leadModel->getAllFollowUps([
    'agent_id'  => $filterAgent,
    'date_from' => $filterDateFrom ?: null,
    'date_to'   => $filterDateTo   ?: null,
    'done'      => $filterDone,
]);

$counts  = $leadModel->getAllFollowUpCounts();
$agents  = $userModel->getAll();
$now     = time();

$pageTitle  = 'Follow-ups';
$activePage = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Follow-ups</h1>
        <div class="breadcrumb">HK Builders CRM <span class="sep">/</span> <span class="current">All Follow-ups</span></div>
    </div>
</div>

<!-- Summary chips -->
<div style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
    <a href="?status=pending" style="text-decoration:none;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#fffbeb;color:#d97706;border:1px solid #fde68a">
        <?= $counts['pending'] ?> Pending
    </a>
    <a href="?status=pending" style="text-decoration:none;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
        <?= $counts['overdue'] ?> Overdue
    </a>
    <a href="?status=done" style="text-decoration:none;padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">
        Done
    </a>
</div>

<!-- Filters -->
<form method="GET" class="filter-bar" style="margin-bottom:20px">
    <input type="hidden" name="status" value="<?= $filterDone ? 'done' : 'pending' ?>">
    <select name="agent_id" class="form-control" style="max-width:200px">
        <option value="">All Agents</option>
        <?php foreach ($agents as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= $filterAgent === (int)$a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="date" name="date_from" class="form-control" style="max-width:160px" value="<?= Security::e($filterDateFrom) ?>" placeholder="From">
    <input type="date" name="date_to"   class="form-control" style="max-width:160px" value="<?= Security::e($filterDateTo) ?>" placeholder="To">
    <button type="submit" class="btn btn-secondary">Filter</button>
    <a href="<?= APP_URL ?>/admin/followups" class="btn btn-secondary">Reset</a>
    <!-- Pending/Done toggle -->
    <div style="margin-left:auto;display:flex;gap:6px">
        <a href="?<?= $filterAgent ? 'agent_id='.$filterAgent.'&' : '' ?>status=pending" class="btn <?= !$filterDone ? 'btn-primary' : 'btn-secondary' ?>">Pending</a>
        <a href="?<?= $filterAgent ? 'agent_id='.$filterAgent.'&' : '' ?>status=done"    class="btn <?= $filterDone  ? 'btn-primary' : 'btn-secondary' ?>">Done</a>
    </div>
</form>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Lead</th>
                <th>Phone</th>
                <th>Scheduled</th>
                <th>Note</th>
                <th>Status</th>
                <?php if ($filterDone): ?><th>Completed</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($followUps as $f):
            $ts      = strtotime($f['scheduled_at']);
            $overdue = !$filterDone && $ts < $now;
            $today   = !$filterDone && date('Y-m-d', $ts) === date('Y-m-d');
        ?>
        <tr>
            <td>
                <a href="<?= APP_URL ?>/admin/agent/<?= (int)$f['agent_id'] ?>" style="font-weight:500;color:var(--navy)">
                    <?= Security::e($f['agent_name']) ?>
                </a>
            </td>
            <td>
                <a href="<?= APP_URL ?>/admin/lead/<?= (int)$f['lead_id'] ?>" class="lead-name">
                    <?= Security::e($f['lead_name']) ?>
                </a>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($f['lead_phone'] ?? '—') ?></td>
            <td>
                <span style="font-size:12px;font-weight:600;color:<?= $overdue ? '#dc2626' : ($today ? '#d97706' : 'var(--text)') ?>">
                    <?= date('d M Y', $ts) ?>
                    <span style="color:var(--text-muted);font-weight:400"><?= date('h:i A', $ts) ?></span>
                    <?php if ($overdue): ?>
                        <span style="display:block;font-size:10px;color:#dc2626;font-weight:700;margin-top:2px">OVERDUE</span>
                    <?php elseif ($today): ?>
                        <span style="display:block;font-size:10px;color:#d97706;font-weight:700;margin-top:2px">TODAY</span>
                    <?php endif; ?>
                </span>
            </td>
            <td style="font-size:12px;color:var(--text-muted);max-width:220px"><?= Security::e($f['note'] ?? '—') ?></td>
            <td>
                <?php if ($f['is_done']): ?>
                    <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Done</span>
                <?php elseif ($overdue): ?>
                    <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">Overdue</span>
                <?php elseif ($today): ?>
                    <span class="status-pill" style="background:#fffbeb;color:#d97706;border:1px solid #fde68a">Today</span>
                <?php else: ?>
                    <span class="status-pill" style="background:var(--bg-card);color:var(--text-muted);border:1px solid var(--border)">Pending</span>
                <?php endif; ?>
            </td>
            <?php if ($filterDone): ?>
            <td style="font-size:12px;color:var(--text-muted)"><?= $f['done_at'] ? date('d M Y', strtotime($f['done_at'])) : '—' ?></td>
            <?php endif; ?>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($followUps)): ?>
            <tr><td colspan="7" class="empty-row">No follow-ups found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php'; ?>
