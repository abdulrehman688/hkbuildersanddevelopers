<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Client.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../helpers/CallUi.php';

$clientModel = new Client();
$userModel   = new User();

$search      = trim($_GET['search']      ?? '');
$fileStatus  = $_GET['file_status']      ?? '';
$agentFilter = (int)($_GET['agent_id']   ?? 0);
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 20;
$offset      = ($page - 1) * $perPage;

$opts = [
    'search'      => $search      ?: null,
    'file_status' => $fileStatus  ?: null,
    'agent_id'    => $agentFilter ?: null,
    'limit'       => $perPage,
    'offset'      => $offset,
];

$clients = $clientModel->getAll($opts);
$total   = $clientModel->countAll($opts);
$stats   = $clientModel->getSummaryStats();
$agents  = $userModel->getAllAgents();
$pages   = (int)ceil($total / $perPage);

$pageTitle  = 'Clients';
$activePage = 'clients';
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Clients</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span><span class="current">Clients</span></div>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label">Total Clients</div>
        <div class="stat-value"><?= (int)$stats['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Mature Files</div>
        <div class="stat-value" style="color:#10b981"><?= (int)$stats['mature'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Immature Files</div>
        <div class="stat-value" style="color:#f59e0b"><?= (int)$stats['immature'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Booking Value</div>
        <div class="stat-value" style="font-size:16px">Rs. <?= number_format((float)$stats['total_booking']) ?></div>
    </div>
</div>

<!-- Filters -->
<form method="GET" action="<?= APP_URL ?>/admin/clients" style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px">
    <input type="text" name="search" value="<?= Security::e($search) ?>" placeholder="Search name, phone, project..." style="flex:1;min-width:180px;padding:9px 14px;border:1px solid var(--border);border-radius:9px;font-size:13px">
    <select name="file_status" style="padding:9px 14px;border:1px solid var(--border);border-radius:9px;font-size:13px">
        <option value="">All Files</option>
        <option value="mature"   <?= $fileStatus === 'mature'   ? 'selected' : '' ?>>Mature</option>
        <option value="immature" <?= $fileStatus === 'immature' ? 'selected' : '' ?>>Immature</option>
    </select>
    <select name="agent_id" style="padding:9px 14px;border:1px solid var(--border);border-radius:9px;font-size:13px">
        <option value="">All Agents</option>
        <?php foreach ($agents as $a): ?>
            <option value="<?= (int)$a['id'] ?>" <?= $agentFilter === (int)$a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="btn btn-secondary">Filter</button>
    <?php if ($search || $fileStatus || $agentFilter): ?>
        <a href="<?= APP_URL ?>/admin/clients" class="btn btn-secondary">Clear</a>
    <?php endif; ?>
</form>

<!-- Table -->
<div class="table-wrapper">
    <table class="data-table table-cards">
        <thead>
            <tr>
                <th>Client</th>
                <th>Project</th>
                <th>Unit / Block</th>
                <th>Booking Amount</th>
                <th>File Status</th>
                <th>Agent</th>
                <th>Date</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($clients as $c): ?>
            <tr>
                <td class="tc-title">
                    <div style="font-weight:500;color:var(--navy)"><?= Security::e($c['name'] ?? 'Unknown') ?></div>
                    <?php if ($c['contact_no']): ?>
                        <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($c['contact_no']) ?></div>
                    <?php endif; ?>
                </td>
                <td data-label="Project">
                    <div style="font-size:13px;font-weight:500"><?= $c['project'] ? Security::e($c['project']) : '-' ?></div>
                    <?php if ($c['category']): ?>
                        <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($c['category']) ?></div>
                    <?php endif; ?>
                </td>
                <td data-label="Unit / Block" style="font-size:13px">
                    <?= $c['unit_no'] ? Security::e($c['unit_no']) : '-' ?>
                    <?php if ($c['block']): ?>
                        <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($c['block']) ?></div>
                    <?php endif; ?>
                </td>
                <td data-label="Booking Amount" style="font-weight:600;color:var(--navy)">
                    <?= $c['booking_amount'] ? 'Rs. ' . number_format((float)$c['booking_amount']) : '-' ?>
                </td>
                <td data-label="File Status">
                    <?php if ($c['file_status'] === 'mature'): ?>
                        <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Mature</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Immature</span>
                    <?php endif; ?>
                </td>
                <td data-label="Agent" style="font-size:13px;color:var(--text-muted)"><?= $c['agent_name'] ? Security::e($c['agent_name']) : '-' ?></td>
                <td data-label="Date" style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td class="tc-actions">
                    <div style="display:flex;gap:6px">
                        <?= CallUi::button($c['contact_no'], 0, (int)$c['id']) ?>
                        <a href="<?= APP_URL ?>/admin/client/<?= (int)$c['id'] ?>" class="btn btn-secondary btn-sm">View</a>
                        <?php if ($c['lead_id']): ?>
                            <a href="<?= APP_URL ?>/admin/lead/<?= (int)$c['lead_id'] ?>" class="btn btn-secondary btn-sm">Lead</a>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($clients)): ?>
            <tr><td colspan="8" class="empty-row">No clients yet. Clients are created when agents close deals.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if ($pages > 1): ?>
<div style="display:flex;justify-content:center;gap:6px;margin-top:20px;flex-wrap:wrap">
    <?php for ($p = 1; $p <= $pages; $p++): ?>
        <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&file_status=<?= urlencode($fileStatus) ?>&agent_id=<?= $agentFilter ?>"
           style="padding:7px 13px;border-radius:8px;font-size:13px;text-decoration:none;
                  <?= $p === $page ? 'background:var(--navy);color:#fff' : 'background:var(--bg);color:var(--navy);border:1px solid var(--border)' ?>">
            <?= $p ?>
        </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
