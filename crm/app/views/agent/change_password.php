<?php
Security::requireLogin();
$pageTitle  = 'Change Password';
$activePage = '';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Change Password</h1>
        <div class="breadcrumb"><span class="current">Account Security</span></div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/agent/dashboard" class="btn btn-secondary">← Back</a>
    </div>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?= Security::e($_SESSION['error']) ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<div style="max-width:480px">
    <div class="form-card">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            <h3>Update Your Password</h3>
        </div>
        <form method="POST" action="<?= APP_URL ?>/agent/password">
            <?= Security::csrfField() ?>
            <div class="form-card-body">
                <div class="form-group">
                    <label>Current Password *</label>
                    <input type="password" name="current_password" required placeholder="Your current password">
                </div>
                <div class="form-group">
                    <label>New Password *</label>
                    <input type="password" name="new_password" required placeholder="Min 8 characters" minlength="8">
                </div>
                <div class="form-group">
                    <label>Confirm New Password *</label>
                    <input type="password" name="confirm_password" required placeholder="Repeat new password" minlength="8">
                </div>
            </div>
            <div class="form-footer">
                <a href="<?= APP_URL ?>/agent/dashboard" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Password</button>
            </div>
        </form>
    </div>
</div>

<?php $content = ob_get_clean();
require_once __DIR__ . '/../layouts/agent.php'; ?>
