<?php
Security::requireAdmin();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Notice.php';
require_once __DIR__ . '/../../models/User.php';

$noticeModel = new Notice();
$userModel   = new User();

$notices   = $noticeModel->getAll();
$tab       = $_GET['tab'] ?? 'notices';

// Activity feed pagination
$actPage = max(1, (int)($_GET['page'] ?? 1));
$actPer  = 60;
if ($tab === 'activity') {
    $actTotal  = $noticeModel->getActivityFeedCount();
    $actPages  = (int)ceil($actTotal / $actPer);
    $actEvents = $noticeModel->getActivityFeed($actPage, $actPer);
}

$pageTitle  = 'Notices & Activity';
$activePage = 'notices';
ob_start();

// ---- helpers ----
$auditLabels = [
    'login_success'             => ['Login',              '#10b981', '🔐'],
    'login_fail'                => ['Login Failed',       '#f59e0b', '⚠️'],
    'login_fail_unknown_email'  => ['Unknown Email',      '#f59e0b', '⚠️'],
    'login_blocked_locked'      => ['Account Locked',     '#ef4444', '🔒'],
    'login_blocked_suspended'   => ['Account Suspended',  '#ef4444', '🔒'],
    'login_remember_me'         => ['Auto Login',         '#3b82f6', '🔑'],
    'logout'                    => ['Logout',             '#6b7280', '👋'],
    'password_changed'          => ['Password Changed',   '#8b5cf6', '🔑'],
];
$leadLabels = [
    'note'          => ['Note Added',       '#3b82f6', '📝'],
    'status_change' => ['Status Changed',   '#f59e0b', '🔄'],
    'claim'         => ['Lead Claimed',     '#10b981', '✋'],
    'reassign'      => ['Reassigned',       '#8b5cf6', '↔️'],
    'call'          => ['Call Logged',      '#0ea5e9', '📞'],
    'email'         => ['Email Logged',     '#6366f1', '✉️'],
    'followup_set'  => ['Follow-up Set',    '#f97316', '📅'],
    'csv_import'    => ['CSV Import',       '#64748b', '📥'],
];
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Notices &amp; Activity</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Notices</span></div>
    </div>
    <button class="btn btn-primary" onclick="openModal('createNoticeModal')">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
        Post Notice / Task
    </button>
</div>

<!-- Tabs -->
<div style="display:flex;gap:4px;margin-bottom:20px;border-bottom:2px solid var(--border)">
    <a href="<?= APP_URL ?>/admin/notices?tab=notices"
       style="padding:10px 20px;font-weight:600;font-size:14px;border-bottom:2px solid <?= $tab === 'notices' ? 'var(--primary)' : 'transparent' ?>;margin-bottom:-2px;color:<?= $tab === 'notices' ? 'var(--primary)' : 'var(--text-muted)' ?>;text-decoration:none">
        Notices &amp; Tasks
        <?php if (count($notices) > 0): ?>
        <span style="background:var(--bg-secondary);border-radius:10px;padding:1px 7px;font-size:12px;margin-left:4px"><?= count($notices) ?></span>
        <?php endif; ?>
    </a>
    <a href="<?= APP_URL ?>/admin/notices?tab=activity"
       style="padding:10px 20px;font-weight:600;font-size:14px;border-bottom:2px solid <?= $tab === 'activity' ? 'var(--primary)' : 'transparent' ?>;margin-bottom:-2px;color:<?= $tab === 'activity' ? 'var(--primary)' : 'var(--text-muted)' ?>;text-decoration:none">
        Activity Feed
    </a>
</div>

<?php if ($tab === 'notices'): ?>

<!-- Notices Grid -->
<?php if (empty($notices)): ?>
<div class="card" style="padding:60px;text-align:center;color:var(--text-muted)">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:48px;height:48px;margin:0 auto 12px;display:block;opacity:.3"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
    No notices posted yet. Post an announcement or task for your team.
</div>
<?php else: ?>
<div style="display:grid;gap:16px">
<?php foreach ($notices as $notice):
    $isTask       = $notice['type'] === 'task';
    $doneCount    = (int)$notice['done_count'];
    $totalStaff   = (int)$notice['total_staff'];
    $pct          = $totalStaff > 0 ? round(($doneCount / $totalStaff) * 100) : 0;
    $borderColor  = $isTask ? '#f59e0b' : '#3b82f6';
    $typeLabel    = $isTask ? 'TASK' : 'ANNOUNCEMENT';
    $typeBg       = $isTask ? '#fef3c7' : '#dbeafe';
    $typeColor    = $isTask ? '#92400e' : '#1e40af';
?>
<div class="card" style="padding:20px;border-left:4px solid <?= $borderColor ?>">
    <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:16px">
        <div style="flex:1">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px">
                <span style="background:<?= $typeBg ?>;color:<?= $typeColor ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;letter-spacing:.5px"><?= $typeLabel ?></span>
                <span style="color:var(--text-muted);font-size:13px"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
                <span style="color:var(--text-muted);font-size:13px">· by <?= Security::e($notice['creator_name'] ?? 'Admin') ?></span>
            </div>
            <h3 style="margin:0 0 8px;font-size:16px;font-weight:600"><?= Security::e($notice['title']) ?></h3>
            <p style="margin:0 0 12px;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap"><?= Security::e($notice['message']) ?></p>
            <?php if ($notice['attachment']): ?>
            <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
               style="display:inline-flex;align-items:center;gap:6px;font-size:13px;color:var(--primary);text-decoration:none">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:15px;height:15px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                Download Attachment
            </a>
            <?php endif; ?>
            <?php if ($isTask): ?>
            <div style="margin-top:14px">
                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px">
                    <span style="font-size:13px;color:var(--text-muted)">Completion</span>
                    <span style="font-size:13px;font-weight:600"><?= $doneCount ?> / <?= $totalStaff ?> staff (<?= $pct ?>%)</span>
                </div>
                <div style="height:6px;background:var(--bg-secondary);border-radius:3px;overflow:hidden">
                    <div style="height:100%;width:<?= $pct ?>%;background:<?= $pct >= 100 ? '#10b981' : ($pct > 50 ? '#f59e0b' : '#ef4444') ?>;border-radius:3px;transition:width .3s"></div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/notices" onsubmit="return confirm('Delete this notice?')">
            <input type="hidden" name="csrf_token"  value="<?= Security::csrfToken() ?>">
            <input type="hidden" name="action"      value="delete">
            <input type="hidden" name="notice_id"   value="<?= $notice['id'] ?>">
            <button type="submit" style="background:none;border:none;cursor:pointer;color:#ef4444;padding:4px" title="Delete">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
            </button>
        </form>
    </div>
</div>
<?php endforeach; ?>
</div>
<?php endif; ?>

<?php else: // Activity Feed tab ?>

<div class="table-wrapper">
    <div style="padding:16px 20px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span style="font-weight:600">All User Activity</span>
        <span style="font-size:13px;color:var(--text-muted)"><?= number_format($actTotal) ?> total events</span>
    </div>
    <table class="data-table">
        <thead>
            <tr>
                <th style="width:160px">Time</th>
                <th style="width:160px">Event</th>
                <th>User</th>
                <th>Detail / Lead</th>
                <th style="width:120px">IP</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($actEvents as $ev):
            if ($ev['source'] === 'system') {
                [$label, $color, $icon] = $auditLabels[$ev['event_type']] ?? [ucwords(str_replace('_',' ',$ev['event_type'])), '#6b7280', '•'];
            } else {
                [$label, $color, $icon] = $leadLabels[$ev['event_type']]  ?? [ucwords(str_replace('_',' ',$ev['event_type'])), '#6b7280', '•'];
            }
            $roleLabel = match($ev['user_role'] ?? '') {
                'admin'         => 'Admin',
                'sales_manager' => 'SM',
                default         => 'Agent',
            };
        ?>
        <tr>
            <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d M y, g:i A', strtotime($ev['created_at'])) ?></td>
            <td>
                <span style="display:inline-flex;align-items:center;gap:5px;font-size:12px;font-weight:600;color:<?= $color ?>">
                    <?= $label ?>
                </span>
                <span style="display:block;font-size:11px;color:var(--text-muted)"><?= $ev['source'] === 'system' ? 'System' : 'Lead Activity' ?></span>
            </td>
            <td>
                <span style="font-size:13px;font-weight:500"><?= Security::e($ev['user_name'] ?? 'Unknown') ?></span>
                <span style="display:inline-block;font-size:10px;background:var(--bg-secondary);border-radius:4px;padding:1px 5px;margin-left:4px;color:var(--text-muted)"><?= $roleLabel ?></span>
            </td>
            <td style="font-size:13px;color:var(--text-secondary)">
                <?php if ($ev['lead_id']): ?>
                    <span style="color:var(--primary);font-weight:500">Lead #<?= $ev['lead_id'] ?></span>
                    <?php if ($ev['lead_name']): ?> — <?= Security::e($ev['lead_name']) ?><?php endif; ?>
                    <?php if ($ev['detail']): ?><br><span style="font-size:12px;color:var(--text-muted)"><?= Security::e(mb_strimwidth($ev['detail'], 0, 120, '…')) ?></span><?php endif; ?>
                <?php else: ?>
                    <?= Security::e(trim($ev['detail'] ?? '—')) ?>
                <?php endif; ?>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($ev['ip_address'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($actEvents)): ?>
        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px">No activity recorded yet.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Pagination -->
<?php if (($actPages ?? 1) > 1): ?>
<div style="display:flex;gap:8px;margin-top:20px;justify-content:center">
    <?php for ($p = 1; $p <= $actPages; $p++): ?>
    <a href="<?= APP_URL ?>/admin/notices?tab=activity&page=<?= $p ?>"
       style="padding:6px 12px;border-radius:6px;font-size:13px;text-decoration:none;<?= $p === $actPage ? 'background:var(--primary);color:#fff;font-weight:600' : 'background:var(--bg-secondary);color:var(--text-secondary)' ?>">
        <?= $p ?>
    </a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php endif; ?>

<!-- Create Notice Modal -->
<div class="modal-backdrop" id="createNoticeModal" style="display:none">
    <div class="modal" style="max-width:560px;width:100%">
        <div class="modal-header">
            <h3 class="modal-title">Post Notice or Task</h3>
            <button class="modal-close" onclick="closeModal('createNoticeModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/notices" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <input type="hidden" name="action"     value="create">

                <div class="form-group" style="margin-bottom:14px">
                    <label style="font-weight:600;font-size:13px;margin-bottom:6px;display:block">Type</label>
                    <div style="display:flex;gap:16px">
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                            <input type="radio" name="type" value="announcement" checked> Announcement
                        </label>
                        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px">
                            <input type="radio" name="type" value="task"> Task <span style="font-size:12px;color:var(--text-muted)">(agents must mark as done)</span>
                        </label>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Title <span style="color:#ef4444">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Team Meeting Tomorrow, Submit Reports by Friday" maxlength="255" required>
                </div>

                <div class="form-group" style="margin-bottom:14px">
                    <label class="form-label">Message <span style="color:#ef4444">*</span></label>
                    <textarea name="message" class="form-control" rows="5" placeholder="Write your message here…" required style="resize:vertical"></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Attachment <span style="font-size:12px;color:var(--text-muted)">(PDF, Word, Excel, Image, ZIP — max 10 MB)</span></label>
                    <input type="file" name="attachment" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.png,.jpg,.jpeg,.gif,.zip">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createNoticeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Post</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
