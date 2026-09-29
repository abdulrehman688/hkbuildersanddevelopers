<?php
Security::requireAdmin();

$pageTitle  = 'Builders - Units';
$activePage = 'builders';
$pkr = fn($v) => 'PKR ' . number_format((float)$v, 0);
$js  = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);

$formAction = APP_URL . '/admin/builders/units' . $filterQs;
$scope      = array_filter(['builder_id' => $fBuilderId, 'project_id' => $fProjectId]);
$scopeUrl   = function (array $extra = []) use ($scope): string {
    $q = http_build_query(array_filter($scope + $extra));
    return APP_URL . '/admin/builders/units' . ($q ? '?' . $q : '');
};
$filtered = $fMaturity !== '' || $fPay !== '';

$payLabels = ['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'];
$payColors = [
    'paid'    => ['rgba(34,197,94,.12)',  '#16a34a'],
    'partial' => ['rgba(245,158,11,.14)', '#b45309'],
    'unpaid'  => ['rgba(220,38,38,.10)',  '#dc2626'],
];

$filterProjects = $fBuilderId
    ? array_filter($allProjects, fn($p) => (int)$p['builder_id'] === $fBuilderId)
    : $allProjects;

$categoryOptions = array_unique(array_merge(['Residential', 'Commercial'], $suggestions['categories']));

ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= Security::e($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-error">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
    <?= Security::e($_SESSION['error']) ?></div>
<?php unset($_SESSION['error']); endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Units</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/builders">Builders</a> <span class="sep">/</span> <span class="current">Units</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
        <button class="btn btn-primary" onclick="openModal('addUnitModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Unit
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/builders',          'Builders', false],
        [APP_URL.'/admin/builders/projects', 'Projects', false],
        [APP_URL.'/admin/builders/units',    'Units',    true],
        [APP_URL.'/admin/builders/payments', 'Payments', false],
    ] as [$url, $label, $active]): ?>
    <a href="<?= $url ?>" style="
        padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;
        text-decoration:none;border:1px solid var(--border);border-bottom:none;margin-bottom:-2px;
        background:<?= $active ? 'var(--bg-card)' : 'transparent' ?>;
        color:<?= $active ? 'var(--gold)' : 'var(--text-muted)' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Unit counts (click to filter) -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px">
    <?php foreach ([
        ['Total Units', (int)($unitStats['total_units']    ?? 0), '#6366f1', $scopeUrl(),                           !$filtered],
        ['Mature',      (int)($unitStats['mature_units']   ?? 0), '#16a34a', $scopeUrl(['maturity' => 'mature']),   $fMaturity === 'mature'],
        ['Immature',    (int)($unitStats['immature_units'] ?? 0), '#d97706', $scopeUrl(['maturity' => 'immature']), $fMaturity === 'immature'],
    ] as [$label, $count, $color, $url, $on]): ?>
    <a href="<?= $url ?>" style="text-decoration:none">
        <div style="background:var(--bg-card);border:1px solid <?= $on ? $color : 'var(--border)' ?>;border-radius:8px;padding:7px 14px;display:flex;align-items:center;gap:7px">
            <span style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= $count ?></span>
            <span style="font-size:12px;color:var(--text-muted)"><?= $label ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Money: commission from units, paid from Payments -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <?php foreach ([
        ['Total Commission', $unitStats['total_commission'] ?? 0, 'var(--gold)'],
        ['Paid',             $unitStats['total_paid']       ?? 0, '#16a34a'],
        ['Unpaid',           $unitStats['unpaid']           ?? 0, '#dc2626'],
    ] as [$label, $amount, $color]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:170px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= $pkr($amount) ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if ((float)($unitStats['unassigned_paid'] ?? 0) > 0): ?>
<div class="alert alert-info">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
    <span>
        <?= $pkr($unitStats['unassigned_paid']) ?> received is not linked to any unit. It is counted in Paid above, but no unit shows it yet.
        Open <a href="<?= APP_URL ?>/admin/builders/payments<?= $scope ? '?' . http_build_query($scope) : '' ?>" style="color:inherit;font-weight:600">Payments</a>
        and edit the payment to choose its unit.
    </span>
</div>
<?php endif; ?>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/builders/units">
        <div class="filter-row">
            <select name="builder_id" class="filter-input" onchange="this.form.project_id.value='';this.form.submit()">
                <option value="">All Builders</option>
                <?php foreach ($allBuilders as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="project_id" class="filter-input">
                <option value="">All Projects</option>
                <?php foreach ($filterProjects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $fProjectId == $p['id'] ? 'selected' : '' ?>><?= Security::e($p['name'] . ($fBuilderId ? '' : ' (' . $p['builder_name'] . ')')) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="maturity" class="filter-input">
                <option value="">All Maturity</option>
                <option value="mature"   <?= $fMaturity === 'mature'   ? 'selected' : '' ?>>Mature</option>
                <option value="immature" <?= $fMaturity === 'immature' ? 'selected' : '' ?>>Immature</option>
            </select>
            <select name="pay" class="filter-input">
                <option value="">All Payment Status</option>
                <?php foreach ($payLabels as $val => $label): ?>
                <option value="<?= $val ?>" <?= $fPay === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/builders/units" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($units)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)"><?= $filtered ? 'No units match these filters.' : 'No units yet. Add the first unit sold.' ?></div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table units-table" style="min-width:1180px">
    <thead>
        <tr>
            <th>Builder / Project</th>
            <th>Unit No.</th>
            <th>Block</th>
            <th>Category / Size</th>
            <th>Total Cost</th>
            <th>Down Payment</th>
            <th>Commission</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Maturity</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($units as $u):
        [$sBg, $sClr] = $payColors[$u['pay_status']];
        $isMature = $u['maturity_status'] === 'mature';
        $payCount = (int)$u['pay_count'];
        $payData  = [
            'id'           => (int)$u['id'],
            'unit_number'  => $u['unit_number'],
            'block_number' => $u['block_number'],
            'builder_name' => $u['builder_name'],
            'project_name' => $u['project_name'],
            'commission'   => (float)$u['commission_amount'],
            'paid'         => (float)$u['paid_amount'],
            'balance'      => (float)$u['balance'],
        ];
        $unpayMsg  = 'Mark unit ' . $u['unit_number'] . ' as unpaid? This deletes its ' . $payCount . ' payment record'
                   . ($payCount === 1 ? '' : 's') . ' (' . $pkr($u['paid_amount']) . ') from Payments.';
        $deleteMsg = 'Delete unit ' . $u['unit_number'] . '?'
                   . ($payCount ? ' Its payments stay in Payments but will no longer be linked to a unit.' : '');
    ?>
    <tr>
        <td>
            <div style="font-weight:600;font-size:13px"><?= Security::e($u['builder_name']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($u['project_name']) ?></div>
        </td>
        <td style="font-weight:600"><?= Security::e($u['unit_number']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($u['block_number'] ?: '-') ?></td>
        <td style="font-size:12px;color:var(--text-muted)">
            <?= Security::e($u['category'] ?: '-') ?>
            <?php if ($u['plot_size']): ?><div><?= Security::e($u['plot_size']) ?></div><?php endif; ?>
        </td>
        <td><?= $pkr($u['total_cost']) ?></td>
        <td><?= $pkr($u['down_payment']) ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($u['commission_amount']) ?></td>
        <td style="color:#16a34a"><?= $pkr($u['paid_amount']) ?></td>
        <td style="font-weight:600;color:<?= $u['balance'] > 0 ? '#dc2626' : 'var(--text-muted)' ?>"><?= $pkr($u['balance']) ?></td>
        <td>
            <form method="POST" action="<?= $formAction ?>" style="margin:0">
                <?= Security::csrfField() ?>
                <input type="hidden" name="form_action" value="maturity">
                <input type="hidden" name="unit_id"     value="<?= (int)$u['id'] ?>">
                <button type="submit" class="unit-toggle" title="Click to mark <?= $isMature ? 'immature' : 'mature' ?>"
                    style="background:<?= $isMature ? 'rgba(34,197,94,.12)' : 'rgba(245,158,11,.14)' ?>;color:<?= $isMature ? '#16a34a' : '#b45309' ?>">
                    <?= $isMature ? 'Mature' : 'Immature' ?>
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
                </button>
            </form>
        </td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:<?= $sBg ?>;color:<?= $sClr ?>">
                <?= $payLabels[$u['pay_status']] ?>
            </span>
        </td>
        <td>
            <div style="display:flex;gap:6px">
                <?php if ($u['balance'] > 0): ?>
                <button type="button" class="btn btn-sm" style="color:#16a34a" onclick='openPayModal(<?= $js($payData) ?>)'>Mark Paid</button>
                <?php elseif ($payCount > 0): ?>
                <form method="POST" action="<?= $formAction ?>" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="unpay">
                    <input type="hidden" name="unit_id"     value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="color:#dc2626" onclick='return confirm(<?= $js($unpayMsg) ?>)'>Mark Unpaid</button>
                </form>
                <?php endif; ?>
                <button type="button" class="btn btn-sm" onclick='editUnit(<?= $js($u) ?>)'>Edit</button>
                <form method="POST" action="<?= $formAction ?>" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="unit_id"     value="<?= (int)$u['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger" onclick='return confirm(<?= $js($deleteMsg) ?>)'>Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
        <tr style="background:var(--bg);font-weight:700;border-top:2px solid var(--border)">
            <td colspan="4">Total &middot; <?= count($units) ?> unit<?= count($units) === 1 ? '' : 's' ?></td>
            <td><?= $pkr(array_sum(array_column($units, 'total_cost'))) ?></td>
            <td><?= $pkr(array_sum(array_column($units, 'down_payment'))) ?></td>
            <td style="color:var(--gold)"><?= $pkr(array_sum(array_column($units, 'commission_amount'))) ?></td>
            <td style="color:#16a34a"><?= $pkr(array_sum(array_column($units, 'paid_amount'))) ?></td>
            <td style="color:#dc2626"><?= $pkr(array_sum(array_column($units, 'balance'))) ?></td>
            <td colspan="2"></td>
            <td></td>
        </tr>
    </tfoot>
</table>
</div>
<?php endif; ?>
</div>

<datalist id="unitCategoryList">
    <?php foreach ($categoryOptions as $c): ?><option value="<?= Security::e($c) ?>"><?php endforeach; ?>
</datalist>
<datalist id="unitSizeList">
    <?php foreach ($suggestions['plot_sizes'] as $s): ?><option value="<?= Security::e($s) ?>"><?php endforeach; ?>
</datalist>

<!-- Add Modal -->
<div class="modal-overlay" id="addUnitModal">
    <div class="modal" style="max-width:600px;width:96%">
        <div class="modal-header">
            <h3>Add Unit</h3>
            <button class="modal-close" onclick="closeModal('addUnitModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= $formAction ?>" id="addUnitForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body" id="addUnitBody">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Builder *</label>
                        <select name="builder_id" class="form-input" required onchange="unitCascade(this.form)">
                            <option value="">-- Select Builder --</option>
                            <?php foreach ($allBuilders as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project *</label>
                        <select name="project_id" class="form-input" required>
                            <option value="">-- Select Project --</option>
                            <?php foreach ($allProjects as $p): ?>
                            <option value="<?= $p['id'] ?>" data-builder="<?= $p['builder_id'] ?>" <?= $fProjectId == $p['id'] ? 'selected' : '' ?>><?= Security::e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit Number *</label>
                        <input type="text" name="unit_number" class="form-input" required placeholder="e.g. A-101">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Block</label>
                        <input type="text" name="block_number" class="form-input" placeholder="e.g. Block B">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category</label>
                        <input type="text" name="category" class="form-input" list="unitCategoryList" placeholder="e.g. Residential, Commercial">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plot Size</label>
                        <input type="text" name="plot_size" class="form-input" list="unitSizeList" placeholder="e.g. 120 sq yd, 5 Marla">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Cost (PKR) *</label>
                        <input type="number" min="0" step="1" name="total_cost" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Down Payment (PKR)</label>
                        <input type="number" min="0" step="1" name="down_payment" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commission / Rebate (PKR) *</label>
                        <input type="number" min="0" step="1" name="commission_amount" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maturity</label>
                        <select name="maturity_status" class="form-input">
                            <option value="immature">Immature</option>
                            <option value="mature">Mature</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
                <div style="font-size:11px;color:var(--text-muted)">Paid and unpaid come from Payments. After saving, use Mark Paid on the unit when the builder pays.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addUnitModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Unit</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editUnitModal">
    <div class="modal" style="max-width:600px;width:96%">
        <div class="modal-header">
            <h3>Edit Unit</h3>
            <button class="modal-close" onclick="closeModal('editUnitModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= $formAction ?>" id="editUnitForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="unit_id">
            <div class="modal-body" id="editUnitBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUnitModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<!-- Mark Paid Modal -->
<div class="modal-overlay" id="payUnitModal">
    <div class="modal" style="max-width:500px;width:96%">
        <div class="modal-header">
            <h3>Mark Paid</h3>
            <button class="modal-close" onclick="closeModal('payUnitModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= $formAction ?>" id="payUnitForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="pay">
            <input type="hidden" name="unit_id">
            <div class="modal-body">
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:8px;padding:10px 14px;font-size:12px">
                    <div style="font-weight:600;color:var(--navy);font-size:13px" data-pay="name"></div>
                    <div style="color:var(--text-muted)" data-pay="project"></div>
                    <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:6px">
                        <span>Commission: <strong data-pay="commission"></strong></span>
                        <span>Already paid: <strong data-pay="paid" style="color:#16a34a"></strong></span>
                        <span>Balance: <strong data-pay="balance" style="color:#dc2626"></strong></span>
                    </div>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Amount Received (PKR) *</label>
                        <input type="number" min="1" step="1" name="amount" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Type</label>
                        <select name="payment_type" class="form-input">
                            <option value="final">Final</option>
                            <option value="installment">Installment</option>
                            <option value="advance">Advance</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference / Cheque No.</label>
                        <input type="text" name="reference" class="form-input" placeholder="e.g. ONLINE, cheque no.">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                </div>
                <div style="font-size:11px;color:var(--text-muted)">Saved in Payments as well. Enter less than the balance to record a part payment.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('payUnitModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Payment</button>
            </div>
        </form>
    </div>
</div>

<script>
function fmtPKR(n) {
    return 'PKR ' + Math.round(Number(n) || 0).toLocaleString('en-US');
}

// Shows only the chosen builder's projects in a unit form.
function unitCascade(form) {
    const builderId = form.builder_id.value;
    const project   = form.project_id;
    for (const opt of project.options) {
        if (opt.value) opt.hidden = builderId !== '' && opt.dataset.builder !== builderId;
    }
    if (project.selectedOptions[0] && project.selectedOptions[0].hidden) project.value = '';
}

function editUnit(u) {
    const form = document.getElementById('editUnitForm');
    const body = document.getElementById('editUnitBody');
    body.innerHTML = document.getElementById('addUnitBody').innerHTML;
    form.unit_id.value = u.id;
    const values = {
        builder_id: u.builder_id, project_id: u.project_id,
        unit_number: u.unit_number, block_number: u.block_number || '',
        category: u.category || '', plot_size: u.plot_size || '',
        total_cost: u.total_cost, down_payment: u.down_payment,
        commission_amount: u.commission_amount, maturity_status: u.maturity_status,
        notes: u.notes || ''
    };
    for (const [name, val] of Object.entries(values)) {
        const el = body.querySelector('[name="' + name + '"]');
        if (el) el.value = val ?? '';
    }
    unitCascade(form);
    openModal('editUnitModal');
}

function openPayModal(u) {
    const form = document.getElementById('payUnitForm');
    const show = (key, text) => { form.querySelector('[data-pay="' + key + '"]').textContent = text; };
    form.reset();
    form.unit_id.value = u.id;
    show('name', 'Unit ' + u.unit_number + (u.block_number ? ' · Block ' + u.block_number : ''));
    show('project', u.builder_name + ' · ' + u.project_name);
    show('commission', fmtPKR(u.commission));
    show('paid', fmtPKR(u.paid));
    show('balance', fmtPKR(u.balance));
    form.amount.value = Math.round(u.balance);
    openModal('payUnitModal');
}

unitCascade(document.getElementById('addUnitForm'));
</script>

<style>
.units-table td:not(:first-child) { white-space:nowrap; }
.unit-toggle {
    display:inline-flex;align-items:center;gap:5px;padding:3px 10px;border-radius:12px;
    font-size:11px;font-weight:600;font-family:inherit;border:1px solid transparent;cursor:pointer;
}
.unit-toggle:hover { border-color:currentColor; }
.unit-toggle svg { width:11px;height:11px;opacity:.6; }
@media print {
    @page { size: A4 landscape; }
    .filter-bar, form[method="GET"], .modal-overlay, .page-header-actions, .alert { display: none !important; }
    .data-table td:last-child, .data-table th:last-child { display: none !important; }
    .unit-toggle svg { display: none; }
    body::before {
        content: "HK Builders & Developers  -  Units Report  -  <?= date('d M Y') ?>";
        display: block; font-size: 12px; font-weight: 600; color: #002147;
        border-bottom: 2px solid #c9a84c; padding-bottom: 8px; margin-bottom: 14px;
    }
}
</style>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
