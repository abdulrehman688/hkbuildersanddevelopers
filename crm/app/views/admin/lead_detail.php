<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Lead.php';
require_once __DIR__ . '/../../models/User.php';

$leadModel  = new Lead();
$userModel  = new User();

$row = $leadModel->findById($id);
if (!$row) {
    $_SESSION['error'] = 'Lead not found.';
    header('Location: ' . APP_URL . '/admin/leads');
    exit;
}

$activities = $leadModel->getActivities($id);
$statuses   = $leadModel->getStatuses();
$sources    = $leadModel->getSources();
$agents     = $userModel->getAllAgents();
$isEdit     = isset($_GET['edit']);

$pageTitle  = Security::e($row['name']);
$activePage = 'leads';

$activityLabels = [
    'note'          => 'Note',
    'status_change' => 'Status Changed',
    'claim'         => 'Lead Claimed',
    'reassign'      => 'Reassigned',
    'call'          => 'Call Logged',
    'email'         => 'Email Logged',
    'followup_set'  => 'Follow-up Set',
    'csv_import'    => 'Imported via CSV',
];

$activityColors = [
    'note'          => '#6b7280',
    'status_change' => '#c9a84c',
    'claim'         => '#3b82f6',
    'reassign'      => '#8b5cf6',
    'call'          => '#10b981',
    'email'         => '#0ea5e9',
    'followup_set'  => '#f97316',
    'csv_import'    => '#6b7280',
];

ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Page header -->
<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($row['name']) ?></h1>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/admin/leads" style="color:var(--text-muted)">Leads</a>
            <span class="sep">/</span>
            <span class="current"><?= Security::e($row['name']) ?></span>
        </div>
    </div>
    <div class="page-header-actions">
        <?php if (!$isEdit): ?>
            <a href="?edit=1" class="btn btn-secondary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                Edit Lead
            </a>
        <?php else: ?>
            <a href="<?= APP_URL ?>/admin/lead/<?= $id ?>" class="btn btn-secondary">Cancel</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back
        </a>
    </div>
</div>

<div class="detail-layout">

    <!-- LEFT: Lead info + actions -->
    <div class="detail-left">

        <!-- Lead Info Card -->
        <div class="form-card" style="margin-bottom:16px">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                <h3><?= $isEdit ? 'Edit Lead' : 'Lead Information' ?></h3>
            </div>

            <?php if ($isEdit): ?>
            <!-- Edit Mode -->
            <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="edit">
                <div class="form-card-body">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required value="<?= Security::e($row['name']) ?>">
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <input type="tel" name="phone" value="<?= Security::e($row['phone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" value="<?= Security::e($row['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Company</label>
                            <input type="text" name="company" value="<?= Security::e($row['company'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>City / Country</label>
                            <input type="text" name="country" value="<?= Security::e($row['country'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>Source</label>
                            <select name="source_id">
                                <option value="">— Select —</option>
                                <?php foreach ($sources as $src): ?>
                                    <option value="<?= (int)$src['id'] ?>" <?= (int)$row['source_id'] === (int)$src['id'] ? 'selected' : '' ?>>
                                        <?= Security::e($src['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="status_id">
                                <?php foreach ($statuses as $st): ?>
                                    <option value="<?= (int)$st['id'] ?>" <?= (int)$row['status_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                                        <?= Security::e($st['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Priority</label>
                            <select name="priority">
                                <option value="hot"  <?= $row['priority'] === 'hot'  ? 'selected' : '' ?>>Hot</option>
                                <option value="warm" <?= $row['priority'] === 'warm' ? 'selected' : '' ?>>Warm</option>
                                <option value="cold" <?= $row['priority'] === 'cold' ? 'selected' : '' ?>>Cold</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= APP_URL ?>/admin/lead/<?= $id ?>" class="btn btn-secondary btn-sm">Cancel</a>
                    <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                </div>
            </form>

            <?php else: ?>
            <!-- View Mode -->
            <div class="form-card-body">
                <div class="info-grid">
                    <div class="info-row">
                        <span class="info-label">Name</span>
                        <span class="info-value"><?= Security::e($row['name']) ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone</span>
                        <span class="info-value">
                            <?php if ($row['phone']): ?>
                                <a href="tel:<?= Security::e($row['phone']) ?>" style="color:var(--gold)"><?= Security::e($row['phone']) ?></a>
                                &nbsp;
                                <a href="https://wa.me/<?= preg_replace('/\D/', '', $row['phone']) ?>" target="_blank" class="wa-link">WhatsApp</a>
                            <?php else: ?>—<?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Email</span>
                        <span class="info-value">
                            <?php if ($row['email']): ?>
                                <a href="mailto:<?= Security::e($row['email']) ?>" style="color:var(--gold)"><?= Security::e($row['email']) ?></a>
                            <?php else: ?>—<?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Company</span>
                        <span class="info-value"><?= Security::e($row['company'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Location</span>
                        <span class="info-value"><?= Security::e($row['country'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Source</span>
                        <span class="info-value"><?= Security::e($row['source_name'] ?? '—') ?></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority</span>
                        <span class="info-value">
                            <span class="badge badge-<?= Security::e($row['priority']) ?>">
                                <span class="badge-dot"></span>
                                <?= ucfirst(Security::e($row['priority'])) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status</span>
                        <span class="info-value">
                            <span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44">
                                <?= Security::e($row['status_name']) ?>
                            </span>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Assigned To</span>
                        <span class="info-value">
                            <?php if ($row['agent_name']): ?>
                                <span class="agent-chip"><?= Security::e($row['agent_name']) ?></span>
                            <?php else: ?>
                                <span style="color:var(--text-muted)">Unassigned</span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Created</span>
                        <span class="info-value" style="color:var(--text-muted)">
                            <?= date('d M Y, h:i A', strtotime($row['created_at'])) ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <!-- Update Status -->
        <?php if (!$isEdit): ?>
        <div class="form-card" style="margin-bottom:16px">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/></svg>
                <h3>Update Status</h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="update_status">
                <div class="form-card-body">
                    <div class="form-group" style="margin-bottom:12px">
                        <label>New Status</label>
                        <select name="status_id" required>
                            <?php foreach ($statuses as $st): ?>
                                <option value="<?= (int)$st['id'] ?>" <?= (int)$row['status_id'] === (int)$st['id'] ? 'selected' : '' ?>>
                                    <?= Security::e($st['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Note (optional)</label>
                        <textarea name="note" rows="2" placeholder="Reason for status change…"></textarea>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Update Status</button>
                </div>
            </form>
        </div>

        <!-- Assign / Reassign -->
        <div class="form-card" style="margin-bottom:16px">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
                <h3><?= $row['assigned_to'] ? 'Reassign Lead' : 'Assign Lead' ?></h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="reassign">
                <div class="form-card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <label>Assign to Agent</label>
                        <select name="agent_id" required>
                            <option value="">— Select Agent —</option>
                            <?php foreach ($agents as $agent): ?>
                                <?php if ($agent['status'] === 'active'): ?>
                                    <option value="<?= (int)$agent['id'] ?>" <?= (int)$row['assigned_to'] === (int)$agent['id'] ? 'selected' : '' ?>>
                                        <?= Security::e($agent['name']) ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <?= $row['assigned_to'] ? 'Reassign' : 'Assign' ?>
                    </button>
                </div>
            </form>

            <?php if ($row['assigned_to']): ?>
            <div style="padding:10px 20px;border-top:1px solid var(--border-light)">
                <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="unassign">
                    <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Return this lead to the unassigned pool?')">
                        Unassign — Return to Pool
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </div>

        <!-- Danger Zone -->
        <div class="form-card danger-zone">
            <div class="form-card-body" style="display:flex;align-items:center;justify-content:space-between">
                <div>
                    <div style="font-weight:600;font-size:13px;color:#b91c1c">Delete Lead</div>
                    <div style="font-size:12px;color:var(--text-muted);margin-top:2px">This action cannot be undone.</div>
                </div>
                <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action" value="delete">
                    <button type="submit" class="btn btn-danger btn-sm"
                        onclick="return confirm('Delete this lead permanently? This cannot be undone.')">
                        Delete Lead
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div>

    <!-- RIGHT: Activity timeline + add note -->
    <div class="detail-right">

        <!-- Add Note -->
        <div class="form-card" style="margin-bottom:16px">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z"/></svg>
                <h3>Add Note</h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/admin/lead/<?= $id ?>">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action" value="add_note">
                <div class="form-card-body">
                    <div class="form-group" style="margin-bottom:0">
                        <textarea name="note" rows="3" placeholder="Add a note, call log, update…" required></textarea>
                    </div>
                </div>
                <div class="form-footer">
                    <button type="submit" class="btn btn-primary btn-sm">Add Note</button>
                </div>
            </form>
        </div>

        <!-- Activity Timeline -->
        <div class="form-card">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3>Activity Timeline</h3>
            </div>
            <div class="timeline">
                <?php if (empty($activities)): ?>
                    <div style="padding:32px;text-align:center;color:var(--text-muted);font-size:13px">No activity yet.</div>
                <?php endif; ?>
                <?php foreach ($activities as $act): ?>
                    <div class="timeline-item">
                        <div class="timeline-dot" style="background:<?= $activityColors[$act['type']] ?? '#6b7280' ?>"></div>
                        <div class="timeline-content">
                            <div class="timeline-header">
                                <span class="timeline-type"><?= $activityLabels[$act['type']] ?? ucfirst($act['type']) ?></span>
                                <span class="timeline-user"><?= Security::e($act['user_name']) ?></span>
                            </div>
                            <?php if ($act['note']): ?>
                                <div class="timeline-note"><?= nl2br(Security::e($act['note'])) ?></div>
                            <?php endif; ?>
                            <?php if ($act['meta'] && $act['type'] === 'status_change'): ?>
                                <?php
                                $meta = json_decode($act['meta'], true);
                                if ($meta && isset($meta['from_status'], $meta['to_status'])):
                                    $fromStatus = array_filter($statuses, fn($s) => (int)$s['id'] === (int)$meta['from_status']);
                                    $toStatus   = array_filter($statuses, fn($s) => (int)$s['id'] === (int)$meta['to_status']);
                                    $fromName   = $fromStatus ? array_values($fromStatus)[0]['name'] : '?';
                                    $toName     = $toStatus   ? array_values($toStatus)[0]['name']   : '?';
                                ?>
                                <div class="timeline-meta">
                                    <span class="meta-from"><?= Security::e($fromName) ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:12px;height:12px;color:var(--text-muted)"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
                                    <span class="meta-to"><?= Security::e($toName) ?></span>
                                </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            <div class="timeline-time"><?= date('d M Y, h:i A', strtotime($act['created_at'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
