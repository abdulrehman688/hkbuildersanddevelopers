<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/User.php';

$userModel = new User();
$agents    = $userModel->getAllAgentsWithStats();

$pageTitle  = 'Agents';
$activePage = 'agents';
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

<div class="page-header">
    <div class="page-header-left">
        <h1>Agents</h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <span class="current">Agents</span>
        </div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addAgentModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Agent
        </button>
    </div>
</div>

<!-- Agents Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Email</th>
                <th>Status</th>
                <th>Total Leads</th>
                <th>Active</th>
                <th>Won</th>
                <th>Lost</th>
                <th>Conversion</th>
                <th>Joined</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($agents as $agent): ?>
            <?php
            $total      = (int)$agent['total_leads'];
            $won        = (int)$agent['won'];
            $lost       = (int)$agent['lost'];
            $closed     = $won + $lost;
            $conversion = $closed > 0 ? round(($won / $closed) * 100) : 0;
            $initials   = strtoupper(substr($agent['name'], 0, 1));
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="sidebar-avatar" style="width:34px;height:34px;font-size:13px">
                            <?= Security::e($initials) ?>
                        </div>
                        <span class="lead-name"><?= Security::e($agent['name']) ?></span>
                    </div>
                </td>
                <td style="color:var(--text-muted);font-size:12px"><?= Security::e($agent['email']) ?></td>
                <td>
                    <?php if ($agent['status'] === 'active'): ?>
                        <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Active</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#fef2f2;color:#dc2626;border:1px solid #fecaca">Suspended</span>
                    <?php endif; ?>
                </td>
                <td style="font-weight:600;color:var(--navy)"><?= $total ?></td>
                <td style="color:#3b82f6;font-weight:500"><?= (int)$agent['active_leads'] ?></td>
                <td style="color:#10b981;font-weight:500"><?= $won ?></td>
                <td style="color:#ef4444;font-weight:500"><?= $lost ?></td>
                <td>
                    <?php if ($closed > 0): ?>
                        <div style="display:flex;align-items:center;gap:8px">
                            <div class="conv-bar">
                                <div class="conv-fill" style="width:<?= $conversion ?>%"></div>
                            </div>
                            <span style="font-size:12px;font-weight:600;color:var(--navy)"><?= $conversion ?>%</span>
                        </div>
                    <?php else: ?>
                        <span style="color:var(--text-muted);font-size:12px">—</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($agent['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px">
                        <!-- Toggle status -->
                        <form method="POST" action="<?= APP_URL ?>/admin/agents?action=toggle" style="margin:0">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="agent_id" value="<?= (int)$agent['id'] ?>">
                            <input type="hidden" name="status" value="<?= $agent['status'] === 'active' ? 'suspended' : 'active' ?>">
                            <button type="submit" class="btn btn-sm <?= $agent['status'] === 'active' ? 'btn-danger' : 'btn-secondary' ?>"
                                onclick="return confirm('<?= $agent['status'] === 'active' ? 'Suspend' : 'Activate' ?> this agent?')"
                                title="<?= $agent['status'] === 'active' ? 'Suspend' : 'Activate' ?>">
                                <?= $agent['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                            </button>
                        </form>
                        <!-- Reset password -->
                        <button class="btn btn-secondary btn-sm"
                            onclick="openResetModal(<?= (int)$agent['id'] ?>, '<?= Security::e($agent['name']) ?>')">
                            Reset PW
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($agents)): ?>
            <tr><td colspan="10" class="empty-row">No agents yet. Add your first agent above.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ========== ADD AGENT MODAL ========== -->
<div class="modal-overlay" id="addAgentModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Add New Agent</h3>
            <button class="modal-close" onclick="closeModal('addAgentModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/agents?action=add">
            <?= Security::csrfField() ?>
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Sara Ahmed">
                </div>
                <div class="form-group">
                    <label>Email Address *</label>
                    <input type="email" name="email" required placeholder="e.g. sara@hkbuilders.com">
                </div>
                <div class="form-group">
                    <label>Password *</label>
                    <input type="password" name="password" required placeholder="Min 8 characters" minlength="8">
                    <span class="form-hint">Agent must change this after first login.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAgentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Agent</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== RESET PASSWORD MODAL ========== -->
<div class="modal-overlay" id="resetPwModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Reset Password — <span id="resetAgentName"></span></h3>
            <button class="modal-close" onclick="closeModal('resetPwModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/agents?action=reset-password">
            <?= Security::csrfField() ?>
            <input type="hidden" name="agent_id" id="resetAgentId">
            <div class="modal-body">
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required placeholder="Min 8 characters" minlength="8">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('resetPwModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Reset Password</button>
            </div>
        </form>
    </div>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
