<?php
Security::requireAdmin();

$pageTitle  = 'Accounts — Salaries';
$activePage = 'accounts';

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$fMonth   = (int)($_GET['month']    ?? 0);
$fYear    = (int)($_GET['year']     ?? 0);
$fAgentId = (int)($_GET['agent_id'] ?? 0);

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
        <h1>Salaries</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/accounts">Accounts</a> <span class="sep">/</span> <span class="current">Salaries</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
        <button class="btn btn-primary" onclick="openModal('addSalModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Salary
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/accounts',            'Overview',   false],
        [APP_URL.'/admin/accounts/commission', 'Commission', false],
        [APP_URL.'/admin/accounts/expenses',   'Expenses',   false],
        [APP_URL.'/admin/accounts/salaries',   'Salaries',   true],
    ] as [$url, $label, $active]): ?>
    <a href="<?= $url ?>" style="
        padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;
        text-decoration:none;border:1px solid var(--border);border-bottom:none;margin-bottom:-2px;
        background:<?= $active ? 'var(--bg-card)' : 'transparent' ?>;
        color:<?= $active ? 'var(--gold)' : 'var(--text-muted)' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Total salary stat -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#f59e0b"><?= $pkr($stats['salary'] ?? 0) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Total Salaries Paid</span>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#6b7280"><?= count($salaries) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">Records</span>
    </div>
    <?php if ($fMonth || $fYear): ?>
    <?php
    $periodTotal = array_sum(array_column($salaries, 'amount'));
    ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:#f59e0b"><?= $pkr($periodTotal) ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px">
            <?= ($fMonth ? $monthNames[$fMonth] . ' ' : '') . ($fYear ?: '') ?>
        </span>
    </div>
    <?php endif; ?>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/accounts/salaries">
        <div class="filter-row">
            <select name="agent_id" class="filter-input">
                <option value="">All Staff</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $fAgentId == $a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
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
            <a href="<?= APP_URL ?>/admin/accounts/salaries" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($salaries)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No salary records found.</div>
<?php else: ?>
<table class="data-table">
    <thead>
        <tr>
            <th>Name / Staff</th>
            <th>Amount</th>
            <th>Month</th>
            <th>Notes</th>
            <th>Added By</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($salaries as $s): ?>
    <tr>
        <td style="font-weight:600"><?= Security::e($s['agent_name'] ?? 'N/A') ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($s['amount']) ?></td>
        <td style="font-size:12px;color:var(--text-muted)">
            <?= $s['expense_month'] ? $monthNames[(int)$s['expense_month']] . ' ' . $s['expense_year'] : '-' ?>
        </td>
        <td style="color:var(--text-muted);max-width:200px"><?= Security::e($s['description'] ?? '-') ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($s['created_by_name'] ?? '-') ?></td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editSal(<?= json_encode($s) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/accounts/salaries" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="expense_id"  value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this salary record?')">Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay" id="addSalModal">
    <div class="modal" style="max-width:480px;width:96%">
        <div class="modal-header">
            <h3>Add Salary Record</h3>
            <button class="modal-close" onclick="closeModal('addSalModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/salaries">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Staff / Agent *</label>
                        <select name="agent_id" class="form-input" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= Security::e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (PKR) *</label>
                        <input type="number" step="1" min="0" name="amount" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Month *</label>
                        <select name="salary_month" class="form-input" required>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>" <?= (int)date('n') === $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Year *</label>
                        <input type="number" name="salary_year" class="form-input" value="<?= date('Y') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Optional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addSalModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editSalModal">
    <div class="modal" style="max-width:480px;width:96%">
        <div class="modal-header">
            <h3>Edit Salary Record</h3>
            <button class="modal-close" onclick="closeModal('editSalModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/salaries" id="editSalForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="expense_id"  id="editSalId">
            <div class="modal-body" id="editSalBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editSalModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editSal(data) {
    document.getElementById('editSalId').value = data.id;
    const src = document.querySelector('#addSalModal .modal-body');
    const dst = document.getElementById('editSalBody');
    dst.innerHTML = src.innerHTML;
    const fields = {
        agent_id: data.agent_id,
        amount: data.amount,
        salary_month: data.expense_month || '',
        salary_year: data.expense_year || '',
        description: data.description || ''
    };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editSalModal');
}
</script>

<style>
@media print {
    .filter-bar, form[method="GET"], .modal-overlay { display: none !important; }
    .data-table td:last-child, .data-table th:last-child { display: none !important; }
    body::before {
        content: "HK Builders & Developers  -  Salary Records  -  <?= date('d M Y') ?>";
        display: block; font-size: 12px; font-weight: 600; color: #002147;
        border-bottom: 2px solid #c9a84c; padding-bottom: 8px; margin-bottom: 14px;
    }
}
</style>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
