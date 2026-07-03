<?php
Security::requireAdmin();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Notice.php';

$noticeModel  = new Notice();
$tab          = $_GET['tab'] ?? 'notices';
$dbError      = null;

try {
    $notices = $noticeModel->getAll();
} catch (PDOException $e) {
    $notices = [];
    $dbError = 'Database table missing. Please run <strong>migration_v3.sql</strong> on Hostinger first.';
}

$totalNotices  = count($notices);
$totalTasks    = count(array_filter($notices, fn($n) => $n['type'] === 'task'));
$totalAnnounce = $totalNotices - $totalTasks;

// Activity feed
$actPage   = max(1, (int)($_GET['page'] ?? 1));
$actPer    = 60;
$actTotal  = 0;
$actPages  = 1;
$actEvents = [];
if ($tab === 'activity' && !$dbError) {
    try {
        $actTotal  = $noticeModel->getActivityFeedCount();
        $actPages  = (int)ceil($actTotal / $actPer);
        $actEvents = $noticeModel->getActivityFeed($actPage, $actPer);
    } catch (PDOException $e) {
        $dbError = 'Could not load activity feed.';
    }
}

$pageTitle  = 'Notices & Activity';
$activePage = 'notices';
ob_start();

$auditLabels = [
    'login_success'              => ['Login',             '#10b981'],
    'login_fail'                 => ['Login Failed',      '#f59e0b'],
    'login_fail_unknown_email'   => ['Unknown Email',     '#f59e0b'],
    'login_blocked_locked'       => ['Account Locked',    '#ef4444'],
    'login_blocked_suspended'    => ['Account Suspended', '#ef4444'],
    'login_remember_me'          => ['Auto Login',        '#3b82f6'],
    'logout'                     => ['Logout',            '#6b7280'],
    'password_changed'           => ['Password Changed',  '#8b5cf6'],
];
$leadLabels = [
    'note'          => ['Note Added',     '#3b82f6'],
    'status_change' => ['Status Changed', '#f59e0b'],
    'claim'         => ['Lead Claimed',   '#10b981'],
    'reassign'      => ['Reassigned',     '#8b5cf6'],
    'call'          => ['Call Logged',    '#0ea5e9'],
    'email'         => ['Email Logged',   '#6366f1'],
    'followup_set'  => ['Follow-up Set',  '#f97316'],
    'csv_import'    => ['CSV Import',     '#64748b'],
];
?>

<?php if ($dbError): ?>
    <div class="alert alert-error"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg><?= $dbError ?></div>
<?php endif; ?>
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg><?= Security::e($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Notices &amp; Activity</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Notices</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('createNoticeModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Post Notice / Task
        </button>
    </div>
</div>

<!-- Stats -->
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card blue">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg></div>
        <div class="stat-number"><?= $totalNotices ?></div>
        <div class="stat-label">Total Posted</div>
    </div>
    <div class="stat-card gold">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/></svg></div>
        <div class="stat-number"><?= $totalAnnounce ?></div>
        <div class="stat-label">Announcements</div>
    </div>
    <div class="stat-card red">
        <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg></div>
        <div class="stat-number"><?= $totalTasks ?></div>
        <div class="stat-label">Tasks Assigned</div>
    </div>
</div>

<!-- Tabs -->
<div style="display:flex;gap:0;margin-bottom:20px;border-bottom:2px solid var(--border-light)">
    <a href="<?= APP_URL ?>/admin/notices?tab=notices"
       style="padding:11px 22px;font-size:13px;font-weight:500;letter-spacing:.3px;border-bottom:2px solid <?= $tab==='notices' ? 'var(--gold)' : 'transparent' ?>;margin-bottom:-2px;color:<?= $tab==='notices' ? 'var(--navy)' : 'var(--text-muted)' ?>;text-decoration:none;transition:color .2s">
        Notices &amp; Tasks
        <span style="background:var(--bg);border:1px solid var(--border-light);border-radius:10px;padding:1px 8px;font-size:11px;font-weight:600;margin-left:6px;color:var(--text-muted)"><?= $totalNotices ?></span>
    </a>
    <a href="<?= APP_URL ?>/admin/notices?tab=activity"
       style="padding:11px 22px;font-size:13px;font-weight:500;letter-spacing:.3px;border-bottom:2px solid <?= $tab==='activity' ? 'var(--gold)' : 'transparent' ?>;margin-bottom:-2px;color:<?= $tab==='activity' ? 'var(--navy)' : 'var(--text-muted)' ?>;text-decoration:none;transition:color .2s">
        Activity Feed
    </a>
</div>

<?php if ($tab === 'notices'): ?>

<?php if (empty($notices)): ?>
<div class="card" style="padding:60px 20px;text-align:center">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:44px;height:44px;margin:0 auto 12px;display:block;color:var(--text-muted);opacity:.4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
    <p style="color:var(--text-muted);font-size:14px">No notices posted yet.</p>
    <button class="btn btn-primary btn-sm" style="margin-top:14px" onclick="openModal('createNoticeModal')">Post first notice</button>
</div>
<?php else: ?>
<div style="display:flex;flex-direction:column;gap:14px">
<?php foreach ($notices as $notice):
    $isTask      = $notice['type'] === 'task';
    $doneCount   = (int)$notice['done_count'];
    $totalStaff  = (int)$notice['total_staff'];
    $pct         = $totalStaff > 0 ? round(($doneCount / $totalStaff) * 100) : 0;
    $accentColor = $isTask ? '#f59e0b' : '#3b82f6';
    $typeBg      = $isTask ? '#fef3c7' : '#dbeafe';
    $typeColor   = $isTask ? '#92400e' : '#1e40af';
    $barColor    = $pct >= 100 ? '#10b981' : ($pct > 50 ? '#f59e0b' : '#ef4444');
?>
<div class="card" style="padding:22px 24px;border-left:3px solid <?= $accentColor ?>">
    <div style="display:flex;align-items:flex-start;gap:20px">
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:10px;flex-wrap:wrap">
                <span style="background:<?= $typeBg ?>;color:<?= $typeColor ?>;font-size:10px;font-weight:700;padding:3px 9px;border-radius:4px;letter-spacing:1px;text-transform:uppercase"><?= $isTask ? 'Task' : 'Announcement' ?></span>
                <span style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
                <?php if ($notice['creator_name']): ?>
                <span style="font-size:12px;color:var(--text-muted)">· <?= Security::e($notice['creator_name']) ?></span>
                <?php endif; ?>
            </div>
            <h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--navy);margin:0 0 8px"><?= Security::e($notice['title']) ?></h3>
            <p style="font-size:13px;color:var(--text-muted);line-height:1.7;margin:0 0 14px;white-space:pre-wrap"><?= Security::e($notice['message']) ?></p>
            <?php if ($notice['attachment']): ?>
            <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;font-size:12px;color:var(--gold);font-weight:500;text-transform:uppercase;letter-spacing:.5px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                Attachment
            </a>
            <?php endif; ?>
            <?php if ($isTask): ?>
            <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--border-light)">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:6px">
                    <span style="font-size:11px;font-weight:600;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted)">Completion</span>
                    <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $doneCount ?> / <?= $totalStaff ?> staff &nbsp;<span style="color:<?= $barColor ?>">(<?= $pct ?>%)</span></span>
                </div>
                <div style="height:5px;background:var(--bg);border-radius:3px;overflow:hidden;border:1px solid var(--border-light)">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $barColor ?>;border-radius:3px;transition:width .4s"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/notices" onsubmit="return confirm('Delete this notice? This cannot be undone.')">
            <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
            <input type="hidden" name="action"     value="delete">
            <input type="hidden" name="notice_id"  value="<?= $notice['id'] ?>">
            <button type="submit" class="btn btn-danger btn-sm">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                Delete
            </button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: // Activity Feed ?>

<div class="table-wrapper">
    <div class="table-toolbar">
        <span style="font-weight:600;font-size:14px;color:var(--navy);font-family:'Cormorant Garamond',serif">All User Activity</span>
        <span style="font-size:12px;color:var(--text-muted)"><?= number_format($actTotal) ?> total events</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th>Time</th>
                <th>Event</th>
                <th>User</th>
                <th>Detail</th>
                <th>IP Address</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($actEvents)): ?>
            <tr><td colspan="5" class="empty-row">No activity recorded yet.</td></tr>
        <?php endif; ?>
        <?php foreach ($actEvents as $ev):
            if ($ev['source'] === 'system') {
                [$label, $color] = $auditLabels[$ev['event_type']] ?? [ucwords(str_replace('_',' ',$ev['event_type'])), '#6b7280'];
            } else {
                [$label, $color] = $leadLabels[$ev['event_type']]  ?? [ucwords(str_replace('_',' ',$ev['event_type'])), '#6b7280'];
            }
            $roleLabel = match($ev['user_role'] ?? '') {
                'admin'         => 'Admin',
                'sales_manager' => 'SM',
                default         => 'Agent',
            };
        ?>
        <tr>
            <td style="white-space:nowrap">
                <div class="lead-name" style="font-size:12px"><?= date('d M Y', strtotime($ev['created_at'])) ?></div>
                <div class="lead-sub"><?= date('g:i A', strtotime($ev['created_at'])) ?></div>
            </td>
            <td>
                <span style="display:inline-block;font-size:11px;font-weight:600;color:<?= $color ?>;background:<?= $color ?>18;padding:3px 8px;border-radius:4px;letter-spacing:.3px"><?= $label ?></span>
                <div class="lead-sub" style="margin-top:3px"><?= $ev['source'] === 'system' ? 'System' : 'Lead Activity' ?></div>
            </td>
            <td>
                <div class="lead-name"><?= Security::e($ev['user_name'] ?? 'Unknown') ?></div>
                <span class="agent-chip" style="margin-top:3px"><?= $roleLabel ?></span>
            </td>
            <td>
                <?php if ($ev['lead_id']): ?>
                    <div class="lead-name" style="color:var(--gold)">Lead #<?= $ev['lead_id'] ?><?= $ev['lead_name'] ? ' — '.Security::e($ev['lead_name']) : '' ?></div>
                    <?php if ($ev['detail']): ?>
                        <div class="lead-sub"><?= Security::e(mb_strimwidth($ev['detail'], 0, 100, '…')) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="lead-sub"><?= Security::e(trim($ev['detail'] ?? '—')) ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= Security::e($ev['ip_address'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (($actPages ?? 1) > 1): ?>
    <div class="pagination">
        <?php if ($actPage > 1): ?>
        <a href="<?= APP_URL ?>/admin/notices?tab=activity&page=<?= $actPage-1 ?>" class="page-btn">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Prev
        </a>
        <?php endif; ?>
        <div class="page-numbers">
        <?php for ($p = max(1,$actPage-2); $p <= min($actPages,$actPage+2); $p++): ?>
            <a href="<?= APP_URL ?>/admin/notices?tab=activity&page=<?= $p ?>" class="page-num <?= $p===$actPage?'active':'' ?>"><?= $p ?></a>
        <?php endfor; ?>
        </div>
        <?php if ($actPage < $actPages): ?>
        <a href="<?= APP_URL ?>/admin/notices?tab=activity&page=<?= $actPage+1 ?>" class="page-btn">
            Next
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
        </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php endif; ?>

<!-- Create Notice Modal -->
<div class="modal-overlay" id="createNoticeModal">
    <div class="modal" style="max-width:540px;width:100%">
        <div class="modal-header">
            <h3>Post Notice or Task</h3>
            <button class="modal-close" onclick="closeModal('createNoticeModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/notices" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <input type="hidden" name="action"     value="create">

                <div class="form-group">
                    <label>Type</label>
                    <div style="display:flex;gap:20px;padding:6px 0">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text)">
                            <input type="radio" name="type" value="announcement" checked style="accent-color:var(--gold)"> Announcement
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:13px;font-weight:400;text-transform:none;letter-spacing:0;color:var(--text)">
                            <input type="radio" name="type" value="task" style="accent-color:var(--gold)"> Task <span style="font-size:11px;color:var(--text-muted)">(agents mark done)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" placeholder="e.g. Team Meeting Tomorrow" maxlength="255" required>
                </div>

                <div class="form-group">
                    <label>Message <span style="color:#ef4444">*</span></label>
                    <textarea name="message" rows="5" placeholder="Write your message here…" required></textarea>
                </div>

                <div class="form-group">
                    <label>Attachment <span style="font-size:10px;text-transform:none;letter-spacing:0;font-weight:400;color:var(--text-muted)">(PDF, Word, Excel, Image, ZIP — max 10 MB)</span></label>
                    <input type="file" name="attachment" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createNoticeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Post Notice</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
