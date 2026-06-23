<?php
Security::requireAdmin();
require_once __DIR__ . '/../../models/Lead.php';

$lead = new Lead();

// --- Filters from GET ---
$search   = trim($_GET['search']   ?? '');
$statusId = (int)($_GET['status']   ?? 0);
$priority = $_GET['priority'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 20;
$offset   = ($page - 1) * $perPage;

$filters = [
    'search'    => $search,
    'status_id' => $statusId ?: null,
    'priority'  => in_array($priority, ['hot','warm','cold']) ? $priority : null,
    'date_from' => $dateFrom ?: null,
    'date_to'   => $dateTo   ?: null,
    'limit'     => $perPage,
    'offset'    => $offset,
];

$leads    = $lead->getAll($filters);
$total    = $lead->countAll($filters);
$pages    = (int)ceil($total / $perPage);
$statuses = $lead->getStatuses();

// Build URL helper preserving all filters except page
function filtersUrl(array $overrides = []): string {
    $base = array_filter([
        'search'    => $_GET['search']   ?? '',
        'status'    => $_GET['status']   ?? '',
        'priority'  => $_GET['priority'] ?? '',
        'date_from' => $_GET['date_from'] ?? '',
        'date_to'   => $_GET['date_to']   ?? '',
        'page'      => $_GET['page']      ?? '',
    ]);
    return '?' . http_build_query(array_merge($base, $overrides));
}

$pageTitle  = 'All Leads';
$activePage = 'leads';
ob_start();
?>

<?php if (!empty($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= Security::e($_SESSION['success']) ?>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<div class="page-header">
    <div class="page-header-left">
        <h1>All Leads</h1>
        <div class="breadcrumb">
            Dashboard <span class="sep">/</span>
            <span class="current">Leads</span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/admin/leads?action=import" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
            Import CSV
        </a>
        <a href="<?= APP_URL ?>/admin/leads?action=add" class="btn btn-primary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/></svg>
            Add Lead
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="filter-bar">
    <form method="GET" action="<?= APP_URL ?>/admin/leads" id="filterForm">
        <div class="filter-row">

            <!-- Search -->
            <div class="filter-search">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/></svg>
                <input type="text" name="search" placeholder="Search name, phone, email, company…"
                    value="<?= Security::e($search) ?>" autocomplete="off">
            </div>

            <!-- Status -->
            <div class="filter-select-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/></svg>
                <select name="status" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <?php foreach ($statuses as $s): ?>
                        <option value="<?= (int)$s['id'] ?>"
                            <?= $statusId === (int)$s['id'] ? 'selected' : '' ?>>
                            <?= Security::e($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Priority -->
            <div class="filter-select-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/></svg>
                <select name="priority" onchange="this.form.submit()">
                    <option value="">All Priorities</option>
                    <option value="hot"  <?= $priority === 'hot'  ? 'selected' : '' ?>>🔴 Hot</option>
                    <option value="warm" <?= $priority === 'warm' ? 'selected' : '' ?>>🟠 Warm</option>
                    <option value="cold" <?= $priority === 'cold' ? 'selected' : '' ?>>🔵 Cold</option>
                </select>
            </div>

            <!-- Date From -->
            <div class="filter-date-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <input type="date" name="date_from" value="<?= Security::e($dateFrom) ?>"
                    title="From date" onchange="this.form.submit()">
            </div>

            <!-- Date To -->
            <div class="filter-date-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/></svg>
                <input type="date" name="date_to" value="<?= Security::e($dateTo) ?>"
                    title="To date" onchange="this.form.submit()">
            </div>

            <!-- Search Button -->
            <button type="submit" class="btn btn-primary btn-sm">Search</button>

            <!-- Clear -->
            <?php if ($search || $statusId || $priority || $dateFrom || $dateTo): ?>
                <a href="<?= APP_URL ?>/admin/leads" class="btn btn-secondary btn-sm">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Results summary -->
<div class="leads-meta">
    <span><?= number_format($total) ?> lead<?= $total !== 1 ? 's' : '' ?> found</span>
    <?php if ($pages > 1): ?>
        <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php endif; ?>
</div>

<!-- Table -->
<div class="table-wrapper">
    <table class="data-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Lead</th>
                <th>Phone</th>
                <th>Source</th>
                <th>Priority</th>
                <th>Status</th>
                <th>Assigned To</th>
                <th>Date Added</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $i => $row): ?>
            <tr onclick="window.location='<?= APP_URL ?>/admin/lead/<?= (int)$row['id'] ?>'">
                <td style="color:var(--text-muted);font-size:11px"><?= $offset + $i + 1 ?></td>
                <td>
                    <div class="lead-name"><?= Security::e($row['name']) ?></div>
                    <?php if ($row['email']): ?>
                        <div class="lead-sub"><?= Security::e($row['email']) ?></div>
                    <?php endif; ?>
                    <?php if ($row['company']): ?>
                        <div class="lead-sub"><?= Security::e($row['company']) ?></div>
                    <?php endif; ?>
                </td>
                <td><?= Security::e($row['phone'] ?? '—') ?></td>
                <td style="font-size:12px;color:var(--text-muted)"><?= Security::e($row['source_name'] ?? '—') ?></td>
                <td>
                    <span class="badge badge-<?= Security::e($row['priority']) ?>">
                        <span class="badge-dot"></span>
                        <?= ucfirst(Security::e($row['priority'])) ?>
                    </span>
                </td>
                <td>
                    <span class="status-pill" style="background:<?= Security::e($row['status_color']) ?>22;color:<?= Security::e($row['status_color']) ?>;border:1px solid <?= Security::e($row['status_color']) ?>44">
                        <?= Security::e($row['status_name']) ?>
                    </span>
                </td>
                <td style="font-size:12px">
                    <?php if ($row['agent_name']): ?>
                        <span class="agent-chip"><?= Security::e($row['agent_name']) ?></span>
                    <?php else: ?>
                        <span style="color:var(--text-muted)">Unassigned</span>
                    <?php endif; ?>
                </td>
                <td style="color:var(--text-muted);font-size:12px;white-space:nowrap">
                    <?= date('d M Y', strtotime($row['created_at'])) ?>
                </td>
                <td>
                    <a href="<?= APP_URL ?>/admin/lead/<?= (int)$row['id'] ?>" onclick="event.stopPropagation()">View</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (empty($leads)): ?>
            <tr><td colspan="9" class="empty-row">
                No leads found.
                <?php if ($search || $statusId || $priority || $dateFrom || $dateTo): ?>
                    <a href="<?= APP_URL ?>/admin/leads">Clear filters</a>
                <?php else: ?>
                    <a href="<?= APP_URL ?>/admin/leads?action=add">Add your first lead</a>
                <?php endif; ?>
            </td></tr>
        <?php endif; ?>
        </tbody>
    </table>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="<?= APP_URL ?>/admin/leads<?= filtersUrl(['page' => $page - 1]) ?>" class="page-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
                Prev
            </a>
        <?php endif; ?>

        <div class="page-numbers">
            <?php
            $start = max(1, $page - 2);
            $end   = min($pages, $page + 2);
            if ($start > 1): ?>
                <a href="<?= APP_URL ?>/admin/leads<?= filtersUrl(['page' => 1]) ?>" class="page-num">1</a>
                <?php if ($start > 2): ?><span class="page-ellipsis">…</span><?php endif; ?>
            <?php endif; ?>
            <?php for ($p = $start; $p <= $end; $p++): ?>
                <a href="<?= APP_URL ?>/admin/leads<?= filtersUrl(['page' => $p]) ?>"
                   class="page-num <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
            <?php endfor; ?>
            <?php if ($end < $pages): ?>
                <?php if ($end < $pages - 1): ?><span class="page-ellipsis">…</span><?php endif; ?>
                <a href="<?= APP_URL ?>/admin/leads<?= filtersUrl(['page' => $pages]) ?>" class="page-num"><?= $pages ?></a>
            <?php endif; ?>
        </div>

        <?php if ($page < $pages): ?>
            <a href="<?= APP_URL ?>/admin/leads<?= filtersUrl(['page' => $page + 1]) ?>" class="page-btn">
                Next
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/></svg>
            </a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
