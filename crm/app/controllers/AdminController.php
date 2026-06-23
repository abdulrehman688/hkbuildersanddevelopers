<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../models/Lead.php';
require_once __DIR__ . '/../models/User.php';

class AdminController {

    private Lead $lead;
    private User $user;

    public function __construct() {
        $this->lead = new Lead();
        $this->user = new User();
    }

    public function dashboard(): void {
        require_once __DIR__ . '/../views/admin/dashboard.php';
    }

    // ---- Leads list + add + import -------------------------------

    public function leads(): void {
        $action = $_GET['action'] ?? '';

        if ($action === 'add') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $this->saveLead() : require_once __DIR__ . '/../views/admin/lead_add.php';
            return;
        }

        if ($action === 'import') {
            $_SERVER['REQUEST_METHOD'] === 'POST' ? $this->processImport() : require_once __DIR__ . '/../views/admin/lead_import.php';
            return;
        }

        require_once __DIR__ . '/../views/admin/leads.php';
    }

    private function processImport(): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: ' . APP_URL . '/admin/leads?action=import');
            exit;
        }

        if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            $_SESSION['error'] = 'Please select a CSV file to upload.';
            header('Location: ' . APP_URL . '/admin/leads?action=import');
            exit;
        }

        $file = $_FILES['csv_file'];

        // Validate extension and MIME
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $mime = mime_content_type($file['tmp_name']);
        $allowedMimes = ['text/plain','text/csv','application/csv','application/vnd.ms-excel'];

        if ($ext !== 'csv' || !in_array($mime, $allowedMimes)) {
            $_SESSION['error'] = 'Only CSV files are allowed.';
            header('Location: ' . APP_URL . '/admin/leads?action=import');
            exit;
        }

        $maxBytes = CSV_MAX_SIZE_MB * 1024 * 1024;
        if ($file['size'] > $maxBytes) {
            $_SESSION['error'] = 'File exceeds ' . CSV_MAX_SIZE_MB . 'MB limit.';
            header('Location: ' . APP_URL . '/admin/leads?action=import');
            exit;
        }

        // Save to upload dir
        if (!is_dir(CSV_UPLOAD_DIR)) mkdir(CSV_UPLOAD_DIR, 0755, true);
        $savedName = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.csv';
        $savedPath = CSV_UPLOAD_DIR . $savedName;

        if (!move_uploaded_file($file['tmp_name'], $savedPath)) {
            $_SESSION['error'] = 'Failed to save uploaded file.';
            header('Location: ' . APP_URL . '/admin/leads?action=import');
            exit;
        }

        require_once __DIR__ . '/../helpers/CsvImporter.php';
        $importer = new CsvImporter();
        $result   = $importer->import($savedPath, (int)$_SESSION['user_id']);

        // Clean up file after import
        @unlink($savedPath);

        $_SESSION['import_result'] = $result;
        header('Location: ' . APP_URL . '/admin/leads?action=import&done=1');
        exit;
    }

    public function downloadCsvTemplate(): void {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hk-leads-template.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        // Header row
        fputcsv($out, ['name','phone','email','company','country','source','priority','notes']);
        // Example rows
        fputcsv($out, ['Ahmed Khan','0300-1234567','ahmed@example.com','Khan Enterprises','Karachi','Facebook Ads','hot','Interested in 2BHK apartment']);
        fputcsv($out, ['Sara Malik','0321-9876543','sara@email.com','','Lahore','Google Ads','warm','']);
        fputcsv($out, ['Bilal Ahmed','','bilal@work.com','ABC Corp','Islamabad','Referral','cold','Called twice, no response']);
        fclose($out);
        exit;
    }

    private function saveLead(): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: ' . APP_URL . '/admin/leads?action=add');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['error'] = 'Name is required.';
            $_SESSION['form']  = $_POST;
            header('Location: ' . APP_URL . '/admin/leads?action=add');
            exit;
        }

        $data = [
            'name'          => $name,
            'email'         => trim($_POST['email'] ?? '') ?: null,
            'phone'         => trim($_POST['phone'] ?? '') ?: null,
            'company'       => trim($_POST['company'] ?? '') ?: null,
            'country'       => trim($_POST['country'] ?? '') ?: null,
            'source_id'     => !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null,
            'status_id'     => (int)($_POST['status_id'] ?? 1),
            'priority'      => in_array($_POST['priority'] ?? '', ['hot','warm','cold']) ? $_POST['priority'] : 'warm',
            'initial_notes' => trim($_POST['initial_notes'] ?? '') ?: null,
        ];

        $leadId = $this->lead->create($data, (int)$_SESSION['user_id']);
        $note   = 'Lead created by admin.';
        if ($data['initial_notes']) $note .= ' Notes: ' . $data['initial_notes'];
        $this->lead->logActivity($leadId, (int)$_SESSION['user_id'], 'note', $note);

        $_SESSION['success'] = 'Lead "' . htmlspecialchars($name, ENT_QUOTES) . '" added successfully.';
        header('Location: ' . APP_URL . '/admin/lead/' . $leadId);
        exit;
    }

    // ---- Lead detail + actions -----------------------------------

    public function leadDetail(int $id): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleLeadAction($id);
            return;
        }
        require_once __DIR__ . '/../views/admin/lead_detail.php';
    }

    private function handleLeadAction(int $id): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: ' . APP_URL . '/admin/lead/' . $id);
            exit;
        }

        $action = $_POST['action'] ?? '';
        $back   = APP_URL . '/admin/lead/' . $id;

        switch ($action) {

            case 'update_status':
                $statusId = (int)($_POST['status_id'] ?? 0);
                $note     = trim($_POST['note'] ?? '');
                if (!$statusId) break;

                $lead = $this->lead->findById($id);
                $this->lead->updateStatus($id, $statusId);
                $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'status_change',
                    $note ?: 'Status updated.',
                    ['from_status' => (int)$lead['status_id'], 'to_status' => $statusId]
                );
                $_SESSION['success'] = 'Status updated.';
                break;

            case 'add_note':
                $note = trim($_POST['note'] ?? '');
                if ($note === '') { $_SESSION['error'] = 'Note cannot be empty.'; break; }
                $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'note', $note);
                $_SESSION['success'] = 'Note added.';
                break;

            case 'reassign':
                $agentId = (int)($_POST['agent_id'] ?? 0);
                if (!$agentId) { $_SESSION['error'] = 'Please select an agent.'; break; }
                $agent = $this->user->findById($agentId);
                $this->lead->reassign($id, $agentId);
                $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'reassign',
                    'Lead reassigned to ' . ($agent['name'] ?? 'agent') . ' by admin.'
                );
                $_SESSION['success'] = 'Lead reassigned.';
                break;

            case 'unassign':
                $this->lead->unassign($id);
                $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'reassign',
                    'Lead unassigned and returned to pool by admin.'
                );
                $_SESSION['success'] = 'Lead returned to pool.';
                break;

            case 'edit':
                $this->updateLead($id);
                return;

            case 'delete':
                $this->lead->softDelete($id);
                $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'note', 'Lead deleted by admin.');
                $_SESSION['success'] = 'Lead deleted.';
                header('Location: ' . APP_URL . '/admin/leads');
                exit;
        }

        header('Location: ' . $back);
        exit;
    }

    private function updateLead(int $id): void {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') {
            $_SESSION['error'] = 'Name is required.';
            header('Location: ' . APP_URL . '/admin/lead/' . $id . '?edit=1');
            exit;
        }

        $this->lead->update($id, [
            'name'      => $name,
            'email'     => trim($_POST['email'] ?? '') ?: null,
            'phone'     => trim($_POST['phone'] ?? '') ?: null,
            'company'   => trim($_POST['company'] ?? '') ?: null,
            'country'   => trim($_POST['country'] ?? '') ?: null,
            'source_id' => !empty($_POST['source_id']) ? (int)$_POST['source_id'] : null,
            'status_id' => (int)($_POST['status_id'] ?? 1),
            'priority'  => in_array($_POST['priority'] ?? '', ['hot','warm','cold']) ? $_POST['priority'] : 'warm',
        ]);
        $this->lead->logActivity($id, (int)$_SESSION['user_id'], 'note', 'Lead details updated by admin.');
        $_SESSION['success'] = 'Lead updated.';
        header('Location: ' . APP_URL . '/admin/lead/' . $id);
        exit;
    }

    // ---- Agents --------------------------------------------------

    public function agents(): void {
        $action = $_GET['action'] ?? '';

        if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->saveAgent();
            return;
        }

        if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->toggleAgent();
            return;
        }

        if ($action === 'reset-password' && $_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->resetAgentPassword();
            return;
        }

        require_once __DIR__ . '/../views/admin/agents.php';
    }

    private function saveAgent(): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'Invalid request.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            $_SESSION['error'] = 'Name, email and password are required.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Invalid email address.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        if ($this->user->emailExists($email)) {
            $_SESSION['error'] = 'An account with this email already exists.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        if (strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        $this->user->createAgent($name, $email, $password);
        $_SESSION['success'] = 'Agent "' . htmlspecialchars($name, ENT_QUOTES) . '" created successfully.';
        header('Location: ' . APP_URL . '/admin/agents');
        exit;
    }

    private function toggleAgent(): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }
        $agentId = (int)($_POST['agent_id'] ?? 0);
        $status  = $_POST['status'] ?? '';
        if ($agentId && in_array($status, ['active', 'suspended'])) {
            $this->user->setStatus($agentId, $status);
            $_SESSION['success'] = 'Agent status updated.';
        }
        header('Location: ' . APP_URL . '/admin/agents');
        exit;
    }

    private function resetAgentPassword(): void {
        if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }
        $agentId  = (int)($_POST['agent_id'] ?? 0);
        $password = $_POST['new_password'] ?? '';

        if (!$agentId || strlen($password) < 8) {
            $_SESSION['error'] = 'Password must be at least 8 characters.';
            header('Location: ' . APP_URL . '/admin/agents');
            exit;
        }

        $this->user->updatePassword($agentId, $password);
        $_SESSION['success'] = 'Password reset successfully.';
        header('Location: ' . APP_URL . '/admin/agents');
        exit;
    }

    public function auditLog(): void {
        require_once __DIR__ . '/../views/admin/audit_log.php';
    }

    public function changePassword(): void {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!Security::verifyCsrfToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Invalid request.';
                header('Location: ' . APP_URL . '/admin/password');
                exit;
            }

            $current = $_POST['current_password'] ?? '';
            $new     = $_POST['new_password']     ?? '';
            $confirm = $_POST['confirm_password'] ?? '';

            $admin = $this->user->findById((int)$_SESSION['user_id']);

            if (!password_verify($current, $admin['password'])) {
                $_SESSION['error'] = 'Current password is incorrect.';
                header('Location: ' . APP_URL . '/admin/password');
                exit;
            }
            if (strlen($new) < 8) {
                $_SESSION['error'] = 'New password must be at least 8 characters.';
                header('Location: ' . APP_URL . '/admin/password');
                exit;
            }
            if ($new !== $confirm) {
                $_SESSION['error'] = 'Passwords do not match.';
                header('Location: ' . APP_URL . '/admin/password');
                exit;
            }

            $this->user->updatePassword((int)$_SESSION['user_id'], $new);

            require_once __DIR__ . '/../helpers/AuditLog.php';
            AuditLog::log('password_changed', (int)$_SESSION['user_id'], 'user', (int)$_SESSION['user_id']);

            $_SESSION['success'] = 'Password changed successfully.';
            header('Location: ' . APP_URL . '/admin/dashboard');
            exit;
        }

        require_once __DIR__ . '/../views/admin/change_password.php';
    }

    public function reports(): void {
        if (($_GET['export'] ?? '') === 'csv') {
            $this->exportLeadsCsv();
            return;
        }
        require_once __DIR__ . '/../views/admin/reports.php';
    }

    private function exportLeadsCsv(): void {
        $dateFrom = $_GET['date_from'] ?? '';
        $dateTo   = $_GET['date_to']   ?? '';
        $statusId = (int)($_GET['status_id'] ?? 0);

        $leads = $this->lead->getAll([
            'limit'     => 99999,
            'offset'    => 0,
            'status_id' => $statusId ?: null,
            'date_from' => $dateFrom ?: null,
            'date_to'   => $dateTo   ?: null,
        ]);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="hk-leads-export-' . date('Y-m-d') . '.csv"');
        header('Pragma: no-cache');

        $out = fopen('php://output', 'w');
        fputcsv($out, ['ID','Name','Phone','Email','Company','Country','Source','Priority','Status','Assigned To','Claimed At','Created At']);
        foreach ($leads as $row) {
            fputcsv($out, [
                $row['id'],
                $row['name'],
                $row['phone'] ?? '',
                $row['email'] ?? '',
                $row['company'] ?? '',
                $row['country'] ?? '',
                $row['source_name'] ?? '',
                $row['priority'],
                $row['status_name'],
                $row['agent_name'] ?? '',
                $row['claimed_at'] ?? '',
                $row['created_at'],
            ]);
        }
        fclose($out);
        exit;
    }
}
