<?php
// ---- Notification counts (title, banner, sidebar badge) ----
if (!isset($_notifOverdue)) {
    try {
        require_once APP_ROOT . '/app/models/Lead.php';
        $_lead = new Lead();
        $_agId = ($_SESSION['user_role'] ?? '') === 'admin' ? null : (int)$_SESSION['user_id'];
        $_c    = $_agId === null ? $_lead->getAllFollowUpCounts() : $_lead->getFollowUpCounts($_agId);
        $_notifOverdue = (int)$_c['overdue'];
        $_notifSoon    = $_lead->getSoonFollowUpCount($_agId, 30);
        unset($_lead, $_agId, $_c);
    } catch (\Throwable $e) {
        $_notifOverdue = 0;
        $_notifSoon    = 0;
    }
}
$_notifTotal = $_notifOverdue + $_notifSoon;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $_notifTotal > 0 ? "({$_notifTotal}) " : '' ?><?= isset($pageTitle) ? Security::e($pageTitle) . ' - ' : '' ?>HK Builders CRM</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/favicon.svg">
    <link rel="stylesheet" href="<?= APP_URL ?>/css/app.css">
</head>
<body class="admin-layout">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-brand">
        <div class="sidebar-logo-mark">HK</div>
        <div class="sidebar-brand-text">
            <span class="name">HK Builders</span>
            <span class="role"><?= $_SESSION['user_role'] === 'admin' ? 'Admin Panel' : 'Agent Portal' ?></span>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php if ($_SESSION['user_role'] === 'admin'): ?>
        <div class="sidebar-nav-label">Main</div>
        <a href="<?= APP_URL ?>/admin/dashboard" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            Dashboard
        </a>
        <a href="<?= APP_URL ?>/admin/leads" class="<?= ($activePage ?? '') === 'leads' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            All Leads
        </a>

        <div class="sidebar-nav-label">Manage</div>
        <a href="<?= APP_URL ?>/admin/agents" class="<?= ($activePage ?? '') === 'agents' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            Agents
        </a>
        <a href="<?= APP_URL ?>/admin/clients" class="<?= ($activePage ?? '') === 'clients' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            Clients
        </a>
        <a href="<?= APP_URL ?>/admin/teams" class="<?= ($activePage ?? '') === 'teams' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            Teams
        </a>
        <a href="<?= APP_URL ?>/admin/reports" class="<?= ($activePage ?? '') === 'reports' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
            Reports
        </a>
        <a href="<?= APP_URL ?>/admin/followups" class="<?= ($activePage ?? '') === 'followups' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between">
            <span style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>
                Follow-ups
            </span>
            <?php if ($_notifOverdue > 0): ?>
            <span style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;min-width:18px;text-align:center"><?= $_notifOverdue ?></span>
            <?php endif; ?>
        </a>
        <a href="<?= APP_URL ?>/admin/notices" class="<?= ($activePage ?? '') === 'notices' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
            Notices
        </a>
        <a href="<?= APP_URL ?>/admin/audit" class="<?= ($activePage ?? '') === 'audit' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
            Audit Log
        </a>

        <div class="sidebar-nav-label">Account</div>
        <a href="<?= APP_URL ?>/admin/password" class="<?= ($activePage ?? '') === 'password' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            Change Password
        </a>

        <?php else: ?>
        <div class="sidebar-nav-label">My Work</div>
        <a href="<?= APP_URL ?>/agent/dashboard" class="<?= ($activePage ?? '') === 'dashboard' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
            Dashboard
        </a>
        <a href="<?= APP_URL ?>/agent/leads" class="<?= ($activePage ?? '') === 'leads' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
            My Leads
        </a>
        <a href="<?= APP_URL ?>/agent/pool" class="<?= ($activePage ?? '') === 'pool' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 2.625c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125"/></svg>
            Lead Pool
        </a>
        <?php if (($_SESSION['user_role'] ?? '') === 'sales_manager'): ?>
        <div class="sidebar-nav-label">My Team</div>
        <a href="<?= APP_URL ?>/agent/team" class="<?= ($activePage ?? '') === 'team' ? 'active' : '' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a3 3 0 00-4.681 2.72 8.986 8.986 0 003.74.477m.94-3.197a5.971 5.971 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z"/></svg>
            Team Overview
        </a>
        <?php endif; ?>
        <div class="sidebar-nav-label">Follow-ups</div>
        <a href="<?= APP_URL ?>/agent/followups" class="<?= ($activePage ?? '') === 'followups' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between">
            <span style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5m-9-6h.008v.008H12v-.008zM12 15h.008v.008H12V15zm0 2.25h.008v.008H12v-.008zM9.75 15h.008v.008H9.75V15zm0 2.25h.008v.008H9.75v-.008zM7.5 15h.008v.008H7.5V15zm0 2.25h.008v.008H7.5v-.008zm6.75-4.5h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V15zm0 2.25h.008v.008h-.008v-.008zm2.25-4.5h.008v.008H16.5v-.008zm0 2.25h.008v.008H16.5V15z"/></svg>
                Follow-ups
            </span>
            <?php if ($_notifOverdue > 0): ?>
            <span style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;min-width:18px;text-align:center"><?= $_notifOverdue ?></span>
            <?php endif; ?>
        </a>
        <div class="sidebar-nav-label">Updates</div>
        <a href="<?= APP_URL ?>/agent/notices" class="<?= ($activePage ?? '') === 'notices' ? 'active' : '' ?>" style="display:flex;align-items:center;justify-content:space-between">
            <span style="display:flex;align-items:center;gap:10px">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                Notices
            </span>
            <?php
            if (!isset($noticePendingCount)) {
                try {
                    require_once APP_ROOT . '/app/models/Notice.php';
                    $noticePendingCount = (new Notice())->getPendingTaskCount((int)$_SESSION['user_id']);
                } catch (PDOException $e) {
                    $noticePendingCount = 0;
                }
            }
            if ($noticePendingCount > 0):
            ?>
            <span style="background:#ef4444;color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;font-weight:700;min-width:18px;text-align:center"><?= $noticePendingCount ?></span>
            <?php endif; ?>
        </a>
        <div class="sidebar-nav-label">Account</div>
        <a href="<?= APP_URL ?>/agent/password">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
            Change Password
        </a>
        <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
        <div class="sidebar-user">
            <div class="sidebar-avatar"><?= strtoupper(substr($_SESSION['user_name'], 0, 1)) ?></div>
            <span class="sidebar-user-name"><?= Security::e($_SESSION['user_name']) ?></span>
        </div>
        <a href="<?= APP_URL ?>/logout" class="sidebar-logout" title="Logout">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
        </a>
    </div>
</aside>

<!-- Mobile overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Main -->
<main class="main-content">
    <div class="topbar">
        <div style="display:flex;align-items:center;gap:12px">
            <button class="hamburger" id="hamburger" aria-label="Toggle menu">
                <span></span><span></span><span></span>
            </button>
            <span class="topbar-title"><?= Security::e($pageTitle ?? 'Dashboard') ?></span>
        </div>
        <div class="topbar-right">
            <span class="topbar-badge"><?= match($_SESSION['user_role'] ?? '') { 'admin' => 'Administrator', 'sales_manager' => 'Sales Manager', default => 'Sales Agent' } ?></span>
        </div>
    </div>
    <?php if ($_notifOverdue > 0 || $_notifSoon > 0): ?>
    <div id="notif-banner" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;padding:10px 20px;font-size:13px;font-weight:500;border-bottom:1px solid rgba(0,0,0,.08);<?= $_notifOverdue > 0 ? 'background:#fef2f2;color:#991b1b' : 'background:#fffbeb;color:#92400e' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:16px;height:16px;flex-shrink:0"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
        <?php if ($_notifOverdue > 0): ?>
        <span><?= $_notifOverdue ?> follow-up<?= $_notifOverdue > 1 ? 's are' : ' is' ?> overdue.</span>
        <?php endif; ?>
        <?php if ($_notifSoon > 0): ?>
        <span><?= $_notifSoon ?> follow-up<?= $_notifSoon > 1 ? 's' : '' ?> due within 30 minutes.</span>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/<?= ($_SESSION['user_role'] ?? '') === 'admin' ? 'admin' : 'agent' ?>/followups" style="font-weight:700;text-decoration:underline;color:inherit;margin-left:4px">View &rarr;</a>
        <button onclick="document.getElementById('notif-banner').style.display='none'" style="margin-left:auto;background:none;border:none;cursor:pointer;font-size:18px;line-height:1;color:inherit;padding:0 4px" aria-label="Dismiss">&times;</button>
    </div>
    <?php endif; ?>
    <div class="content-wrapper">
        <?= $content ?>
    </div>
</main>

<script src="<?= APP_URL ?>/js/app.js"></script>
<?php if (($activePage ?? '') === 'dashboard'): ?>
<script>setTimeout(function(){location.reload()},300000);</script>
<?php endif; ?>
</body>
</html>
