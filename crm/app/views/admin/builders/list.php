<?php
Security::requireAdmin();

$pageTitle  = 'Builders';
$activePage = 'builders';
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

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
        <h1>Builders</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Builders</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addBuilderModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Builder
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/builders',          'Builders', true],
        [APP_URL.'/admin/builders/projects', 'Projects', false],
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

<!-- Stats -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:24px">
    <?php foreach ([
        ['Active Builders', $stats['builders']   ?? 0,   '#6366f1', false],
        ['Total Projects',  $stats['projects']   ?? 0,   '#3b82f6', false],
        ['Total Paid Out',  $pkr($stats['total_paid'] ?? 0), '#f59e0b', true],
    ] as [$label, $val, $color, $isPkr]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px">
        <div style="font-size:20px;font-weight:700;color:<?= $color ?>"><?= $val ?></div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($builders)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No builders found. Add your first builder.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:700px">
    <thead>
        <tr>
            <th>Builder</th>
            <th>Contact</th>
            <th>Phone</th>
            <th>Projects</th>
            <th>Total Paid</th>
            <th>Status</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($builders as $b): ?>
    <tr>
        <td style="font-weight:600"><?= Security::e($b['name']) ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($b['contact_person'] ?? '-') ?></td>
        <td style="color:var(--text-muted)"><?= Security::e($b['phone'] ?? '-') ?></td>
        <td style="font-weight:600;color:#6366f1"><?= (int)$b['project_count'] ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($b['total_paid']) ?></td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:<?= $b['status']==='active' ? 'rgba(34,197,94,.12)' : 'rgba(156,163,175,.15)' ?>;
                color:<?= $b['status']==='active' ? '#16a34a' : '#6b7280' ?>">
                <?= ucfirst($b['status']) ?>
            </span>
        </td>
        <td>
            <div style="display:flex;gap:6px">
                <a href="<?= APP_URL ?>/admin/builders/detail/<?= $b['id'] ?>" class="btn btn-sm">View</a>
                <button class="btn btn-sm" onclick='editBuilder(<?= json_encode($b) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/builders" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="builder_id"  value="<?= (int)$b['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete <?= addslashes(Security::e($b['name'])) ?> and all their data?')">Delete</button>
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
<div class="modal-overlay" id="addBuilderModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Add Builder</h3>
            <button class="modal-close" onclick="closeModal('addBuilderModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body" id="addBuilderBody">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Builder / Company Name *</label>
                        <input type="text" name="name" class="form-input" required placeholder="e.g. Falaknaz Group">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Contact Person</label>
                        <input type="text" name="contact_person" class="form-input" placeholder="Name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input" placeholder="+92...">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input" placeholder="email@example.com">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-input">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Address</label>
                        <input type="text" name="address" class="form-input" placeholder="Office address">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Any notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addBuilderModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Builder</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editBuilderModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Edit Builder</h3>
            <button class="modal-close" onclick="closeModal('editBuilderModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/builders">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="builder_id"  id="editBuilderId">
            <div class="modal-body" id="editBuilderBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editBuilderModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editBuilder(data) {
    document.getElementById('editBuilderId').value = data.id;
    const src = document.getElementById('addBuilderBody');
    const dst = document.getElementById('editBuilderBody');
    dst.innerHTML = src.innerHTML;
    const fields = { name: data.name, contact_person: data.contact_person || '',
        phone: data.phone || '', email: data.email || '',
        status: data.status, address: data.address || '', notes: data.notes || '' };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (opt.value === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editBuilderModal');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
