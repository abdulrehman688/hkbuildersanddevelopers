<?php
Security::requireAdmin();
$agent  = $detail;
$stats  = $agent['stats'];
$month  = $_GET['month'] ?? '';

$pageTitle  = 'Agent: ' . ($agent['name'] ?? 'Detail');
$activePage = 'agents';
ob_start();

$total      = (int)$stats['total_leads'];
$won        = (int)$stats['st_won'];
$lost       = (int)$stats['st_lost'];
$closed     = $won + $lost;
$conversion = $closed > 0 ? round(($won / $closed) * 100) : 0;
$initials   = strtoupper(substr($agent['name'] ?? 'A', 0, 1));
$isSM       = $agent['role'] === 'sales_manager';

// Commission from clients table (accurate)
$totalBooking  = (float)($commissionSummary['total_booking']     ?? 0);
$estCommission = (float)($commissionSummary['total_commission']  ?? 0);

// Follow-up completion rate
$fuTotal = (int)$followUpStats['total'];
$fuRate  = $fuTotal > 0 ? round(((int)$followUpStats['done'] / $fuTotal) * 100) : 0;

// Chart data
$chartLabels = json_encode(array_column($monthlyTrend, 'label'));
$chartTotal  = json_encode(array_map(fn($r) => (int)$r['total'], $monthlyTrend));
$chartWon    = json_encode(array_map(fn($r) => (int)$r['won'],   $monthlyTrend));
?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($agent['name'] ?? 'Agent') ?></h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <a href="<?= APP_URL ?>/admin/agents">Agents</a>
            <span class="sep">/</span>
            <span class="current"><?= Security::e($agent['name'] ?? 'Detail') ?></span>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.056 48.056 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print Report
        </button>
    </div>
</div>

<!-- Month Filter -->
<form method="GET" action="<?= APP_URL ?>/admin/agent/<?= (int)$agent['id'] ?>" style="margin-bottom:20px;display:flex;align-items:center;gap:12px;flex-wrap:wrap">
    <label style="font-size:13px;font-weight:500;color:var(--text-muted)">Filter by month:</label>
    <select name="month" onchange="this.form.submit()" style="font-size:13px;padding:7px 12px;border:1px solid var(--border);border-radius:8px;background:#fff">
        <option value="">All Time</option>
        <?php foreach ($months as $m): ?>
            <option value="<?= Security::e($m['ym']) ?>" <?= $month === $m['ym'] ? 'selected' : '' ?>><?= Security::e($m['label']) ?></option>
        <?php endforeach; ?>
    </select>
    <?php if ($month): ?>
        <a href="<?= APP_URL ?>/admin/agent/<?= (int)$agent['id'] ?>" style="font-size:13px;color:var(--text-muted)">Clear</a>
    <?php endif; ?>
</form>

<div style="display:grid;grid-template-columns:300px 1fr;gap:24px;align-items:start">

    <!-- LEFT: Profile Card -->
    <div style="display:flex;flex-direction:column;gap:18px">
        <div class="card" style="padding:20px">
            <div style="text-align:center;padding:0 0 14px">
                <div class="sidebar-avatar" style="width:70px;height:70px;font-size:28px;margin:0 auto 12px;<?= $isSM ? 'background:var(--gold);color:var(--navy)' : '' ?>">
                    <?= Security::e($initials) ?>
                </div>
                <div style="font-size:18px;font-weight:600;color:var(--navy)"><?= Security::e($agent['name'] ?? '—') ?></div>
                <div style="margin-top:6px">
                    <?php if ($isSM): ?>
                        <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Sales Manager</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fa">Agent</span>
                    <?php endif; ?>
                    <?php if ($agent['status'] === 'suspended'): ?>
                        <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca;margin-left:4px">Suspended</span>
                    <?php endif; ?>
                </div>
                <?php if ($agent['designation']): ?>
                    <div style="font-size:13px;color:var(--text-muted);margin-top:6px"><?= Security::e($agent['designation']) ?></div>
                <?php endif; ?>
            </div>
            <hr style="border:none;border-top:1px solid var(--border);margin:0 0 14px">
            <div style="display:flex;flex-direction:column;gap:10px;padding:0 0 4px">
                <?php foreach ([
                    ['Email',    $agent['email']          ?? null],
                    ['Phone',    $agent['phone']          ?? null],
                    ['CNIC',     $agent['cnic']           ?? null],
                    ['Guardian', $agent['guardian_phone'] ?? null],
                    ['Address',  $agent['address']        ?? null],
                ] as [$label, $value]): ?>
                    <?php if ($value): ?>
                    <div style="display:flex;gap:10px;font-size:13px">
                        <span style="color:var(--text-muted);width:70px;flex-shrink:0"><?= $label ?></span>
                        <span style="color:var(--navy);font-weight:500;word-break:break-word"><?= Security::e($value) ?></span>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
                <div style="display:flex;gap:10px;font-size:13px">
                    <span style="color:var(--text-muted);width:70px;flex-shrink:0">Joined</span>
                    <span style="color:var(--navy);font-weight:500"><?= date('d M Y', strtotime($agent['created_at'])) ?></span>
                </div>
                <div style="display:flex;gap:10px;font-size:13px">
                    <span style="color:var(--text-muted);width:70px;flex-shrink:0">Salary</span>
                    <span style="color:var(--navy);font-weight:500">Rs. <?= number_format((float)$agent['base_salary']) ?>/mo</span>
                </div>
                <div style="display:flex;gap:10px;font-size:13px">
                    <span style="color:var(--text-muted);width:70px;flex-shrink:0">Commission</span>
                    <span style="color:var(--navy);font-weight:500"><?= number_format((float)$agent['commission_rate'], 1) ?>% of booking</span>
                </div>
            </div>
        </div>

        <!-- Follow-up Stats Card -->
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:14px">Follow-up Discipline</div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
                <?php foreach ([
                    ['Total',   $followUpStats['total'],   'var(--navy)'],
                    ['Done',    $followUpStats['done'],    '#10b981'],
                    ['Pending', $followUpStats['pending'], '#f59e0b'],
                    ['Overdue', $followUpStats['overdue'], '#ef4444'],
                ] as [$label, $val, $color]): ?>
                <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:12px;text-align:center">
                    <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$val ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- Completion bar -->
            <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px">Completion rate</div>
            <div style="background:#f0f0f0;border-radius:20px;height:8px;overflow:hidden">
                <div style="height:100%;width:<?= $fuRate ?>%;background:linear-gradient(90deg,#10b981,#34d399);border-radius:20px;transition:width .4s"></div>
            </div>
            <div style="font-size:13px;font-weight:600;color:#10b981;margin-top:6px"><?= $fuRate ?>% completed</div>
            <?php if ((int)$followUpStats['overdue'] > 0): ?>
            <div style="margin-top:10px;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:8px 12px;font-size:12px;color:#dc2626">
                ⚠ <?= (int)$followUpStats['overdue'] ?> overdue follow-up<?= $followUpStats['overdue'] > 1 ? 's' : '' ?> need attention
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- RIGHT: Analytics -->
    <div style="display:flex;flex-direction:column;gap:20px">

        <!-- Summary Stats Row -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Total Leads</div>
                <div class="stat-value"><?= $total ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Won</div>
                <div class="stat-value" style="color:#10b981"><?= $won ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Lost / Dead</div>
                <div class="stat-value" style="color:#ef4444"><?= (int)$stats['not_interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">In Pipeline</div>
                <div class="stat-value" style="color:#f59e0b"><?= (int)$stats['interested'] ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Conversion</div>
                <div class="stat-value"><?= $conversion ?>%</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">Est. Commission</div>
                <div class="stat-value" style="font-size:16px;color:#10b981">Rs. <?= number_format($estCommission) ?></div>
            </div>
        </div>

        <!-- Career History -->
        <div class="card" style="padding:0">
            <div style="padding:16px 20px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px;color:var(--gold)"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0M12 12.75h.008v.008H12v-.008z"/></svg>
                <span style="font-size:14px;font-weight:600;color:var(--navy)">Career History</span>
            </div>

            <!-- Commission Summary Card -->
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:1px;background:var(--border);border-bottom:1px solid var(--border)">
                <?php foreach ([
                    ['Joined',              date('d M Y', strtotime($agent['created_at'])),              'var(--navy)',  false],
                    ['Total Clients',       (int)($commissionSummary['total_clients'] ?? 0),              'var(--navy)',  false],
                    ['Total Business',      'Rs. ' . number_format($totalBooking),                        '#10b981',     false],
                    ['Commission Rate',     number_format((float)$agent['commission_rate'], 1) . '%',     'var(--navy)',  false],
                    ['Total Earned',        'Rs. ' . number_format($estCommission),                       '#10b981',     false],
                    ['From Mature Files',   'Rs. ' . number_format((float)($commissionSummary['mature_commission']   ?? 0)), '#10b981', false],
                    ['From Immature Files', 'Rs. ' . number_format((float)($commissionSummary['immature_commission'] ?? 0)), '#f59e0b', false],
                ] as [$label, $value, $color, $_]): ?>
                <div style="background:var(--card-bg,var(--bg));padding:14px 18px">
                    <div style="font-size:11px;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px"><?= $label ?></div>
                    <div style="font-size:15px;font-weight:700;color:<?= $color ?>"><?= $value ?></div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Year-by-Year Table -->
            <?php if (!empty($careerByYear)): ?>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Year</th>
                            <th style="text-align:center">Assigned</th>
                            <th style="text-align:center">Won</th>
                            <th style="text-align:center">Lost</th>
                            <th>Booking Value</th>
                            <th>Commission</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($careerByYear as $yr):
                        $yrWon   = (int)$yr['won'];
                        $yrLost  = (int)$yr['lost'];
                        $yrCl    = $yrWon + $yrLost;
                        $yrConv  = $yrCl > 0 ? round(($yrWon / $yrCl) * 100) : 0;
                    ?>
                    <tr>
                        <td style="font-weight:700;color:var(--navy);font-size:15px"><?= (int)$yr['year'] ?></td>
                        <td style="text-align:center;font-weight:600"><?= (int)$yr['total_assigned'] ?></td>
                        <td style="text-align:center;color:#10b981;font-weight:600"><?= $yrWon ?></td>
                        <td style="text-align:center;color:#ef4444;font-weight:500"><?= $yrLost ?></td>
                        <td style="font-weight:600;color:var(--navy)">
                            <?= $yr['booking_value'] > 0 ? 'Rs. ' . number_format((float)$yr['booking_value']) : '—' ?>
                        </td>
                        <td style="color:#10b981;font-weight:600">
                            <?= $yr['commission'] > 0 ? 'Rs. ' . number_format((float)$yr['commission']) : '—' ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Monthly Trend Chart -->
        <?php if (!empty($monthlyTrend)): ?>
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:16px">
                Monthly Trend — Last 6 Months
            </div>
            <canvas id="trendChart" height="90"></canvas>
        </div>
        <?php endif; ?>

        <!-- Status Breakdown -->
        <div class="card" style="padding:20px">
            <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:14px">Status Breakdown</div>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px">
                <?php foreach ([
                    ['New',         $stats['st_new'],         '#3B82F6'],
                    ['Contacted',   $stats['st_contacted'],   '#8B5CF6'],
                    ['Qualified',   $stats['st_qualified'],   '#F59E0B'],
                    ['Proposal',    $stats['st_proposal'],    '#EC4899'],
                    ['Negotiation', $stats['st_negotiation'], '#F97316'],
                    ['Won',         $stats['st_won'],         '#10B981'],
                    ['Lost',        $stats['st_lost'],        '#EF4444'],
                    ['Dead',        $stats['st_dead'],        '#6B7280'],
                ] as [$label, $count, $color]): ?>
                <div style="background:#f9fafb;border:1px solid var(--border);border-radius:10px;padding:12px 14px;border-left:3px solid <?= $color ?>">
                    <div style="font-size:22px;font-weight:700;color:<?= $color ?>"><?= (int)$count ?></div>
                    <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Lead Source Breakdown -->
        <?php if (!empty($sourceBreakdown)): ?>
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">
                Lead Source Breakdown
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Source</th>
                            <th>Total Leads</th>
                            <th>Won</th>
                            <th>Lost</th>
                            <th>Conversion</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sourceBreakdown as $src):
                        $sWon    = (int)$src['won'];
                        $sLost   = (int)$src['lost'];
                        $sClosed = $sWon + $sLost;
                        $sConv   = $sClosed > 0 ? round(($sWon / $sClosed) * 100) : 0;
                    ?>
                    <tr>
                        <td style="font-weight:500;color:var(--navy)"><?= Security::e($src['source_name']) ?></td>
                        <td style="font-weight:600"><?= (int)$src['total'] ?></td>
                        <td style="color:#10b981;font-weight:500"><?= $sWon ?></td>
                        <td style="color:#ef4444;font-weight:500"><?= $sLost ?></td>
                        <td>
                            <?php if ($sClosed > 0): ?>
                            <div style="display:flex;align-items:center;gap:8px">
                                <div class="conv-bar" style="width:60px"><div class="conv-fill" style="width:<?= $sConv ?>%"></div></div>
                                <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $sConv ?>%</span>
                            </div>
                            <?php else: ?>
                                <span style="color:var(--text-muted);font-size:12px">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Won Deals -->
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:14px;font-weight:600;color:var(--navy)">Won Deals (<?= count($wonDeals) ?>)</span>
                <?php if ($totalBooking > 0): ?>
                <span style="font-size:12px;color:var(--text-muted);display:flex;gap:12px;flex-wrap:wrap">
                    <span>Booking: <b style="color:var(--navy)">Rs. <?= number_format($totalBooking) ?></b></span>
                    <span>Commission: <b style="color:#10b981">Rs. <?= number_format($estCommission) ?></b></span>
                    <span>Mature: <b style="color:#10b981"><?= (int)($commissionSummary['mature_count'] ?? 0) ?></b></span>
                    <span>Immature: <b style="color:#f59e0b"><?= (int)($commissionSummary['immature_count'] ?? 0) ?></b></span>
                </span>
                <?php endif; ?>
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Client</th>
                            <th>Project</th>
                            <th>File Status</th>
                            <th>Booking Amount</th>
                            <th>Commission</th>
                            <th>Closed On</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($wonDeals as $deal):
                        $dealAmt  = (float)($deal['booking_amount'] ?? 0);
                        $dealComm = $dealAmt * ((float)$agent['commission_rate'] / 100);
                        $isMature = ($deal['file_status'] ?? 'mature') === 'mature';
                    ?>
                    <tr>
                        <td>
                            <div style="font-weight:500;color:var(--navy)"><?= Security::e($deal['name']) ?></div>
                            <?php if ($deal['phone']): ?>
                                <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($deal['phone']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:13px">
                            <?= $deal['project'] ? Security::e($deal['project']) : '—' ?>
                            <?php if (!empty($deal['block']) || !empty($deal['unit_no'])): ?>
                                <div style="font-size:11px;color:var(--text-muted)"><?= Security::e(trim(($deal['block'] ?? '') . ' ' . ($deal['unit_no'] ?? ''))) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($isMature): ?>
                                <span class="status-pill" style="background:#d1fae5;color:#065f46;border:1px solid #6ee7b7">Mature</span>
                            <?php else: ?>
                                <span class="status-pill" style="background:#fef3c7;color:#92400e;border:1px solid #fcd34d">Immature</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight:600;color:var(--navy)">
                            <?= $dealAmt > 0 ? 'Rs. ' . number_format($dealAmt) : '—' ?>
                        </td>
                        <td style="color:<?= $isMature ? '#10b981' : '#f59e0b' ?>;font-weight:500">
                            <?= $dealComm > 0 ? 'Rs. ' . number_format($dealComm) : '—' ?>
                        </td>
                        <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($deal['updated_at'])) ?></td>
                        <td><a href="<?= APP_URL ?>/admin/lead/<?= (int)$deal['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($wonDeals)): ?>
                        <tr><td colspan="7" class="empty-row">No won deals yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- All Leads Table -->
        <div class="card" style="padding:0">
            <div style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">
                All Leads<?= $month ? ' — ' . Security::e($month) : '' ?> (<?= count($agentLeads) ?>)
            </div>
            <div class="table-wrapper" style="margin:0">
                <table class="data-table">
                    <thead>
                        <tr><th>Name / Phone</th><th>Project</th><th>Category</th><th>Status</th><th>Date</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($agentLeads as $row): ?>
                        <tr>
                            <td>
                                <div style="font-weight:500;color:var(--navy)"><?= Security::e($row['name']) ?></div>
                                <?php if ($row['phone']): ?>
                                    <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($row['phone']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:13px"><?= $row['project'] ? Security::e($row['project']) : '—' ?></td>
                            <td style="font-size:13px"><?= $row['category'] ? Security::e($row['category']) : '—' ?></td>
                            <td>
                                <span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44">
                                    <?= Security::e($row['status_name']) ?>
                                </span>
                            </td>
                            <td style="font-size:12px;color:var(--text-muted)"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                            <td><a href="<?= APP_URL ?>/admin/lead/<?= (int)$row['id'] ?>" class="btn btn-secondary btn-sm">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($agentLeads)): ?>
                        <tr><td colspan="6" class="empty-row">No leads<?= $month ? ' for this month' : '' ?>.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div><!-- /right col -->
</div>

<!-- Chart.js -->
<?php if (!empty($monthlyTrend)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function(){
    const ctx = document.getElementById('trendChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: <?= $chartLabels ?>,
            datasets: [
                {
                    label: 'Leads',
                    data: <?= $chartTotal ?>,
                    backgroundColor: 'rgba(59,130,246,0.15)',
                    borderColor: '#3b82f6',
                    borderWidth: 2,
                    borderRadius: 6,
                    order: 2
                },
                {
                    label: 'Won',
                    data: <?= $chartWon ?>,
                    backgroundColor: 'rgba(16,185,129,0.85)',
                    borderColor: '#10b981',
                    borderWidth: 0,
                    borderRadius: 6,
                    order: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 12 } } },
                tooltip: { mode: 'index' }
            },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#f0f0f0' } },
                x: { grid: { display: false } }
            }
        }
    });
})();
</script>
<?php endif; ?>

<style>
@media print {
    .sidebar, .hamburger-btn, .sidebar-overlay, .page-header-actions, form[method="GET"] { display:none !important }
    .main-content { margin:0 !important; padding:20px !important }
    body::before {
        content: "HK Builders & Developers — Agent Report — <?= date('d M Y') ?>";
        display:block; font-size:13px; color:#666; margin-bottom:20px;
        border-bottom:2px solid #c9a84c; padding-bottom:10px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
