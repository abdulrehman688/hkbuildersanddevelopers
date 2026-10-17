<?php
Security::requireAdmin();

$pageTitle  = 'Accounts';
$activePage = 'accounts';
ob_start();

$pkr = fn($v) => 'PKR ' . number_format((float)$v, 0);
$totalComm = (float)($commStats['total_commission'] ?? 0);
$totalPaid = (float)($commStats['total_paid']       ?? 0);
$totalRem  = (float)($commStats['total_remaining']  ?? 0);
$totalExp  = (float)($expStats['total']    ?? 0);
$totalMkt  = (float)($expStats['marketing'] ?? 0);
$totalSal  = (float)($expStats['salary']   ?? 0);
$totalGen  = (float)($expStats['general']  ?? 0);
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
        <h1>Accounts</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Accounts</span></div>
    </div>
</div>

<!-- Sub-nav tabs -->
<div class="sub-nav" style="display:flex;gap:4px;margin-bottom:28px;border-bottom:2px solid var(--border)">
    <?php foreach ([
        [APP_URL.'/admin/accounts',            'Overview',   true],
        [APP_URL.'/admin/accounts/commission', 'Commission', false],
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

<!-- Stat cards -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:16px;margin-bottom:28px">
    <?php foreach ([
        ['Total Commission',  $pkr($totalComm), '#c9a84c', APP_URL.'/admin/accounts/commission'],
        ['Commission Paid',   $pkr($totalPaid), '#10b981', APP_URL.'/admin/accounts/commission?payment_status=paid'],
        ['Commission Due',    $pkr($totalRem),  '#ef4444', APP_URL.'/admin/accounts/commission?payment_status=pending'],
        ['Total Expenses',    $pkr($totalExp),  '#6366f1', APP_URL.'/admin/accounts/expenses'],
        ['Marketing',         $pkr($totalMkt),  '#3b82f6', APP_URL.'/admin/accounts/expenses?type=marketing'],
        ['Salaries',          $pkr($totalSal),  '#f59e0b', APP_URL.'/admin/accounts/salaries'],
        ['General',           $pkr($totalGen),  '#8b5cf6', APP_URL.'/admin/accounts/expenses?type=general'],
        ['Net (Comm - Exp)',  $pkr($totalComm - $totalExp), ($totalComm - $totalExp) >= 0 ? '#10b981' : '#ef4444', null],
    ] as [$label, $value, $color, $link]): ?>
    <?= $link ? '<a href="' . $link . '" style="text-decoration:none">' : '<div>' ?>
    <div class="card" style="padding:20px 22px">
        <div style="font-size:11px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:8px"><?= $label ?></div>
        <div style="font-size:20px;font-weight:700;color:<?= $color ?>;font-family:'Cormorant Garamond',serif"><?= $value ?></div>
    </div>
    <?= $link ? '</a>' : '</div>' ?>
    <?php endforeach; ?>
</div>

<!-- Quick links -->
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px">
    <?php foreach ([
        [APP_URL.'/admin/accounts/commission', 'Commission Management',
         'Track agent commissions, maturity status, and payment history.',
         'M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        [APP_URL.'/admin/accounts/expenses',   'Marketing & Expenses',
         'Log marketing campaigns, office costs, and miscellaneous expenses.',
         'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75'],
        [APP_URL.'/admin/accounts/salaries',   'Salary Management',
         'Record monthly salaries for agents and staff members.',
         'M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z'],
    ] as [$url, $title, $desc, $svgPath]): ?>
    <a href="<?= $url ?>" style="text-decoration:none">
        <div class="card" style="padding:22px;transition:border-color .2s;border:1px solid var(--border)"
             onmouseenter="this.style.borderColor='var(--gold)'" onmouseleave="this.style.borderColor='var(--border)'">
            <div style="width:40px;height:40px;background:var(--navy);border-radius:10px;display:flex;align-items:center;justify-content:center;margin-bottom:14px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#c9a84c" style="width:22px;height:22px">
                    <path stroke-linecap="round" stroke-linejoin="round" d="<?= $svgPath ?>"/>
                </svg>
            </div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:700;color:var(--text);margin-bottom:6px"><?= $title ?></div>
            <div style="font-size:13px;color:var(--text-muted);line-height:1.5"><?= $desc ?></div>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
