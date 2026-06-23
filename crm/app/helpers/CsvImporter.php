<?php
require_once __DIR__ . '/../../config/database.php';

class CsvImporter {

    private PDO $db;

    // Maps common CSV header variations → our field names
    private const COLUMN_MAP = [
        'name'         => ['name','full name','client name','customer name','contact name','lead name'],
        'phone'        => ['phone','mobile','contact','phone number','mobile number','cell','telephone'],
        'email'        => ['email','email address','e-mail','mail'],
        'company'      => ['company','organization','organisation','business','firm','company name'],
        'country'      => ['country','city','location','city/country','address','area'],
        'source'       => ['source','lead source','origin','channel','from'],
        'priority'     => ['priority','urgency','grade','rank','hot/warm/cold'],
        'notes'        => ['notes','note','remarks','comments','comment','description','details'],
    ];

    public function __construct() {
        $this->db = Database::connect();
    }

    public function import(string $filePath, int $uploadedBy): array {
        $result = ['imported' => 0, 'skipped' => 0, 'errors' => [], 'rows' => 0];

        if (!file_exists($filePath)) {
            $result['errors'][] = 'File not found.';
            return $result;
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $result['errors'][] = 'Could not open file.';
            return $result;
        }

        // Read header row
        $rawHeaders = fgetcsv($handle);
        if (!$rawHeaders) {
            $result['errors'][] = 'CSV file is empty or has no headers.';
            fclose($handle);
            return $result;
        }

        $columnIndex = $this->detectColumns($rawHeaders);

        if (!isset($columnIndex['name'])) {
            $result['errors'][] = 'Could not find a "name" column. Headers found: ' . implode(', ', $rawHeaders);
            fclose($handle);
            return $result;
        }

        // Load existing phones + emails for duplicate check
        $existingPhones = $this->getExistingValues('phone');
        $existingEmails = $this->getExistingValues('email');

        // Load source map: name → id
        $sourceMap = $this->getSourceMap();

        $batchId = $this->createBatch($uploadedBy, basename($filePath));

        $rowNum = 1;
        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            $result['rows']++;

            // Skip blank rows
            if (empty(array_filter($row))) continue;

            $name  = $this->col($row, $columnIndex, 'name');
            $phone = $this->normalizePhone($this->col($row, $columnIndex, 'phone'));
            $email = strtolower(trim($this->col($row, $columnIndex, 'email')));

            if ($name === '') {
                $result['errors'][] = "Row $rowNum: skipped — name is empty.";
                $result['skipped']++;
                continue;
            }

            // Duplicate check
            if ($phone && isset($existingPhones[$phone])) {
                $result['errors'][] = "Row $rowNum: skipped — phone \"$phone\" already exists.";
                $result['skipped']++;
                continue;
            }
            if ($email && isset($existingEmails[$email])) {
                $result['errors'][] = "Row $rowNum: skipped — email \"$email\" already exists.";
                $result['skipped']++;
                continue;
            }

            // Resolve source
            $sourceName = strtolower(trim($this->col($row, $columnIndex, 'source')));
            $sourceId   = null;
            if ($sourceName) {
                foreach ($sourceMap as $sName => $sId) {
                    if (strtolower($sName) === $sourceName) {
                        $sourceId = $sId;
                        break;
                    }
                }
                if (!$sourceId) $sourceId = $sourceMap['Manual Entry'] ?? $sourceMap['CSV Import'] ?? null;
            } else {
                $sourceId = $sourceMap['CSV Import'] ?? null;
            }

            // Resolve priority
            $priority = strtolower(trim($this->col($row, $columnIndex, 'priority')));
            if (!in_array($priority, ['hot','warm','cold'])) $priority = 'warm';

            // Insert lead
            $stmt = $this->db->prepare("
                INSERT INTO leads (name, phone, email, company, country, source_id, status_id, priority, initial_notes, created_by)
                VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $phone ?: null,
                $email ?: null,
                $this->col($row, $columnIndex, 'company') ?: null,
                $this->col($row, $columnIndex, 'country') ?: null,
                $sourceId,
                $priority,
                $this->col($row, $columnIndex, 'notes') ?: null,
                $uploadedBy,
            ]);

            $leadId = (int)$this->db->lastInsertId();

            // Log activity
            $this->db->prepare(
                "INSERT INTO lead_activities (lead_id, user_id, type, note) VALUES (?, ?, 'csv_import', 'Imported via CSV upload.')"
            )->execute([$leadId, $uploadedBy]);

            // Track for duplicate check within same file
            if ($phone) $existingPhones[$phone] = true;
            if ($email) $existingEmails[$email]  = true;

            $result['imported']++;
        }

        fclose($handle);

        // Update batch record
        $this->db->prepare(
            "UPDATE import_batches SET total_rows=?, imported=?, skipped=? WHERE id=?"
        )->execute([$result['rows'], $result['imported'], $result['skipped'], $batchId]);

        return $result;
    }

    private function detectColumns(array $headers): array {
        $map = [];
        foreach ($headers as $idx => $header) {
            $h = strtolower(trim($header));
            foreach (self::COLUMN_MAP as $field => $variants) {
                if (in_array($h, $variants) && !isset($map[$field])) {
                    $map[$field] = $idx;
                }
            }
        }
        return $map;
    }

    private function col(array $row, array $colIdx, string $field): string {
        if (!isset($colIdx[$field])) return '';
        return trim($row[$colIdx[$field]] ?? '');
    }

    private function normalizePhone(string $phone): string {
        // Strip spaces and dashes for comparison
        return preg_replace('/[\s\-()]/', '', $phone);
    }

    private function getExistingValues(string $field): array {
        $rows = $this->db->query("SELECT $field FROM leads WHERE $field IS NOT NULL AND deleted_at IS NULL")->fetchAll(PDO::FETCH_COLUMN);
        $map  = [];
        foreach ($rows as $v) {
            $key = $field === 'phone' ? preg_replace('/[\s\-()]/', '', $v) : strtolower($v);
            $map[$key] = true;
        }
        return $map;
    }

    private function getSourceMap(): array {
        $rows = $this->db->query("SELECT name, id FROM lead_sources")->fetchAll();
        $map  = [];
        foreach ($rows as $r) $map[$r['name']] = (int)$r['id'];
        return $map;
    }

    private function createBatch(int $userId, string $filename): int {
        $this->db->prepare(
            "INSERT INTO import_batches (uploaded_by, filename, total_rows, imported, skipped) VALUES (?, ?, 0, 0, 0)"
        )->execute([$userId, $filename]);
        return (int)$this->db->lastInsertId();
    }
}
