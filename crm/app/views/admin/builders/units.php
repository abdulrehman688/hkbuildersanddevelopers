<?php
Security::requireAdmin();

$pageTitle  = 'Builders - Units';
$activePage = 'builders';
$pkr = fn($v) => 'PKR ' . number_format((float)$v, 0);

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

<!-- Stats -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#6366f1"><?= (int)($unitStats['total_units'] ?? 0) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Total Units</span>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:var(--gold)"><?= $pkr($unitStats['total_commission'] ?? 0) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Total Commission</span>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#16a34a"><?= $pkr($unitStats['paid_commission'] ?? 0) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Paid</span>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#dc2626"><?= $pkr($unitStats['unpaid_commission'] ?? 0) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Unpaid</span>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/builders/units">
        <div class="filter-row">
            <select name="builder_id" class="filter-input" id="filterBuilder" onchange="this.form.submit()">
                <option value="">All Builders</option>
                <?php foreach ($allBuilders as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="project_id" class="filter-input">
                <option value="">All Projects</option>
                <?php foreach ($allProjects as $p): ?>
                <option value="<?= $p['id'] ?>" <?= $fProjectId == $p['id'] ? 'selected' : '' ?>><?= Security::e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <option value="unpaid" <?= $fStatus === 'unpaid' ? 'selected' : '' ?>>Unpaid</option>
                <option value="paid"   <?= $fStatus === 'paid'   ? 'selected' : '' ?>>Paid</option>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/builders/units" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($units)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No units found.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:900px">
    <thead>
        <tr>
            <th>Builder / Project</th>
            <th>Unit No.</th>
            <th>Block</th>
            <th>Category</th>
            <th>Plot Size</th>
            <th>Total Cost</th>
            <th>Down Payment</th>
            <th>Commission</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($units as $u): ?>
    <tr>
        <td>
            <div style="font-weight:600;font-size:13px"><?= Security::e($u['builder_name']) ?></div>
            <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($u['project_name']) ?></div>
        </td>
        <td style="font-weight:600"><?= Security::e($u['unit_number']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($u['block_number'] ?? '-') ?></td>
        <td style="color:var(--text-muted);font-size:12px"><?= Security::e($u['category'] ?? '-') ?></td>
        <td style="color:var(--text-muted);font-size:12px"><?= Security::e($u['plot_size'] ?? '-') ?></td>
        <td style="font-weight:600"><?= $pkr($u['total_cost']) ?></td>
        <td><?= $pkr($u['down_payment']) ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($u['commission_amount']) ?></td>
        <td>
            <?php if ($u['commission_status'] === 'paid'): ?>
                <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:rgba(34,197,94,.12);color:#16a34a;border:1px solid #bbf7d0">Paid</span>
            <?php else: ?>
                <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:rgba(220,38,38,.1);color:#dc2626;border:1px solid #fecaca">Unpaid</span>
            <?php endif; ?>
        </td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editUnit(<?= json_encode($u) ?>)'>Edit</button>
                <!-- Toggle paid/unpaid -->
                <form method="POST" action="<?= APP_URL ?>/admin/builders/units" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action"   value="toggle">
                    <input type="hidden" name="unit_id"       value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="f_builder_id"  value="<?= $fBuilderId ?>">
                    <input type="hidden" name="f_project_id"  value="<?= $fProjectId ?>">
                    <input type="hidden" name="f_status"      value="<?= Security::e($fStatus) ?>">
                    <button type="submit" class="btn btn-sm" style="<?= $u['commission_status']==='paid' ? 'color:#dc2626' : 'color:#16a34a' ?>">
                        <?= $u['commission_status'] === 'paid' ? 'Mark Unpaid' : 'Mark Paid' ?>
                    </button>
                </form>
                <form method="POST" action="<?= APP_URL ?>/admin/builders/units" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action"  value="delete">
                    <input type="hidden" name="unit_id"      value="<?= (int)$u['id'] ?>">
                    <input type="hidden" name="f_builder_id" value="<?= $fBuilderId ?>">
                    <input type="hidden" name="f_project_id" value="<?= $fProjectId ?>">
                    <input type="hidden" name="f_status"     value="<?= Security::e($fStatus) ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this unit?')">Delete</button>
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
<div class="modal-overlay" id="addUnitModal">
    <div class="modal" style="max-width:600px;width:96%">
        <div class="modal-header">
            <h3>Add Unit</h3>
            <button class="modal-close" onclick="closeModal('addUnitModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders/units">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action"  value="add">
            <input type="hidden" name="f_builder_id" value="<?= $fBuilderId ?>">
            <input type="hidden" name="f_project_id" value="<?= $fProjectId ?>">
            <input type="hidden" name="f_status"     value="<?= Security::e($fStatus) ?>">
            <div class="modal-body" id="addUnitBody">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Builder *</label>
                        <select name="builder_id" class="form-input" required id="addBuilderSel" onchange="filterProjects(this.value,'addProjectSel')">
                            <option value="">-- Select Builder --</option>
                            <?php foreach ($allBuilders as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Project *</label>
                        <select name="project_id" class="form-input" required id="addProjectSel">
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
                        <input type="text" name="category" class="form-input" placeholder="e.g. Residential, Commercial">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plot Size</label>
                        <input type="text" name="plot_size" class="form-input" placeholder="e.g. 5 Marla, 10 Marla">
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
                        <label class="form-label">Commission / Rebate (PKR)</label>
                        <input type="number" min="0" step="1" name="commission_amount" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Commission Status</label>
                        <select name="commission_status" class="form-input">
                            <option value="unpaid">Unpaid</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                    </div>
                </div>
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
        <form method="POST" action="<?= APP_URL ?>/admin/builders/units" id="editUnitForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action"  value="edit">
            <input type="hidden" name="unit_id"      id="editUnitId">
            <input type="hidden" name="f_builder_id" value="<?= $fBuilderId ?>">
            <input type="hidden" name="f_project_id" value="<?= $fProjectId ?>">
            <input type="hidden" name="f_status"     value="<?= Security::e($fStatus) ?>">
            <div class="modal-body" id="editUnitBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editUnitModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
// Filter project dropdown by builder
function filterProjects(builderId, selId) {
    const sel = document.getElementById(selId);
    if (!sel) return;
    for (const opt of sel.options) {
        if (!opt.value) continue;
        opt.hidden = builderId && opt.dataset.builder !== String(builderId);
    }
    if (sel.options[sel.selectedIndex]?.hidden) sel.value = '';
}

function editUnit(data) {
    document.getElementById('editUnitId').value = data.id;
    const src = document.getElementById('addUnitBody');
    const dst = document.getElementById('editUnitBody');
    dst.innerHTML = src.innerHTML;

    // Fix the project select id so filterProjects works in edit modal
    const editBuilderSel = dst.querySelector('[name="builder_id"]');
    const editProjectSel = dst.querySelector('[name="project_id"]');
    if (editBuilderSel) {
        editBuilderSel.id = 'editBuilderSel';
        editBuilderSel.setAttribute('onchange', "filterProjects(this.value,'editProjectSel')");
    }
    if (editProjectSel) editProjectSel.id = 'editProjectSel';

    const fields = {
        builder_id: data.builder_id, project_id: data.project_id,
        unit_number: data.unit_number, block_number: data.block_number || '',
        category: data.category || '', plot_size: data.plot_size || '',
        total_cost: data.total_cost, down_payment: data.down_payment,
        commission_amount: data.commission_amount,
        commission_status: data.commission_status,
        notes: data.notes || ''
    };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    // Show only projects for this builder
    filterProjects(data.builder_id, 'editProjectSel');
    openModal('editUnitModal');
}
</script>

<style>
@media print {
    .filter-bar, form[method="GET"], .modal-overlay, .page-header-actions { display: none !important; }
    .data-table td:last-child, .data-table th:last-child { display: none !important; }
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
