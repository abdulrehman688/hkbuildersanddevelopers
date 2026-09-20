<?php
Security::requireAdmin();

$pageTitle  = 'Builders - Projects';
$activePage = 'builders';
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$statusLabels = ['active' => 'Active', 'completed' => 'Completed', 'on_hold' => 'On Hold'];
$statusColors = ['active' => ['rgba(34,197,94,.12)', '#16a34a'], 'completed' => ['rgba(59,130,246,.12)', '#2563eb'], 'on_hold' => ['rgba(245,158,11,.12)', '#d97706']];

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
        <h1>Projects</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/builders">Builders</a> <span class="sep">/</span> <span class="current">Projects</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addProjectModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Project
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/builders',          'Builders', false],
        [APP_URL.'/admin/builders/projects', 'Projects', true],
        [APP_URL.'/admin/builders/units',    'Units',    false],
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

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/builders/projects">
        <div class="filter-row">
            <select name="builder_id" class="filter-input">
                <option value="">All Builders</option>
                <?php foreach ($allBuilders as $b): ?>
                <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status" class="filter-input">
                <option value="">All Statuses</option>
                <?php foreach ($statusLabels as $val => $label): ?>
                <option value="<?= $val ?>" <?= $fStatus === $val ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/builders/projects" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($projects)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No projects found.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:750px">
    <thead>
        <tr>
            <th>Builder</th>
            <th>Project Name</th>
            <th>Location</th>
            <th>Plots</th>
            <th>Total Value</th>
            <th>Paid</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($projects as $p): ?>
    <?php [$bg, $clr] = $statusColors[$p['status']] ?? ['rgba(156,163,175,.15)', '#6b7280']; ?>
    <tr>
        <td style="color:var(--text-muted);font-size:13px"><?= Security::e($p['builder_name']) ?></td>
        <td style="font-weight:600"><?= Security::e($p['name']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($p['location'] ?? '-') ?></td>
        <td style="color:#6366f1;font-weight:600"><?= (int)$p['total_plots'] ?: '-' ?></td>
        <td style="font-weight:600"><?= $p['total_value'] > 0 ? $pkr($p['total_value']) : '-' ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($p['paid_amount']) ?></td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;background:<?= $bg ?>;color:<?= $clr ?>">
                <?= $statusLabels[$p['status']] ?? ucfirst($p['status']) ?>
            </span>
        </td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editProject(<?= json_encode($p) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/builders/projects" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="project_id"  value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this project?')">Delete</button>
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
<div class="modal-overlay" id="addProjectModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Add Project</h3>
            <button class="modal-close" onclick="closeModal('addProjectModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders/projects">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body" id="addProjectBody">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Builder *</label>
                        <select name="builder_id" class="form-input" required>
                            <option value="">-- Select Builder --</option>
                            <?php foreach ($allBuilders as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= $fBuilderId == $b['id'] ? 'selected' : '' ?>><?= Security::e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Project Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. Falaknaz Wonder City">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Location</label>
                        <input type="text" name="location" class="form-input" placeholder="City / Area">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Plots</label>
                        <input type="number" min="0" name="total_plots" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Value (PKR)</label>
                        <input type="number" min="0" step="1" name="total_value" class="form-input" value="0">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="active">Active</option>
                            <option value="completed">Completed</option>
                            <option value="on_hold">On Hold</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addProjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Project</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editProjectModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Edit Project</h3>
            <button class="modal-close" onclick="closeModal('editProjectModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders/projects">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="project_id"  id="editProjectId">
            <div class="modal-body" id="editProjectBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editProjectModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editProject(data) {
    document.getElementById('editProjectId').value = data.id;
    const src = document.getElementById('addProjectBody');
    const dst = document.getElementById('editProjectBody');
    dst.innerHTML = src.innerHTML;
    const fields = { builder_id: data.builder_id, name: data.name,
        location: data.location || '', total_plots: data.total_plots || 0,
        total_value: data.total_value || 0, status: data.status, notes: data.notes || '' };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editProjectModal');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
