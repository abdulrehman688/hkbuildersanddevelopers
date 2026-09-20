<?php
Security::requireAdmin();

$pageTitle  = 'Builders - Payments';
$activePage = 'builders';
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);
$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];

$typeLabels = ['advance' => 'Advance', 'installment' => 'Installment', 'final' => 'Final', 'other' => 'Other'];
$typeColors = ['advance' => '#6366f1', 'installment' => '#3b82f6', 'final' => '#16a34a', 'other' => '#6b7280'];

$totalPaid = array_sum(array_column($payments, 'amount'));

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
        <h1>Payments</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/builders">Builders</a> <span class="sep">/</span> <span class="current">Payments</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addPayModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Payment
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/builders',          'Builders', false],
        [APP_URL.'/admin/builders/projects', 'Projects', false],
        [APP_URL.'/admin/builders/units',    'Units',    false],
        [APP_URL.'/admin/builders/payments', 'Payments', true],
    ] as [$url, $label, $active]): ?>
    <a href="<?= $url ?>" style="
        padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;
        text-decoration:none;border:1px solid var(--border);border-bottom:none;margin-bottom:-2px;
        background:<?= $active ? 'var(--bg-card)' : 'transparent' ?>;
        color:<?= $active ? 'var(--gold)' : 'var(--text-muted)' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Stat chip -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:16px;font-weight:700;color:var(--gold)"><?= $pkr($totalPaid) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Total Shown</span>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:16px;font-weight:700;color:#6b7280"><?= count($payments) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Records</span>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/builders/payments">
        <div class="filter-row">
            <select name="builder_id" class="filter-input">
                <option value="">All Builders</option>
                <?php foreach ($allBuilders as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="project_id" class="filter-input">
                <option value="">All Projects</option>
                <?php foreach ($allProjects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $fProjectId == $p['id'] ? 'selected' : '' ?>><?= Security::e($p['builder_name'] . ' - ' . $p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="month" class="filter-input">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $fMonth === $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="year" value="<?= $fYear ?: '' ?>" placeholder="Year" class="filter-input" style="width:90px">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/builders/payments" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($payments)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No payments found.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:800px">
    <thead>
        <tr>
            <th>Builder</th>
            <th>Project</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Date</th>
            <th>Reference</th>
            <th>Added By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $p): ?>
    <tr>
        <td style="font-weight:600"><?= Security::e($p['builder_name']) ?></td>
        <td style="color:var(--text-muted);font-size:13px"><?= Security::e($p['project_name'] ?? '-') ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($p['amount']) ?></td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:rgba(99,102,241,.1);color:<?= $typeColors[$p['payment_type']] ?? '#6b7280' ?>">
                <?= $typeLabels[$p['payment_type']] ?? ucfirst($p['payment_type']) ?>
            </span>
        </td>
        <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($p['reference'] ?? '-') ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($p['created_by_name'] ?? '-') ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editPay(<?= json_encode($p) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/builders/payments" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="payment_id"  value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this payment?')">Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addPayModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Add Payment</h3>
            <button class="modal-close" onclick="closeModal('addPayModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders/payments">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body" id="addPayBody">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Builder *</label>
                        <select name="builder_id" class="form-input" required>
                            <option value="">-- Select Builder --</option>
                            <?php foreach ($allBuilders as $b): ?>
                            <option value="<?= $b['id'] ?>"><?= Security::e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Project</label>
                        <select name="project_id" class="form-input">
                            <option value="">-- No Project --</option>
                            <?php foreach ($allProjects as $p): ?>
                            <option value="<?= $p['id'] ?>"><?= Security::e($p['builder_name'] . ' - ' . $p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (PKR) *</label>
                        <input type="number" step="1" min="0" name="amount" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Type *</label>
                        <select name="payment_type" class="form-input" required>
                            <option value="advance">Advance</option>
                            <option value="installment" selected>Installment</option>
                            <option value="final">Final</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Date *</label>
                        <input type="date" name="payment_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Reference / Cheque No.</label>
                        <input type="text" name="reference" class="form-input" placeholder="Optional">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addPayModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Payment</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editPayModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Edit Payment</h3>
            <button class="modal-close" onclick="closeModal('editPayModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders/payments">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="payment_id"  id="editPayId">
            <div class="modal-body" id="editPayBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editPayModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editPay(data) {
    document.getElementById('editPayId').value = data.id;
    const src = document.getElementById('addPayBody');
    const dst = document.getElementById('editPayBody');
    dst.innerHTML = src.innerHTML;
    const fields = { builder_id: data.builder_id, project_id: data.project_id || '',
        amount: data.amount, payment_type: data.payment_type,
        payment_date: data.payment_date, reference: data.reference || '', notes: data.notes || '' };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editPayModal');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
