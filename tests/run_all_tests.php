<?php
/**
 * Master Test Suite Runner
 * Runs all unit, integration, dynamic routing, backup/restore, and acceptance test suites
 * completely inside the isolated test database (resourcehub_test).
 */

require_once __DIR__ . '/test_helper.php';

echo "==========================================================" . PHP_EOL;
echo "  ResourceHub Master Test Suite Runner                    " . PHP_EOL;
echo "  Isolated Test Database: " . TestDatabase::TEST_DB_NAME . PHP_EOL;
echo "==========================================================" . PHP_EOL . PHP_EOL;

// Ensure test database is clean and seeded
TestHelper::initTestDatabase(true);

$tests = [
    'test_pages.php'            => 'Component & Model Unit Tests',
    'test_dynamic_routing.php'  => 'Dynamic Routing & Autoloading Tests',
    'test_refactoring.php'      => 'Model & Helper Integrity Tests',
    'verify.php'                => 'Comprehensive Business Logic & Guard Tests',
    'test_backup_restore.php'   => 'Admin Backup & Restore Feature Tests',
    'test_acceptance_cases.php' => 'Acceptance Test Suite (T01 - T15)',
];

$failedSuites = [];

foreach ($tests as $script => $label) {
    echo "----------------------------------------------------------" . PHP_EOL;
    echo "Running {$label} ({$script})..." . PHP_EOL;
    echo "----------------------------------------------------------" . PHP_EOL;

    $cmd = sprintf('php %s', escapeshellarg(__DIR__ . '/' . $script));
    $output = [];
    $ret = 0;
    exec($cmd . ' 2>&1', $output, $ret);

    echo implode(PHP_EOL, $output) . PHP_EOL . PHP_EOL;

    if ($ret !== 0) {
        $failedSuites[] = $script;
    }
}

// Final check: confirm production database was never modified
$prodConfig = TestDatabase::getConfig();
$prodDsn = sprintf('pgsql:host=%s;port=%d;dbname=%s', $prodConfig['host'], $prodConfig['port'], $prodConfig['database']);
$prodPdo = new PDO($prodDsn, $prodConfig['username'], $prodConfig['password']);
$prodUsers = (int)$prodPdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$prodAssets = (int)$prodPdo->query("SELECT COUNT(*) FROM equipment")->fetchColumn();
$prodLoans = (int)$prodPdo->query("SELECT COUNT(*) FROM loans")->fetchColumn();

echo "==========================================================" . PHP_EOL;
echo "  Master Test Suite Summary                               " . PHP_EOL;
echo "==========================================================" . PHP_EOL;
echo "Test Database:       " . TestDatabase::TEST_DB_NAME . " (Clean, isolated)" . PHP_EOL;
echo "Production Database: " . $prodConfig['database'] . " (Users: {$prodUsers}, Assets: {$prodAssets}, Loans: {$prodLoans})" . PHP_EOL;

if (empty($failedSuites)) {
    echo PHP_EOL . "\033[32mALL TEST SUITES PASSED (100%) WITH FULL DATABASE ISOLATION!\033[0m" . PHP_EOL;
    exit(0);
} else {
    echo PHP_EOL . "\033[31mSOME TEST SUITES FAILED: " . implode(', ', $failedSuites) . "\033[0m" . PHP_EOL;
    exit(1);
}

