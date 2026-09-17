<?php
Security::requireAdmin();
// $client, $sources injected by AdminController::clientDetail()
$pageTitle  = 'Client: ' . ($client['name'] ?? 'Detail');
$activePage = 'clients';
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><?= Security::e($_SESSION['success']) ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1><?= Security::e($client['name'] ?? 'Client Detail') ?></h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <a href="<?= APP_URL ?>/admin/clients">Clients</a>
            <span class="sep">/</span>
            <span class="current"><?= Security::e($client['name'] ?? 'Detail') ?></span>
        </div>
    </div>
    <div class="page-header-actions">
        <?php if ($client['lead_id']): ?>
            <a href="<?= APP_URL ?>/admin/lead/<?= (int)$client['lead_id'] ?>" class="btn btn-secondary">View Original Lead</a>
        <?php endif; ?>
        <button class="btn btn-secondary" onclick="window.print()">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:16px;height:16px"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0l.229 2.523a1.125 1.125 0 01-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0021 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 00-1.913-.247M6.34 18H5.25A2.25 2.25 0 013 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 011.913-.247m10.5 0a48.536 48.536 0 00-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5zm-3 0h.008v.008H15V10.5z"/></svg>
            Print File
        </button>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start">

    <!-- Current Details (read view) -->
    <div class="card" style="padding:24px">
        <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:18px">Current Record</div>
        <?php $rows = [
            ['Name',           $client['name']           ?? null],
            ['Contact No.',    $client['contact_no']     ?? null],
            ['Address',        $client['address']        ?? null],
            ['Project',        $client['project']        ?? null],
            ['Category',       $client['category']       ?? null],
            ['Block',          $client['block']          ?? null],
            ['Unit No.',       $client['unit_no']        ?? null],
            ['Booking Amount', $client['booking_amount'] ? 'Rs. ' . number_format((float)$client['booking_amount']) : null],
            ['Agent',          $client['agent_name']     ?? null],
            ['Lead From',      $client['source_name']    ?? null],
            ['Created On',     date('d M Y', strtotime($client['created_at']))],
        ]; ?>
        <div style="display:flex;flex-direction:column;gap:10px">
            <?php foreach ($rows as [$label, $value]): ?>
            <div style="display:flex;gap:12px;font-size:13px;padding:8px 0;border-bottom:1px solid var(--border)">
                <span style="color:var(--text-muted);width:110px;flex-shrink:0"><?= $label ?></span>
                <span style="color:var(--navy);font-weight:500"><?= $value ? Security::e($value) : '<span style="color:var(--text-muted)">—</span>' ?></span>
            </div>
            <?php endforeach; ?>
            <!-- File Status -->
            <div style="display:flex;gap:12px;font-size:13px;padding:8px 0">
                <span style="color:var(--text-muted);width:110px;flex-shrink:0">File Status</span>
                <span>
                    <?php if ($client['file_status'] === 'mature'): ?>
                        <span class="status-pill" style="background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0">Mature</span>
                    <?php else: ?>
                        <span class="status-pill" style="background:#fff8e8;color:#92600a;border:1px solid #f0e2bd">Immature</span>
                        <?php if ($client['file_flag_reason']): ?>
                            <div style="font-size:11px;color:#92600a;margin-top:4px">Reason: <?= Security::e($client['file_flag_reason']) ?></div>
                        <?php endif; ?>
                    <?php endif; ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Edit Form -->
    <div class="card" style="padding:24px">
        <div style="font-size:14px;font-weight:600;color:var(--navy);margin-bottom:18px">Edit Details</div>
        <form method="POST" action="<?= APP_URL ?>/admin/client/<?= (int)$client['id'] ?>">
            <?= Security::csrfField() ?>
            <div style="display:flex;flex-direction:column;gap:12px">
                <div class="form-group">
                    <label>Name</label>
                    <input type="text" name="name" value="<?= Security::e($client['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Contact No.</label>
                    <input type="text" name="contact_no" value="<?= Security::e($client['contact_no'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Address</label>
                    <input type="text" name="address" value="<?= Security::e($client['address'] ?? '') ?>">
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                    <div class="form-group">
                        <label>Project</label>
                        <input type="text" name="project" value="<?= Security::e($client['project'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category">
                            <option value="">— Select —</option>
                            <?php foreach (['Residential','Commercial','Plot','Other'] as $cat): ?>
                                <option value="<?= $cat ?>" <?= ($client['category'] ?? '') === $cat ? 'selected' : '' ?>><?= $cat ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Block</label>
                        <input type="text" name="block" value="<?= Security::e($client['block'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Unit No.</label>
                        <input type="text" name="unit_no" value="<?= Security::e($client['unit_no'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Booking Amount (Rs.)</label>
                    <input type="number" name="booking_amount" value="<?= Security::e($client['booking_amount'] ?? '') ?>" min="0" step="1000">
                </div>
                <div class="form-group">
                    <label>File Status</label>
                    <select name="file_status" id="adminFileStatus" onchange="document.getElementById('adminFlagReason').style.display=this.value==='immature'?'block':'none'">
                        <option value="mature"   <?= $client['file_status'] === 'mature'   ? 'selected' : '' ?>>Mature</option>
                        <option value="immature" <?= $client['file_status'] === 'immature' ? 'selected' : '' ?>>Immature</option>
                    </select>
                </div>
                <div class="form-group" id="adminFlagReason" style="display:<?= $client['file_status'] === 'immature' ? 'block' : 'none' ?>">
                    <label>Reason (Immature)</label>
                    <select name="flag_reason">
                        <option value="no_funds"        <?= ($client['file_flag_reason'] ?? '') === 'no_funds'        ? 'selected' : '' ?>>No funds</option>
                        <option value="partial_payment" <?= ($client['file_flag_reason'] ?? '') === 'partial_payment' ? 'selected' : '' ?>>Partial payment</option>
                        <option value="other"           <?= ($client['file_flag_reason'] ?? '') === 'other'           ? 'selected' : '' ?>>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Lead Source</label>
                    <select name="source_id">
                        <option value="">— Unknown —</option>
                        <?php foreach ($sources as $src): ?>
                            <option value="<?= (int)$src['id'] ?>" <?= ((int)($client['source_id'] ?? 0)) === (int)$src['id'] ? 'selected' : '' ?>><?= Security::e($src['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top:4px">Save Changes</button>
            </div>
        </form>
    </div>

</div>

<style>
@media print {
    /* Hide edit form - only print the client record card */
    div[style*="grid-template-columns:1fr 1fr"] > div:last-child { display: none !important; }
    div[style*="grid-template-columns:1fr 1fr"] { grid-template-columns: 1fr !important; }
    body::before {
        content: "HK Builders & Developers  -  Client File Record  -  <?= date('d M Y') ?>";
        display: block; font-size: 12px; font-weight: 600; color: #002147;
        border-bottom: 2px solid #c9a84c; padding-bottom: 8px; margin-bottom: 14px;
    }
}
</style>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
