<?php
/**
 * ResourceHub Isolated Test Database Manager
 * Dedicated testing database harness that provisions, seeds, and injects
 * an isolated test database (resourcehub_test) into testing executions
 * without modifying src/Database.php or includes/init.php.
 */

class TestDatabase {
    const TEST_DB_NAME = 'resourcehub_test';
    private static $testPdo = null;

    /**
     * Read the base configuration from config/database.php
     */
    public static function getConfig(): array {
        $configFile = __DIR__ . '/../config/database.php';
        if (!file_exists($configFile)) {
            throw new RuntimeException("Database configuration file not found at: {$configFile}.");
        }
        return require $configFile;
    }

    /**
     * Create the test database if it does not exist, apply schema, and seed test data
     */
    public static function init(bool $reseed = true): PDO {
        $config = self::getConfig();
        self::ensureTestDatabaseExists($config);

        if (self::$testPdo === null) {
            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $config['driver'] ?? 'pgsql',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 5432,
                self::TEST_DB_NAME
            );

            self::$testPdo = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        // Inject the test PDO instance into Database::$pdo using Reflection
        self::injectIntoDatabase(self::$testPdo);

        if ($reseed) {
            self::resetSchemaAndSeed();
        }

        return self::$testPdo;
    }

    /**
     * Get the active test PDO connection
     */
    public static function getConnection(): PDO {
        if (self::$testPdo === null) {
            return self::init();
        }
        return self::$testPdo;
    }

    /**
     * Check if resourcehub_test exists; if not, create it via maintenance database
     */
    private static function ensureTestDatabaseExists(array $config): void {
        $maintenanceDbs = ['postgres', 'template1', $config['database'] ?? 'resourcehub'];
        $maintPdo = null;

        foreach ($maintenanceDbs as $mDb) {
            try {
                $dsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $config['host'], $config['port'], $mDb);
                $maintPdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                break;
            } catch (Exception $e) {
                continue;
            }
        }

        if (!$maintPdo) {
            throw new RuntimeException("Could not connect to PostgreSQL to provision test database.");
        }

        $stmt = $maintPdo->prepare("SELECT 1 FROM pg_database WHERE datname = :dbname");
        $stmt->execute([':dbname' => self::TEST_DB_NAME]);
        $exists = (bool)$stmt->fetchColumn();

        if (!$exists) {
            $dbName = self::TEST_DB_NAME;
            $maintPdo->exec("CREATE DATABASE \"{$dbName}\" WITH ENCODING 'UTF8'");

            $user = $config['username'];
            try {
                $maintPdo->exec("GRANT ALL PRIVILEGES ON DATABASE \"{$dbName}\" TO \"{$user}\"");
            } catch (Exception $e) {
                // Ignore if privileges already granted
            }
        }

        $maintPdo = null;
    }

    /**
     * Apply schema.sql and run seed.php against the isolated test database
     */
    public static function resetSchemaAndSeed(): void {
        $pdo = self::getConnection();

        // Ensure Database::$pdo points to test PDO so seeder works on test database
        self::injectIntoDatabase($pdo);

        $schemaFile = __DIR__ . '/../database/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new RuntimeException("schema.sql not found at: {$schemaFile}");
        }

        $schemaSql = file_get_contents($schemaFile);
        $pdo->exec($schemaSql);

        // Run seeder with output buffering so it does not clutter test runner output
        ob_start();
        include __DIR__ . '/../database/seed.php';
        ob_end_clean();
    }

    /**
     * Inject the test PDO connection into Database::$pdo without modifying Database.php
     */
    public static function injectIntoDatabase(?PDO $pdo): void {
        if (class_exists('Database')) {
            $reflection = new ReflectionProperty('Database', 'pdo');
            $reflection->setValue(null, $pdo);
        }
    }

    /**
     * Restore original Database connection state
     */
    public static function restoreOriginal(): void {
        self::injectIntoDatabase(null);
    }
}

