<?php
Security::requireLogin();

$agentId = (int)$_SESSION['user_id'];
$now     = time();
$today   = date('Y-m-d');
$in7days = date('Y-m-d', strtotime('+7 days'));

// Categorise
$overdue  = [];
$upcoming = [];
$later    = [];
foreach ($followUps as $f) {
    $ts = strtotime($f['scheduled_at']);
    if ($ts < $now) {
        $overdue[] = $f;
    } elseif (date('Y-m-d', $ts) <= $in7days) {
        $upcoming[] = $f;
    } else {
        $later[] = $f;
    }
}

$pageTitle  = 'My Follow-ups';
$activePage = 'followups';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>My Follow-ups</h1>
        <div class="breadcrumb"><span class="current">Follow-up Tracker</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('scheduleModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Schedule Follow-up
        </button>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= Security::e($_SESSION['success']) ?><?php unset($_SESSION['success']); ?></div>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error"><?= Security::e($_SESSION['error']) ?><?php unset($_SESSION['error']); ?></div>
<?php endif; ?>

<!-- Summary chips -->
<div style="display:flex;gap:10px;margin-bottom:24px;flex-wrap:wrap">
    <span style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#fef2f2;color:#dc2626;border:1px solid #fecaca">
        <?= count($overdue) ?> Overdue
    </span>
    <span style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:#fffbeb;color:#d97706;border:1px solid #fde68a">
        <?= count($upcoming) ?> This Week
    </span>
    <span style="padding:6px 14px;border-radius:20px;font-size:12px;font-weight:600;background:var(--bg-card);color:var(--text-muted);border:1px solid var(--border)">
        <?= count($later) ?> Later
    </span>
</div>

<?php
// Render a section
function renderFollowUpSection(string $title, array $items, string $accent, string $bg): void { ?>
<?php if (empty($items)): return; endif; ?>
<div class="section-header" style="margin-top:4px">
    <h2 style="display:flex;align-items:center;gap:8px">
        <span style="width:10px;height:10px;border-radius:50%;background:<?= $accent ?>;display:inline-block"></span>
        <?= $title ?>
        <span style="font-size:12px;font-weight:600;padding:2px 8px;border-radius:10px;background:<?= $bg ?>;color:<?= $accent ?>"><?= count($items) ?></span>
    </h2>
</div>
<div class="table-wrapper" style="margin-bottom:28px">
    <table class="data-table">
        <thead>
            <tr><th>Lead</th><th>Phone</th><th>Scheduled</th><th>Note</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach ($items as $f): ?>
        <tr>
            <td>
                <a href="<?= APP_URL ?>/agent/lead/<?= (int)$f['lead_id'] ?>" class="lead-name"><?= Security::e($f['lead_name']) ?></a>
            </td>
            <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($f['lead_phone'] ?? '—') ?></td>
            <td>
                <span style="font-size:12px;color:<?= $accent ?>;font-weight:600">
                    <?= date('d M Y', strtotime($f['scheduled_at'])) ?>
                    <span style="color:var(--text-muted);font-weight:400"><?= date('h:i A', strtotime($f['scheduled_at'])) ?></span>
                </span>
            </td>
            <td style="font-size:12px;color:var(--text-muted);max-width:220px"><?= Security::e($f['note'] ?? '—') ?></td>
            <td>
                <form method="POST" action="<?= APP_URL ?>/agent/followups" style="display:inline">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action"      value="done_followup">
                    <input type="hidden" name="followup_id" value="<?= (int)$f['id'] ?>">
                    <input type="hidden" name="lead_id"     value="<?= (int)$f['lead_id'] ?>">
                    <button type="submit" class="btn btn-secondary btn-sm">Mark Done</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php } ?>

<?php renderFollowUpSection('Overdue',   $overdue,  '#dc2626', '#fef2f2'); ?>
<?php renderFollowUpSection('This Week', $upcoming, '#d97706', '#fffbeb'); ?>
<?php renderFollowUpSection('Later',     $later,    '#6366f1', '#eef2ff'); ?>

<?php if (empty($followUps)): ?>
<div class="card" style="padding:48px;text-align:center;color:var(--text-muted)">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" style="width:48px;height:48px;margin:0 auto 12px;display:block;opacity:0.3"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
    No pending follow-ups. <a href="#" onclick="openModal('scheduleModal')">Schedule one now.</a>
</div>
<?php endif; ?>

<!-- Schedule Follow-up Modal -->
<div class="modal-overlay" id="scheduleModal">
    <div class="modal" style="max-width:480px">
        <div class="modal-header">
            <h3 class="modal-title">Schedule Follow-up</h3>
            <button class="modal-close" onclick="closeModal('scheduleModal')">&times;</button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/agent/followups">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="schedule_followup">
            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Lead *</label>
                    <select name="lead_id" class="form-control" required>
                        <option value="">— Select a lead —</option>
                        <?php foreach ($myLeads as $l): ?>
                            <option value="<?= (int)$l['id'] ?>"><?= Security::e($l['name'] ?? $l['phone'] ?? 'Lead #'.$l['id']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label class="form-label">Date *</label>
                        <input type="date" name="followup_date" class="form-control" min="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Time</label>
                        <input type="time" name="followup_time" class="form-control" value="10:00">
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Note</label>
                    <textarea name="followup_note" class="form-control" rows="3" placeholder="What to discuss or action to take..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('scheduleModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Schedule</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
