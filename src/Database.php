<?php
/**
 * Database Connection Class
 * Provides a shared PDO database connection for the application.
 */

class Database {
    private static $pdo = null;

    public static function getConnection() {
        if (self::$pdo === null) {
            $configFile = __DIR__ . '/../config/database.php';
            if (!file_exists($configFile)) {
                die("Database configuration file not found at: {$configFile}.");
            }
            $config = require $configFile;

            $dsn = sprintf(
                '%s:host=%s;port=%d;dbname=%s',
                $config['driver'] ?? 'pgsql',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? 5432,
                $config['database'] ?? 'resourcehub'
            );

            try {
                self::$pdo = new PDO($dsn, $config['username'], $config['password'], [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return self::$pdo;
    }

    // Helper for backwards compatibility
    public static function getInstance() {
        return new self();
    }

    public function getPdo() {
        return self::getConnection();
    }
}

