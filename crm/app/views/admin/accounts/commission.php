<?php
Security::requireAdmin();

$pageTitle  = 'Accounts - Commission';
$activePage = 'accounts';

$monthNames = ['','January','February','March','April','May','June','July','August','September','October','November','December'];
$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);

$fAgentId  = (int)($_GET['agent_id']        ?? 0);
$fMaturity = $_GET['maturity_status'] ?? '';
$fPayment  = $_GET['payment_status']  ?? '';
$fMonth    = (int)($_GET['sale_month'] ?? 0);
$fYear     = (int)($_GET['sale_year']  ?? 0);

$sourceLabels = ['cash' => 'Cash', 'online' => 'Online', 'cheque' => 'Cheque', 'bank_transfer' => 'Bank Transfer', 'other' => 'Other'];

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

<!-- Count chips -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:14px">
    <?php foreach ([
        ['Total',    (int)($stats['total_entries']  ?? 0), '#6b7280', ''],
        ['Mature',   (int)($stats['mature_count']   ?? 0), '#10b981', '?maturity_status=mature'],
        ['Immature', (int)($stats['immature_count'] ?? 0), '#f59e0b', '?maturity_status=immature'],
        ['Paid',     (int)($stats['paid_count']     ?? 0), '#3b82f6', '?payment_status=paid'],
        ['Pending',  (int)($stats['pending_count']  ?? 0), '#ef4444', '?payment_status=pending'],
    ] as [$label, $count, $color, $qs]): ?>
    <a href="<?= APP_URL . '/admin/accounts/commission' . $qs ?>" style="text-decoration:none">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:7px 14px;display:flex;align-items:center;gap:7px">
            <span style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= $count ?></span>
            <span style="font-size:12px;color:var(--text-muted)"><?= $label ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- Financial chips (auto-calculated, read-only) -->
<div style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:22px">
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:160px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">Total Commission</div>
        <div style="font-size:17px;font-weight:700;color:var(--gold)"><?= $pkr($stats['total_commission'] ?? 0) ?></div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:160px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">Total Paid</div>
        <div style="font-size:17px;font-weight:700;color:#10b981"><?= $pkr($stats['total_paid'] ?? 0) ?></div>
    </div>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:160px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">Remaining</div>
        <div style="font-size:17px;font-weight:700;color:#ef4444"><?= $pkr($stats['total_remaining'] ?? 0) ?></div>
    </div>
    <?php if (($stats['recently_paid_amount'] ?? 0) > 0): ?>
    <div style="background:var(--bg-card);border:1px solid rgba(59,130,246,.3);border-radius:8px;padding:10px 18px;min-width:160px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px">Recently Paid</div>
        <div style="font-size:17px;font-weight:700;color:#3b82f6"><?= $pkr($stats['recently_paid_amount']) ?></div>
        <?php if ($stats['recently_paid_agent'] || $stats['recently_paid_date']): ?>
        <div style="font-size:11px;color:var(--text-muted);margin-top:2px">
            <?= Security::e($stats['recently_paid_agent']) ?>
            <?= $stats['recently_paid_date'] ? ' &middot; ' . date('d M Y', strtotime($stats['recently_paid_date'])) : '' ?>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
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
<table class="data-table" style="min-width:1000px">
    <thead>
        <tr>
            <th>Agent</th>
            <th>Client / Project</th>
            <th>Commission</th>
            <th>Due</th>
            <th>Sale Date</th>
            <th>Source</th>
            <th>Ref No.</th>
            <th>Maturity</th>
            <th>Payment</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($commissions as $c):
        $rem      = (float)$c['total_commission'] - (float)$c['paid_amount'];
        $projName = $c['project_name'] ?: $c['project'] ?: '-';
        $isOverdue = $c['due_date'] && $c['payment_status'] === 'pending' && strtotime($c['due_date']) < time();
    ?>
    <tr <?= $isOverdue ? 'style="background:rgba(239,68,68,.04)"' : '' ?>>
        <td><?= Security::e($c['agent_name'] ?? '-') ?></td>
        <td>
            <div style="font-weight:600"><?= Security::e($c['client_name'] ?: '-') ?></div>
            <div style="font-size:12px;color:var(--text-muted)">
                <?= Security::e($projName) ?>
                <?= $c['plot_number'] ? ' &middot; Plot ' . Security::e($c['plot_number']) : '' ?>
            </div>
            <?php if ($c['due_date'] && $c['payment_status'] === 'pending'): ?>
            <div style="font-size:11px;color:<?= $isOverdue ? '#ef4444' : '#f59e0b' ?>">
                Due: <?= date('d M Y', strtotime($c['due_date'])) ?><?= $isOverdue ? ' (overdue)' : '' ?>
            </div>
            <?php endif; ?>
        </td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($c['total_commission']) ?></td>
        <td style="color:<?= $rem > 0 ? '#ef4444' : '#10b981' ?>;font-weight:600"><?= $rem > 0 ? $pkr($rem) : 'Paid' ?></td>
        <td style="font-size:12px;color:var(--text-muted)">
            <?php if ($c['sale_date']): ?>
                <?= date('d M Y', strtotime($c['sale_date'])) ?>
            <?php elseif ($c['sale_month']): ?>
                <?= $monthNames[(int)$c['sale_month']] . ' ' . $c['sale_year'] ?>
            <?php else: ?>-<?php endif; ?>
        </td>
        <td style="font-size:12px">
            <?php if ($c['payment_source']): ?>
            <span style="padding:2px 8px;border-radius:10px;background:var(--bg);border:1px solid var(--border);font-size:11px">
                <?= $sourceLabels[$c['payment_source']] ?? ucfirst($c['payment_source']) ?>
            </span>
            <?php else: ?>-<?php endif; ?>
        </td>
        <td style="font-size:12px;color:var(--text-muted)"><?= $c['reference_no'] ? Security::e($c['reference_no']) : '-' ?></td>
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
    <div class="modal" style="max-width:640px;width:96%">
        <div class="modal-header">
            <h3>Add Commission Record</h3>
            <button class="modal-close" onclick="closeModal('addCommModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/commission">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body" id="addCommBody">

                <!-- Agent + Project -->
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Agent *</label>
                        <select name="agent_id" class="form-input" required id="addAgentSel" onchange="onAgentOrProjectChange()">
                            <option value="">-- Select Agent --</option>
                            <?php foreach ($agents as $a): ?>
                            <option value="<?= $a['id'] ?>"><?= Security::e($a['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Project</label>
                        <select name="project_id" class="form-input" id="addProjectSel" onchange="onAgentOrProjectChange()">
                            <option value="">-- Select Project --</option>
                            <?php foreach ($allProjects as $p): ?>
                            <option value="<?= $p['id'] ?>" data-builder="<?= Security::e($p['builder_name']) ?>"><?= Security::e($p['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Summary badge (shown when agent+project both selected) -->
                <div id="addCommSummary" style="display:none;background:rgba(59,130,246,.08);border:1px solid rgba(59,130,246,.25);border-radius:8px;padding:10px 14px;font-size:12px;margin-top:-4px">
                    <span style="font-weight:600;color:var(--navy)">Existing records for this agent + project:</span>
                    <div style="display:flex;gap:18px;margin-top:6px">
                        <span>Total: <strong id="sumTotal">-</strong></span>
                        <span>Already Paid: <strong id="sumPaid" style="color:#10b981">-</strong></span>
                        <span>Remaining: <strong id="sumRemain" style="color:#ef4444">-</strong></span>
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Client Name</label>
                        <input type="text" name="client_name" class="form-input" placeholder="Client name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Plot / File No.</label>
                        <input type="text" name="plot_number" class="form-input" placeholder="Plot or file number">
                    </div>
                    <div class="form-group" style="grid-column:1/-1">
                        <label class="form-label">Total Commission (PKR) *</label>
                        <input type="number" step="1" min="0" name="total_commission" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sale Date</label>
                        <input type="date" name="sale_date" class="form-input" value="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Due Date</label>
                        <input type="date" name="due_date" class="form-input">
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
                        <select name="payment_status" class="form-input" id="addPaymentStatus" onchange="togglePaidFields(this,'add')">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                        </select>
                    </div>
                    <div class="form-group" id="addSourceRow">
                        <label class="form-label">Payment Source</label>
                        <select name="payment_source" class="form-input" id="addSourceSel" onchange="toggleRefField(this,'add')">
                            <option value="">-- Select --</option>
                            <option value="cash">Cash</option>
                            <option value="online">Online Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group" id="addRefRow">
                        <label class="form-label">Reference / Cheque No.</label>
                        <input type="text" name="reference_no" class="form-input" placeholder="Ref or cheque number">
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
    <div class="modal" style="max-width:640px;width:96%">
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
const AJAX_URL = '<?= APP_URL ?>/admin/accounts/commission-ajax';

function fmtPKR(n) {
    return 'PKR ' + Number(n).toLocaleString('en-PK', {maximumFractionDigits:0});
}

function onAgentOrProjectChange() {
    const agentId   = document.getElementById('addAgentSel').value;
    const projectId = document.getElementById('addProjectSel').value;
    const box = document.getElementById('addCommSummary');
    if (!agentId || !projectId) { box.style.display = 'none'; return; }
    fetch(AJAX_URL + '?agent_id=' + agentId + '&project_id=' + projectId)
        .then(r => r.json())
        .then(d => {
            if (!d.records) { box.style.display = 'none'; return; }
            document.getElementById('sumTotal').textContent  = fmtPKR(d.total_commission);
            document.getElementById('sumPaid').textContent   = fmtPKR(d.paid_amount);
            document.getElementById('sumRemain').textContent = fmtPKR(d.remaining);
            box.style.display = 'block';
        })
        .catch(() => { box.style.display = 'none'; });
}

function toggleRefField(sel, prefix) {
    const refRow = document.getElementById(prefix + 'RefRow');
    if (!refRow) return;
    const showRef = ['online','cheque','bank_transfer'].includes(sel.value);
    refRow.style.display = showRef ? '' : 'none';
}

function editComm(data) {
    document.getElementById('editCommId').value = data.id;
    const src = document.getElementById('addCommBody');
    const dst = document.getElementById('editCommBody');
    dst.innerHTML = src.innerHTML;

    // Remove summary banner from edit modal
    const sumBanner = dst.querySelector('#addCommSummary');
    if (sumBanner) sumBanner.remove();

    // Fix IDs
    const agSel  = dst.querySelector('[name="agent_id"]');
    const prSel  = dst.querySelector('[name="project_id"]');
    const paySel = dst.querySelector('[name="payment_status"]');
    const srcSel = dst.querySelector('[name="payment_source"]');
    if (agSel)  agSel.id  = 'editAgentSel';
    if (prSel)  prSel.id  = 'editProjectSel';
    if (paySel) { paySel.id = 'editPaymentStatus'; paySel.removeAttribute('onchange'); }
    if (srcSel) { srcSel.id = 'editSourceSel'; srcSel.setAttribute('onchange', "toggleRefField(this,'edit')"); }
    const refRow = dst.querySelector('#addRefRow'); if (refRow) refRow.id = 'editRefRow';

    const fields = {
        agent_id: data.agent_id,
        project_id: data.project_id || '',
        client_name: data.client_name || '',
        plot_number: data.plot_number || '',
        total_commission: data.total_commission,
        sale_date: data.sale_date ? data.sale_date.substring(0,10) : '',
        due_date:  data.due_date  ? data.due_date.substring(0,10)  : '',
        maturity_status: data.maturity_status,
        payment_status: data.payment_status,
        payment_source: data.payment_source || '',
        reference_no: data.reference_no || '',
        pending_reason_id: data.pending_reason_id || '',
        pending_notes: data.pending_notes || '',
        notes: data.notes || ''
    };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (String(opt.value) === String(val));
        } else { el.value = val ?? ''; }
    }
    // Toggle ref field visibility
    if (srcSel) toggleRefField(srcSel, 'edit');
    openModal('editCommModal');
}
</script>

<style>
@media print {
    .filter-bar, form[method="GET"], .modal-overlay, .page-header-actions { display: none !important; }
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
