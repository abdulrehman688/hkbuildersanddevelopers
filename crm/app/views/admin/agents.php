<?php
Security::requireAdmin();
$js = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
require_once __DIR__ . '/../../models/User.php';

$userModel   = new User();
$agents      = $userModel->getAllAgentsWithStats();
$agentStats  = $userModel->getAgentSummaryStats();

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
        <div class="breadcrumb">Dashboard <span class="sep">/</span><span class="current">Agents</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('addAgentModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Agent
        </button>
    </div>
</div>

<!-- Stats Bar -->
<?php
$totalLeads = array_sum(array_column($agents, 'total_leads'));
$totalWon   = array_sum(array_column($agents, 'won'));
$totalLost  = array_sum(array_column($agents, 'lost'));
$totalClosed = $totalWon + $totalLost;
$avgConv    = $totalClosed > 0 ? round(($totalWon / $totalClosed) * 100) : 0;
?>
<div class="stats-grid" style="margin-bottom:24px">
    <div class="stat-card">
        <div class="stat-label">Total Agents</div>
        <div class="stat-value"><?= (int)$agentStats['total'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Active</div>
        <div class="stat-value" style="color:#10b981"><?= (int)$agentStats['active'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Suspended</div>
        <div class="stat-value" style="color:#ef4444"><?= (int)$agentStats['suspended'] ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Leads</div>
        <div class="stat-value"><?= $totalLeads ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Total Won</div>
        <div class="stat-value" style="color:#10b981"><?= $totalWon ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-label">Avg Conversion</div>
        <div class="stat-value"><?= $avgConv ?>%</div>
    </div>
</div>

<!-- Agents Table -->
<div class="table-wrapper">
    <table class="data-table table-cards">
        <thead>
            <tr>
                <th>Agent</th>
                <th>Role</th>
                <th>Designation</th>
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
            $isSM       = $agent['role'] === 'sales_manager';
            ?>
            <tr>
                <td>
                    <div style="display:flex;align-items:center;gap:10px">
                        <div class="sidebar-avatar" style="width:34px;height:34px;font-size:13px;<?= $isSM ? 'background:var(--gold);color:var(--navy)' : '' ?>">
                            <?= Security::e($initials) ?>
                        </div>
                        <div>
                            <div class="lead-name"><?= Security::e($agent['name']) ?></div>
                            <div style="font-size:11px;color:var(--text-muted)"><?= Security::e($agent['email']) ?></div>
                        </div>
                    </div>
                </td>
                <td>
                    <?php if ($isSM): ?>
                        <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Sales Manager</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fa">Agent</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px"><?= $agent['designation'] ? Security::e($agent['designation']) : '-' ?></td>
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
                        <span style="color:var(--text-muted);font-size:12px">-</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($agent['created_at'])) ?></td>
                <td>
                    <div style="display:flex;gap:6px;flex-wrap:wrap">
                        <a href="<?= APP_URL ?>/admin/agent/<?= (int)$agent['id'] ?>" class="btn btn-secondary btn-sm">View Details</a>
                        <form method="POST" action="<?= APP_URL ?>/admin/agents?action=toggle" style="margin:0">
                            <?= Security::csrfField() ?>
                            <input type="hidden" name="agent_id" value="<?= (int)$agent['id'] ?>">
                            <input type="hidden" name="status" value="<?= $agent['status'] === 'active' ? 'suspended' : 'active' ?>">
                            <button type="submit" class="btn btn-sm <?= $agent['status'] === 'active' ? 'btn-danger' : 'btn-secondary' ?>"
                                onclick="return confirm('<?= $agent['status'] === 'active' ? 'Suspend' : 'Activate' ?> this agent?')">
                                <?= $agent['status'] === 'active' ? 'Suspend' : 'Activate' ?>
                            </button>
                        </form>
                        <button class="btn btn-secondary btn-sm"
                            onclick='openResetModal(<?= (int)$agent["id"] ?>, <?= $js($agent["name"]) ?>)'>
                            Reset PW
                        </button>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($agents)): ?>
            <tr><td colspan="11" class="empty-row">No agents yet. Add your first agent above.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ========== ADD AGENT MODAL ========== -->
<div class="modal-overlay" id="addAgentModal">
    <div class="modal" style="max-width:640px">
        <div class="modal-header">
            <h3>Add New Agent / Sales Manager</h3>
            <button class="modal-close" onclick="closeModal('addAgentModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/agents?action=add">
            <?= Security::csrfField() ?>
            <div class="modal-body">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="name" required placeholder="e.g. Sara Ahmed">
                    </div>
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role">
                            <option value="agent">Agent</option>
                            <option value="sales_manager">Sales Manager</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" required placeholder="sara@hkbuilders.com">
                    </div>
                    <div class="form-group">
                        <label>Password *</label>
                        <input type="password" name="password" required placeholder="Min 8 characters" minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Contact No.</label>
                        <input type="text" name="phone" placeholder="03XX-XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>CNIC No.</label>
                        <input type="text" name="cnic" placeholder="XXXXX-XXXXXXX-X">
                    </div>
                    <div class="form-group">
                        <label>Guardian Contact No.</label>
                        <input type="text" name="guardian_phone" placeholder="03XX-XXXXXXX">
                    </div>
                    <div class="form-group">
                        <label>Designation</label>
                        <input type="text" name="designation" placeholder="e.g. Sales Executive">
                    </div>
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="2" placeholder="Home address"></textarea>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group">
                        <label>Base Salary (Rs.)</label>
                        <input type="number" name="base_salary" placeholder="e.g. 30000" min="0" step="500">
                    </div>
                    <div class="form-group">
                        <label>Commission Rate (%)</label>
                        <input type="number" name="commission_rate" placeholder="e.g. 1.5" min="0" step="0.1" max="100">
                        <span class="form-hint">% of booking amount per closed deal</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('addAgentModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<!-- ========== RESET PASSWORD MODAL ========== -->
<div class="modal-overlay" id="resetPwModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Reset Password - <span id="resetAgentName"></span></h3>
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
