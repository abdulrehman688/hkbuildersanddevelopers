<?php
Security::requireAdmin();

$pageTitle  = 'Accounts — Commission';
$activePage = 'accounts';

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$fAgentId  = (int)($_GET['agent_id']        ?? 0);
$fMaturity = $_GET['maturity_status'] ?? '';
$fPayment  = $_GET['payment_status']  ?? '';
$fMonth    = (int)($_GET['sale_month'] ?? 0);
$fYear     = (int)($_GET['sale_year']  ?? 0);

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
        <h1>Commission</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/accounts">Accounts</a> <span class="sep">/</span> <span class="current">Commission</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
        <button class="btn btn-primary" onclick="openModal('addCommModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Commission
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/accounts',            'Overview',   false],
        [APP_URL.'/admin/accounts/commission', 'Commission', true],
        [APP_URL.'/admin/accounts/expenses',   'Expenses',   false],
        [APP_URL.'/admin/accounts/salaries',   'Salaries',   false],
    ] as [$url, $label, $active]): ?>
    <a href="<?= $url ?>" style="
        padding:10px 18px;font-size:13px;font-weight:600;border-radius:6px 6px 0 0;
        text-decoration:none;border:1px solid var(--border);border-bottom:none;margin-bottom:-2px;
        background:<?= $active ? 'var(--bg-card)' : 'transparent' ?>;
        color:<?= $active ? 'var(--gold)' : 'var(--text-muted)' ?>;
    "><?= $label ?></a>
    <?php endforeach; ?>
</div>

<!-- Stat chips -->
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <?php foreach ([
        ['Total',    (int)($stats['total_entries']  ?? 0), '#6b7280', ''],
        ['Mature',   (int)($stats['mature_count']   ?? 0), '#10b981', '?maturity_status=mature'],
        ['Immature', (int)($stats['immature_count'] ?? 0), '#f59e0b', '?maturity_status=immature'],
        ['Paid',     (int)($stats['paid_count']     ?? 0), '#3b82f6', '?payment_status=paid'],
        ['Pending',  (int)($stats['pending_count']  ?? 0), '#ef4444', '?payment_status=pending'],
    ] as [$label, $count, $color, $qs]): ?>
    <a href="<?= APP_URL . '/admin/accounts/commission' . $qs ?>" style="text-decoration:none">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px;display:flex;align-items:center;gap:8px">
            <span style="font-size:18px;font-weight:700;color:<?= $color ?>"><?= $count ?></span>
            <span style="font-size:12px;color:var(--text-muted)"><?= $label ?></span>
        </div>
    </a>
    <?php endforeach; ?>
    <?php foreach ([
        [$pkr($stats['total_commission'] ?? 0), '#c9a84c', 'Total Comm'],
        [$pkr($stats['total_paid']       ?? 0), '#10b981', 'Paid'],
        [$pkr($stats['total_remaining']  ?? 0), '#ef4444', 'Due'],
    ] as [$amount, $color, $label]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
        <span style="font-size:15px;font-weight:700;color:<?= $color ?>"><?= $amount ?></span>
        <span style="font-size:12px;color:var(--text-muted);margin-left:6px"><?= $label ?></span>
    </div>
    <?php endforeach; ?>
</div>

<!-- Filters -->
<div class="filter-bar" style="margin-bottom:20px">
    <form method="GET" action="<?= APP_URL ?>/admin/accounts/commission">
        <div class="filter-row">
            <select name="agent_id" class="filter-input">
                <option value="">All Agents</option>
                <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>" <?= $fAgentId == $a['id'] ? 'selected' : '' ?>><?= Security::e($a['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="maturity_status" class="filter-input">
                <option value="">All Maturity</option>
                <option value="mature"   <?= $fMaturity === 'mature'   ? 'selected' : '' ?>>Mature</option>
                <option value="immature" <?= $fMaturity === 'immature' ? 'selected' : '' ?>>Immature</option>
            </select>
            <select name="payment_status" class="filter-input">
                <option value="">All Payment</option>
                <option value="paid"    <?= $fPayment === 'paid'    ? 'selected' : '' ?>>Paid</option>
                <option value="pending" <?= $fPayment === 'pending' ? 'selected' : '' ?>>Pending</option>
            </select>
            <select name="sale_month" class="filter-input">
                <option value="">All Months</option>
                <?php for ($m = 1; $m <= 12; $m++): ?>
                <option value="<?= $m ?>" <?= $fMonth === $m ? 'selected' : '' ?>><?= $monthNames[$m] ?></option>
                <?php endfor; ?>
            </select>
            <input type="number" name="sale_year" value="<?= $fYear ?: '' ?>" placeholder="Year" class="filter-input" style="width:90px">
            <button type="submit" class="btn btn-secondary">Filter</button>
            <a href="<?= APP_URL ?>/admin/accounts/commission" class="btn btn-secondary">Clear</a>
        </div>
    </form>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($commissions)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No commission records found.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:860px">
    <thead>
        <tr>
            <th>Agent</th>
            <th>Client / Project</th>
            <th>Total</th>
            <th>Paid</th>
            <th>Due</th>
            <th>Maturity</th>
            <th>Payment</th>
            <th>Period</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($commissions as $c): $rem = (float)$c['total_commission'] - (float)$c['paid_amount']; ?>
    <tr>
        <td><?= Security::e($c['agent_name'] ?? '-') ?></td>
        <td>
            <div style="font-weight:600"><?= Security::e($c['client_name'] ?: '-') ?></div>
            <?php if ($c['project']): ?>
            <div style="font-size:12px;color:var(--text-muted)">
                <?= Security::e($c['project']) ?><?= $c['plot_number'] ? ' &middot; Plot ' . Security::e($c['plot_number']) : '' ?>
            </div>
            <?php endif; ?>
        </td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($c['total_commission']) ?></td>
        <td style="color:#10b981"><?= $pkr($c['paid_amount']) ?></td>
        <td style="color:<?= $rem > 0 ? '#ef4444' : 'var(--text-muted)' ?>"><?= $pkr($rem) ?></td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:<?= $c['maturity_status'] === 'mature' ? 'rgba(16,185,129,.12)' : 'rgba(245,158,11,.12)' ?>;
                color:<?= $c['maturity_status'] === 'mature' ? '#10b981' : '#f59e0b' ?>">
                <?= ucfirst($c['maturity_status']) ?>
            </span>
        </td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:<?= $c['payment_status'] === 'paid' ? 'rgba(59,130,246,.12)' : 'rgba(239,68,68,.12)' ?>;
                color:<?= $c['payment_status'] === 'paid' ? '#3b82f6' : '#ef4444' ?>">
                <?= ucfirst($c['payment_status']) ?>
            </span>
            <?php if ($c['payment_status'] === 'pending' && $c['pending_reason_text']): ?>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= Security::e($c['pending_reason_text']) ?></div>
            <?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--text-muted)">
            <?= $c['sale_month'] ? $monthNames[(int)$c['sale_month']] . ' ' . $c['sale_year'] : '-' ?>
        </td>
        <td>
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editComm(<?= json_encode($c) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/accounts/commission" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action"   value="delete">
                    <input type="hidden" name="commission_id" value="<?= (int)$c['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this commission record?')">Delete</button>
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
<div class="modal-overlay" id="addCommModal">
    <div class="modal" style="max-width:620px;width:96%">
        <div class="modal-header">
            <h3>Add Commission Record</h3>
            <button class="modal-close" onclick="closeModal('addCommModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/commission">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Agent *</label>
                        <select name="agent_id" class="form-input" required>
                            <option value="">-- Select Agent --</option>
                            <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= Security::e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" class="form-input" placeholder="Client name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <input type="text" name="project" class="form-input" placeholder="Project name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plot No.</label>
                        <input type="text" name="plot_number" class="form-input" placeholder="Plot / File no.">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Total Commission (PKR) *</label>
                        <input type="number" step="1" min="0" name="total_commission" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Paid Amount (PKR)</label>
                        <input type="number" step="1" min="0" name="paid_amount" class="form-input" value="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Maturity Status</label>
                        <select name="maturity_status" class="form-input">
                            <option value="immature">Immature</option>
                            <option value="mature">Mature</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-input">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Pending Reason</label>
                        <select name="pending_reason_id" class="form-input">
                            <option value="">-- None --</option>
                            <?php foreach ($pendingReasons as $r): ?>
                            <option value="<?= $r['id'] ?>"><?= Security::e($r['reason']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sale Month</label>
                        <select name="sale_month" class="form-input">
                            <option value="">-- Month --</option>
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                            <option value="<?= $m ?>"><?= $monthNames[$m] ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sale Year</label>
                        <input type="number" name="sale_year" class="form-input" placeholder="<?= date('Y') ?>" value="<?= date('Y') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Pending Notes</label>
                    <textarea name="pending_notes" class="form-input" rows="2" placeholder="Reason details..."></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-input" rows="2" placeholder="Additional notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addCommModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Record</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editCommModal">
    <div class="modal" style="max-width:620px;width:96%">
        <div class="modal-header">
            <h3>Edit Commission Record</h3>
            <button class="modal-close" onclick="closeModal('editCommModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/commission" id="editCommForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action"   value="edit">
            <input type="hidden" name="commission_id" id="editCommId">
            <div class="modal-body" id="editCommBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editCommModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editComm(data) {
    document.getElementById('editCommId').value = data.id;
    const src = document.querySelector('#addCommModal .modal-body');
    const dst = document.getElementById('editCommBody');
    dst.innerHTML = src.innerHTML;

    const fields = {
        agent_id: data.agent_id, client_name: data.client_name,
        project: data.project, plot_number: data.plot_number,
        total_commission: data.total_commission, paid_amount: data.paid_amount,
        maturity_status: data.maturity_status, payment_status: data.payment_status,
        pending_reason_id: data.pending_reason_id || '',
        sale_month: data.sale_month || '', sale_year: data.sale_year || '',
        pending_notes: data.pending_notes || '', notes: data.notes || ''
    };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editCommModal');
}
</script>

<style>
@media print {
    .filter-bar, form[method="GET"], .modal-overlay { display: none !important; }
    .data-table td:last-child, .data-table th:last-child { display: none !important; }
    body::before {
        content: "HK Builders & Developers  -  Commission Report  -  <?= date('d M Y') ?>";
        display: block; font-size: 12px; font-weight: 600; color: #002147;
        border-bottom: 2px solid #c9a84c; padding-bottom: 8px; margin-bottom: 14px;
    }
}
</style>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
