<?php
Security::requireLogin();
require_once __DIR__ . '/../../models/Client.php';
require_once __DIR__ . '/../../helpers/CallUi.php';

$clientModel = new Client();
$agentId     = (int)$_SESSION['user_id'];
$search      = trim($_GET['search'] ?? '');
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;

$opts = [
    'agent_id' => $agentId,
    'search'   => $search ?: null,
    'limit'    => $perPage,
    'offset'   => ($page - 1) * $perPage,
];
$clients = $clientModel->getAll($opts);
$total   = $clientModel->countAll($opts);
$pages   = (int)ceil($total / $perPage);

$pageTitle  = 'My Clients';
$activePage = 'clients';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Clients</h1>
        <div class="breadcrumb"><span class="current"><?= $total ?> client<?= $total !== 1 ? 's' : '' ?></span></div>
    </div>
</div>

<div class="filter-bar">
    <form method="GET">
        <div class="filter-row">
            <div class="filter-search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" placeholder="Search name, phone, project…" value="<?= Security::e($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="<?= APP_URL ?>/agent/clients" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-wrapper">
    <table class="data-table table-cards">
        <thead>
            <tr><th>Client</th><th>Project</th><th>Unit / Block</th><th>Booking</th><th>File</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $c): ?>
            <tr>
                <td class="tc-title">
                    <div class="lead-name"><?= Security::e($c['name'] ?? 'Unknown') ?></div>
                    <?php if ($c['contact_no']): ?><div class="lead-sub"><?= Security::e($c['contact_no']) ?></div><?php endif; ?>
                </td>
                <td data-label="Project"><?= $c['project'] ? Security::e($c['project']) : '-' ?></td>
                <td data-label="Unit / Block">
                    <?= $c['unit_no'] ? Security::e($c['unit_no']) : '-' ?>
                    <?php if ($c['block']): ?><div class="lead-sub"><?= Security::e($c['block']) ?></div><?php endif; ?>
                </td>
                <td data-label="Booking" style="font-weight:600;color:var(--navy)"><?= $c['booking_amount'] ? 'Rs. ' . number_format((float)$c['booking_amount']) : '-' ?></td>
                <td data-label="File">
                    <?php if ($c['file_status'] === 'mature'): ?>
                        <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Mature</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Immature</span>
                    <?php endif; ?>
                </td>
                <td class="tc-actions">
                    <div style="display:flex;gap:6px">
                        <?= CallUi::button($c['contact_no'], 0, (int)$c['id']) ?>
                        <?php if ($c['lead_id']): ?>
                            <a href="<?= APP_URL ?>/agent/lead/<?= (int)$c['lead_id'] ?>" class="btn btn-secondary btn-sm">Open</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($clients)): ?>
            <tr><td colspan="6" class="empty-row">No clients yet. A lead becomes a client when you mark it Won and fill in the booking.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>&search=<?= urlencode($search) ?>" class="page-btn">← Prev</a><?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>&search=<?= urlencode($search) ?>" class="page-btn">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
