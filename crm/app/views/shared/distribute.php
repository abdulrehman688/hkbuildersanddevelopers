<?php
// $section ('admin'|'agent'), $agents, $leads, $unassigned, $team injected by DistributeController
$pageTitle  = 'Distribute Leads';
$activePage = 'distribute';
$action     = APP_URL . '/' . $section . '/distribute';
$shownCount = count($leads);
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg><?= Security::e($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Distribute Leads</h1>
        <div class="breadcrumb">
            <?php if ($section === 'admin'): ?>
                <a href="<?= APP_URL ?>/admin/leads" style="color:var(--text-muted)">Leads</a>
            <?php else: ?>
                <span>Team: <?= Security::e($team['name']) ?></span>
            <?php endif; ?>
            <span class="sep">/</span><span class="current">Distribute</span>
        </div>
    </div>
</div>

<div class="chip-row" style="display:flex;flex-wrap:wrap;gap:10px;margin-bottom:20px">
    <?php foreach ([
        ['Unassigned Leads', $unassigned, 'var(--gold)'],
        [$section === 'admin' ? 'Active Agents' : 'Agents in Team', count($agents), 'var(--navy)'],
    ] as [$label, $value, $color]): ?>
    <div style="background:var(--bg-card);border:1px solid var(--border);border-radius:8px;padding:10px 18px;min-width:170px">
        <div style="font-size:11px;color:var(--text-muted);margin-bottom:3px;text-transform:uppercase;letter-spacing:.5px"><?= $label ?></div>
        <div style="font-size:17px;font-weight:700;color:<?= $color ?>"><?= (int)$value ?></div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (!$agents): ?>
    <div class="alert alert-info"><?= $section === 'admin' ? 'There are no active agents yet.' : 'No agents are in your team yet. Ask admin to add them.' ?></div>
<?php elseif ($unassigned === 0): ?>
    <div class="alert alert-info">All leads already have an agent. New leads will show up here.</div>
<?php else: ?>

<!-- Share equally -->
<div class="form-card" style="margin-bottom:20px">
    <div class="form-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5"/></svg>
        <h3>Share Equally</h3>
    </div>
    <form method="POST" action="<?= $action ?>" id="autoForm">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="auto">
        <div class="form-card-body">
            <div style="font-size:13px;color:var(--text-muted);margin-bottom:12px">
                Unassigned leads are shared one by one, hot leads first, so every ticked agent gets the same number.
                Any leftover leads go to the agents with the fewest open leads.
            </div>
            <div class="dist-agents">
                <?php foreach ($agents as $a): ?>
                <label class="dist-agent">
                    <input type="checkbox" name="agent_ids[]" value="<?= (int)$a['id'] ?>" <?= $a['role'] === 'agent' ? 'checked' : '' ?>>
                    <span>
                        <b><?= Security::e($a['name']) ?></b>
                        <small><?= $a['role'] === 'sales_manager' ? 'Sales Manager' : Security::e($a['team_name'] ?? 'No team') ?> &middot; <?= (int)$a['open_leads'] ?> open</small>
                    </span>
                </label>
                <?php endforeach; ?>
            </div>
            <div class="form-group" style="margin-top:14px;max-width:260px">
                <label>How many leads to share</label>
                <input type="number" name="max_leads" id="maxLeads" min="1" max="<?= (int)$unassigned ?>" placeholder="All (<?= (int)$unassigned ?>)">
            </div>
            <div id="autoPreview" style="font-size:13px;font-weight:600;color:var(--navy);margin-top:10px"></div>
        </div>
        <div class="form-footer">
            <button type="submit" class="btn btn-primary">Share Equally</button>
        </div>
    </form>
</div>

<!-- Assign by hand -->
<div class="form-card">
    <div class="form-card-header">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.042 21.672L13.684 16.6m0 0l-2.51 2.225.569-9.47 5.227 7.917-3.286-.672zM12 2.25V4.5m5.834.166l-1.591 1.591M20.25 10.5H18M7.757 14.743l-1.59 1.59M6 10.5H3.75m4.007-4.243l-1.59-1.59"/></svg>
        <h3>Assign by Hand</h3>
    </div>
    <form method="POST" action="<?= $action ?>" id="manualForm">
        <?= Security::csrfField() ?>
        <input type="hidden" name="action" value="assign">
        <div class="form-card-body">
            <div class="bulk-bar">
                <label class="lead-pick" style="font-weight:600"><input type="checkbox" id="pickAll"> Select all</label>
                <span id="pickCount" style="font-size:12px;color:var(--text-muted)">0 selected</span>
                <select name="agent_id" class="filter-input" required>
                    <option value="">Assign to…</option>
                    <?php foreach ($agents as $a): ?>
                        <option value="<?= (int)$a['id'] ?>"><?= Security::e($a['name']) ?> (<?= (int)$a['open_leads'] ?> open)</option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary btn-sm">Assign Selected</button>
            </div>
            <?php if ($shownCount < $unassigned): ?>
                <div style="font-size:12px;color:var(--text-muted);margin:8px 0">Showing the first <?= $shownCount ?> of <?= (int)$unassigned ?> unassigned leads (hot first, oldest first).</div>
            <?php endif; ?>
        </div>
        <div style="overflow-x:auto">
            <table class="data-table table-cards">
                <thead><tr><th>Lead</th><th>Phone</th><th>Project</th><th>Source</th><th>Priority</th><th>Added</th></tr></thead>
                <tbody>
                <?php foreach ($leads as $l): ?>
                    <tr>
                        <td class="tc-title">
                            <label class="lead-pick"><input type="checkbox" name="lead_ids[]" value="<?= (int)$l['id'] ?>" class="pick"> <?= Security::e($l['name']) ?></label>
                        </td>
                        <td style="white-space:nowrap"><?= Security::e($l['phone'] ?? '-') ?></td>
                        <td><?= Security::e($l['project'] ?? '-') ?></td>
                        <td><?= Security::e($l['source_name'] ?? '-') ?></td>
                        <td><span class="badge badge-<?= Security::e($l['priority']) ?>"><span class="badge-dot"></span><?= ucfirst(Security::e($l['priority'])) ?></span></td>
                        <td style="font-size:12px;color:var(--text-muted);white-space:nowrap"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </form>
</div>

<script>
(function() {
    var unassigned = <?= (int)$unassigned ?>;
    var auto = document.getElementById('autoForm');
    var preview = document.getElementById('autoPreview');
    var maxInput = document.getElementById('maxLeads');

    function updatePreview() {
        var k = auto.querySelectorAll('input[name="agent_ids[]"]:checked').length;
        var n = Math.min(unassigned, parseInt(maxInput.value, 10) || unassigned);
        if (!k) { preview.textContent = 'Tick at least one agent.'; return ''; }
        var each = Math.floor(n / k), extra = n % k;
        var text = n + ' lead' + (n === 1 ? '' : 's') + ' to ' + k + ' agent' + (k === 1 ? '' : 's') + ': '
                 + (extra ? extra + ' get ' + (each + 1) + ', ' + (k - extra) + ' get ' + each : 'each gets ' + each) + '.';
        preview.textContent = text;
        return text;
    }
    auto.addEventListener('change', updatePreview);
    maxInput.addEventListener('input', updatePreview);
    updatePreview();
    auto.addEventListener('submit', function(e) {
        var text = updatePreview();
        if (!text || !confirm('Share ' + text + '\n\nContinue?')) e.preventDefault();
    });

    var manual = document.getElementById('manualForm');
    var picks = manual.querySelectorAll('.pick');
    var all = document.getElementById('pickAll');
    var count = document.getElementById('pickCount');
    function updateCount() {
        var n = manual.querySelectorAll('.pick:checked').length;
        count.textContent = n + ' selected';
        all.checked = n > 0 && n === picks.length;
    }
    all.addEventListener('change', function() {
        picks.forEach(function(p) { p.checked = all.checked; });
        updateCount();
    });
    picks.forEach(function(p) { p.addEventListener('change', updateCount); });
    manual.addEventListener('submit', function(e) {
        if (!manual.querySelectorAll('.pick:checked').length) { e.preventDefault(); alert('Tick at least one lead.'); }
    });
})();
</script>

<?php endif; ?>

<?php
$content = ob_get_clean();
require APP_ROOT . '/app/views/layouts/admin.php';
?>
