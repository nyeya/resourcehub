<?php
/**
 * ResourceHub Database Reset Script
 * Re-applies the schema and re-seeds sample data.
 */

require_once __DIR__ . '/../includes/init.php';

try {
    echo "===========================================" . PHP_EOL;
    echo "  ResourceHub Database Initializer / Reset " . PHP_EOL;
    echo "===========================================" . PHP_EOL;

    $pdo = Database::getConnection();

    echo "1. Applying schema.sql..." . PHP_EOL;
    $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
    if ($schemaSql === false) {
        throw new RuntimeException("Could not read schema.sql");
    }

    $pdo->exec($schemaSql);
    echo " -> Schema applied successfully." . PHP_EOL;

    echo "2. Seeding sample dataset..." . PHP_EOL;
    require __DIR__ . '/seed.php';

    echo "===========================================" . PHP_EOL;
    echo "  Database setup and seeding completed!   " . PHP_EOL;
    echo "===========================================" . PHP_EOL;
} catch (Exception $e) {
    echo "Error during database reset: " . $e->getMessage() . PHP_EOL;
    exit(1);
}
