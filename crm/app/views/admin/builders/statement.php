<?php
$pkr = fn($v) => 'PKR ' . number_format((float)$v, 0);

$totalCommission = array_sum(array_column($units, 'commission_amount'));
$totalPaid       = array_sum(array_column($payments, 'amount'));
$balanceDue      = max(0, $totalCommission - $totalPaid);
$payLabels       = ['paid' => 'Paid', 'partial' => 'Partial', 'unpaid' => 'Unpaid'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Builder Statement - <?= htmlspecialchars($builder['name'] ?? '') ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:13px;color:#1a1a2e;background:#fff;padding:32px}
h1{font-size:22px;font-weight:700;margin-bottom:2px}
h2{font-size:15px;font-weight:700;margin:24px 0 8px}
.header{display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #002147;padding-bottom:16px;margin-bottom:20px}
.company{font-size:11px;color:#555;text-align:right}
.company strong{display:block;font-size:14px;font-weight:700;color:#002147}
.meta{display:flex;gap:24px;flex-wrap:wrap;background:#f8f9fc;border:1px solid #e5e7eb;border-radius:6px;padding:14px 18px;margin-bottom:20px}
.meta-item label{font-size:10px;text-transform:uppercase;color:#6b7280;display:block;margin-bottom:2px}
.meta-item span{font-size:14px;font-weight:600}
.stat-row{display:flex;gap:16px;flex-wrap:wrap;margin-bottom:24px}
.stat{border:1px solid #e5e7eb;border-radius:6px;padding:10px 16px;min-width:140px}
.stat .val{font-size:18px;font-weight:700;color:#002147}
.stat .lbl{font-size:11px;color:#6b7280;margin-top:2px}
.stat.due{border-color:#fecaca;background:#fef2f2}
.stat.due .val{color:#b91c1c}
table{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:24px}
th{background:#002147;color:#fff;padding:7px 10px;text-align:left;font-weight:600}
td{padding:7px 10px;border-bottom:1px solid #f0f0f0}
tr:nth-child(even) td{background:#fafafa}
.badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:10px;font-weight:600}
.badge-advance{background:#ede9fe;color:#5b21b6}
.badge-installment{background:#dbeafe;color:#1d4ed8}
.badge-final{background:#dcfce7;color:#15803d}
.badge-other{background:#f3f4f6;color:#374151}
.badge-active{background:#dcfce7;color:#15803d}
.badge-completed{background:#dbeafe;color:#1d4ed8}
.badge-on_hold{background:#fef9c3;color:#a16207}
.badge-paid{background:#dcfce7;color:#15803d}
.badge-partial{background:#fef3c7;color:#b45309}
.badge-unpaid{background:#fee2e2;color:#b91c1c}
.badge-mature{background:#dcfce7;color:#15803d}
.badge-immature{background:#fef3c7;color:#b45309}
.total-row td{font-weight:700;background:#f0f4ff;border-top:2px solid #002147}
.footer{margin-top:40px;border-top:1px solid #e5e7eb;padding-top:16px;display:flex;justify-content:space-between;font-size:11px;color:#9ca3af}
@media print{
    body{padding:16px}
    .no-print{display:none!important}
    @page{margin:12mm}
}
@media screen and (max-width:640px){
    body{padding:14px}
    .header{flex-direction:column;gap:10px}
    .company{text-align:left}
    .stat{flex:1 1 140px;min-width:0}
    table{display:block;overflow-x:auto;white-space:nowrap}
}
</style>
</head>
<body>

<div class="header">
    <div>
        <h1><?= htmlspecialchars($builder['name']) ?></h1>
        <p style="color:#555;margin-top:4px">Builder Statement</p>
    </div>
    <div class="company">
        <strong>HK Builders &amp; Developers</strong>
        CRM System<br>
        Generated: <?= date('d M Y, h:i A') ?>
    </div>
</div>

<button class="no-print" onclick="window.print()"
    style="margin-bottom:20px;padding:8px 18px;background:#002147;color:#fff;border:none;border-radius:6px;font-size:13px;cursor:pointer">
    Print / Save PDF
</button>

<!-- Builder info -->
<div class="meta">
    <?php if (!empty($builder['contact_person'])): ?>
    <div class="meta-item"><label>Contact</label><span><?= htmlspecialchars($builder['contact_person']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($builder['phone'])): ?>
    <div class="meta-item"><label>Phone</label><span><?= htmlspecialchars($builder['phone']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($builder['email'])): ?>
    <div class="meta-item"><label>Email</label><span><?= htmlspecialchars($builder['email']) ?></span></div>
    <?php endif; ?>
    <?php if (!empty($builder['address'])): ?>
    <div class="meta-item"><label>Address</label><span><?= htmlspecialchars($builder['address']) ?></span></div>
    <?php endif; ?>
</div>

<!-- Summary -->
<div class="stat-row">
    <div class="stat"><div class="val"><?= count($projects) ?></div><div class="lbl">Projects</div></div>
    <div class="stat"><div class="val"><?= count($units) ?></div><div class="lbl">Units</div></div>
    <div class="stat"><div class="val"><?= $pkr($totalCommission) ?></div><div class="lbl">Total Commission</div></div>
    <div class="stat"><div class="val"><?= $pkr($totalPaid) ?></div><div class="lbl">Total Paid</div></div>
    <div class="stat due"><div class="val"><?= $pkr($balanceDue) ?></div><div class="lbl">Balance Due</div></div>
</div>

<!-- Projects -->
<h2>Projects</h2>
<?php if (empty($projects)): ?>
<p style="color:#6b7280">No projects on record.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Project Name</th>
            <th>Location</th>
            <th>Units</th>
            <th>Commission</th>
            <th>Paid</th>
            <th>Unpaid</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php $pIdx = 0; foreach ($projects as $p): $pIdx++; ?>
    <tr>
        <td><?= $pIdx ?></td>
        <td style="font-weight:600"><?= htmlspecialchars($p['name']) ?></td>
        <td><?= htmlspecialchars($p['location'] ?? '-') ?></td>
        <td><?= (int)$p['unit_count'] ?></td>
        <td><?= $pkr($p['total_commission']) ?></td>
        <td><?= $pkr($p['paid_amount']) ?></td>
        <td><?= $pkr(max(0, (float)$p['total_commission'] - (float)$p['paid_amount'])) ?></td>
        <td><span class="badge badge-<?= $p['status'] ?>"><?= ucfirst(str_replace('_', ' ', $p['status'])) ?></span></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>

<!-- Units -->
<h2>Units</h2>
<?php if (empty($units)): ?>
<p style="color:#6b7280">No units on record.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Project</th>
            <th>Unit</th>
            <th>Block</th>
            <th>Category / Size</th>
            <th>Commission</th>
            <th>Paid</th>
            <th>Balance</th>
            <th>Maturity</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    <?php $uIdx = 0; foreach ($units as $u): $uIdx++; ?>
    <tr>
        <td><?= $uIdx ?></td>
        <td><?= htmlspecialchars($u['project_name'] ?? '-') ?></td>
        <td style="font-weight:600"><?= htmlspecialchars($u['unit_number']) ?></td>
        <td><?= htmlspecialchars($u['block_number'] ?: '-') ?></td>
        <td><?= htmlspecialchars(implode(' / ', array_filter([$u['category'], $u['plot_size']])) ?: '-') ?></td>
        <td><?= $pkr($u['commission_amount']) ?></td>
        <td><?= $pkr($u['paid_amount']) ?></td>
        <td><?= $pkr($u['balance']) ?></td>
        <td><span class="badge badge-<?= $u['maturity_status'] ?>"><?= ucfirst($u['maturity_status']) ?></span></td>
        <td><span class="badge badge-<?= $u['pay_status'] ?>"><?= $payLabels[$u['pay_status']] ?></span></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-row">
        <td colspan="5">Total</td>
        <td><?= $pkr($totalCommission) ?></td>
        <td><?= $pkr(array_sum(array_column($units, 'paid_amount'))) ?></td>
        <td><?= $pkr(array_sum(array_column($units, 'balance'))) ?></td>
        <td colspan="2"></td>
    </tr>
    </tbody>
</table>
<?php endif; ?>

<!-- Payments -->
<h2>Payment History</h2>
<?php if (empty($payments)): ?>
<p style="color:#6b7280">No payments recorded.</p>
<?php else: ?>
<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Project</th>
            <th>Unit</th>
            <th>Amount</th>
            <th>Type</th>
            <th>Reference</th>
            <th>Notes</th>
        </tr>
    </thead>
    <tbody>
    <?php $idx = 0; foreach ($payments as $p): $idx++; ?>
    <tr>
        <td><?= $idx ?></td>
        <td style="white-space:nowrap"><?= date('d M Y', strtotime($p['payment_date'])) ?></td>
        <td><?= htmlspecialchars($p['project_name'] ?? '-') ?></td>
        <td><?= $p['unit_number'] !== null ? htmlspecialchars($p['unit_number']) : '-' ?></td>
        <td style="font-weight:600"><?= $pkr($p['amount']) ?></td>
        <td><span class="badge badge-<?= $p['payment_type'] ?>"><?= ucfirst($p['payment_type']) ?></span></td>
        <td><?= htmlspecialchars($p['reference'] ?? '-') ?></td>
        <td><?= htmlspecialchars($p['notes'] ?? '-') ?></td>
    </tr>
    <?php endforeach; ?>
    <tr class="total-row">
        <td colspan="4">Total</td>
        <td><?= $pkr($totalPaid) ?></td>
        <td colspan="3"></td>
    </tr>
    </tbody>
</table>
<?php endif; ?>

<?php if (!empty($builder['notes'])): ?>
<h2>Notes</h2>
<p style="color:#374151;font-size:13px"><?= htmlspecialchars($builder['notes']) ?></p>
<?php endif; ?>

<div class="footer">
    <span>HK Builders &amp; Developers - CRM</span>
    <span>Statement generated on <?= date('d M Y') ?></span>
</div>

</body>
</html>
