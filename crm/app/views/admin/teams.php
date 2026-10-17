<?php
Security::requireAdmin();
$js = fn($v) => json_encode($v, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
require_once __DIR__ . '/../../models/Team.php';
require_once __DIR__ . '/../../models/User.php';

$teamModel = new Team();
$userModel = new User();

$teams           = $teamModel->getAll();
$salesManagers   = $teamModel->getSalesManagers();
$unassigned      = $teamModel->getUnassignedAgents();
$allAgents       = $userModel->getAllAgents();

$pageTitle  = 'Teams';
$activePage = 'teams';
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Teams</h1>
        <div class="breadcrumb">Dashboard <span class="sep">/</span><span class="current">Teams</span></div>
    </div>
    <div class="page-header-actions">
        <button class="btn btn-primary" onclick="openModal('createTeamModal')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Create Team
        </button>
    </div>
</div>

<!-- Teams Cards -->
<?php if (empty($teams)): ?>
    <div class="card" style="text-align:center;padding:48px;color:var(--text-muted)">
        No teams yet. Create your first team above.
    </div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:20px;margin-bottom:32px">
<?php foreach ($teams as $t):
    $won    = (int)$t['won'];
    $lost   = (int)$t['lost'];
    $closed = $won + $lost;
    $conv   = $closed > 0 ? round(($won / $closed) * 100) : 0;
?>
<div class="card" style="padding:0">
    <!-- Team Header -->
    <div style="background:var(--navy);border-radius:12px 12px 0 0;padding:18px 20px;display:flex;justify-content:space-between;align-items:center">
        <div>
            <div style="font-family:'Cormorant Garamond',serif;font-size:20px;font-weight:700;color:#fff"><?= Security::e($t['name']) ?></div>
            <div style="font-size:12px;color:var(--gold-light);margin-top:2px">
                <?= $t['manager_name'] ? Security::e($t['manager_name']) : '<span style="color:#6b7280">No manager assigned</span>' ?>
            </div>
        </div>
        <div style="display:flex;gap:6px">
            <button class="btn btn-sm" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2)"
                onclick='openEditModal(<?= (int)$t["id"] ?>, <?= $js($t["name"]) ?>, <?= (int)($t["sales_manager_id"] ?? 0) ?>)'>Edit</button>
            <form method="POST" action="<?= APP_URL ?>/admin/teams" style="margin:0">
                <?= Security::csrfField() ?>
                <input type="hidden" name="action"  value="delete">
                <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"
                    onclick="return confirm('Delete team <?= Security::e($t['name']) ?>? Agents will be unassigned.')">Delete</button>
            </form>
        </div>
    </div>

    <!-- Stats Row -->
    <div style="display:grid;grid-template-columns:repeat(4,1fr);border-bottom:1px solid var(--border)">
        <?php foreach ([['Agents',(int)$t['member_count'],'var(--navy)'],['Leads',(int)$t['total_leads'],'#3b82f6'],['Won',$won,'#10b981'],['Conv',$conv.'%','#f59e0b']] as [$label,$val,$color]): ?>
        <div style="padding:14px 0;text-align:center;border-right:1px solid var(--border)">
            <div style="font-size:20px;font-weight:700;color:<?= $color ?>"><?= $val ?></div>
            <div style="font-size:11px;color:var(--text-muted);margin-top:2px"><?= $label ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Members -->
    <?php $members = $teamModel->getMembers((int)$t['id']); ?>
    <div style="padding:14px 18px">
        <div style="font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">Members</div>
        <?php if (empty($members)): ?>
            <div style="font-size:13px;color:var(--text-muted)">No agents assigned yet.</div>
        <?php endif; ?>
        <?php foreach ($members as $m): ?>
        <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--border)">
            <div style="display:flex;align-items:center;gap:10px">
                <div class="sidebar-avatar" style="width:30px;height:30px;font-size:12px">
                    <?= strtoupper(substr($m['name'],0,1)) ?>
                </div>
                <div>
                    <div style="font-size:13px;font-weight:500;color:var(--navy)"><?= Security::e($m['name']) ?></div>
                    <div style="font-size:11px;color:var(--text-muted)"><?= $m['designation'] ? Security::e($m['designation']) : $m['email'] ?></div>
                </div>
            </div>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="font-size:12px;color:#10b981;font-weight:600"><?= (int)$m['won'] ?> won</span>
                <form method="POST" action="<?= APP_URL ?>/admin/teams" style="margin:0">
                    <?= Security::csrfField() ?>
                    <input type="hidden" name="action"   value="remove_agent">
                    <input type="hidden" name="agent_id" value="<?= (int)$m['id'] ?>">
                    <button type="submit" class="btn btn-sm" style="padding:3px 10px;font-size:11px;color:#ef4444;border-color:#fecaca;background:#fef2f2"
                        onclick="return confirm('Remove from team?')">Remove</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Add agent to this team -->
        <?php if (!empty($unassigned)): ?>
        <form method="POST" action="<?= APP_URL ?>/admin/teams" style="margin-top:12px;display:flex;gap:8px">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action"  value="assign_agent">
            <input type="hidden" name="team_id" value="<?= (int)$t['id'] ?>">
            <select name="agent_id" style="flex:1;font-size:13px;padding:7px 10px;border:1px solid var(--border);border-radius:8px">
                <option value="">Add agent</option>
                <?php foreach ($unassigned as $ua): ?>
                    <option value="<?= (int)$ua['id'] ?>"><?= Security::e($ua['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-secondary btn-sm">Add</button>
        </form>
        <?php endif; ?>
    </div>
</div>
<?php endforeach; ?>
</div>

<!-- Unassigned Agents -->
<?php if (!empty($unassigned)): ?>
<div class="card">
    <div class="card-header" style="padding:14px 18px 12px;border-bottom:1px solid var(--border);font-size:14px;font-weight:600;color:var(--navy)">
        Unassigned Agents (<?= count($unassigned) ?>)
    </div>
    <div style="padding:14px 18px;display:flex;flex-wrap:wrap;gap:10px">
        <?php foreach ($unassigned as $ua): ?>
        <div style="background:var(--bg);border:1px solid var(--border);border-radius:10px;padding:10px 14px;display:flex;align-items:center;gap:10px">
            <div class="sidebar-avatar" style="width:30px;height:30px;font-size:12px">
                <?= strtoupper(substr($ua['name'],0,1)) ?>
            </div>
            <div>
                <div style="font-size:13px;font-weight:500;color:var(--navy)"><?= Security::e($ua['name']) ?></div>
                <div style="font-size:11px;color:var(--text-muted)"><?= $ua['designation'] ? Security::e($ua['designation']) : 'Agent' ?></div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- ===== CREATE TEAM MODAL ===== -->
<div class="modal-overlay" id="createTeamModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Create New Team</h3>
            <button class="modal-close" onclick="closeModal('createTeamModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/teams">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Team Name *</label>
                    <input type="text" name="name" required placeholder="e.g. Team A">
                </div>
                <div class="form-group">
                    <label>Sales Manager</label>
                    <select name="manager_id">
                        <option value="">Assign later</option>
                        <?php foreach ($salesManagers as $sm): ?>
                            <option value="<?= (int)$sm['id'] ?>"><?= Security::e($sm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <?php if (empty($salesManagers)): ?>
                        <span class="form-hint" style="color:#f59e0b">No sales managers yet. Create one in Agents first.</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createTeamModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Create Team</button>
            </div>
        </form>
    </div>
</div>

<!-- ===== EDIT TEAM MODAL ===== -->
<div class="modal-overlay" id="editTeamModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Edit Team</h3>
            <button class="modal-close" onclick="closeModal('editTeamModal')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form method="POST" action="<?= APP_URL ?>/admin/teams">
            <?= Security::csrfField() ?>
            <input type="hidden" name="action"  value="edit">
            <input type="hidden" name="team_id" id="editTeamId">
            <div class="modal-body">
                <div class="form-group">
                    <label>Team Name *</label>
                    <input type="text" name="name" id="editTeamName" required>
                </div>
                <div class="form-group">
                    <label>Sales Manager</label>
                    <select name="manager_id" id="editTeamManager">
                        <option value="">None</option>
                        <?php foreach ($salesManagers as $sm): ?>
                            <option value="<?= (int)$sm['id'] ?>"><?= Security::e($sm['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editTeamModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(id, name, managerId) {
    document.getElementById('editTeamId').value      = id;
    document.getElementById('editTeamName').value    = name;
    document.getElementById('editTeamManager').value = managerId || '';
    openModal('editTeamModal');
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
