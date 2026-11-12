<?php
/**
 * ResourceHub Test Suite Helper
 * Automatically ensures tests run inside the isolated test database (resourcehub_test)
 * without modifying src/Database.php or includes/init.php.
 */

require_once __DIR__ . '/../includes/init.php';
require_once __DIR__ . '/TestDatabase.php';

// Automatically inject isolated test database connection into testing environment
TestDatabase::init(false);

class TestHelper {
    public static function initTestDatabase(bool $reseed = true): PDO {
        return TestDatabase::init($reseed);
    }

    public static function resetTestDatabase(): void {
        TestDatabase::resetSchemaAndSeed();
    }
}
