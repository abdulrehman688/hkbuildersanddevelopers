<?php
Security::requireAgent();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Notice.php';

$noticeModel = new Notice();
$userId      = (int)$_SESSION['user_id'];
$dbError     = null;

try {
    $notices = $noticeModel->getForUser($userId);
} catch (PDOException $e) {
    $notices = [];
    $dbError = true;
}

$pendingTasks   = array_filter($notices, fn($n) => $n['type'] === 'task' && !$n['marked_done_at']);
$completedTasks = array_filter($notices, fn($n) => $n['type'] === 'task' &&  $n['marked_done_at']);
$announcements  = array_filter($notices, fn($n) => $n['type'] === 'announcement');

$pageTitle          = 'Notices';
$activePage         = 'notices';
$noticePendingCount = count($pendingTasks);
ob_start();
?>

<?php if ($dbError): ?>
    <div class="alert alert-error"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>Notices are temporarily unavailable. Please check back later.</div>
<?php endif; ?>
<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Notices &amp; Tasks</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Notices</span></div>
    </div>
    <?php if ($noticePendingCount > 0): ?>
    <div class="page-header-actions">
        <span class="alert alert-warning" style="margin:0;padding:6px 14px;font-size:12px;font-weight:600">
            <?= $noticePendingCount ?> pending task<?= $noticePendingCount !== 1 ? 's' : '' ?> require your attention
        </span>
    </div>
    <?php endif; ?>
</div>

<?php if (empty($notices)): ?>
<div class="card" style="padding:60px 20px;text-align:center">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:44px;height:44px;margin:0 auto 12px;display:block;color:var(--text-muted);opacity:.4"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
    <p style="color:var(--text-muted);font-size:14px">No notices from admin yet.</p>
</div>
<?php else: ?>

<?php if (!empty($pendingTasks)): ?>
<!-- Pending Tasks -->
<div style="margin-bottom:28px">
    <div class="section-header">
        <h2 style="color:#ef4444;display:flex;align-items:center;gap:8px">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
            Pending Tasks
        </h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($pendingTasks as $notice): ?>
    <div class="card" style="padding:22px 24px;border-left:3px solid #ef4444">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">
                    <span style="background:#fee2e2;color:#991b1b;font-size:10px;font-weight:700;padding:3px 9px;border-radius:4px;letter-spacing:1px;text-transform:uppercase">Task</span>
                    <span style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
                    <?php if ($notice['creator_name']): ?>
                    <span style="font-size:12px;color:var(--text-muted)">· <?= Security::e($notice['creator_name']) ?></span>
                    <?php endif; ?>
                </div>
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--navy);margin:0 0 8px"><?= Security::e($notice['title']) ?></h3>
                <p style="font-size:13px;color:var(--text-muted);line-height:1.7;margin:0;white-space:pre-wrap"><?= Security::e($notice['message']) ?></p>
                <?php if ($notice['attachment']): ?>
                <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
                   style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:12px">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                    Download Attachment
                </a>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= APP_URL ?>/agent/notices" style="flex-shrink:0">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action"     value="mark_done">
                <input type="hidden" name="notice_id"  value="<?= $notice['id'] ?>">
                <button type="submit" class="btn btn-danger">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Mark Done
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($announcements)): ?>
<!-- Announcements -->
<div style="margin-bottom:28px">
    <div class="section-header">
        <h2>Announcements</h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:12px">
    <?php foreach ($announcements as $notice): ?>
    <div class="card" style="padding:22px 24px;border-left:3px solid #3b82f6">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:8px;flex-wrap:wrap">
            <span style="background:#dbeafe;color:#1e40af;font-size:10px;font-weight:700;padding:3px 9px;border-radius:4px;letter-spacing:1px;text-transform:uppercase">Announcement</span>
            <span style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
            <?php if ($notice['creator_name']): ?>
            <span style="font-size:12px;color:var(--text-muted)">· <?= Security::e($notice['creator_name']) ?></span>
            <?php endif; ?>
        </div>
        <h3 style="font-family:'Cormorant Garamond',serif;font-size:18px;font-weight:600;color:var(--navy);margin:0 0 8px"><?= Security::e($notice['title']) ?></h3>
        <p style="font-size:13px;color:var(--text-muted);line-height:1.7;margin:0;white-space:pre-wrap"><?= Security::e($notice['message']) ?></p>
        <?php if ($notice['attachment']): ?>
        <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:5px;font-size:11px;color:var(--gold);font-weight:600;text-transform:uppercase;letter-spacing:.5px;margin-top:12px">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:13px;height:13px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
            Download Attachment
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php if (!empty($completedTasks)): ?>
<!-- Completed Tasks -->
<div>
    <div class="section-header">
        <h2 style="color:#10b981;display:flex;align-items:center;gap:8px">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:18px;height:18px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Completed Tasks
        </h2>
    </div>
    <div style="display:flex;flex-direction:column;gap:10px">
    <?php foreach ($completedTasks as $notice): ?>
    <div class="card" style="padding:18px 24px;border-left:3px solid #10b981;opacity:.85">
        <div style="display:flex;align-items:center;gap:16px">
            <div style="flex:1;min-width:0">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="background:#d1fae5;color:#065f46;font-size:10px;font-weight:700;padding:3px 9px;border-radius:4px;letter-spacing:1px;text-transform:uppercase">Done</span>
                    <span style="font-size:12px;color:var(--text-muted)">Completed <?= date('d M Y, g:i A', strtotime($notice['marked_done_at'])) ?></span>
                </div>
                <h3 style="font-family:'Cormorant Garamond',serif;font-size:16px;font-weight:600;color:var(--text-muted);margin:0;text-decoration:line-through;opacity:.7"><?= Security::e($notice['title']) ?></h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/agent/notices">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action"     value="unmark_done">
                <input type="hidden" name="notice_id"  value="<?= $notice['id'] ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Undo</button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
