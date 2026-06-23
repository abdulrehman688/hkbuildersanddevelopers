<?php
Security::requireLogin();
require_once __DIR__ . '/../../models/Lead.php';

$leadModel = new Lead();
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 20;
$offset    = ($page - 1) * $perPage;

$leads = $leadModel->getUnclaimedPool($perPage, $offset);
$total = $leadModel->countUnclaimed();
$pages = (int)ceil($total / $perPage);

$pageTitle  = 'Lead Pool';
$activePage = 'pool';
ob_start();
?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Lead Pool</h1>
        <div class="breadcrumb">
            <span class="current"><?= $total ?> unclaimed lead<?= $total !== 1 ? 's' : '' ?> available</span>
        </div>
    </div>
</div>

<?php if ($total > 0): ?>
<div class="alert alert-info" style="margin-bottom:20px">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
    Click <strong>Claim</strong> to assign a lead to yourself. Once claimed, no other agent can take it.
</div>
<?php endif; ?>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Phone</th><th>Source</th><th>Priority</th><th>Added</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $row): ?>
            <tr>
                <td>
                    <div class="lead-name"><?= Security::e($row['name']) ?></div>
                    <?php if ($row['company']): ?><div class="lead-sub"><?= Security::e($row['company']) ?></div><?php endif; ?>
                </td>
                <td><?= Security::e($row['phone'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($row['source_name'] ?? '—') ?></td>
                <td><span class="badge badge-<?= Security::e($row['priority']) ?>"><span class="badge-dot"></span><?= ucfirst(Security::e($row['priority'])) ?></span></td>
                <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td>
                    <form method="POST" action="<?= APP_URL ?>/agent/claim/<?= (int)$row['id'] ?>">
                        <?= Security::csrfField() ?>
                        <button type="submit" class="btn btn-gold btn-sm"
                            onclick="return confirm('Claim this lead? It will be assigned to you exclusively.')">
                            <span>Claim Lead</span>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($leads)): ?>
            <tr><td colspan="6" class="empty-row">No unclaimed leads available right now. Check back later.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?><a href="?page=<?= $page-1 ?>" class="page-btn">← Prev</a><?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)">Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="?page=<?= $page+1 ?>" class="page-btn">Next →</a><?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
