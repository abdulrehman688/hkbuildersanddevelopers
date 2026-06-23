<?php
Security::requireLogin();
require_once __DIR__ . '/../../models/Lead.php';

$leadModel = new Lead();
$agentId   = (int)$_SESSION['user_id'];

$search   = trim($_GET['search'] ?? '');
$statusId = (int)($_GET['status'] ?? 0);
$priority = $_GET['priority'] ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;

$filters = [
    'agent_id'  => $agentId,
    'search'    => $search,
    'status_id' => $statusId ?: null,
    'priority'  => in_array($priority, ['hot','warm','cold']) ? $priority : null,
    'limit'     => $perPage,
    'offset'    => ($page - 1) * $perPage,
];

$leads    = $leadModel->getAll($filters);
$total    = $leadModel->countAll($filters);
$pages    = (int)ceil($total / $perPage);
$statuses = $leadModel->getStatuses();

$pageTitle  = 'My Leads';
$activePage = 'leads';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Leads</h1>
        <div class="breadcrumb"><span class="current"><?= $total ?> lead<?= $total !== 1 ? 's' : '' ?></span></div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/agent/pool" class="btn btn-primary">Browse Pool</a>
    </div>
</div>

<div class="filter-bar">
    <form method="GET">
        <div class="filter-row">
            <div class="filter-search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" placeholder="Search name, phone…" value="<?= Security::e($search) ?>">
            </div>
            <div class="filter-select-wrap">
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= $statusId === (int)$s['id'] ? 'selected' : '' ?>><?= Security::e($s['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-select-wrap">
                <select name="priority" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="hot"  <?= $priority === 'hot'  ? 'selected' : '' ?>>Hot</option>
                    <option value="warm" <?= $priority === 'warm' ? 'selected' : '' ?>>Warm</option>
                    <option value="cold" <?= $priority === 'cold' ? 'selected' : '' ?>>Cold</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search || $statusId || $priority): ?>
                <a href="<?= APP_URL ?>/agent/leads" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Phone</th><th>Source</th><th>Priority</th><th>Status</th><th>Claimed</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $row): ?>
            <tr onclick="window.location='<?= APP_URL ?>/agent/lead/<?= (int)$row['id'] ?>'">
                <td>
                    <div class="lead-name"><?= Security::e($row['name']) ?></div>
                    <?php if ($row['email']): ?><div class="lead-sub"><?= Security::e($row['email']) ?></div><?php endif; ?>
                </td>
                <td><?= Security::e($row['phone'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($row['source_name'] ?? '—') ?></td>
                <td><span class="badge badge-<?= Security::e($row['priority']) ?>"><span class="badge-dot"></span><?= ucfirst(Security::e($row['priority'])) ?></span></td>
                <td><span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44"><?= Security::e($row['status_name']) ?></span></td>
                <td style="color:var(--text-muted);font-size:12px"><?= $row['claimed_at'] ? date('d M Y', strtotime($row['claimed_at'])) : '—' ?></td>
                <td><a href="<?= APP_URL ?>/agent/lead/<?= (int)$row['id'] ?>" onclick="event.stopPropagation()">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($leads)): ?>
            <tr><td colspan="7" class="empty-row">No leads found. <a href="<?= APP_URL ?>/agent/pool">Browse the pool</a></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusId ?>&priority=<?= urlencode($priority) ?>" class="page-btn">← Prev</a><?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>&status=<?= $statusId ?>&priority=<?= urlencode($priority) ?>" class="page-btn">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
