<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Lead.php';

$lead     = new Lead();
$sources  = $lead->getSources();
$statuses = $lead->getStatuses();

// Repopulate form on validation error
$old = $_SESSION['form'] ?? [];
unset($_SESSION['form']);

$pageTitle  = 'Add Lead';
$activePage = 'leads';
ob_start();
?>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Add New Lead</h1>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/admin/leads" style="color:var(--text-muted)">Leads</a>
            <span class="sep">/</span>
            <span class="current">Add Lead</span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/></svg>
            Back to Leads
        </a>
    </div>
</div>

<form method="POST" action="<?= APP_URL ?>/admin/leads?action=add" novalidate>
    <?= Security::csrfField() ?>

    <!-- Contact Information -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/></svg>
            <h3>Contact Information</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid">
                <div class="form-group">
                    <label for="name">Full Name <span style="color:#ef4444">*</span></label>
                    <input type="text" id="name" name="name" required
                        value="<?= Security::e($old['name'] ?? '') ?>"
                        placeholder="e.g. Ahmed Khan">
                </div>
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone"
                        value="<?= Security::e($old['phone'] ?? '') ?>"
                        placeholder="e.g. 0300-1234567">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email"
                        value="<?= Security::e($old['email'] ?? '') ?>"
                        placeholder="e.g. ahmed@example.com">
                </div>
                <div class="form-group">
                    <label for="company">Company / Organization</label>
                    <input type="text" id="company" name="company"
                        value="<?= Security::e($old['company'] ?? '') ?>"
                        placeholder="e.g. Khan Enterprises">
                </div>
                <div class="form-group">
                    <label for="country">City / Country</label>
                    <input type="text" id="country" name="country"
                        value="<?= Security::e($old['country'] ?? '') ?>"
                        placeholder="e.g. Karachi, Pakistan">
                </div>
            </div>
        </div>
    </div>

    <!-- Lead Classification -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
            <h3>Lead Classification</h3>
        </div>
        <div class="form-card-body">
            <div class="form-grid form-grid-3">
                <div class="form-group">
                    <label for="source_id">Lead Source</label>
                    <select id="source_id" name="source_id">
                        <option value="">— Select Source —</option>
                        <?php foreach ($sources as $src): ?>
                            <option value="<?= (int)$src['id'] ?>"
                                <?= ((int)($old['source_id'] ?? 0)) === (int)$src['id'] ? 'selected' : '' ?>>
                                <?= Security::e($src['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status_id">Initial Status</label>
                    <select id="status_id" name="status_id">
                        <?php foreach ($statuses as $st): ?>
                            <option value="<?= (int)$st['id'] ?>"
                                <?= ((int)($old['status_id'] ?? 1)) === (int)$st['id'] ? 'selected' : '' ?>>
                                <?= Security::e($st['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority">
                        <option value="warm" <?= ($old['priority'] ?? 'warm') === 'warm' ? 'selected' : '' ?>>Warm</option>
                        <option value="hot"  <?= ($old['priority'] ?? '') === 'hot'  ? 'selected' : '' ?>>Hot</option>
                        <option value="cold" <?= ($old['priority'] ?? '') === 'cold' ? 'selected' : '' ?>>Cold</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Notes -->
    <div class="form-card" style="margin-bottom:20px">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            <h3>Initial Notes</h3>
        </div>
        <div class="form-card-body">
            <div class="form-group">
                <label for="initial_notes">Notes (optional)</label>
                <textarea id="initial_notes" name="initial_notes" rows="4"
                    placeholder="Any initial information about this lead — what they're looking for, how they contacted us, etc."><?= Security::e($old['initial_notes'] ?? '') ?></textarea>
                <span class="form-hint">This will be saved as the first activity entry on the lead timeline.</span>
            </div>
        </div>
    </div>

    <!-- Actions -->
    <div style="display:flex;gap:12px;justify-content:flex-end">
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Save Lead
        </button>
    </div>

</form>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
