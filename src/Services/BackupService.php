<?php
/**
 * Backup and Restoration Service
 * Manages database dumps (pg_dump), snapshot storage, archive downloads,
 * and verified transactional restorations.
 */

class BackupService {
    private $db;
    private $backupsDir;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Database::getConnection();
        $this->backupsDir = (defined('RH_ROOT') ? RH_ROOT : dirname(__DIR__, 2)) . '/backups';
        $this->ensureBackupsDir();
    }

    /**
     * Get the absolute path to the protected backups directory
     */
    public function getBackupsDir(): string {
        return $this->backupsDir;
    }

    /**
     * Ensure the backups directory exists and is secured against direct web access
     */
    public function ensureBackupsDir(): void {
        if (!is_dir($this->backupsDir)) {
            mkdir($this->backupsDir, 0755, true);
        }

        // Place .htaccess to prevent Apache serving backups directly if configured improperly
        $htaccess = $this->backupsDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Order deny,allow\nDeny from all\n");
        }

        // Place index.html as a secondary safety shield
        $indexHtml = $this->backupsDir . '/index.html';
        if (!file_exists($indexHtml)) {
            file_put_contents($indexHtml, "<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Access Denied</h1></body></html>");
        }
    }

    /**
     * List all available backup archives in backups/
     */
    public function listBackups(): array {
        $this->ensureBackupsDir();
        $files = glob($this->backupsDir . '/*.sql');
        if ($files === false) {
            return [];
        }

        $backups = [];
        foreach ($files as $file) {
            $filename = basename($file);
            $size = filesize($file);
            $mtime = filemtime($file);

            $backups[] = [
                'filename'       => $filename,
                'filepath'       => $file,
                'size'           => $size,
                'size_formatted' => $this->formatFileSize($size),
                'created_at'     => $mtime,
                'created_date'   => date('Y-m-d H:i:s', $mtime),
                'created_human'  => format_datetime(date('Y-m-d H:i:s', $mtime)),
            ];
        }

        // Sort descending by creation timestamp (newest first)
        usort($backups, fn($a, $b) => $b['created_at'] <=> $a['created_at']);

        return $backups;
    }

    /**
     * Get safe path to a specific backup file by filename (prevents directory traversal)
     */
    public function getBackupFilePath(string $filename): ?string {
        $cleanName = basename($filename);
        if ($cleanName !== $filename || !str_ends_with($filename, '.sql')) {
            return null;
        }

        $filePath = $this->backupsDir . '/' . $cleanName;
        if (!file_exists($filePath) || !is_file($filePath)) {
            return null;
        }

        return $filePath;
    }

    /**
     * Delete an existing backup file safely
     */
    public function deleteBackup(string $filename): bool {
        $filePath = $this->getBackupFilePath($filename);
        if (!$filePath) {
            throw new InvalidArgumentException("Backup file '{$filename}' not found or invalid.");
        }

        return unlink($filePath);
    }

    /**
     * Read local database credentials configuration safely
     */
    public function getConfig(): array {
        $configFile = defined('RH_CONFIG') ? (RH_CONFIG . '/database.php') : (__DIR__ . '/../../config/database.php');
        if (!file_exists($configFile)) {
            throw new RuntimeException("Database configuration file not found at: {$configFile}.");
        }
        return require $configFile;
    }

    /**
     * Retrieve target database name from active connection or config
     */
    public function getTargetDatabaseName(): string {
        try {
            $name = $this->db->query("SELECT current_database()")->fetchColumn();
            if (!empty($name)) {
                return (string)$name;
            }
        } catch (Exception $e) {}

        $config = $this->getConfig();
        return $config['database'] ?? 'resourcehub';
    }

    /**
     * Get system and database status metrics for the admin interface
     */
    public function getDatabaseStats(): array {
        $config = $this->getConfig();
        $targetDb = $this->getTargetDatabaseName();

        $stats = [
            'database_name' => $targetDb,
            'username'      => $config['username'] ?? 'resourcehub_app',
            'host'          => $config['host'] ?? '127.0.0.1',
            'port'          => $config['port'] ?? 5432,
            'driver'        => $config['driver'] ?? 'pgsql',
            'counts'        => [
                'users'        => 0,
                'equipment'    => 0,
                'loans'        => 0,
                'categories'   => 0,
                'locations'    => 0,
                'maintenance'  => 0,
                'loan_history' => 0,
            ],
            'pg_version'    => 'Unknown',
            'db_size'       => 'Unknown',
        ];

        try {
            foreach ($stats['counts'] as $table => &$count) {
                $count = (int)$this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            }
            unset($count);

            $ver = $this->db->query("SELECT version()")->fetchColumn();
            if ($ver) {
                // Shorten version string (e.g. "PostgreSQL 18.6 on x86_64-pc-windows-msvc...")
                $parts = explode(' on ', (string)$ver);
                $stats['pg_version'] = $parts[0] ?? (string)$ver;
            }

            $size = $this->db->query("SELECT pg_size_pretty(pg_database_size(current_database()))")->fetchColumn();
            if ($size) {
                $stats['db_size'] = (string)$size;
            }
        } catch (Exception $e) {
            // Non-critical, display available data
        }

        return $stats;
    }

    /**
     * Create a database backup and save it to the backups/ directory
     */
    public function createBackup(?string $customFilename = null): array {
        $this->ensureBackupsDir();

        if ($customFilename) {
            $filename = basename($customFilename);
            if (!str_ends_with($filename, '.sql')) {
                $filename .= '.sql';
            }
        } else {
            $dbName = $this->getTargetDatabaseName();
            $filename = sprintf('%s_backup_%s.sql', $dbName, date('Y-m-d_His'));
        }

        $targetPath = $this->backupsDir . '/' . $filename;
        $this->dumpDatabaseToFile($targetPath);

        if (!file_exists($targetPath) || filesize($targetPath) === 0) {
            throw new RuntimeException("Failed to generate backup archive '{$filename}'.");
        }

        $size = filesize($targetPath);
        return [
            'success'        => true,
            'filename'       => $filename,
            'filepath'       => $targetPath,
            'size'           => $size,
            'size_formatted' => $this->formatFileSize($size),
        ];
    }

    /**
     * Generate SQL dump content directly in-memory or streamed to output
     */
    public function dumpDatabaseToFile(string $targetFile): void {
        $config = $this->getConfig();
        $targetDb = $this->getTargetDatabaseName();

        // 1. Primary engine: pg_dump CLI utility
        $pgDumpPath = $this->locateBinary('pg_dump');
        if ($pgDumpPath) {
            $cmd = sprintf(
                '"%s" -U %s -h %s -p %d -d %s --clean --if-exists --no-owner --no-privileges -F p -f "%s"',
                $pgDumpPath,
                escapeshellarg($config['username']),
                escapeshellarg($config['host']),
                (int)$config['port'],
                escapeshellarg($targetDb),
                $targetFile
            );

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge($_ENV, [
                'PGPASSWORD' => $config['password'],
            ]);

            $process = proc_open($cmd, $descriptors, $pipes, null, $env);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                if ($exitCode === 0 && file_exists($targetFile) && filesize($targetFile) > 0) {
                    return;
                }
            }
        }

        // 2. Pure PHP fallback if pg_dump binary is unavailable or fails
        $this->dumpDatabasePhp($targetFile);
    }

    /**
     * Restore database from a SQL file with verification
     */
    public function restoreFromSqlFile(string $filePath): array {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            throw new InvalidArgumentException("Restoration file does not exist or is not readable: {$filePath}");
        }

        if (filesize($filePath) === 0) {
            throw new InvalidArgumentException("Restoration file is empty.");
        }

        // Basic sanity check on header content
        $sample = file_get_contents($filePath, false, null, 0, 4096);
        if ($sample === false || (!str_contains($sample, 'PostgreSQL') && !str_contains($sample, 'users') && !str_contains($sample, 'CREATE TABLE') && !str_contains($sample, 'INSERT INTO'))) {
            throw new InvalidArgumentException("The provided file does not appear to be a valid SQL backup.");
        }

        $config = $this->getConfig();
        $targetDb = $this->getTargetDatabaseName();
        $restoredSuccessfully = false;
        $errorMessage = '';

        // 1. Primary method: psql CLI utility
        $psqlPath = $this->locateBinary('psql');
        if ($psqlPath) {
            $cmd = sprintf(
                '"%s" -U %s -h %s -p %d -d %s -f "%s"',
                $psqlPath,
                escapeshellarg($config['username']),
                escapeshellarg($config['host']),
                (int)$config['port'],
                escapeshellarg($targetDb),
                $filePath
            );

            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $env = array_merge($_ENV, [
                'PGPASSWORD' => $config['password'],
            ]);

            $process = proc_open($cmd, $descriptors, $pipes, null, $env);
            if (is_resource($process)) {
                fclose($pipes[0]);
                $stdout = stream_get_contents($pipes[1]);
                fclose($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[2]);
                $exitCode = proc_close($process);

                if ($exitCode === 0) {
                    $restoredSuccessfully = true;
                } else {
                    $errorMessage = "psql process failed (exit code {$exitCode}): " . trim($stderr ?: $stdout);
                }
            }
        }

        // 2. Fallback method: PDO statement execution if psql is not available or errored
        if (!$restoredSuccessfully) {
            try {
                $this->restoreViaPdo($filePath);
                $restoredSuccessfully = true;
            } catch (Exception $e) {
                throw new RuntimeException("Restoration failed: " . ($errorMessage ? ($errorMessage . '; ' . $e->getMessage()) : $e->getMessage()));
            }
        }

        // 3. Post-Restoration Verification: check table counts and integrity
        try {
            $ref = new ReflectionProperty('Database', 'pdo');
            $ref->setValue(null, null);
        } catch (Throwable $t) {}
        $this->db = Database::getConnection();

        $counts = [];
        $coreTables = ['users', 'categories', 'locations', 'equipment', 'loans'];
        foreach ($coreTables as $table) {
            try {
                $counts[$table] = (int)$this->db->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
            } catch (Exception $e) {
                throw new RuntimeException("Post-restore verification failed for table '{$table}': " . $e->getMessage());
            }
        }

        return [
            'success' => true,
            'message' => 'Database successfully restored and verified.',
            'counts'  => $counts,
        ];
    }

    /**
     * Restore SQL dump using PDO statement by statement
     */
    private function restoreViaPdo(string $filePath): void {
        $sql = file_get_contents($filePath);
        if ($sql === false) {
            throw new RuntimeException("Could not read backup file: {$filePath}");
        }

        // Remove comments and execute statements
        $this->db->beginTransaction();
        try {
            $this->db->exec($sql);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Pure PHP SQL dump fallback when pg_dump is not available
     */
    private function dumpDatabasePhp(string $targetFile): void {
        $fp = fopen($targetFile, 'wb');
        if (!$fp) {
            throw new RuntimeException("Cannot open file for writing: {$targetFile}");
        }

        fwrite($fp, "-- ResourceHub PostgreSQL Pure-PHP Dump\n");
        fwrite($fp, "-- Generated: " . date('Y-m-d H:i:s') . " UTC\n");
        fwrite($fp, "-- Database: " . $this->getTargetDatabaseName() . "\n\n");
        fwrite($fp, "SET client_encoding = 'UTF8';\n");
        fwrite($fp, "SET standard_conforming_strings = on;\n\n");

        // Dependency order for drop and create
        $tables = ['loan_history', 'maintenance', 'loans', 'equipment', 'locations', 'categories', 'users'];

        foreach ($tables as $t) {
            fwrite($fp, "DROP TABLE IF EXISTS {$t} CASCADE;\n");
        }
        fwrite($fp, "\n");

        // Export schema
        $schemaFile = RH_ROOT . '/database/schema.sql';
        if (file_exists($schemaFile)) {
            fwrite($fp, "-- Schema definitions\n");
            $schemaContent = file_get_contents($schemaFile);
            fwrite($fp, $schemaContent . "\n\n");
        }

        // Reverse for data insertion (independent tables first)
        $insertOrder = array_reverse($tables);
        foreach ($insertOrder as $table) {
            fwrite($fp, "-- Data for table: {$table}\n");
            $stmt = $this->db->query("SELECT * FROM {$table} ORDER BY id ASC");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $columns = array_keys($rows[0]);
                $colList = implode(', ', array_map(fn($c) => '"' . $c . '"', $columns));

                foreach ($rows as $row) {
                    $vals = [];
                    foreach ($row as $val) {
                        if ($val === null) {
                            $vals[] = 'NULL';
                        } elseif (is_bool($val)) {
                            $vals[] = $val ? 'TRUE' : 'FALSE';
                        } elseif (is_numeric($val) && !is_string($val)) {
                            $vals[] = (string)$val;
                        } else {
                            $vals[] = $this->db->quote((string)$val);
                        }
                    }
                    fwrite($fp, "INSERT INTO {$table} ({$colList}) VALUES (" . implode(', ', $vals) . ");\n");
                }

                // Reset sequence
                fwrite($fp, "SELECT setval(pg_get_serial_sequence('{$table}', 'id'), COALESCE((SELECT MAX(id) FROM {$table}), 1), true);\n");
            }
            fwrite($fp, "\n");
        }

        fclose($fp);
    }

    /**
     * Locate system binary (pg_dump, psql) in system PATH
     */
    private function locateBinary(string $binary): ?string {
        $extensions = (DIRECTORY_SEPARATOR === '\\') ? ['.exe', '.cmd', '.bat', ''] : [''];

        // 1. Check if binary is in PATH
        $pathEnv = getenv('PATH') ?: '';
        $pathDirs = explode(PATH_SEPARATOR, $pathEnv);

        // Add standard PostgreSQL paths on Windows and Linux
        $standardPaths = [
            'C:\\Program Files\\PostgreSQL\\18\\bin',
            'C:\\Program Files\\PostgreSQL\\17\\bin',
            'C:\\Program Files\\PostgreSQL\\16\\bin',
            '/usr/bin',
            '/usr/local/bin',
            '/usr/lib/postgresql/18/bin',
            '/usr/lib/postgresql/16/bin',
        ];
        $searchDirs = array_unique(array_merge($pathDirs, $standardPaths));

        foreach ($searchDirs as $dir) {
            $dir = trim($dir, '"');
            if (!$dir || !is_dir($dir)) {
                continue;
            }
            foreach ($extensions as $ext) {
                $candidate = $dir . DIRECTORY_SEPARATOR . $binary . $ext;
                if (file_exists($candidate) && is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Format byte size into human readable string
     */
    private function formatFileSize(int $bytes): string {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }
        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 1) . ' KB';
        }
        return $bytes . ' B';
    }
}

// Alias for convenience and autoloader
class Backup extends BackupService {}

