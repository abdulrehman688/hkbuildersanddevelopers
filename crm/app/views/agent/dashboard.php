<?php
Security::requireLogin();
require_once __DIR__ . '/../../models/Lead.php';

$leadModel  = new Lead();
$agentId    = (int)$_SESSION['user_id'];
$stats      = $leadModel->getAgentStats($agentId);
$myLeads    = $leadModel->getAll(['agent_id' => $agentId, 'limit' => 8]);
$poolCount  = $leadModel->countUnclaimed();
$followUps  = $leadModel->getAgentFollowUps($agentId);
$overdueCount = count(array_filter($followUps, fn($f) => strtotime($f['scheduled_at']) < time()));

$pageTitle  = 'My Dashboard';
$activePage = 'dashboard';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Welcome, <?= Security::e($_SESSION['user_name']) ?></h1>
        <div class="breadcrumb"><span class="current">Agent Dashboard</span></div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/agent/pool" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg>
            Lead Pool
            <?php if ($poolCount): ?>
                <span style="background:rgba(201,168,76,0.2);color:var(--gold-light);padding:1px 7px;border-radius:10px;font-size:11px"><?= $poolCount ?></span>
            <?php endif; ?>
        </a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card gold">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg></div>
        <div class="stat-number"><?= (int)$stats['total'] ?></div>
        <div class="stat-label">My Total Leads</div>
    </div>
    <div class="stat-card purple">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="stat-number"><?= (int)$stats['active'] ?></div>
        <div class="stat-label">Active</div>
    </div>
    <div class="stat-card green">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="stat-number"><?= (int)$stats['won'] ?></div>
        <div class="stat-label">Won</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <div class="stat-number"><?= (int)$stats['lost'] ?></div>
        <div class="stat-label">Lost</div>
    </div>
    <div class="stat-card blue">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375"/></svg></div>
        <div class="stat-number"><?= $poolCount ?></div>
        <div class="stat-label">Available in Pool</div>
    </div>
</div>

<div class="section-header">
    <h2>My Recent Leads</h2>
    <a href="<?= APP_URL ?>/agent/leads">View All &rarr;</a>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Phone</th><th>Priority</th><th>Status</th><th>Date</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($myLeads as $row): ?>
            <tr onclick="window.location='<?= APP_URL ?>/agent/lead/<?= (int)$row['id'] ?>'">
                <td>
                    <div class="lead-name"><?= Security::e($row['name']) ?></div>
                    <?php if ($row['email']): ?><div class="lead-sub"><?= Security::e($row['email']) ?></div><?php endif; ?>
                </td>
                <td><?= Security::e($row['phone'] ?? '—') ?></td>
                <td><span class="badge badge-<?= Security::e($row['priority']) ?>"><span class="badge-dot"></span><?= ucfirst(Security::e($row['priority'])) ?></span></td>
                <td><span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44"><?= Security::e($row['status_name']) ?></span></td>
                <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
                <td><a href="<?= APP_URL ?>/agent/lead/<?= (int)$row['id'] ?>" onclick="event.stopPropagation()">Open</a></td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($myLeads)): ?>
            <tr><td colspan="6" class="empty-row">No leads yet. <a href="<?= APP_URL ?>/agent/pool">Grab one from the pool</a></td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Follow-up Reminders -->
<?php if (!empty($followUps)): ?>
<div class="section-header" style="margin-top:28px">
    <h2>
        Upcoming Follow-ups
        <?php if ($overdueCount > 0): ?>
            <span style="background:#fef2f2;color:#dc2626;font-size:11px;font-weight:600;padding:2px 8px;border-radius:10px;margin-left:8px"><?= $overdueCount ?> overdue</span>
        <?php endif; ?>
    </h2>
</div>
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Phone</th><th>Scheduled</th><th>Note</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($followUps as $fup): ?>
            <?php $overdue = strtotime($fup['scheduled_at']) < time(); ?>
            <tr>
                <td>
                    <a href="<?= APP_URL ?>/agent/lead/<?= (int)$fup['lead_id'] ?>" class="lead-name" style="color:var(--navy)"><?= Security::e($fup['lead_name']) ?></a>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($fup['lead_phone'] ?? '—') ?></td>
                <td>
                    <span style="font-size:12px;<?= $overdue ? 'color:#dc2626;font-weight:600' : 'color:var(--text-muted)' ?>">
                        <?= date('d M Y, h:i A', strtotime($fup['scheduled_at'])) ?>
                        <?php if ($overdue): ?><span class="overdue-tag">Overdue</span><?php endif; ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($fup['note'] ?? '—') ?></td>
                <td>
                    <form method="POST" action="<?= APP_URL ?>/agent/lead/<?= (int)$fup['lead_id'] ?>">
                        <?= Security::csrfField() ?>
                        <input type="hidden" name="action" value="done_followup">
                        <input type="hidden" name="followup_id" value="<?= (int)$fup['id'] ?>">
                        <button type="submit" class="btn btn-secondary btn-sm">Mark Done</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
