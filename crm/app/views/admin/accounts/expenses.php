<?php
Security::requireAdmin();

$pageTitle  = 'Accounts — Expenses';
$activePage = 'accounts';

$pkr        = fn($v) => 'PKR ' . number_format((float)$v, 0);
$typeLabels = ['marketing' => 'Marketing', 'general' => 'General', 'salary' => 'Salary'];
$typeColors = ['marketing' => '#3b82f6', 'general' => '#8b5cf6', 'salary' => '#f59e0b'];

// Compute period label for display and print header
$periodLabel = 'All Time';
if ($period === 'today')
    $periodLabel = 'Today, ' . date('d M Y');
elseif ($period === 'yesterday')
    $periodLabel = 'Yesterday, ' . date('d M Y', strtotime('-1 day'));
elseif ($period === 'this_week')
    $periodLabel = 'This Week (' . date('d M', strtotime('monday this week')) . ' - ' . date('d M Y') . ')';
elseif ($period === 'last_week')
    $periodLabel = 'Last Week (' . date('d M', strtotime('monday last week')) . ' - ' . date('d M Y', strtotime('sunday last week')) . ')';
elseif ($period === 'this_month')
    $periodLabel = date('F Y');
elseif ($period === 'last_month')
    $periodLabel = date('F Y', strtotime('last month'));
elseif ($period === 'this_year')
    $periodLabel = 'Year ' . date('Y');
elseif ($dateFrom || $dateTo)
    $periodLabel = ($dateFrom ? date('d M Y', strtotime($dateFrom)) : 'Start') . ' to ' . ($dateTo ? date('d M Y', strtotime($dateTo)) : 'Today');

// Filtered totals (from current result set)
$fTotal     = array_sum(array_column($expenses, 'amount'));
$fMarketing = array_sum(array_map(fn($e) => $e['type'] === 'marketing' ? (float)$e['amount'] : 0, $expenses));
$fGeneral   = array_sum(array_map(fn($e) => $e['type'] === 'general'   ? (float)$e['amount'] : 0, $expenses));
$fSalary    = array_sum(array_map(fn($e) => $e['type'] === 'salary'    ? (float)$e['amount'] : 0, $expenses));

// Helper: build URL preserving active filters
$filterUrl = fn(array $extra = []) => APP_URL . '/admin/accounts/expenses?' . http_build_query(array_filter(array_merge(
    ['period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'type' => $type],
    $extra
), fn($v) => $v !== '' && $v !== null));

ob_start();
?>

<!-- Print-only styles -->
<style>
@media print {
    .sidebar, .topbar, .page-header-actions, .no-print,
    .sub-nav-wrap, .filter-section, .stat-chips-row,
    .type-tabs-row, .data-table td:last-child, .data-table th:last-child
    { display: none !important; }
    .print-header { display: flex !important; }
    .print-summary { display: flex !important; }
    .main-content { margin-left: 0 !important; }
    .content-wrapper { padding: 16px !important; }
    .card { box-shadow: none !important; border: 1px solid #e5e7eb !important; }
    body { background: #fff !important; color: #111 !important; }
    .data-table th { background: #002147 !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .data-table tr:nth-child(even) td { background: #f9fafb !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    .page-header { margin-bottom: 8px !important; }
    .page-header h1 { font-size: 18px !important; }
    .print-footer { display: block !important; margin-top: 24px; font-size: 11px; color: #6b7280; border-top: 1px solid #e5e7eb; padding-top: 10px; }
}
.print-header { display: none; }
.print-summary { display: none; }
.print-footer  { display: none; }
</style>

<?php if (!empty($_SESSION['success'])): ?>
<div class="alert alert-success no-print">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
    <?= Security::e($_SESSION['success']) ?></div>
<?php unset($_SESSION['success']); endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
<div class="alert alert-error no-print">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
    <?= Security::e($_SESSION['error']) ?></div>
<?php unset($_SESSION['error']); endif; ?>

<!-- Print header (hidden on screen, visible when printing) -->
<div class="print-header" style="justify-content:space-between;align-items:flex-start;border-bottom:3px solid #002147;padding-bottom:14px;margin-bottom:20px">
    <div>
        <div style="font-size:22px;font-weight:800;color:#002147">Expense Report</div>
        <div style="font-size:13px;color:#555;margin-top:3px">Period: <?= htmlspecialchars($periodLabel) ?></div>
        <?php if ($type): ?><div style="font-size:12px;color:#555">Type: <?= htmlspecialchars($typeLabels[$type] ?? ucfirst($type)) ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
        <div style="font-size:15px;font-weight:700;color:#002147">HK Builders &amp; Developers</div>
        <div style="font-size:12px;color:#555">Generated: <?= date('d M Y, h:i A') ?></div>
    </div>
</div>

<!-- Print summary (hidden on screen) -->
<div class="print-summary" style="flex-wrap:wrap;gap:16px;margin-bottom:20px">
    <?php foreach ([
        ['Total',     $fTotal,     '#002147'],
        ['Marketing', $fMarketing, '#3b82f6'],
        ['General',   $fGeneral,   '#8b5cf6'],
        ['Salary',    $fSalary,    '#f59e0b'],
    ] as [$lbl, $amt, $clr]): ?>
    <div style="border:1px solid #e5e7eb;border-radius:6px;padding:8px 14px;min-width:140px">
        <div style="font-size:16px;font-weight:700;color:<?= $clr ?>"><?= $pkr($amt) ?></div>
        <div style="font-size:11px;color:#6b7280;margin-top:2px"><?= $lbl ?></div>
    </div>
    <?php endforeach; ?>
</div>

<div class="page-header">
    <div class="page-header-left">
        <h1>Expenses</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <a href="<?= APP_URL ?>/admin/accounts">Accounts</a> <span class="sep">/</span> <span class="current">Expenses</span></div>
    </div>
    <div class="page-header-actions no-print">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
        <button class="btn btn-primary" onclick="openModal('addExpModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Expense
        </button>
    </div>
</div>

<!-- Sub-nav -->
<div class="sub-nav-wrap" style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/accounts',            'Overview',   false],
        [APP_URL.'/admin/accounts/commission', 'Commission', false],
        [APP_URL.'/admin/accounts/expenses',   'Expenses',   true],
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

<!-- All-time stat chips -->
<div class="stat-chips-row no-print" style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px">
    <?php foreach ([
        ['All Time Total', $pkr($stats['total']    ?? 0), '#6366f1', ''],
        ['Marketing',      $pkr($stats['marketing'] ?? 0), '#3b82f6', '?type=marketing'],
        ['Salary',         $pkr($stats['salary']   ?? 0), '#f59e0b', '?type=salary'],
        ['General',        $pkr($stats['general']  ?? 0), '#8b5cf6', '?type=general'],
    ] as [$label, $amount, $color, $qs]): ?>
    <a href="<?= APP_URL . '/admin/accounts/expenses' . $qs ?>" style="text-decoration:none">
        <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:8px 16px">
            <span style="font-size:15px;font-weight:700;color:<?= $color ?>"><?= $amount ?></span>
            <span style="font-size:12px;color:var(--text-muted);margin-left:6px"><?= $label ?></span>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- ===== FILTER SECTION ===== -->
<div class="filter-section no-print" style="background:var(--bg-card);border:1px solid var(--border);border-radius:10px;padding:16px 18px;margin-bottom:20px">

    <!-- Quick period buttons -->
    <div style="margin-bottom:14px">
        <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Quick Period</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            <?php
            $periods = [
                ''           => 'All Time',
                'today'      => 'Today',
                'yesterday'  => 'Yesterday',
                'this_week'  => 'This Week',
                'last_week'  => 'Last Week',
                'this_month' => 'This Month',
                'last_month' => 'Last Month',
                'this_year'  => 'This Year',
            ];
            foreach ($periods as $val => $label):
                $isActive = ($period === $val) && !($val === '' && ($dateFrom || $dateTo));
            ?>
            <a href="<?= APP_URL ?>/admin/accounts/expenses?period=<?= urlencode($val) ?><?= $type ? '&type='.urlencode($type) : '' ?>"
               style="padding:5px 13px;border-radius:20px;font-size:12px;font-weight:600;text-decoration:none;white-space:nowrap;
                      background:<?= $isActive ? 'var(--navy)' : 'var(--bg)' ?>;
                      color:<?= $isActive ? '#fff' : 'var(--text-muted)' ?>;
                      border:1px solid <?= $isActive ? 'var(--navy)' : 'var(--border)' ?>">
                <?= $label ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Custom date range -->
    <div>
        <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px">Custom Range</div>
        <form method="GET" action="<?= APP_URL ?>/admin/accounts/expenses">
            <?php if ($type): ?><input type="hidden" name="type" value="<?= Security::e($type) ?>"><?php endif; ?>
            <div style="display:flex;flex-wrap:wrap;align-items:center;gap:8px">
                <div style="display:flex;align-items:center;gap:6px">
                    <label style="font-size:12px;color:var(--text-muted)">From</label>
                    <input type="date" name="date_from" value="<?= htmlspecialchars($dateFrom) ?>" class="filter-input" style="width:150px">
                </div>
                <div style="display:flex;align-items:center;gap:6px">
                    <label style="font-size:12px;color:var(--text-muted)">To</label>
                    <input type="date" name="date_to" value="<?= htmlspecialchars($dateTo) ?>" class="filter-input" style="width:150px">
                </div>
                <button type="submit" class="btn btn-secondary">Apply</button>
                <a href="<?= APP_URL ?>/admin/accounts/expenses<?= $type ? '?type='.urlencode($type) : '' ?>" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<!-- Type tabs -->
<div class="type-tabs-row no-print" style="display:flex;gap:8px;margin-bottom:16px">
    <?php foreach (['' => 'All', 'marketing' => 'Marketing', 'general' => 'General'] as $val => $label): ?>
    <a href="<?= APP_URL ?>/admin/accounts/expenses?type=<?= $val ?><?= $period ? '&period='.urlencode($period) : '' ?><?= (!$period && $dateFrom) ? '&date_from='.urlencode($dateFrom) : '' ?><?= (!$period && $dateTo) ? '&date_to='.urlencode($dateTo) : '' ?>"
       style="padding:6px 14px;border-radius:20px;font-size:13px;font-weight:600;text-decoration:none;
              background:<?= $type === $val ? 'var(--navy)' : 'var(--bg-card)' ?>;
              color:<?= $type === $val ? '#fff' : 'var(--text-muted)' ?>;
              border:1px solid <?= $type === $val ? 'var(--navy)' : 'var(--border)' ?>">
        <?= $label ?>
    </a>
    <?php endforeach; ?>
    <?php if ($period || $dateFrom): ?>
    <span style="margin-left:auto;font-size:12px;color:var(--text-muted);align-self:center">
        Showing: <strong style="color:var(--text)"><?= htmlspecialchars($periodLabel) ?></strong>
        &middot; <?= count($expenses) ?> records
        &middot; <strong style="color:var(--gold)"><?= $pkr($fTotal) ?></strong>
    </span>
    <?php endif; ?>
</div>

<!-- Table -->
<div class="card" style="padding:0;overflow:hidden">
<?php if (empty($expenses)): ?>
<div style="padding:48px;text-align:center;color:var(--text-muted)">No expenses found for this period.</div>
<?php else: ?>
<div style="overflow-x:auto">
<table class="data-table" style="min-width:700px">
    <thead>
        <tr>
            <th>#</th>
            <th>Type</th>
            <th>Category</th>
            <th>Description</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Added By</th>
            <th class="no-print"></th>
        </tr>
    </thead>
    <tbody>
    <?php $idx = 0; foreach ($expenses as $e): $idx++; ?>
    <tr>
        <td style="color:var(--text-muted);font-size:12px"><?= $idx ?></td>
        <td>
            <span style="padding:3px 10px;border-radius:12px;font-size:11px;font-weight:600;
                background:<?= $e['type'] === 'marketing' ? 'rgba(59,130,246,.12)' : ($e['type'] === 'general' ? 'rgba(139,92,246,.12)' : 'rgba(245,158,11,.12)') ?>;
                color:<?= $typeColors[$e['type']] ?? '#6b7280' ?>">
                <?= $typeLabels[$e['type']] ?? ucfirst($e['type']) ?>
            </span>
        </td>
        <td style="font-weight:600"><?= Security::e($e['category']) ?></td>
        <td style="color:var(--text-muted);max-width:220px"><?= Security::e($e['description'] ?? '-') ?></td>
        <td style="font-weight:600;color:var(--gold)"><?= $pkr($e['amount']) ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($e['expense_date'])) ?></td>
        <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($e['created_by_name'] ?? '-') ?></td>
        <td class="no-print">
            <div style="display:flex;gap:6px">
                <button class="btn btn-sm" onclick='editExp(<?= json_encode($e) ?>)'>Edit</button>
                <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="form_action" value="delete">
                    <input type="hidden" name="expense_id"  value="<?= (int)$e['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-danger"
                        onclick="return confirm('Delete this expense?')">Delete</button>
                </form>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
    <!-- Total row -->
    <tr style="background:var(--bg-card)">
        <td colspan="4" style="font-weight:700;font-size:13px;padding:10px 12px">Total (<?= count($expenses) ?> records)</td>
        <td style="font-weight:700;font-size:14px;color:var(--gold)"><?= $pkr($fTotal) ?></td>
        <td colspan="3" class="no-print"></td>
        <td colspan="2" style="display:none" class="print-only"></td>
    </tr>
    </tbody>
</table>
</div>
<?php endif; ?>
</div>

<!-- Print footer -->
<div class="print-footer">
    HK Builders &amp; Developers &nbsp;&middot;&nbsp; Expense Report &nbsp;&middot;&nbsp; <?= htmlspecialchars($periodLabel) ?> &nbsp;&middot;&nbsp; Generated <?= date('d M Y, h:i A') ?>
</div>

<!-- Add Modal -->
<div class="modal-overlay no-print" id="addExpModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Add Expense</h3>
            <button class="modal-close" onclick="closeModal('addExpModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="add">
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label class="form-label">Type *</label>
                        <select name="type" class="form-input" required>
                            <option value="marketing">Marketing</option>
                            <option value="general">General</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Category *</label>
                        <input type="text" name="category" class="form-input" placeholder="e.g. Facebook Ads" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Amount (PKR) *</label>
                        <input type="number" step="1" min="0" name="amount" class="form-input" value="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="expense_date" class="form-input" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-input" rows="2" placeholder="Details..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addExpModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay no-print" id="editExpModal">
    <div class="modal" style="max-width:520px;width:96%">
        <div class="modal-header">
            <h3>Edit Expense</h3>
            <button class="modal-close" onclick="closeModal('editExpModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/accounts/expenses" id="editExpForm">
            <?= Security::csrfField() ?>
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="expense_id"  id="editExpId">
            <div class="modal-body" id="editExpBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editExpModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function editExp(data) {
    document.getElementById('editExpId').value = data.id;
    const src = document.querySelector('#addExpModal .modal-body');
    const dst = document.getElementById('editExpBody');
    dst.innerHTML = src.innerHTML;
    const fields = { type: data.type, category: data.category, amount: data.amount,
                     expense_date: data.expense_date, description: data.description || '' };
    for (const [key, val] of Object.entries(fields)) {
        const el = dst.querySelector('[name="' + key + '"]');
        if (!el) continue;
        if (el.tagName === 'SELECT') {
            for (const opt of el.options) opt.selected = (opt.value === String(val));
        } else { el.value = val ?? ''; }
    }
    openModal('editExpModal');
}
</script>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
