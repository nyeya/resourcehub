<?php
/**
 * Backup Controller
 * Handles administrative database snapshot creation, downloads,
 * and confirmed database restoration.
 */

class BackupController {
    private $backupModel;

    public function __construct() {
        $this->backupModel = new BackupService();
    }

    /**
     * Display the Backup & Restore management console
     */
    public function index() {
        Auth::requireRole('administrator');

        $backups = $this->backupModel->listBackups();
        $dbStats = $this->backupModel->getDatabaseStats();

        view('backup/index', [
            'backups' => $backups,
            'dbStats' => $dbStats,
        ]);
    }

    /**
     * Create a new server snapshot backup
     */
    public function create() {
        Auth::requireRole('administrator');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('backup'));
            try {
                $customName = !empty($_POST['backup_label']) ? preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['backup_label']) : null;
                $res = $this->backupModel->createBackup($customName);
                flash('success', "Database snapshot created successfully ({$res['filename']}, {$res['size_formatted']}).");
            } catch (Exception $e) {
                flash('danger', 'Failed to create backup: ' . $e->getMessage());
            }
        }

        redirect(url('backup'));
    }

    /**
     * Download a database backup file
     * - If 'file' parameter is provided: downloads the specified saved snapshot.
     * - If no parameter: creates and streams a fresh database dump on-the-fly.
     */
    public function download() {
        Auth::requireRole('administrator');

        $filename = $_GET['file'] ?? null;

        if ($filename) {
            $filePath = $this->backupModel->getBackupFilePath($filename);
            if (!$filePath) {
                flash('danger', 'Requested backup file does not exist or has an invalid name.');
                redirect(url('backup'));
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($filePath);
            exit;
        }

        // Fresh on-the-fly backup download
        try {
            $dbName = $this->backupModel->getTargetDatabaseName();
            $downloadName = sprintf('%s_backup_%s.sql', $dbName, date('Y-m-d_His'));
            $tempFile = tempnam(sys_get_temp_dir(), 'rh_bak_');

            $this->backupModel->dumpDatabaseToFile($tempFile);

            if (!file_exists($tempFile) || filesize($tempFile) === 0) {
                throw new RuntimeException("Failed to generate database dump.");
            }

            header('Content-Description: File Transfer');
            header('Content-Type: application/sql; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $downloadName . '"');
            header('Content-Length: ' . filesize($tempFile));
            header('Pragma: no-cache');
            header('Expires: 0');
            readfile($tempFile);
            unlink($tempFile);
            exit;
        } catch (Exception $e) {
            flash('danger', 'Error generating database backup: ' . $e->getMessage());
            redirect(url('backup'));
        }
    }

    /**
     * Restore database from snapshot or uploaded file with mandatory confirmation
     */
    public function restore() {
        Auth::requireRole('administrator');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(url('backup'));
        }

        require_post_csrf(url('backup'));

        // Mandatory confirmation verification
        $confirmed = !empty($_POST['confirm_restore']) && (
            $_POST['confirm_restore'] === '1' ||
            strtoupper(trim($_POST['confirm_text'] ?? '')) === 'RESTORE'
        );

        if (!$confirmed) {
            flash('danger', 'Restoration cancelled: You must check the confirmation checkbox or type "RESTORE" to proceed.');
            redirect(url('backup'));
        }

        $source = $_POST['source'] ?? 'server';
        $restoreFile = null;
        $tempUploadFile = null;

        try {
            if ($source === 'upload') {
                if (empty($_FILES['backup_file']) || $_FILES['backup_file']['error'] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Please select a valid SQL backup file to upload.");
                }

                $uploadedName = $_FILES['backup_file']['name'];
                if (!str_ends_with(strtolower($uploadedName), '.sql')) {
                    throw new RuntimeException("Only .sql backup files are accepted for restoration.");
                }

                $tempUploadFile = $_FILES['backup_file']['tmp_name'];
                $restoreFile = $tempUploadFile;
                $sourceLabel = "uploaded file '{$uploadedName}'";
            } else {
                $filename = $_POST['filename'] ?? '';
                $restoreFile = $this->backupModel->getBackupFilePath($filename);
                if (!$restoreFile) {
                    throw new RuntimeException("Selected backup snapshot does not exist or has an invalid name.");
                }
                $sourceLabel = "server snapshot '{$filename}'";
            }

            // Perform restoration with verification
            $result = $this->backupModel->restoreFromSqlFile($restoreFile);

            $summary = [];
            foreach ($result['counts'] as $table => $count) {
                $summary[] = "{$count} {$table}";
            }

            flash('success', "Database successfully restored from {$sourceLabel}! Verified records: " . implode(', ', $summary) . '.');
        } catch (Exception $e) {
            flash('danger', 'Restoration failed: ' . $e->getMessage());
        }

        redirect(url('backup'));
    }

    /**
     * Delete an existing backup file
     */
    public function delete() {
        Auth::requireRole('administrator');

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            require_post_csrf(url('backup'));
            try {
                $filename = $_POST['filename'] ?? '';
                $this->backupModel->deleteBackup($filename);
                flash('success', "Backup snapshot '{$filename}' deleted successfully.");
            } catch (Exception $e) {
                flash('danger', 'Failed to delete backup: ' . $e->getMessage());
            }
        }

        redirect(url('backup'));
    }
}

