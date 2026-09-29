<?php
Security::requireAdmin();

$pageTitle  = 'Builder - ' . ($builderRow['name'] ?? '');
$activePage = 'builders';
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$statusLabels = ['active' => 'Active', 'completed' => 'Completed', 'on_hold' => 'On Hold'];
$typeLabels   = ['advance' => 'Advance', 'installment' => 'Installment', 'final' => 'Final', 'other' => 'Other'];
$payLabels    = ['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'];
$payColors    = [
    'paid'    => ['rgba(34,197,94,.12)',  '#16a34a'],
    'partial' => ['rgba(245,158,11,.14)', '#b45309'],
    'unpaid'  => ['rgba(220,38,38,.10)',  '#dc2626'],
];

$totalCommission = array_sum(array_column($units, 'commission_amount'));
$totalPaid       = array_sum(array_column($payments, 'amount'));
$unpaid          = max(0, $totalCommission - $totalPaid);

ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= Security::e($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($builderRow['name']) ?></h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/builders">Builders</a> <span class="sep">/</span> <span class="current"><?= Security::e($builderRow['name']) ?></span></div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/admin/builders/statement/<?= $builderRow['id'] ?>" target="_blank" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Statement
        </a>
        <a href="<?= APP_URL ?>/admin/builders" class="btn btn-secondary">Back to Builders</a>
    </div>
</div>

<!-- Builder info card -->
<div class="card" style="margin-bottom:16px;padding:20px 22px">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:20px">
        <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Contact Person</div>
            <div style="font-weight:600"><?= Security::e($builderRow['contact_person'] ?? '-') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Phone</div>
            <div style="font-weight:600"><?= Security::e($builderRow['phone'] ?? '-') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Email</div>
            <div style="font-weight:600"><?= Security::e($builderRow['email'] ?? '-') ?></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Projects / Units</div>
            <div style="font-weight:700;font-size:18px;color:#6366f1"><?= count($projects) ?> <span style="color:var(--text-muted);font-weight:400">/</span> <span style="color:#8b5cf6"><?= count($units) ?></span></div>
        </div>
        <div>
            <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:4px">Status</div>
            <span style="padding:3px 10px;border-radius:12px;font-size:12px;font-weight:600;
                background:<?= $builderRow['status']==='active' ? 'rgba(34,197,94,.12)' : 'rgba(156,163,175,.15)' ?>;
                color:<?= $builderRow['status']==='active' ? '#16a34a' : '#6b7280' ?>">
                <?= ucfirst($builderRow['status']) ?>
            </span>
        </div>
    </div>
    <?php if (!empty($builderRow['address'])): ?>
    <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--border-light);font-size:13px;color:var(--text-muted)">
        <?= Security::e($builderRow['address']) ?>
    </div>
    <?php endif; ?>
    <?php if (!empty($builderRow['notes'])): ?>
    <div style="margin-top:8px;font-size:13px;color:var(--text-muted)"><?= Security::e($builderRow['notes']) ?></div>
    <?php endif; ?>
</div>

<!-- Commission summary -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:28px">
    <?php foreach ([
        ['Total Commission', $totalCommission, 'var(--gold)'],
        ['Total Paid',       $totalPaid,       '#16a34a'],
        ['Unpaid',           $unpaid,          '#dc2626'],
    ] as [$label, $amount, $color]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:170px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= $pkr($amount) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Projects -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h2 style="font-size:16px;font-weight:700;margin:0">Projects</h2>
    <a href="<?= APP_URL ?>/admin/builders/projects?builder_id=<?= $builderRow['id'] ?>" class="btn btn-sm btn-secondary">Manage Projects</a>
</div>
<div class="card" style="padding:0;overflow:hidden;margin-bottom:28px">
<?php if (empty($projects)): ?>
<div style="padding:32px;text-align:center;color:var(--text-muted)">No projects yet.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:760px">
    <thead>
        <tr><th>Project</th><th>Location</th><th>Units</th><th>Commission</th><th>Paid</th><th>Unpaid</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($projects as $p): $pUnpaid = max(0, (float)$p['total_commission'] - (float)$p['paid_amount']); ?>
    <tr>
        <td style="font-weight:600"><?= Security::e($p['name']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($p['location'] ?? '-') ?></td>
        <td style="font-weight:600;color:#8b5cf6"><?= (int)$p['unit_count'] ?></td>
        <td style="font-weight:600;color:var(--gold);white-space:nowrap"><?= $pkr($p['total_commission']) ?></td>
        <td style="font-weight:600;color:#16a34a;white-space:nowrap"><?= $pkr($p['paid_amount']) ?></td>
        <td style="font-weight:600;white-space:nowrap;color:<?= $pUnpaid > 0 ? '#dc2626' : 'var(--text-muted)' ?>"><?= $pkr($pUnpaid) ?></td>
        <td>
            <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;
                background:<?= $p['status']==='active' ? 'rgba(34,197,94,.12)' : ($p['status']==='completed' ? 'rgba(59,130,246,.12)' : 'rgba(245,158,11,.12)') ?>;
                color:<?= $p['status']==='active' ? '#16a34a' : ($p['status']==='completed' ? '#2563eb' : '#d97706') ?>">
                <?= $statusLabels[$p['status']] ?? ucfirst($p['status']) ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Units -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h2 style="font-size:16px;font-weight:700;margin:0">Units</h2>
    <a href="<?= APP_URL ?>/admin/builders/units?builder_id=<?= $builderRow['id'] ?>" class="btn btn-sm btn-secondary">Manage Units</a>
</div>
<div class="card" style="padding:0;overflow:hidden;margin-bottom:28px">
<?php if (empty($units)): ?>
<div style="padding:32px;text-align:center;color:var(--text-muted)">No units recorded.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:860px">
    <thead>
        <tr><th>Project</th><th>Unit No.</th><th>Block</th><th>Category / Size</th><th>Commission</th><th>Paid</th><th>Balance</th><th>Maturity</th><th>Status</th></tr>
    </thead>
    <tbody>
    <?php foreach ($units as $u): [$sBg, $sClr] = $payColors[$u['pay_status']]; $isMature = $u['maturity_status'] === 'mature'; ?>
    <tr>
        <td style="color:var(--text-muted);font-size:13px"><?= Security::e($u['project_name']) ?></td>
        <td style="font-weight:600"><?= Security::e($u['unit_number']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($u['block_number'] ?: '-') ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e(implode(' / ', array_filter([$u['category'], $u['plot_size']])) ?: '-') ?></td>
        <td style="font-weight:600;color:var(--gold);white-space:nowrap"><?= $pkr($u['commission_amount']) ?></td>
        <td style="color:#16a34a;white-space:nowrap"><?= $pkr($u['paid_amount']) ?></td>
        <td style="font-weight:600;white-space:nowrap;color:<?= $u['balance'] > 0 ? '#dc2626' : 'var(--text-muted)' ?>"><?= $pkr($u['balance']) ?></td>
        <td>
            <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;
                background:<?= $isMature ? 'rgba(34,197,94,.12)' : 'rgba(245,158,11,.14)' ?>;color:<?= $isMature ? '#16a34a' : '#b45309' ?>">
                <?= $isMature ? 'Mature' : 'Immature' ?>
            </span>
        </td>
        <td>
            <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:<?= $sBg ?>;color:<?= $sClr ?>">
                <?= $payLabels[$u['pay_status']] ?>
            </span>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Payments -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
    <h2 style="font-size:16px;font-weight:700;margin:0">Payment History</h2>
    <a href="<?= APP_URL ?>/admin/builders/payments?builder_id=<?= $builderRow['id'] ?>" class="btn btn-sm btn-secondary">All Payments</a>
</div>
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($payments)): ?>
<div style="padding:32px;text-align:center;color:var(--text-muted)">No payments recorded.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:700px">
    <thead>
        <tr><th>Date</th><th>Project</th><th>Unit</th><th>Amount</th><th>Type</th><th>Reference</th><th>Notes</th></tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($p['project_name'] ?? '-') ?></td>
        <td style="font-size:13px">
            <?php if ($p['unit_number'] !== null): ?>
                <?= Security::e($p['unit_number'] . ($p['block_number'] ? ' (Block ' . $p['block_number'] . ')' : '')) ?>
            <?php else: ?>
                <span style="font-size:11px;color:#b45309">Not assigned</span>
            <?php endif; ?>
        </td>
        <td style="font-weight:700;color:var(--gold)"><?= $pkr($p['amount']) ?></td>
        <td>
            <span style="padding:2px 8px;border-radius:10px;font-size:11px;font-weight:600;background:rgba(99,102,241,.1);color:#6366f1">
                <?= $typeLabels[$p['payment_type']] ?? ucfirst($p['payment_type']) ?>
            </span>
        </td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($p['reference'] ?? '-') ?></td>
        <td style="font-size:12px;color:var(--text-muted);max-width:200px"><?= Security::e($p['notes'] ?? '-') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
