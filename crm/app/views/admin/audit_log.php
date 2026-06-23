<?php
Security::requireAdmin();
require_once __DIR__ . '/../../../config/database.php';

$db   = Database::connect();
$page = max(1, (int)($_GET['page'] ?? 1));
$per  = 50;

$total = (int)$db->query("SELECT COUNT(*) FROM audit_log")->fetchColumn();
$pages = (int)ceil($total / $per);

$logs = $db->prepare("
    SELECT a.*, u.name AS user_name
    FROM audit_log a
    LEFT JOIN users u ON u.id = a.user_id
    ORDER BY a.created_at DESC
    LIMIT $per OFFSET " . (($page - 1) * $per)
);
$logs->execute();
$logs = $logs->fetchAll();

$actionLabels = [
    'login_success'          => ['Login',              '#10b981'],
    'login_fail'             => ['Login Failed',        '#f59e0b'],
    'login_fail_unknown_email'=> ['Login — Unknown Email','#f59e0b'],
    'login_blocked_locked'   => ['Blocked — Locked',   '#ef4444'],
    'login_blocked_suspended'=> ['Blocked — Suspended','#ef4444'],
    'login_remember_me'      => ['Auto Login',          '#3b82f6'],
    'logout'                 => ['Logout',              '#6b7280'],
    'password_changed'       => ['Password Changed',   '#8b5cf6'],
];

$pageTitle  = 'Audit Log';
$activePage = 'audit';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Audit Log</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Audit Log</span></div>
    </div>
</div>

<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Action</th>
                <th>User</th>
                <th>IP Address</th>
                <th>Target</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $log): ?>
            <?php
            $label = $actionLabels[$log['action']] ?? [ucwords(str_replace('_', ' ', $log['action'])), '#6b7280'];
            ?>
            <tr>
                <td style="color:var(--text-muted);font-size:12px;white-space:nowrap">
                    <?= date('d M Y, h:i:s A', strtotime($log['created_at'])) ?>
                </td>
                <td>
                    <span class="status-pill" style="background:<?= $label[1] ?>22;color:<?= $label[1] ?>;border:1px solid <?= $label[1] ?>44">
                        <?= Security::e($label[0]) ?>
                    </span>
                </td>
                <td style="font-size:13px">
                    <?= $log['user_name'] ? Security::e($log['user_name']) : '<span style="color:var(--text-muted)">—</span>' ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted);font-family:monospace">
                    <?= Security::e($log['ip_address'] ?? '—') ?>
                </td>
                <td style="font-size:12px;color:var(--text-muted)">
                    <?php if ($log['target_type'] && $log['target_id']): ?>
                        <?= Security::e($log['target_type']) ?> #<?= (int)$log['target_id'] ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($logs)): ?>
            <tr><td colspan="5" class="empty-row">No audit events yet. They'll appear here as users log in and take actions.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>" class="page-btn">← Prev</a>
        <?php endif; ?>
        <span style="font-size:12px;color:var(--text-muted)"><?= number_format($total) ?> events &middot; Page <?= $page ?> of <?= $pages ?></span>
        <?php if ($page < $pages): ?>
            <a href="?page=<?= $page + 1 ?>" class="page-btn">Next →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
