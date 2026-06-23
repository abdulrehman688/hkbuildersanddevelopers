<?php
Security::requireAdmin();

$result  = $_SESSION['import_result'] ?? null;
$isDone  = isset($_GET['done']) && $result;
unset($_SESSION['import_result']);

$pageTitle  = 'Import Leads';
$activePage = 'leads';
ob_start();
?>

<div class="page-header">
    <div class="page-header-left">
        <h1>Import Leads via CSV</h1>
        <div class="breadcrumb">
            <a href="<?= APP_URL ?>/admin/leads" style="color:var(--text-muted)">Leads</a>
            <span class="sep">/</span>
            <span class="current">CSV Import</span>
        </div>
    </div>
    <div class="page-header-actions">
        <a href="<?= APP_URL ?>/admin/csv-template" class="btn btn-secondary">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
            Download Template
        </a>
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-secondary">← Back to Leads</a>
    </div>
</div>

<?php if ($isDone && $result): ?>
<!-- Import Results -->
<div class="import-results">
    <div class="import-result-cards">
        <div class="stat-card green">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
            <div class="stat-number"><?= (int)$result['imported'] ?></div>
            <div class="stat-label">Imported</div>
        </div>
        <div class="stat-card gold">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg></div>
            <div class="stat-number"><?= (int)$result['skipped'] ?></div>
            <div class="stat-label">Skipped (Duplicates)</div>
        </div>
        <div class="stat-card blue">
            <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9h16.5m-16.5 6.75h16.5"/></svg></div>
            <div class="stat-number"><?= (int)$result['rows'] ?></div>
            <div class="stat-label">Total Rows Processed</div>
        </div>
    </div>

    <?php if ($result['imported'] > 0): ?>
        <div class="alert alert-success" style="margin-top:20px">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            Successfully imported <strong><?= (int)$result['imported'] ?></strong> lead<?= $result['imported'] !== 1 ? 's' : '' ?>.
            <a href="<?= APP_URL ?>/admin/leads" style="margin-left:8px;font-weight:600">View All Leads →</a>
        </div>
    <?php endif; ?>

    <?php if (!empty($result['errors'])): ?>
        <div class="form-card" style="margin-top:20px">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="color:#f59e0b"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <h3>Skipped / Warning Rows (<?= count($result['errors']) ?>)</h3>
            </div>
            <div class="form-card-body">
                <ul class="import-errors">
                    <?php foreach ($result['errors'] as $err): ?>
                        <li><?= Security::e($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <div style="margin-top:20px;display:flex;gap:10px">
        <a href="<?= APP_URL ?>/admin/leads?action=import" class="btn btn-secondary">Import Another File</a>
        <a href="<?= APP_URL ?>/admin/leads" class="btn btn-primary">View All Leads</a>
    </div>
</div>

<?php else: ?>
<!-- Upload Form -->

<div class="two-col" style="align-items:start;gap:24px">

    <!-- Left: Upload form -->
    <div>
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-error">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9.303 3.376c.866 1.5-.217 3.374-1.948 3.374H4.645c-1.73 0-2.813-1.874-1.948-3.374l7.108-12.374c.866-1.5 3.032-1.5 3.898 0L20.303 16.126zM12 15.75h.007v.008H12v-.008z"/></svg>
                <?= Security::e($_SESSION['error']) ?>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="form-card">
            <div class="form-card-header">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                <h3>Upload CSV File</h3>
            </div>
            <form method="POST" action="<?= APP_URL ?>/admin/leads?action=import" enctype="multipart/form-data">
                <?= Security::csrfField() ?>
                <div class="form-card-body">
                    <div class="csv-drop-zone" id="dropZone">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m6.75 12l-3-3m0 0l-3 3m3-3v6m-1.5-15H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                        <p class="drop-title">Drag & drop your CSV file here</p>
                        <p class="drop-sub">or click to browse</p>
                        <input type="file" name="csv_file" id="csvFile" accept=".csv" required>
                        <p class="drop-hint" id="fileName">Max <?= CSV_MAX_SIZE_MB ?>MB · CSV files only</p>
                    </div>
                </div>
                <div class="form-footer">
                    <a href="<?= APP_URL ?>/admin/csv-template" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                        Download Template
                    </a>
                    <button type="submit" class="btn btn-primary" id="importBtn" disabled>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5"/></svg>
                        Import Leads
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Right: Instructions -->
    <div class="form-card">
        <div class="form-card-header">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            <h3>How It Works</h3>
        </div>
        <div class="form-card-body">

            <div class="import-step">
                <div class="import-step-num">1</div>
                <div>
                    <strong>Download the template</strong>
                    <p>Use our CSV template to ensure the correct format. Column order doesn't matter — we auto-detect headers.</p>
                </div>
            </div>
            <div class="import-step">
                <div class="import-step-num">2</div>
                <div>
                    <strong>Fill in your leads</strong>
                    <p>Only <strong>name</strong> is required. Leave other columns blank if unknown.</p>
                </div>
            </div>
            <div class="import-step">
                <div class="import-step-num">3</div>
                <div>
                    <strong>Upload and import</strong>
                    <p>We'll check for duplicates by phone and email. Duplicate rows are skipped and reported.</p>
                </div>
            </div>

            <div class="divider"></div>

            <p style="font-size:12px;font-weight:600;color:var(--navy);margin-bottom:10px;letter-spacing:0.5px;text-transform:uppercase">Accepted Column Names</p>
            <div class="col-map-table">
                <div class="col-map-row"><span>name</span><span class="col-vars">name, full name, client name, contact name</span></div>
                <div class="col-map-row"><span>phone</span><span class="col-vars">phone, mobile, telephone, contact</span></div>
                <div class="col-map-row"><span>email</span><span class="col-vars">email, email address, e-mail</span></div>
                <div class="col-map-row"><span>company</span><span class="col-vars">company, organization, business</span></div>
                <div class="col-map-row"><span>country</span><span class="col-vars">country, city, location, area</span></div>
                <div class="col-map-row"><span>source</span><span class="col-vars">source, lead source, channel</span></div>
                <div class="col-map-row"><span>priority</span><span class="col-vars">priority · values: hot / warm / cold</span></div>
                <div class="col-map-row"><span>notes</span><span class="col-vars">notes, remarks, comments</span></div>
            </div>

            <div class="divider"></div>

            <p style="font-size:12px;font-weight:600;color:var(--navy);margin-bottom:8px;letter-spacing:0.5px;text-transform:uppercase">Valid Sources</p>
            <div style="font-size:12px;color:var(--text-muted);line-height:1.8">
                Facebook Ads · Google Ads · Website Form · Referral · Walk-in · Manual Entry · Other
            </div>
        </div>
    </div>

</div>
<?php endif; ?>

<script>
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('csvFile');
const fileName  = document.getElementById('fileName');
const importBtn = document.getElementById('importBtn');

if (dropZone && fileInput) {
    dropZone.addEventListener('click', () => fileInput.click());

    dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragging'); });
    dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragging'));
    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('dragging');
        if (e.dataTransfer.files.length) {
            fileInput.files = e.dataTransfer.files;
            updateFileName(e.dataTransfer.files[0]);
        }
    });

    fileInput.addEventListener('change', () => {
        if (fileInput.files.length) updateFileName(fileInput.files[0]);
    });

    function updateFileName(file) {
        fileName.textContent = file.name + ' (' + (file.size / 1024).toFixed(1) + ' KB)';
        fileName.style.color = 'var(--gold)';
        importBtn.disabled = false;
    }
}
</script>

<?php
$content = ob_get_clean();
require_once __DIR__ . '/../layouts/admin.php';
?>
