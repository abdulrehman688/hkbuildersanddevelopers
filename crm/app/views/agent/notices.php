<?php
Security::requireAgent();
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../../models/Notice.php';

$noticeModel = new Notice();
$userId      = (int)$_SESSION['user_id'];
$notices     = $noticeModel->getForUser($userId);

$pendingTasks     = array_filter($notices, fn($n) => $n['type'] === 'task' && !$n['marked_done_at']);
$completedTasks   = array_filter($notices, fn($n) => $n['type'] === 'task' &&  $n['marked_done_at']);
$announcements    = array_filter($notices, fn($n) => $n['type'] === 'announcement');

$pageTitle        = 'Notices';
$activePage       = 'notices';
$noticePendingCount = count($pendingTasks);
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Notices &amp; Tasks</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span> <span class="current">Notices</span></div>
    </div>
    <div style="display:flex;gap:10px;align-items:center">
        <?php if (count($pendingTasks) > 0): ?>
        <span style="background:#fef3c7;color:#92400e;border-radius:20px;padding:6px 14px;font-size:13px;font-weight:600">
            <?= count($pendingTasks) ?> pending task<?= count($pendingTasks) !== 1 ? 's' : '' ?>
        </span>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (empty($notices)): ?>
<div class="card" style="padding:60px;text-align:center;color:var(--text-muted)">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:48px;height:48px;margin:0 auto 12px;display:block;opacity:.3"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
    No notices from admin yet.
</div>
<?php else: ?>

<!-- PENDING TASKS -->
<?php if (!empty($pendingTasks)): ?>
<div style="margin-bottom:8px">
    <h2 style="font-size:14px;font-weight:700;color:#ef4444;letter-spacing:.5px;margin:0 0 12px;display:flex;align-items:center;gap:8px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        PENDING TASKS
    </h2>
    <div style="display:grid;gap:12px">
    <?php foreach ($pendingTasks as $notice): ?>
    <div class="card" style="padding:20px;border-left:4px solid #ef4444;background:linear-gradient(135deg,#fff5f5 0%,#fff 100%)">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
                    <span style="background:#fee2e2;color:#991b1b;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;letter-spacing:.5px">TASK</span>
                    <span style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
                    <?php if ($notice['creator_name']): ?><span style="font-size:12px;color:var(--text-muted)">· by <?= Security::e($notice['creator_name']) ?></span><?php endif; ?>
                </div>
                <h3 style="margin:0 0 8px;font-size:15px;font-weight:600"><?= Security::e($notice['title']) ?></h3>
                <p style="margin:0;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;font-size:14px"><?= Security::e($notice['message']) ?></p>
                <?php if ($notice['attachment']): ?>
                <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
                   style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--primary);margin-top:10px;text-decoration:none">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                    Download Attachment
                </a>
                <?php endif; ?>
            </div>
            <form method="POST" action="<?= APP_URL ?>/agent/notices">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <input type="hidden" name="action"     value="mark_done">
                <input type="hidden" name="notice_id"  value="<?= $notice['id'] ?>">
                <button type="submit" style="background:#ef4444;color:#fff;border:none;border-radius:8px;padding:10px 18px;font-size:13px;font-weight:600;cursor:pointer;white-space:nowrap;display:flex;align-items:center;gap:6px">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Mark Done
                </button>
            </form>
        </div>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ANNOUNCEMENTS -->
<?php if (!empty($announcements)): ?>
<div style="margin-top:24px;margin-bottom:8px">
    <h2 style="font-size:14px;font-weight:700;color:#3b82f6;letter-spacing:.5px;margin:0 0 12px;display:flex;align-items:center;gap:8px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M10.34 15.84c-.688-.06-1.386-.09-2.09-.09H7.5a4.5 4.5 0 110-9h.75c.704 0 1.402-.03 2.09-.09m0 9.18c.253.962.584 1.892.985 2.783.247.55.06 1.21-.463 1.511l-.657.38c-.551.318-1.26.117-1.527-.461a20.845 20.845 0 01-1.44-4.282m3.102.069a18.03 18.03 0 01-.59-4.59c0-1.586.205-3.124.59-4.59m0 9.18a23.848 23.848 0 018.835 2.535M10.34 6.66a23.847 23.847 0 008.835-2.535m0 0A23.74 23.74 0 0018.795 3m.38 1.125a23.91 23.91 0 011.014 5.395m-1.014 8.855c-.118.38-.245.754-.38 1.125m.38-1.125a23.91 23.91 0 001.014-5.395m0-3.46c.495.413.811 1.035.811 1.73 0 .695-.316 1.317-.811 1.73m0-3.46a24.347 24.347 0 010 3.46"/></svg>
        ANNOUNCEMENTS
    </h2>
    <div style="display:grid;gap:12px">
    <?php foreach ($announcements as $notice): ?>
    <div class="card" style="padding:20px;border-left:4px solid #3b82f6">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px">
            <span style="background:#dbeafe;color:#1e40af;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;letter-spacing:.5px">ANNOUNCEMENT</span>
            <span style="font-size:12px;color:var(--text-muted)"><?= date('d M Y, g:i A', strtotime($notice['created_at'])) ?></span>
            <?php if ($notice['creator_name']): ?><span style="font-size:12px;color:var(--text-muted)">· by <?= Security::e($notice['creator_name']) ?></span><?php endif; ?>
        </div>
        <h3 style="margin:0 0 8px;font-size:15px;font-weight:600"><?= Security::e($notice['title']) ?></h3>
        <p style="margin:0;color:var(--text-secondary);line-height:1.6;white-space:pre-wrap;font-size:14px"><?= Security::e($notice['message']) ?></p>
        <?php if ($notice['attachment']): ?>
        <a href="<?= APP_URL ?>/uploads/notices/<?= Security::e($notice['attachment']) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:5px;font-size:13px;color:var(--primary);margin-top:10px;text-decoration:none">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
            Download Attachment
        </a>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- COMPLETED TASKS -->
<?php if (!empty($completedTasks)): ?>
<div style="margin-top:28px">
    <h2 style="font-size:14px;font-weight:700;color:#10b981;letter-spacing:.5px;margin:0 0 12px;display:flex;align-items:center;gap:8px">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        COMPLETED TASKS
    </h2>
    <div style="display:grid;gap:10px">
    <?php foreach ($completedTasks as $notice): ?>
    <div class="card" style="padding:18px 20px;border-left:4px solid #10b981;background:linear-gradient(135deg,#f0fdf4 0%,#fff 100%);opacity:.9">
        <div style="display:flex;align-items:flex-start;gap:16px">
            <div style="flex:1">
                <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
                    <span style="background:#d1fae5;color:#065f46;font-size:11px;font-weight:700;padding:2px 8px;border-radius:4px;letter-spacing:.5px">✓ DONE</span>
                    <span style="font-size:12px;color:var(--text-muted)">Completed <?= date('d M Y, g:i A', strtotime($notice['marked_done_at'])) ?></span>
                </div>
                <h3 style="margin:0;font-size:14px;font-weight:600;color:var(--text-secondary);text-decoration:line-through;opacity:.7"><?= Security::e($notice['title']) ?></h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/agent/notices">
                <input type="hidden" name="csrf_token" value="<?= Security::csrfToken() ?>">
                <input type="hidden" name="action"     value="unmark_done">
                <input type="hidden" name="notice_id"  value="<?= $notice['id'] ?>">
                <button type="submit" style="background:none;border:1px solid var(--border);color:var(--text-muted);border-radius:6px;padding:6px 12px;font-size:12px;cursor:pointer">
                    Undo
                </button>
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
